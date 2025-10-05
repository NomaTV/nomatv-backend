<?php
/**
 * =================================================================
 * ENDPOINT DE CONTROLE DE IPS - NomaTV API v4.2
 * =================================================================
 * * ARQUIVO: /api/ips.php
 * VERSÃO: 4.3 - Simplificado para Testes (Sem Permissões/Logs)
 * * RESPONSABILIDADES:
 * ✅ Gerenciamento CRUD de IPs (adicionar, listar, atualizar, remover).
 * ✅ Suporte completo a paginação, filtros e estatísticas.
 * ✅ Ações de status (bloquear, permitir, suspeito, monitorado) individuais e em lote.
 * ✅ SIMPLIFICADO: Acesso direto (sem verificação de permissão complexa)
 * ✅ SIMPLIFICADO: Sem logs de auditoria
 * ✅ Refatorado: Lógica de criação de tabelas movida para db_installer.php
 * * =================================================================
 */

// Configuração de erro reporting
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Headers de segurança e CORS
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Tratar requisições OPTIONS (CORS preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Dependências obrigatórias
require_once __DIR__ . '/config/database_sqlite.php'; // Apenas a conexão com o banco de dados
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
        'extraData' => $extraData // Mantido para compatibilidade
    ]);
    exit(); // Garante que nada mais seja enviado
}

// ✅ AUTENTICAÇÃO USANDO SESSION COMUM
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
$input = json_decode(file_get_contents('php://input'), true) ?? [];

$pathInfo = $_SERVER['PATH_INFO'] ?? '';
$pathParts = explode('/', $pathInfo);
$resourceId = isset($pathParts[1]) && !empty($pathParts[1]) ? $pathParts[1] : null; // ID agora é VARCHAR

try {
    switch ($method) {
        case 'GET':
            listarIPs($db, $_GET, $loggedInRevendedorId, $loggedInUserType);
            break;
            
        case 'POST':
            salvarIP($db, $loggedInRevendedorId, $input); // Unificado adicionar/editar
            break;
            
        case 'PUT':
            // PUT agora é para ações de status em lote ou individual
            atualizarStatusIP($db, $loggedInRevendedorId, $input);
            break;
            
        case 'DELETE':
            // DELETE agora é para remoção em lote ou individual
            removerIP($db, $loggedInRevendedorId, $input);
            break;
            
        default:
            http_response_code(405);
            standardResponse(false, null, 'Método não permitido.');
            break;
    }
} catch (Exception $e) {
    error_log("NomaTV v4.2 [IPS] Erro geral: " . $e->getMessage());
    standardResponse(false, null, 'Erro interno do servidor.');
}


/**
 * Lista IPs com filtros e paginação.
 */
function listarIPs(PDO $db, array $params, string $loggedInRevendedorId, string $loggedInUserType): void {
    try {
        // Parâmetros de paginação
        $page = max(1, intval($params['page'] ?? 1));
        $limit = max(1, min(100, intval($params['limit'] ?? 25)));
        $offset = ($page - 1) * $limit;

        $sql = "FROM ips_bloqueados WHERE 1=1";
        $queryParams = [];
        
        // Filtros
        if (!empty($params['search'])) {
            $sql .= " AND (ip LIKE :search OR observacoes LIKE :search OR pais LIKE :search OR cidade LIKE :search OR provedor LIKE :search)";
            $queryParams[':search'] = "%" . $params['search'] . "%";
        }
        
        if (!empty($params['status'])) {
            $sql .= " AND status = :status";
            $queryParams[':status'] = $params['status'];
        }

        if (!empty($params['pais'])) {
            $sql .= " AND pais = :pais";
            $queryParams[':pais'] = $params['pais'];
        }

        if (!empty($params['provedor'])) {
            $sql .= " AND provedor LIKE :provedor";
            $queryParams[':provedor'] = "%" . $params['provedor'] . "%";
        }

        // Contagem total e estatísticas
        $statsQuery = "
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'bloqueado' THEN 1 ELSE 0 END) as bloqueados,
                SUM(CASE WHEN status = 'suspeito' THEN 1 ELSE 0 END) as suspeitos,
                SUM(CASE WHEN status = 'monitorado' THEN 1 ELSE 0 END) as monitorados,
                SUM(CASE WHEN status = 'permitido' THEN 1 ELSE 0 END) as permitidos
            " . $sql;
        
        $stmtStats = $db->prepare($statsQuery);
        $stmtStats->execute($queryParams);
        $stats = $stmtStats->fetch(PDO::FETCH_ASSOC);

        // Dados paginados
        $dataQuery = "SELECT * " . $sql . " ORDER BY criado_em DESC LIMIT :limit OFFSET :offset";
        $stmtData = $db->prepare($dataQuery);
        $stmtData->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmtData->bindValue(':offset', $offset, PDO::PARAM_INT);
        foreach ($queryParams as $key => $val) {
            $stmtData->bindValue($key, $val);
        }
        $stmtData->execute();
        $ips = $stmtData->fetchAll(PDO::FETCH_ASSOC);

        // Cálculo da paginação
        $totalRegistros = intval($stats['total'] ?? 0);
        $totalPages = max(1, ceil($totalRegistros / $limit));
        
        standardResponse(
            true,
            $ips,
            'IPs listados com sucesso.',
            [
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'totalRecords' => $totalRegistros,
                    'totalPages' => $totalPages,
                    'hasNext' => $page < $totalPages,
                    'hasPrev' => $page > 1
                ],
                'stats' => [
                    'total' => intval($stats['total'] ?? 0),
                    'bloqueados' => intval($stats['bloqueados'] ?? 0),
                    'suspeitos' => intval($stats['suspeitos'] ?? 0),
                    'monitorados' => intval($stats['monitorados'] ?? 0),
                    'permitidos' => intval($stats['permitidos'] ?? 0)
                ]
            ]
        );
        
    } catch (Exception $e) {
        http_response_code(500);
        error_log("NomaTV v4.2 [IPS] Erro em listarIPs: " . $e->getMessage());
        standardResponse(false, null, 'Erro ao listar IPs.');
    }
}

