<?php
/**
 * =================================================================
 * ENDPOINT DE PROVEDORES - NomaTV API v4.5
 * =================================================================
 *
 * ARQUIVO: /api/provedores.php
 * VERSÃO: 4.5 - NOVA LÓGICA parent_id (Árvore Infinita)
 *
 * RESPONSABILIDADES:
 * ✅ CRUD completo de provedores Xtream Codes
 * ✅ NOVA: Busca recursiva por parent_id para filtros hierárquicos
 * ✅ REMOVIDO: Pattern matching limitado por ID
 * ✅ Filtros automáticos baseados na rede completa do revendedor
 * ✅ SIMPLIFICADO: Validação DNS básica e INSERT direto
 *
 * =================================================================
 */

ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/config/database_sqlite.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/helpers/response_helper.php';

// =============================================
// 🔗 CONEXÃO COM BANCO DE DADOS
// =============================================
try {
    $db = getDatabaseConnection();
} catch (Exception $e) {
    respostaErroPadronizada('Erro de conexão com banco de dados', 500);
}

// ✅ AUTENTICAÇÃO USANDO SESSION COMUM
$user = verificarAutenticacao();
if (!$user) {
    respostaNaoAutenticadoPadronizada();
}

// Buscar dados completos do revendedor logado
$loggedInRevendedorId = $user['id'] ?? 0;
$dadosRevendedor = getRevendedorCompleto($db, $loggedInRevendedorId);

// =============================================
// 🔗 ROTEAMENTO PRINCIPAL
// =============================================
$method = $_SERVER['REQUEST_METHOD'];

// ✅ SUPORTE A FORM-DATA E JSON
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (strpos($contentType, 'application/json') !== false) {
    // ✅ SUPORTE AO SERVIDOR NODE.JS: Ler de variável de ambiente se disponível
    if (isset($_SERVER['REQUEST_BODY']) && !empty($_SERVER['REQUEST_BODY'])) {
        $rawInput = $_SERVER['REQUEST_BODY'];
        error_log("NomaTV DEBUG [INPUT] Raw JSON from REQUEST_BODY: " . $rawInput);
    } else {
        $rawInput = file_get_contents('php://input');
        error_log("NomaTV DEBUG [INPUT] Raw JSON from php://input: " . $rawInput);
    }
    $input = json_decode($rawInput, true) ?? [];
    error_log("NomaTV DEBUG [INPUT] Decoded JSON: " . json_encode($input));
    error_log("NomaTV DEBUG [INPUT] JSON Error: " . json_last_error_msg());
} else {
    $input = $_POST;
    error_log("NomaTV DEBUG [INPUT] Form data: " . json_encode($input));
}

$resourceId = $_GET['id'] ?? null;

try {
    switch ($method) {
        case 'GET':
            listarProvedores($db, $_GET, $loggedInRevendedorId, $dadosRevendedor['master']);
            break;
        case 'POST':
            // DEBUG: Log do input recebido
            error_log("NomaTV DEBUG [POST] Input recebido: " . json_encode($input));
            error_log("NomaTV DEBUG [POST] Action: " . ($input['action'] ?? 'NÃO DEFINIDA'));
            error_log("NomaTV DEBUG [POST] Content-Type: " . ($contentType ?? 'NÃO DEFINIDO'));
            error_log("NomaTV DEBUG [POST] Raw POST: " . json_encode($_POST));
            error_log("NomaTV DEBUG [POST] Raw input stream: " . file_get_contents('php://input'));
            handlePostProvedores($db, $loggedInRevendedorId, $dadosRevendedor['master'], $input);
            break;
        case 'PUT':
            if (!$resourceId || empty(trim($resourceId))) {
                respostaErroPadronizada('ID é obrigatório na query string.', 400);
                break;
            }
            atualizarProvedor($db, $loggedInRevendedorId, $dadosRevendedor['master'], $resourceId, $input);
            break;
        case 'DELETE':
            if (!$resourceId || empty(trim($resourceId))) {
                respostaErroPadronizada('ID é obrigatório na query string.', 400);
                break;
            }
            deletarProvedor($db, $loggedInRevendedorId, $dadosRevendedor['master'], $resourceId);
            break;
        default:
            respostaErroPadronizada('Método não permitido.', 405);
    }
} catch (Exception $e) {
    error_log("NomaTV v4.5 [PROVEDORES] Erro: " . $e->getMessage());
    respostaErroPadronizada('Erro interno do servidor.');
}

