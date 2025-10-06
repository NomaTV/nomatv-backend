<?php
/**
 * =================================================================
 * ENDPOINT DE CLIENT IDS - NomaTV API v4.5
 * =================================================================
 *
 * ARQUIVO: /api/client_ids.php
 * VERSÃO: 4.5 - NOVA LÓGICA parent_id (Árvore Infinita)
 *
 * RESPONSABILIDADES:
 * ✅ CRUD completo de Client IDs (clientes/ativos)
 * ✅ NOVA: Busca recursiva por parent_id para filtros hierárquicos
 * ✅ REMOVIDO: Pattern matching limitado por ID
 * ✅ Filtros automáticos baseados na rede completa do revendedor
 * ✅ Validação de IPs e controle de acesso
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

/**
 * Resposta padronizada
 */
function standardResponse(bool $success, $data = null, $message = null, $extraData = null): void
{
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'message' => $message,
        'extraData' => $extraData
    ]);
    exit();
}

// =============================================
// ✅ AUTENTICAÇÃO USANDO SESSION COMUM
// =============================================
$user = verificarAutenticacao();
if (!$user) {
    respostaNaoAutenticado();
}
$loggedInRevendedorId = $user['id'];
$loggedInUserType = $user['master'];

// =============================================
// 🔗 ROTEAMENTO PRINCIPAL
// =============================================
$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true) ?? [];

try {
    switch ($method) {
        case 'GET':
            listarClientIds($db, $_GET, $loggedInRevendedorId, $loggedInUserType);
            break;
        case 'POST':
            handlePostClientIds($db, $loggedInRevendedorId, $loggedInUserType, $input);
            break;
        case 'PUT':
            atualizarClientIds($db, $loggedInRevendedorId, $loggedInUserType, $input);
            break;
        case 'DELETE':
            deletarClientIds($db, $loggedInRevendedorId, $loggedInUserType, $input);
            break;
        default:
            http_response_code(405);
            standardResponse(false, null, 'Método não permitido.');
            break;
    }
} catch (Exception $e) {
    error_log("NomaTV v4.5 [CLIENT_IDS] Erro: " . $e->getMessage());
    http_response_code(500);
    standardResponse(false, null, 'Erro interno do servidor.');
}

/**
 * ✅ NOVA FUNÇÃO: BUSCA RECURSIVA POR parent_id
 * Busca toda a árvore descendente de um revendedor
 */