/**
 * Adiciona ou atualiza um IP.
 */
function salvarIP(PDO $db, string $loggedInRevendedorId, array $input): void
{
    $isEdit = isset($input['id']) && is_numeric($input['id']);
    $ipId = $isEdit ? (int)$input['id'] : null;

    if (empty($input['ip']) || !filter_var($input['ip'], FILTER_VALIDATE_IP)) {
        http_response_code(400);
        standardResponse(false, null, 'Endereço de IP inválido ou não fornecido.');
    }

    $ip = $input['ip'];
    $status = $input['status'] ?? 'permitido';
    $observacoes = $input['observacoes'] ?? '';
    $pais = $input['pais'] ?? null;
    $cidade = $input['cidade'] ?? null;
    $provedor = $input['provedor'] ?? null;
    $tentativasFalhas = isset($input['tentativas_falhas']) ? (int)$input['tentativas_falhas'] : 0;
    $scoreRisco = isset($input['score_risco']) ? (int)$input['score_risco'] : 0;
    $bloqueadoAutomaticamente = isset($input['bloqueado_automaticamente']) ? (bool)$input['bloqueado_automaticamente'] : 0;
    $motivoBloqueio = $input['motivo_bloqueio'] ?? null;

    try {
        if ($isEdit) {
            // Atualizar IP existente
            $stmt = $db->prepare("
                UPDATE ips_bloqueados 
                SET ip = ?, status = ?, observacoes = ?, pais = ?, cidade = ?, provedor = ?, 
                    tentativas_falhas = ?, score_risco = ?, bloqueado_automaticamente = ?, 
                    id_revendedor_bloqueador = ?, motivo_bloqueio = ?, atualizado_em = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            $stmt->execute([
                $ip, $status, $observacoes, $pais, $cidade, $provedor, 
                $tentativasFalhas, $scoreRisco, (int)$bloqueadoAutomaticamente, 
                $loggedInRevendedorId, $motivoBloqueio, $ipId
            ]);
            
            if ($stmt->rowCount() === 0) {
                http_response_code(404);
                standardResponse(false, null, 'IP não encontrado para atualização.');
            }
            // logAction($db, $loggedInRevendedorId, 'ip_update', "IP '{$ip}' (ID: {$ipId}) atualizado.");
            standardResponse(true, ['id' => $ipId], 'IP atualizado com sucesso!');

        } else {
            // Adicionar novo IP
            $stmt = $db->prepare("
                INSERT INTO ips_bloqueados (ip, status, observacoes, pais, cidade, provedor, tentativas_falhas, score_risco, bloqueado_automaticamente, id_revendedor_bloqueador, motivo_bloqueio) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $ip, $status, $observacoes, $pais, $cidade, $provedor, 
                $tentativasFalhas, $scoreRisco, (int)$bloqueadoAutomaticamente, 
                $loggedInRevendedorId, $motivoBloqueio
            ]);
            
            $newId = $db->lastInsertId();
            // logAction($db, $loggedInRevendedorId, 'ip_add', "IP '{$ip}' adicionado com ID: {$newId}");
            standardResponse(true, ['id' => $newId], 'IP adicionado com sucesso!');
        }

    } catch (PDOException $e) {
        http_response_code(500);
        error_log("NomaTV v4.2 [IPS] Erro em salvarIP: " . $e->getMessage());
        standardResponse(false, null, 'Erro ao salvar o IP. Ele pode já existir ou dados inválidos.');
    }
}