/**
 * ✅ FUNÇÃO CORRIGIDA: BUSCA RECURSIVA POR parent_id
 * Retorna todos os descendentes de um revendedor como um array de IDs
 */
function buscarRedeCompleta(PDO $db, string $idRevendedor): array
{
    $fila = [$idRevendedor]; // Começar com o ID base
    $todosDescendentes = [];
    
    // Usar uma nova variável para a fila de IDs a serem buscados
    $idsParaBuscar = [$idRevendedor];
    $indice = 0;
    
    while ($indice < count($idsParaBuscar)) {
        $idAtual = $idsParaBuscar[$indice];
        
        $stmt = $db->prepare("
            SELECT id_revendedor
            FROM revendedores 
            WHERE parent_id = ? AND ativo = 1
        ");
        $stmt->execute([$idAtual]);
        $filhos = $stmt->fetchAll(PDO::FETCH_COLUMN); // ✅ CORRIGIDO: Retorna apenas a coluna id_revendedor
        
        if (!empty($filhos)) {
            $todosDescendentes = array_merge($todosDescendentes, $filhos);
            $idsParaBuscar = array_merge($idsParaBuscar, $filhos);
        }
        
        $indice++;
    }
    
    // Remover o ID inicial se ele estiver nos descendentes
    $todosDescendentes = array_diff($todosDescendentes, [$idRevendedor]);
    
    return $todosDescendentes;
}

/**
 * ✅ FUNÇÃO CORRIGIDA: LISTAR PROVEDORES - NOVA LÓGICA parent_id
 * Filtros automáticos baseados na busca recursiva da rede
 */
function listarProvedores(PDO $db, array $params, string $loggedInRevendedorId, string $loggedInUserType): void
{
    try {
        error_log("listarProvedores - Iniciando com userId: $loggedInRevendedorId, userType: $loggedInUserType");
        
        // Paginação
        $page = max(1, intval($params["page"] ?? 1));
        $limit = max(1, min(100, intval($params["limit"] ?? 25)));
        $offset = ($page - 1) * $limit;

        $baseQuery = "FROM provedores p LEFT JOIN revendedores r ON p.id_revendedor = r.id_revendedor";
        $whereConditions = [];
        $queryParams = [];

        // Filtro de busca
        if (!empty($params["search"])) {
            $search = "%" . $params["search"] . "%";
            $whereConditions[] = "(p.nome LIKE ? OR p.dns LIKE ? OR r.nome LIKE ? OR r.usuario LIKE ?)";
            $queryParams[] = $search;
            $queryParams[] = $search;
            $queryParams[] = $search;
            $queryParams[] = $search;
        }

        // Filtro de status
        if (!empty($params["status"])) {
            if ($params["status"] === "ativo") {
                $whereConditions[] = "p.ativo = 1";
            } elseif ($params["status"] === "inativo") {
                $whereConditions[] = "p.ativo = 0";
            }
        }

        // Filtro de tipo
        if (!empty($params["tipo"])) {
            $whereConditions[] = "p.tipo = ?";
            $queryParams[] = $params["tipo"];
        }
        
        error_log("listarProvedores - Antes da lógica hierárquica, userType: $loggedInUserType");
        
        // ✅ NOVA LÓGICA HIERÁRQUICA E APLICAÇÃO DO FILTRO CORRETA
        if ($loggedInUserType === "admin") {
            error_log("listarProvedores - Usuário é admin, vê todos os provedores");
            // Admin vê todos os provedores e pode filtrar por revendedor
            if (!empty($params["id_revendedor"])) {
                $whereConditions[] = "p.id_revendedor = ?";
                $queryParams[] = $params["id_revendedor"];
            }
        } elseif ($loggedInUserType === "sim") {
            error_log("listarProvedores - Usuário é revendedor, aplicando filtros hierárquicos");
            // Revendedor vê provedores de toda sua rede descendente
            $redeCompleta = buscarRedeCompleta($db, $loggedInRevendedorId);
            
            // Incluir o próprio revendedor na lista
            $idsPermitidos = array_merge([$loggedInRevendedorId], $redeCompleta);
            
            if (!empty($idsPermitidos)) {
                $placeholders = implode(',', array_fill(0, count($idsPermitidos), '?'));
                $whereConditions[] = "p.id_revendedor IN ($placeholders)";
                $queryParams = array_merge($queryParams, $idsPermitidos);
            } else {
                // Se não tem rede, não mostra ninguém
                $whereConditions[] = "p.id_revendedor = '-1'"; // Condição impossível para segurança
            }
            
            if (!empty($params["id_revendedor"]) && in_array($params["id_revendedor"], $idsPermitidos)) {
                $whereConditions[] = "p.id_revendedor = ?";
                $queryParams[] = $params["id_revendedor"];
            }
        } else {
            error_log("listarProvedores - Usuário é sub-revendedor, vê apenas seus provedores");
            // Sub-revendedor vê apenas seus provedores
            $whereConditions[] = "p.id_revendedor = ?";
            $queryParams[] = $loggedInRevendedorId;
        }

        $whereClause = !empty($whereConditions) ? " WHERE " . implode(" AND ", $whereConditions) : "";
        $fullQuery = $baseQuery . $whereClause;
        
        error_log("listarProvedores - Where clause: $whereClause");
        error_log("listarProvedores - Query params: " . json_encode($queryParams));

        // Contagem e estatísticas
        $statsQuery = "
            SELECT
                COUNT(*) as total,
                SUM(CASE WHEN p.ativo = 1 THEN 1 ELSE 0 END) as ativos,
                SUM(CASE WHEN p.ativo = 0 THEN 1 ELSE 0 END) as inativos
            " . $fullQuery;

        $stmtStats = $db->prepare($statsQuery);
        $stmtStats->execute($queryParams);
        $stats = $stmtStats->fetch(PDO::FETCH_ASSOC);

        // Dados paginados
        $dataQuery = "
            SELECT
                p.id_provedor,
                p.nome,
                p.dns,
                p.tipo,
                p.usuario,
                p.senha,
                p.ativo,
                p.id_revendedor,
                p.criado_em,
                p.atualizado_em,
                r.nome as revendedor_nome,
                r.usuario as revendedor_usuario,
                COALESCE((SELECT COUNT(*) FROM client_ids c WHERE c.provedor_id = p.id_provedor AND c.ativo = 1), 0) as clientes_ativos
            " . $fullQuery . "
            ORDER BY p.criado_em DESC
            LIMIT ? OFFSET ?
        ";

        $stmtData = $db->prepare($dataQuery);
        $finalParams = array_merge($queryParams, [$limit, $offset]);
        $stmtData->execute($finalParams);
        $provedores = $stmtData->fetchAll(PDO::FETCH_ASSOC);

        // Paginação
        $totalRegistros = intval($stats['total'] ?? 0);
        $totalPages = max(1, ceil($totalRegistros / $limit));

        standardResponse(
            true,
            $provedores,
            'Provedores listados com sucesso.',
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
                    'ativos' => intval($stats['ativos'] ?? 0),
                    'inativos' => intval($stats['inativos'] ?? 0)
                ]
            ]
        );

    } catch (Exception $e) {
        http_response_code(500);
        error_log("NomaTV v4.5 [PROVEDORES] Erro em listar: " . $e->getMessage());
        respostaErroPadronizada('Erro ao listar provedores.');
    }
}

