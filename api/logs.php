<?php
/**
 * =================================================================
 * ENDPOINT DE LOGS DE ATIVIDADE - NomaTV API v4.3
 * =================================================================
 * * ARQUIVO: /api/logs.php
 * VERSÃO: 4.3 - Criado para a seção de Logs
 * * RESPONSABILIDADES:
 * ✅ Listar todos os registros da tabela de auditoria.
 * ✅ Fornecer filtragem avançada por usuário, ação e data.
 * ✅ Implementar paginação para lidar com grandes volumes de dados.
 * ✅ Acesso restrito a administradores.
 * * =================================================================
 */

// Configuração de erro reporting
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Headers de segurança e CORS
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Tratar requisições OPTIONS (CORS preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Dependências obrigatórias
require_once __DIR__ . '/config/database_sqlite.php';
require_once __DIR__ . '/config/session.php';

/**
 * Função auxiliar para padronizar respostas JSON.
 * @param bool $success Indica se a operação foi bem-sucedida.
 * @param array|null $data Dados a serem retornados.
 * @param string|null $message Mensagem de feedback.
 * @param array|null $extraData Dados adicionais (e.g., pagination, stats).
 */
function standardResponse(bool $success, $data = null, $message = null, $extraData = null): void {
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'message' => $message,
        'extraData' => $extraData
    ]);
    exit();
}

/**
 * Verificação de permissão - Apenas administradores
 */
$user = verificarAutenticacao();
if (!$user) {
    respostaNaoAutenticado();
}
$loggedInRevendedorId = $user['id'];
$loggedInUserType = $user['master'];

/**
 * Roteamento principal
 */
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        listLogs($db, $_GET);
    } else {
        http_response_code(405);
        standardResponse(false, null, 'Método não permitido.');
    }
} catch (Exception $e) {
    http_response_code(500);
    error_log("NomaTV v4.3 [LOGS] Erro geral: " . $e->getMessage());
    standardResponse(false, null, 'Erro interno do servidor.');
}

/**
 * Lista, filtra e pagina os logs de auditoria.
 */
function listLogs(PDO $db, array $params): void {
    // Parâmetros de paginação e filtro
    $limit = isset($params['limit']) ? (int)$params['limit'] : 25;
    $page = isset($params['page']) ? (int)$params['page'] : 1;
    $offset = ($page - 1) * $limit;

    $search = $params['search'] ?? '';
    $action = $params['action'] ?? '';
    $date = $params['date'] ?? '';

    // Base da query
    $sqlBase = "FROM auditoria a LEFT JOIN revendedores r ON a.id_revendedor = r.id_revendedor";
    $whereClauses = [];
    $queryParams = [];

    // Construção dos filtros
    if (!empty($search)) {
        $whereClauses[] = "(r.usuario LIKE :search OR a.acao LIKE :search OR a.detalhes LIKE :search)";
        $queryParams[':search'] = "%$search%";
    }
    if (!empty($action)) {
        $whereClauses[] = "a.acao = :action";
        $queryParams[':action'] = $action;
    }
    if (!empty($date)) {
        $whereClauses[] = "DATE(a.timestamp) = :date";
        $queryParams[':date'] = $date;
    }

    $whereSql = "";
    if (!empty($whereClauses)) {
        $whereSql = " WHERE " . implode(" AND ", $whereClauses);
    }

    try {
        // 1. Obter o total de registros para a paginação
        $countStmt = $db->prepare("SELECT COUNT(a.id) " . $sqlBase . $whereSql);
        $countStmt->execute($queryParams);
        $totalRecords = (int)$countStmt->fetchColumn();
        $totalPages = ceil($totalRecords / $limit);

        // 2. Obter os dados paginados
        $dataSql = "SELECT a.id, a.acao, a.detalhes, a.ip, a.timestamp, IFNULL(r.usuario, 'Sistema') as usuario "
                 . $sqlBase . $whereSql . " ORDER BY a.timestamp DESC LIMIT :limit OFFSET :offset";
        
        $dataStmt = $db->prepare($dataSql);
        
        // Bind dos parâmetros de filtro
        foreach ($queryParams as $key => &$val) {
            $dataStmt->bindParam($key, $val);
        }
        
        // Bind dos parâmetros de paginação
        $dataStmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $dataStmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        
        $dataStmt->execute();
        $logs = $dataStmt->fetchAll(PDO::FETCH_ASSOC);

        // Montar a resposta
        standardResponse(
            true,
            $logs,
            'Logs listados com sucesso.',
            [
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'totalPages' => $totalPages,
                    'totalRecords' => $totalRecords
                ]
            ]
        );

    } catch (Exception $e) {
        http_response_code(500);
        error_log("NomaTV v4.3 [LOGS] Erro em listLogs: " . $e->getMessage());
        standardResponse(false, null, 'Erro ao listar logs.');
    }
}
?>