/**
 * Atualiza o status de um ou múltiplos IPs.
 */
function atualizarStatusIP(PDO $db, string $loggedInRevendedorId, array $input): void
{
    $ipIds = $input['ip_ids'] ?? [];
    $action = $input['action'] ?? ''; // 'bloquear', 'permitir', 'marcar_suspeito', 'marcar_monitorado'
    $motivo = $input['motivo'] ?? null;

    if (!is_array($ipIds)) {
        $ipIds = [$ipIds];
    }

    if (empty($ipIds) || empty($action)) {
        http_response_code(400);
        standardResponse(false, null, 'IDs de IP e ação são obrigatórios.');
    }

    $placeholders = implode(',', array_fill(0, count($ipIds), '?'));
    $statusToSet = '';
    $message = '';

    switch ($action) {
        case 'bloquear':
            $statusToSet = 'bloqueado';
            $message = 'IP(s) bloqueado(s) com sucesso.';
            break;
        case 'permitir':
            $statusToSet = 'permitido';
            $message = 'IP(s) permitido(s) com sucesso.';
            break;
        case 'marcar_suspeito':
            $statusToSet = 'suspeito';
            $message = 'IP(s) marcado(s) como suspeito(s).';
            break;
        case 'marcar_monitorado':
            $statusToSet = 'monitorado';
            $message = 'IP(s) marcado(s) como monitorado(s).';
            break;
        default:
            http_response_code(400);
            standardResponse(false, null, 'Ação de status inválida.');
    }

    try {
        $db->beginTransaction();

        $stmt = $db->prepare("
            UPDATE ips_bloqueados 
            SET status = ?, id_revendedor_bloqueador = ?, motivo_bloqueio = ?, atualizado_em = CURRENT_TIMESTAMP
            WHERE id IN ($placeholders)
        ");
        
        $params = [$statusToSet, $loggedInRevendedorId, $motivo, ...$ipIds];
        $stmt->execute($params);

        if ($stmt->rowCount() === 0) {
            $db->rollBack();
            http_response_code(404);
            standardResponse(false, null, 'Nenhum IP encontrado para a ação.');
        }

        // logAction($db, $loggedInRevendedorId, $logActionType, $detalhes);
        
        $db->commit();
        standardResponse(true, null, $message);

    } catch (Exception $e) {
        $db->rollBack();
        http_response_code(500);
        error_log("NomaTV v4.2 [IPS] Erro em atualizarStatusIP: " . $e->getMessage());
        standardResponse(false, null, 'Erro ao atualizar status do IP.');
    }
}

/**
 * Remove um ou múltiplos IPs.
 */
function removerIP(PDO $db, string $loggedInRevendedorId, array $input): void
{
    $ipIds = $input['ip_ids'] ?? [];

    if (!is_array($ipIds)) {
        $ipIds = [$ipIds];
    }

    if (empty($ipIds)) {
        http_response_code(400);
        standardResponse(false, null, 'IDs de IP são obrigatórios para remoção.');
    }

    $placeholders = implode(',', array_fill(0, count($ipIds), '?'));

    try {
        $db->beginTransaction();

        $stmt = $db->prepare("DELETE FROM ips_bloqueados WHERE id IN ($placeholders)");
        $stmt->execute($ipIds);

        if ($stmt->rowCount() === 0) {
            $db->rollBack();
            http_response_code(404);
            standardResponse(false, null, 'Nenhum IP encontrado para remoção.');
        }
        
        // logAction($db, $loggedInRevendedorId, 'ip_delete', $detalhes);

        $db->commit();
        standardResponse(true, null, 'IP(s) removido(s) com sucesso!');

    } catch (Exception $e) {
        $db->rollBack();
        http_response_code(500);
        error_log("NomaTV v4.2 [IPS] Erro em removerIP: " . $e->getMessage());
        standardResponse(false, null, 'Erro ao remover o IP.');
    }
}
?>