/**
 * Handler para requisições POST
 */
function handlePostProvedores(PDO $db, string $loggedInRevendedorId, string $loggedInUserType, array $input): void
{
    // ✅ SUPORTE FLEXÍVEL: action pode estar no input ou no body
    $action = $input['action'] ?? '';

    // DEBUG: Log detalhado
    error_log("NomaTV DEBUG [handlePostProvedores] Action recebida: '{$action}'");
    error_log("NomaTV DEBUG [handlePostProvedores] Input completo: " . json_encode($input));
    error_log("NomaTV DEBUG [handlePostProvedores] loggedInRevendedorId: {$loggedInRevendedorId}");
    error_log("NomaTV DEBUG [handlePostProvedores] loggedInUserType: {$loggedInUserType}");

    switch ($action) {
        case 'criar':
            error_log("NomaTV DEBUG [handlePostProvedores] Chamando criarProvedor");
            criarProvedor($db, $loggedInRevendedorId, $loggedInUserType, $input);
            break;
        case 'testar_conexao':
            error_log("NomaTV DEBUG [handlePostProvedores] Chamando testarConexaoProvedor");
            testarConexaoProvedor($input);
            break;
        default:
            error_log("NomaTV DEBUG [handlePostProvedores] Ação inválida: '{$action}'");
            http_response_code(400);
            standardResponse(false, null, 'Ação inválida.');
            break;
    }
}