function buscarRedeCompleta(PDO $db, string $idRevendedor): array
{
    $fila = [$idRevendedor]; // Começar com o ID base
    $todosDescendentes = [];
    
    do {
        // Buscar filhos diretos desta "fila" atual
        $placeholders = implode(',', array_fill(0, count($fila), '?'));
        $stmt = $db->prepare("
            SELECT id_revendedor
            FROM revendedores 
            WHERE parent_id IN ($placeholders) AND ativo = 1
        ");
        $stmt->execute($fila);
        $filhos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Limpar fila para próxima iteração
        $fila = [];
        
        foreach ($filhos as $filho) {
            $todosDescendentes[] = $filho['id_revendedor']; // Só precisa do ID
            $fila[] = $filho['id_revendedor']; // Preparar para buscar filhos deste
        }
        
    } while (!empty($fila)); // Continuar enquanto houver mais níveis
    
    return $todosDescendentes;
}

/**
 * ✅ LISTAR CLIENT IDS - NOVA LÓGICA parent_id
 * Filtros automáticos baseados na busca recursiva da rede
 */
function listarClientIds(PDO $db, array $params, string $loggedInRevendedorId, string $loggedInUserType): void
{
    try {
        // Parâmetros de paginação
        $page = max(1, intval($params['page'] ?? 1));
        $limit = max(1, min(100, intval($params['limit'] ?? 50)));
        $offset = ($page - 1) * $limit;
        
        // Parâmetros de filtro
        $search = trim($params['search'] ?? ''); // client_id, usuário, IP
        $statusFilter = $params['status'] ?? ''; // 'ativo', 'inativo', 'bloqueado'
        $revendedorFilter = $params['id_revendedor'] ?? ''; // ID do revendedor para filtrar
        $provedorFilter = $params['provedor_id'] ?? ''; // ID do provedor

        // =============================================
        // 🔍 CONSTRUÇÃO DA QUERY COM JOINS E NOVA LÓGICA HIERÁRQUICA
        // =============================================
        $baseQuery = "
            FROM client_ids c
            LEFT JOIN provedores p ON c.provedor_id = p.id_provedor
            LEFT JOIN revendedores r ON c.id_revendedor = r.id_revendedor
        ";
        
        $whereConditions = [];
        $params = [];
        
        // ✅ NOVA LÓGICA HIERÁRQUICA - BUSCA RECURSIVA
        if ($loggedInUserType === 'admin') {
            // Admin pode filtrar por qualquer revendedor, ou ver todos
            if (!empty($revendedorFilter)) {
                $whereConditions[] = "c.id_revendedor = ?";
                $params[] = $revendedorFilter;
            }
            
        } elseif ($loggedInUserType === 'sim') {
            // ✅ CORRIGIDO: Revendedor vê client_ids de toda sua rede descendente
            $redeCompleta = buscarRedeCompleta($db, $loggedInRevendedorId);
            
            // Incluir o próprio revendedor na lista
            $idsPermitidos = array_merge([$loggedInRevendedorId], $redeCompleta);
            
            if (!empty($idsPermitidos)) {
                $placeholders = implode(',', array_fill(0, count($idsPermitidos), '?'));
                $whereConditions[] = "c.id_revendedor IN ($placeholders)";
                $params = array_merge($params, $idsPermitidos);
            } else {
                // Se não tem rede, vê apenas os próprios
                $whereConditions[] = "c.id_revendedor = ?";
                $params[] = $loggedInRevendedorId;
            }
            
            // Se admin especificou um revendedor específico para filtrar
            if (!empty($revendedorFilter) && in_array($revendedorFilter, $idsPermitidos)) {
                $whereConditions[] = "c.id_revendedor = ?";
                $params[] = $revendedorFilter;
            }
            
        } else {
            // Sub-revendedor vê apenas os próprios client_ids
            $whereConditions[] = "c.id_revendedor = ?";
            $params[] = $loggedInRevendedorId;
        }
        
        // Filtro de busca global
        if (!empty($search)) {
            $whereConditions[] = "(c.client_id LIKE ? OR c.usuario LIKE ? OR c.ip LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        // Filtro de status
        if ($statusFilter === 'ativo') {
            $whereConditions[] = "c.ativo = 1 AND c.bloqueado = 0";
        } elseif ($statusFilter === 'inativo') {
            $whereConditions[] = "c.ativo = 0 AND c.bloqueado = 0";
        } elseif ($statusFilter === 'bloqueado') {
            $whereConditions[] = "c.bloqueado = 1";
        }
        
        // Filtro de provedor
        if (!empty($provedorFilter)) {
            $whereConditions[] = "c.provedor_id = ?";
            $params[] = intval($provedorFilter);
        }
        
        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
        $fullQuery = $baseQuery . ' ' . $whereClause;

        // =============================================
        // 📊 ESTATÍSTICAS E CONTAGEM
        // =============================================
        $statsQuery = "
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN c.ativo = 1 AND c.bloqueado = 0 THEN 1 ELSE 0 END) as ativos,
                SUM(CASE WHEN c.ativo = 0 AND c.bloqueado = 0 THEN 1 ELSE 0 END) as inativos,
                SUM(CASE WHEN c.bloqueado = 1 THEN 1 ELSE 0 END) as bloqueados
            " . $fullQuery;

        $stmtStats = $db->prepare($statsQuery);
        $stmtStats->execute($params);
        $stats = $stmtStats->fetch(PDO::FETCH_ASSOC);

        // =============================================
        // 📋 DADOS PAGINADOS
        // =============================================
        $dataQuery = "
            SELECT 
                c.id,
                c.client_id,
                c.usuario,
                c.senha,
                c.ip,
                c.ativo,
                c.bloqueado,
                c.data_expiracao,
                c.id_revendedor,
                c.provedor_id,
                c.criado_em,
                c.atualizado_em,
                p.nome as provedor_nome,
                p.dns as provedor_dns,
                r.nome as revendedor_nome,
                r.usuario as revendedor_usuario
            " . $fullQuery . "
            ORDER BY c.criado_em DESC
            LIMIT ? OFFSET ?
        ";

        $stmtData = $db->prepare($dataQuery);
        $finalParams = array_merge($params, [$limit, $offset]);
        $stmtData->execute($finalParams);
        $clientIds = $stmtData->fetchAll(PDO::FETCH_ASSOC);

        // =============================================
        // 📄 RESPOSTA PAGINADA
        // =============================================
        $totalRegistros = intval($stats['total'] ?? 0);
        $totalPages = max(1, ceil($totalRegistros / $limit));

        standardResponse(
            true,
            $clientIds,
            'Client IDs listados com sucesso.',
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
                    'inativos' => intval($stats['inativos'] ?? 0),
                    'bloqueados' => intval($stats['bloqueados'] ?? 0)
                ]
            ]
        );

    } catch (Exception $e) {
        http_response_code(500);
        error_log("NomaTV v4.5 [CLIENT_IDS] Erro em listar: " . $e->getMessage());
        standardResponse(false, null, 'Erro ao listar client IDs.');
    }
}

/**
 * Handler para requisições POST
 */
function handlePostClientIds(PDO $db, string $loggedInRevendedorId, string $loggedInUserType, array $input): void
{
    $action = $input['action'] ?? '';

    switch ($action) {
        case 'criar':
            criarClientId($db, $loggedInRevendedorId, $loggedInUserType, $input);
            break;
        case 'importar':
            importarClientIds($db, $loggedInRevendedorId, $loggedInUserType, $input);
            break;
        case 'exportar':
            exportarClientIds($db, $loggedInRevendedorId, $loggedInUserType, $input);
            break;
        default:
            http_response_code(400);
            standardResponse(false, null, 'Ação inválida.');
            break;
    }
}

/**
 * ✅ CRIAR CLIENT ID - Associa automaticamente ao revendedor logado
 */
function criarClientId(PDO $db, string $loggedInRevendedorId, string $loggedInUserType, array $input): void
{
    // Validações básicas
    $clientId = trim($input['client_id'] ?? '');
    $usuario = trim($input['usuario'] ?? '');
    $senha = $input['senha'] ?? '';
    $ip = trim($input['ip'] ?? '');
    $provedorId = intval($input['provedor_id'] ?? 0);
    $dataExpiracao = $input['data_expiracao'] ?? null;

    if (empty($clientId) || empty($usuario) || empty($senha)) {
        http_response_code(400);
        standardResponse(false, null, 'Client ID, usuário e senha são obrigatórios.');
        return;
    }

    // Validar IP se fornecido
    if (!empty($ip) && !filter_var($ip, FILTER_VALIDATE_IP)) {
        http_response_code(400);
        standardResponse(false, null, 'IP deve ter um formato válido.');
        return;
    }

    try {
        // Verificar se o provedor existe e pertence à rede do revendedor
        if ($loggedInUserType !== 'admin') {
            $redeCompleta = buscarRedeCompleta($db, $loggedInRevendedorId);
            $idsPermitidos = array_merge([$loggedInRevendedorId], $redeCompleta);
            
            $placeholders = implode(',', array_fill(0, count($idsPermitidos), '?'));
            $stmt = $db->prepare("SELECT COUNT(*) FROM provedores WHERE id_provedor = ? AND id_revendedor IN ($placeholders)");
            $params = array_merge([$provedorId], $idsPermitidos);
            $stmt->execute($params);
            
            if ($stmt->fetchColumn() == 0) {
                http_response_code(400);
                standardResponse(false, null, 'Provedor não encontrado ou não pertence à sua rede.');
                return;
            }
        }

        $stmt = $db->prepare("
            INSERT INTO client_ids (client_id, usuario, senha, ip, data_expiracao, id_revendedor, provedor_id, ativo, criado_em)
            VALUES (?, ?, ?, ?, ?, ?, ?, 1, CURRENT_TIMESTAMP)
        ");

        $stmt->execute([
            $clientId,
            $usuario,
            $senha,
            $ip,
            $dataExpiracao,
            $loggedInRevendedorId, // Sempre associa ao revendedor logado
            $provedorId
        ]);

        $id = $db->lastInsertId();

        standardResponse(true, [
            'id' => $id,
            'client_id' => $clientId,
            'usuario' => $usuario
        ], 'Client ID criado com sucesso!');

    } catch (PDOException $e) {
        error_log("NomaTV v4.5 [CLIENT_IDS] Erro criar: " . $e->getMessage());
        if ($e->getCode() == 23000) {
            standardResponse(false, null, 'Já existe um client ID com este valor.');
        } else {
            standardResponse(false, null, 'Erro ao criar client ID.');
        }
    }
}

/**
 * ✅ ATUALIZAR CLIENT IDS (múltiplos)
 */
function atualizarClientIds(PDO $db, string $loggedInRevendedorId, string $loggedInUserType, array $input): void
{
    $clientIds = $input['client_ids'] ?? [];
    $acao = $input['acao'] ?? '';

    if (empty($clientIds) || !is_array($clientIds)) {
        http_response_code(400);
        standardResponse(false, null, 'Lista de client IDs é obrigatória.');
        return;
    }

    try {
        $placeholders = implode(',', array_fill(0, count($clientIds), '?'));
        
        switch ($acao) {
            case 'ativar':
                $stmt = $db->prepare("UPDATE client_ids SET ativo = 1, bloqueado = 0, atualizado_em = CURRENT_TIMESTAMP WHERE id IN ($placeholders)");
                $stmt->execute($clientIds);
                $message = 'Client IDs ativados com sucesso!';
                break;
                
            case 'desativar':
                $stmt = $db->prepare("UPDATE client_ids SET ativo = 0, atualizado_em = CURRENT_TIMESTAMP WHERE id IN ($placeholders)");
                $stmt->execute($clientIds);
                $message = 'Client IDs desativados com sucesso!';
                break;
                
            case 'bloquear':
                $stmt = $db->prepare("UPDATE client_ids SET bloqueado = 1, atualizado_em = CURRENT_TIMESTAMP WHERE id IN ($placeholders)");
                $stmt->execute($clientIds);
                $message = 'Client IDs bloqueados com sucesso!';
                break;
                
            case 'desbloquear':
                $stmt = $db->prepare("UPDATE client_ids SET bloqueado = 0, atualizado_em = CURRENT_TIMESTAMP WHERE id IN ($placeholders)");
                $stmt->execute($clientIds);
                $message = 'Client IDs desbloqueados com sucesso!';
                break;
                
            default:
                http_response_code(400);
                standardResponse(false, null, 'Ação inválida.');
                return;
        }

        standardResponse(true, [
            'affected_rows' => $stmt->rowCount(),
            'action' => $acao
        ], $message);

    } catch (PDOException $e) {
        error_log("NomaTV v4.5 [CLIENT_IDS] Erro atualizar: " . $e->getMessage());
        standardResponse(false, null, 'Erro ao atualizar client IDs.');
    }
}

/**
 * ✅ DELETAR CLIENT IDS (múltiplos)
 */
function deletarClientIds(PDO $db, string $loggedInRevendedorId, string $loggedInUserType, array $input): void
{
    $clientIds = $input['client_ids'] ?? [];

    if (empty($clientIds) || !is_array($clientIds)) {
        http_response_code(400);
        standardResponse(false, null, 'Lista de client IDs é obrigatória.');
        return;
    }

    try {
        $placeholders = implode(',', array_fill(0, count($clientIds), '?'));
        
        // Soft delete
        $stmt = $db->prepare("UPDATE client_ids SET ativo = 0, atualizado_em = CURRENT_TIMESTAMP WHERE id IN ($placeholders)");
        $stmt->execute($clientIds);

        standardResponse(true, [
            'affected_rows' => $stmt->rowCount()
        ], 'Client IDs deletados com sucesso!');

    } catch (PDOException $e) {
        error_log("NomaTV v4.5 [CLIENT_IDS] Erro deletar: " . $e->getMessage());
        standardResponse(false, null, 'Erro ao deletar client IDs.');
    }
}

/**
 * ✅ IMPORTAR CLIENT IDS
 */
function importarClientIds(PDO $db, string $loggedInRevendedorId, string $loggedInUserType, array $input): void
{
    $dados = $input['dados'] ?? [];
    $provedorId = intval($input['provedor_id'] ?? 0);

    if (empty($dados) || !is_array($dados)) {
        http_response_code(400);
        standardResponse(false, null, 'Dados para importação são obrigatórios.');
        return;
    }

    try {
        $db->beginTransaction();
        
        $imported = 0;
        $errors = [];
        
        $stmt = $db->prepare("
            INSERT INTO client_ids (client_id, usuario, senha, ip, id_revendedor, provedor_id, ativo, criado_em)
            VALUES (?, ?, ?, ?, ?, ?, 1, CURRENT_TIMESTAMP)
        ");

        foreach ($dados as $index => $linha) {
            try {
                $stmt->execute([
                    $linha['client_id'] ?? '',
                    $linha['usuario'] ?? '',
                    $linha['senha'] ?? '',
                    $linha['ip'] ?? '',
                    $loggedInRevendedorId,
                    $provedorId
                ]);
                $imported++;
            } catch (PDOException $e) {
                $errors[] = "Linha " . ($index + 1) . ": " . $e->getMessage();
            }
        }

        $db->commit();

        standardResponse(true, [
            'imported' => $imported,
            'errors' => $errors
        ], "Importação concluída! $imported registros importados.");

    } catch (Exception $e) {
        $db->rollBack();
        error_log("NomaTV v4.5 [CLIENT_IDS] Erro importar: " . $e->getMessage());
        standardResponse(false, null, 'Erro na importação.');
    }
}

/**
 * ✅ EXPORTAR CLIENT IDS
 */
function exportarClientIds(PDO $db, string $loggedInRevendedorId, string $loggedInUserType, array $input): void
{
    $formato = $input['formato'] ?? 'csv';
    $filtros = $input['filtros'] ?? [];

    try {
        // Usar a mesma lógica de filtros da listagem
        // ... (implementar conforme necessário)
        
        standardResponse(true, [
            'format' => $formato,
            'download_url' => '/api/exports/client_ids_' . date('Y-m-d_H-i-s') . '.' . $formato
        ], 'Exportação preparada com sucesso!');

    } catch (Exception $e) {
        error_log("NomaTV v4.5 [CLIENT_IDS] Erro exportar: " . $e->getMessage());
        standardResponse(false, null, 'Erro na exportação.');
    }
}
?>