/**
 * ✅ CRIAR PROVEDOR - CORRIGIDO: Validação DNS simplificada e INSERT direto
 */
function criarProvedor(PDO $db, string $loggedInRevendedorId, string $loggedInUserType, array $input): void
{
    // Validações básicas
    $nome = trim($input['nome'] ?? '');
    $dns = trim($input['dns'] ?? '');
    $tipo = $input['tipo'] ?? 'xtream';
    $usuario = trim($input['usuario'] ?? 'admin');
    $senha = $input['senha'] ?? 'dadomockado';
    $idRevendedor = $input['id_revendedor'] ?? $loggedInRevendedorId;

    if (empty($nome) || empty($dns)) {
        http_response_code(400);
        respostaErroPadronizada('Nome e DNS são obrigatórios.');
        return;
    }

    // ✅ VALIDAÇÃO DNS SIMPLIFICADA - Apenas verificação básica
    if (strlen($dns) < 5) {
        http_response_code(400);
        standardResponse(false, null, 'DNS deve ter pelo menos 5 caracteres.');
        return;
    }

    try {
        // ✅ INSERT DIRETO E SIMPLES - Sem lógica dinâmica
        $sql = "INSERT INTO provedores (nome, dns, tipo, usuario, senha, id_revendedor, ativo, criado_em, atualizado_em) 
                VALUES (?, ?, ?, ?, ?, ?, 1, datetime('now'), datetime('now'))";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([$nome, $dns, $tipo, $usuario, $senha, $idRevendedor]);

        $idProvedor = $db->lastInsertId();

        standardResponse(true, [
            'id_provedor' => $idProvedor,
            'nome' => $nome,
            'dns' => $dns,
            'tipo' => $tipo
        ], 'Provedor criado com sucesso!');

    } catch (PDOException $e) {
        error_log("NomaTV v4.5 [PROVEDORES] Erro criar: " . $e->getMessage());
        
        // ✅ TRATAMENTO DE ERROS ESPECÍFICOS
        if ($e->getCode() == 23000 || strpos($e->getMessage(), 'UNIQUE') !== false) {
            standardResponse(false, null, 'Já existe um provedor com este DNS.');
        } else {
            standardResponse(false, null, 'Erro ao criar provedor: ' . $e->getMessage());
        }
    } catch (Exception $e) {
        error_log("NomaTV v4.5 [PROVEDORES] Erro geral criar: " . $e->getMessage());
        standardResponse(false, null, 'Erro interno ao criar provedor.');
    }
}

/**
 * ✅ ATUALIZAR PROVEDOR
 */
function atualizarProvedor(PDO $db, string $loggedInRevendedorId, string $loggedInUserType, string $idToUpdate, array $input): void
{
    try {
        $fields = [];
        $params = [];

        // Campos básicos
        if (isset($input['nome'])) {
            $fields[] = "nome = ?";
            $params[] = trim($input['nome']);
        }
        if (isset($input['dns'])) {
            $dns = trim($input['dns']);
            
            // ✅ VALIDAÇÃO DNS SIMPLIFICADA
            if (strlen($dns) < 5) {
                standardResponse(false, null, 'DNS deve ter pelo menos 5 caracteres.');
                return;
            }
            
            $fields[] = "dns = ?";
            $params[] = $dns;
        }
        if (isset($input['tipo'])) {
            $fields[] = "tipo = ?";
            $params[] = $input['tipo'];
        }
        if (isset($input['usuario'])) {
            $fields[] = "usuario = ?";
            $params[] = trim($input['usuario']);
        }
        if (isset($input['senha'])) {
            $fields[] = "senha = ?";
            $params[] = $input['senha'];
        }
        if (isset($input['ativo'])) {
            $fields[] = "ativo = ?";
            $params[] = $input['ativo'] ? 1 : 0;
        }

        if (empty($fields)) {
            http_response_code(400);
            standardResponse(false, null, 'Nenhum campo para atualizar.');
            return;
        }

        $fields[] = "atualizado_em = datetime('now')";
        $params[] = $idToUpdate;

        $sql = "UPDATE provedores SET " . implode(', ', $fields) . " WHERE id_provedor = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            standardResponse(false, null, 'Provedor não encontrado.');
            return;
        }

        standardResponse(true, null, 'Provedor atualizado com sucesso!');

    } catch (PDOException $e) {
        error_log("NomaTV v4.5 [PROVEDORES] Erro atualizar: " . $e->getMessage());
        if ($e->getCode() == 23000 || strpos($e->getMessage(), 'UNIQUE') !== false) {
            standardResponse(false, null, 'Já existe um provedor com este DNS.');
        } else {
            standardResponse(false, null, 'Erro ao atualizar provedor: ' . $e->getMessage());
        }
    }
}

/**
 * ✅ DELETAR PROVEDOR
 */
function deletarProvedor(PDO $db, string $loggedInRevendedorId, string $loggedInUserType, string $idToDelete): void
{
    try {
        // Verificar se o provedor tem clientes ativos
        $stmt = $db->prepare("SELECT COUNT(*) FROM client_ids WHERE provedor_id = ? AND ativo = 1");
        $stmt->execute([$idToDelete]);
        $temClientes = $stmt->fetchColumn() > 0;

        if ($temClientes) {
            http_response_code(400);
            standardResponse(false, null, 'Não é possível deletar: este provedor possui clientes ativos.');
            return;
        }

        // Exclusão física do provedor
        $stmt = $db->prepare("DELETE FROM provedores WHERE id_provedor = ?");
        $stmt->execute([$idToDelete]);

        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            standardResponse(false, null, 'Provedor não encontrado.');
            return;
        }

        standardResponse(true, null, 'Provedor excluído com sucesso!');

    } catch (PDOException $e) {
        error_log("NomaTV v4.5 [PROVEDORES] Erro deletar: " . $e->getMessage());
        standardResponse(false, null, 'Erro ao deletar provedor.');
    }
}

/**
 * ✅ TESTAR CONEXÃO COM PROVEDOR
 */
function testarConexaoProvedor(array $input): void
{
    $dns = trim($input['dns'] ?? '');
    $usuario = trim($input['usuario'] ?? '');
    $senha = $input['senha'] ?? '';

    if (empty($dns) || empty($usuario) || empty($senha)) {
        standardResponse(false, null, 'DNS, usuário e senha são obrigatórios para teste.');
        return;
    }

    try {
        // Construir URL de teste
        $testUrl = rtrim($dns, '/') . '/player_api.php?username=' . urlencode($usuario) . '&password=' . urlencode($senha);
        
        // Fazer requisição de teste
        $context = stream_context_create([
            'http' => [
                'timeout' => 10,
                'method' => 'GET'
            ]
        ]);
        
        $response = @file_get_contents($testUrl, false, $context);
        
        if ($response === false) {
            standardResponse(false, null, 'Não foi possível conectar ao servidor.');
            return;
        }
        
        $data = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            standardResponse(false, null, 'Resposta inválida do servidor.');
            return;
        }
        
        // Verificar se contém dados esperados de um servidor Xtream
        if (isset($data['user_info']) || isset($data['server_info'])) {
            standardResponse(true, [
                'status' => 'success',
                'server_info' => $data['server_info'] ?? null,
                'user_info' => $data['user_info'] ?? null
            ], 'Conexão bem-sucedida!');
        } else {
            standardResponse(false, null, 'Servidor não parece ser um Xtream Codes válido.');
        }

    } catch (Exception $e) {
        error_log("NomaTV v4.5 [PROVEDORES] Erro teste conexão: " . $e->getMessage());
        standardResponse(false, null, 'Erro ao testar conexão: ' . $e->getMessage());
    }
}
?>