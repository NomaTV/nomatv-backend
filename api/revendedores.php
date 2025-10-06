<?php
/**
 * ENDPOINT DE REVENDEDORES - NomaTV API v4.5
 * RESPONSABILIDADES:
 * ✅ CRUD completo de revendedores
 * ✅ Busca recursiva por parent_id para filtros hierárquicos
 * ✅ Filtros automáticos baseados na rede completa do revendedor
 * ✅ Sistema hierárquico com parent_id automático
 * ✅ Geração de IDs hierárquicos e permissões baseadas em rede
 */

ini_set('display_errors', 0);
error_reporting(E_ALL);

// Headers CORS
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

// ✅ ROTEAMENTO PRINCIPAL
$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true) ?? [];
$resourceId = $_GET['id'] ?? null;

try {
    switch ($method) {
        case 'GET': listarRevendedores($db, $_GET, $loggedInRevendedorId, $dadosRevendedor['master']); break;
        case 'POST': handlePostRevendedores($db, $loggedInRevendedorId, $dadosRevendedor['master'], $input); break;
        case 'PUT': atualizarRevendedor($db, $loggedInRevendedorId, $dadosRevendedor['master'], $resourceId, $input); break;
        case 'DELETE': deletarRevendedor($db, $loggedInRevendedorId, $dadosRevendedor['master'], $resourceId); break;
        default:
            respostaErroPadronizada('Método não permitido.', 405);
    }
} catch (Exception $e) {
    error_log("NomaTV v4.5 [REVENDEDORES] Erro: " . $e->getMessage());
    respostaErroPadronizada('Erro interno do servidor.');
}

/**
 * =================================================================
 * BUSCA RECURSIVA PADRÃO
 * =================================================================
 */

/**
 * Busca toda a rede descendente de um revendedor (recursiva)
 */
function buscarRedeCompleta(PDO $db, string $idRevendedor): array
{
    $idsParaBuscar = [$idRevendedor];
    $todosDescendentes = [];
    $indice = 0;
    
    while ($indice < count($idsParaBuscar)) {
        $idAtual = $idsParaBuscar[$indice];
        
        $stmt = $db->prepare("
            SELECT id_revendedor
            FROM revendedores 
            WHERE parent_id = ? AND ativo = 1
        ");
        $stmt->execute([$idAtual]);
        $filhos = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (!empty($filhos)) {
            $todosDescendentes = array_merge($todosDescendentes, $filhos);
            $idsParaBuscar = array_merge($idsParaBuscar, $filhos);
        }
        
        $indice++;
    }
    
    return array_diff($todosDescendentes, [$idRevendedor]);
}

/**
 * =================================================================
 * HANDLERS PRINCIPAIS
 * =================================================================
 */

/**
 * Listar revendedores com filtros hierárquicos
 */
function listarRevendedores(PDO $db, array $params, string $loggedInRevendedorId, string $loggedInUserType): void
{
    try {
        $page = max(1, intval($params['page'] ?? 1));
        $limit = max(1, min(100, intval($params['limit'] ?? 25)));
        $offset = ($page - 1) * $limit;

        $whereConditions = ["1=1"];
        $queryParams = [];

        // Filtros de busca
        if (!empty($params['search'])) {
            $search = '%' . $params['search'] . '%';
            $whereConditions[] = "(r.nome LIKE ? OR r.usuario LIKE ? OR r.id_revendedor LIKE ?)";
            $queryParams[] = $search;
            $queryParams[] = $search;
            $queryParams[] = $search;
        }

        if (!empty($params['status'])) {
            if ($params['status'] === 'ativo') {
                $whereConditions[] = "r.ativo = 1";
            } elseif ($params['status'] === 'inativo') {
                $whereConditions[] = "r.ativo = 0";
            }
        }

        // ✅ LÓGICA HIERÁRQUICA PADRÃO
        if ($loggedInUserType === "admin") {
            // Admin vê todos os revendedores (exceto outros admins)
            $whereConditions[] = "r.master != 'admin'";
            
        } elseif ($loggedInUserType === "sim") {
            // Revendedor Master vê toda sua rede descendente
            $redeCompleta = buscarRedeCompleta($db, $loggedInRevendedorId);
            $idsPermitidos = array_merge([$loggedInRevendedorId], $redeCompleta);
            
            if (!empty($idsPermitidos)) {
                $placeholders = implode(',', array_fill(0, count($idsPermitidos), '?'));
                $whereConditions[] = "r.id_revendedor IN ($placeholders)";
                $queryParams = array_merge($queryParams, $idsPermitidos);
            } else {
                // Sem rede, só vê a si mesmo
                $whereConditions[] = "r.id_revendedor = ?";
                $queryParams[] = $loggedInRevendedorId;
            }
            
        } else {
            // Sub-revendedor vê apenas seus próprios dados
            $whereConditions[] = "r.id_revendedor = ?";
            $queryParams[] = $loggedInRevendedorId;
        }

        $whereClause = implode(' AND ', $whereConditions);

        // Query para estatísticas
        $statsQuery = "
            SELECT
                COUNT(*) as total,
                SUM(CASE WHEN r.ativo = 1 THEN 1 ELSE 0 END) as ativos,
                SUM(CASE WHEN r.ativo = 0 THEN 1 ELSE 0 END) as inativos,
                SUM(CASE WHEN r.master = 'sim' THEN 1 ELSE 0 END) as total_masters,
                SUM(CASE WHEN r.master = 'nao' THEN 1 ELSE 0 END) as total_sub_revendedores
            FROM revendedores r
            WHERE $whereClause
        ";

        $stmtStats = $db->prepare($statsQuery);
        $stmtStats->execute($queryParams);
        $stats = $stmtStats->fetch();

        // Query para dados principais
        $dataQuery = "
            SELECT
                r.id_revendedor,
                r.usuario,
                r.nome,
                r.email,
                r.master,
                r.parent_id,
                r.plano,
                r.valor_ativo,
                r.valor_mensal,
                r.limite_ativos,
                r.ativo,
                r.data_vencimento,
                r.data_bloqueio,
                r.criado_em,
                r.atualizado_em,
                COALESCE((SELECT COUNT(*) FROM client_ids c WHERE c.id_revendedor = r.id_revendedor AND c.ativo = 1), 0) as ativos_count,
                COALESCE((SELECT COUNT(*) FROM provedores p WHERE p.id_revendedor = r.id_revendedor AND p.ativo = 1), 0) as provedores_count,
                COALESCE((SELECT COUNT(*) FROM revendedores sub WHERE sub.parent_id = r.id_revendedor AND sub.ativo = 1), 0) as sub_revendedores_count
            FROM revendedores r
            WHERE $whereClause
            ORDER BY r.criado_em DESC
            LIMIT ? OFFSET ?
        ";

        $stmtData = $db->prepare($dataQuery);
        $finalParams = array_merge($queryParams, [$limit, $offset]);
        $stmtData->execute($finalParams);
        $revendedores = $stmtData->fetchAll();

        $totalRegistros = intval($stats['total'] ?? 0);
        $totalPages = max(1, ceil($totalRegistros / $limit));

        standardResponse(
            true,
            $revendedores,
            'Revendedores listados com sucesso.',
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
                    'total_masters' => intval($stats['total_masters'] ?? 0),
                    'total_sub_revendedores' => intval($stats['total_sub_revendedores'] ?? 0)
                ]
            ]
        );

    } catch (Exception $e) {
        error_log("NomaTV v4.5 [REVENDEDORES] Erro em listarRevendedores: " . $e->getMessage());
        http_response_code(500);
        standardResponse(false, null, 'Erro ao listar revendedores.');
    }
}

/**
 * Handler para requisições POST
 */
function handlePostRevendedores(PDO $db, string $loggedInRevendedorId, string $loggedInUserType, array $input): void
{
    $action = $input['action'] ?? '';

    try {
        switch ($action) {
            case 'criar':
                criarRevendedor($db, $loggedInRevendedorId, $loggedInUserType, $input);
                break;
            case 'reset_senha':
                resetarSenhaRevendedor($db, $loggedInRevendedorId, $loggedInUserType, $input);
                break;
            default:
                http_response_code(400);
                standardResponse(false, null, 'Ação inválida.');
        }
    } catch (Exception $e) {
        error_log("NomaTV v4.5 [REVENDEDORES] Erro em handlePostRevendedores: " . $e->getMessage());
        http_response_code(500);
        standardResponse(false, null, 'Erro ao processar ação.');
    }
}

/**
 * Criar novo revendedor com parent_id automático
 */
function criarRevendedor(PDO $db, string $loggedInRevendedorId, string $loggedInUserType, array $input): void
{
    // Validações básicas
    $nome = trim($input['nome'] ?? '');
    $usuario = trim($input['usuario'] ?? '');
    $senha = $input['senha'] ?? '';
    $email = trim($input['email'] ?? '');
    $masterType = $input['master'] ?? 'nao';
    $plano = $input['plano'] ?? 'Básico';
    $limiteAtivos = intval($input['limite_ativos'] ?? 100);
    $tipoCobranca = $input['tipo_cobranca'] ?? 'mensal';
    $valorCobranca = floatval($input['valor_cobranca'] ?? 0.0);
    $dataVencimento = $input['data_vencimento'] ?? date('Y-m-d', strtotime('+30 days'));

    if (empty($nome) || empty($usuario) || empty($senha)) {
        http_response_code(400);
        standardResponse(false, null, 'Nome, usuário e senha são obrigatórios.');
    }

    // ✅ VERIFICAÇÃO DE PERMISSÃO HIERÁRQUICA
    if ($loggedInUserType === 'nao') {
        http_response_code(403);
        standardResponse(false, null, 'Sub-revendedores não podem criar novos revendedores.');
    }

    if ($loggedInUserType === 'sim' && $masterType === 'sim') {
        http_response_code(403);
        standardResponse(false, null, 'Revendedores Master só podem criar sub-revendedores.');
    }

    try {
        $db->beginTransaction();

        // Gerar ID hierárquico
        $newRevendedorId = gerarIdHierarquico($db, $loggedInRevendedorId);

        // Definir parent_id automaticamente
        $parentId = null;
        if ($loggedInUserType !== 'admin') {
            $parentId = $loggedInRevendedorId;
        }

        // Definir valores de cobrança
        $valorAtivo = null;
        $valorMensal = null;
        if ($tipoCobranca === 'por_ativo') {
            $valorAtivo = $valorCobranca;
        } elseif ($tipoCobranca === 'mensal') {
            $valorMensal = $valorCobranca;
        }

        // Verificar se usuário já existe
        $stmt = $db->prepare("SELECT COUNT(*) FROM revendedores WHERE usuario = ?");
        $stmt->execute([$usuario]);
        if ($stmt->fetchColumn() > 0) {
            http_response_code(400);
            standardResponse(false, null, 'Nome de usuário já existe.');
        }

        // Inserir novo revendedor
        $stmt = $db->prepare("
            INSERT INTO revendedores
            (id_revendedor, usuario, senha, nome, email, master, parent_id, plano, valor_ativo, valor_mensal, limite_ativos, ativo, data_vencimento, criado_em)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, CURRENT_TIMESTAMP)
        ");

        $stmt->execute([
            $newRevendedorId,
            $usuario,
            password_hash($senha, PASSWORD_DEFAULT),
            $nome,
            $email,
            $masterType,
            $parentId,
            $plano,
            $valorAtivo,
            $valorMensal,
            $limiteAtivos,
            $dataVencimento
        ]);

        $db->commit();
        standardResponse(true, [
            'id_revendedor' => $newRevendedorId,
            'parent_id' => $parentId
        ], 'Revendedor criado com sucesso.');

    } catch (Exception $e) {
        $db->rollBack();
        error_log("NomaTV v4.5 [REVENDEDORES] Erro em criarRevendedor: " . $e->getMessage());
        http_response_code(500);
        standardResponse(false, null, 'Erro ao criar revendedor.');
    }
}

/**
 * Atualizar revendedor existente
 */
function atualizarRevendedor(PDO $db, string $loggedInRevendedorId, string $loggedInUserType, string $resourceId, array $input): void
{
    if (!$resourceId) {
        http_response_code(400);
        standardResponse(false, null, 'ID do revendedor é obrigatório.');
    }

    // ✅ VERIFICAR PERMISSÕES HIERÁRQUICAS
    if (!verificarPermissaoRevendedor($db, $loggedInRevendedorId, $loggedInUserType, $resourceId)) {
        http_response_code(403);
        standardResponse(false, null, 'Sem permissão para atualizar este revendedor.');
    }

    try {
        $fields = [];
        $params = [];

        // Campos básicos
        if (isset($input['nome'])) {
            $fields[] = "nome = ?";
            $params[] = trim($input['nome']);
        }
        if (isset($input['usuario'])) {
            $fields[] = "usuario = ?";
            $params[] = trim($input['usuario']);
        }
        if (isset($input['email'])) {
            $fields[] = "email = ?";
            $params[] = trim($input['email']);
        }
        if (isset($input['senha']) && !empty($input['senha'])) {
            $fields[] = "senha = ?";
            $params[] = password_hash($input['senha'], PASSWORD_DEFAULT);
        }
        if (isset($input['plano'])) {
            $fields[] = "plano = ?";
            $params[] = $input['plano'];
        }
        if (isset($input['limite_ativos'])) {
            $fields[] = "limite_ativos = ?";
            $params[] = intval($input['limite_ativos']);
        }

        // Valores de cobrança
        if (isset($input['tipo_cobranca']) && isset($input['valor_cobranca'])) {
            if ($input['tipo_cobranca'] === 'por_ativo') {
                $fields[] = "valor_ativo = ?";
                $fields[] = "valor_mensal = NULL";
                $params[] = floatval($input['valor_cobranca']);
            } elseif ($input['tipo_cobranca'] === 'mensal') {
                $fields[] = "valor_mensal = ?";
                $fields[] = "valor_ativo = NULL";
                $params[] = floatval($input['valor_cobranca']);
            }
        }

        if (isset($input['data_vencimento'])) {
            $fields[] = "data_vencimento = ?";
            $params[] = $input['data_vencimento'];
        }

        // Apenas admin pode alterar tipo master
        if ($loggedInUserType === 'admin' && isset($input['master'])) {
            $fields[] = "master = ?";
            $params[] = $input['master'];
        }

        // Ação de toggle status
        if (isset($input['action']) && $input['action'] === 'toggle_status') {
            $fields[] = "ativo = CASE WHEN ativo = 1 THEN 0 ELSE 1 END";
        }

        if (empty($fields)) {
            http_response_code(400);
            standardResponse(false, null, 'Nenhum campo para atualizar.');
        }

        $fields[] = "atualizado_em = CURRENT_TIMESTAMP";
        $params[] = $resourceId;

        $sql = "UPDATE revendedores SET " . implode(', ', $fields) . " WHERE id_revendedor = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            standardResponse(false, null, 'Revendedor não encontrado.');
        }

        // Para toggle status, retornar novo status
        if (isset($input['action']) && $input['action'] === 'toggle_status') {
            $stmt = $db->prepare("SELECT ativo FROM revendedores WHERE id_revendedor = ?");
            $stmt->execute([$resourceId]);
            $novoStatus = (bool)$stmt->fetchColumn();
            
            standardResponse(true, ['novo_status' => $novoStatus], 'Status alterado com sucesso.');
        } else {
            standardResponse(true, null, 'Revendedor atualizado com sucesso.');
        }

    } catch (Exception $e) {
        error_log("NomaTV v4.5 [REVENDEDORES] Erro em atualizarRevendedor: " . $e->getMessage());
        http_response_code(500);
        standardResponse(false, null, 'Erro ao atualizar revendedor.');
    }
}

/**
 * Deletar (desativar) revendedor
 */
function deletarRevendedor(PDO $db, string $loggedInRevendedorId, string $loggedInUserType, string $resourceId): void
{
    if (!$resourceId) {
        http_response_code(400);
        standardResponse(false, null, 'ID do revendedor é obrigatório.');
    }

    // ✅ VERIFICAR PERMISSÕES HIERÁRQUICAS
    if (!verificarPermissaoRevendedor($db, $loggedInRevendedorId, $loggedInUserType, $resourceId)) {
        http_response_code(403);
        standardResponse(false, null, 'Sem permissão para deletar este revendedor.');
    }

    // Sub-revendedores não podem deletar
    if ($loggedInUserType === 'nao') {
        http_response_code(403);
        standardResponse(false, null, 'Sub-revendedores não podem deletar revendedores.');
    }

    try {
        // Verificar se tem filhos na árvore
        $stmt = $db->prepare("SELECT COUNT(*) FROM revendedores WHERE parent_id = ? AND ativo = 1");
        $stmt->execute([$resourceId]);
        $temFilhos = $stmt->fetchColumn() > 0;

        if ($temFilhos) {
            http_response_code(400);
            standardResponse(false, null, 'Não é possível deletar: este revendedor possui sub-revendedores ativos.');
        }

        // Soft delete
        $stmt = $db->prepare("UPDATE revendedores SET ativo = 0, atualizado_em = CURRENT_TIMESTAMP WHERE id_revendedor = ?");
        $stmt->execute([$resourceId]);

        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            standardResponse(false, null, 'Revendedor não encontrado.');
        }

        standardResponse(true, null, 'Revendedor desativado com sucesso.');

    } catch (Exception $e) {
        error_log("NomaTV v4.5 [REVENDEDORES] Erro em deletarRevendedor: " . $e->getMessage());
        http_response_code(500);
        standardResponse(false, null, 'Erro ao deletar revendedor.');
    }
}

/**
 * Resetar senha de revendedor
 */
function resetarSenhaRevendedor(PDO $db, string $loggedInRevendedorId, string $loggedInUserType, array $input): void
{
    $targetId = $input['id'] ?? null;
    
    if (!$targetId) {
        http_response_code(400);
        standardResponse(false, null, 'ID do revendedor é obrigatório.');
    }

    // ✅ VERIFICAR PERMISSÕES HIERÁRQUICAS
    if (!verificarPermissaoRevendedor($db, $loggedInRevendedorId, $loggedInUserType, $targetId)) {
        http_response_code(403);
        standardResponse(false, null, 'Sem permissão para resetar senha deste revendedor.');
    }

    try {
        // Gerar nova senha
        $novaSenha = bin2hex(random_bytes(4)); // 8 caracteres
        $senhaHash = password_hash($novaSenha, PASSWORD_DEFAULT);

        $stmt = $db->prepare("UPDATE revendedores SET senha = ?, atualizado_em = CURRENT_TIMESTAMP WHERE id_revendedor = ?");
        $stmt->execute([$senhaHash, $targetId]);

        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            standardResponse(false, null, 'Revendedor não encontrado.');
        }

        standardResponse(true, ['nova_senha' => $novaSenha], 'Senha resetada com sucesso.');

    } catch (Exception $e) {
        error_log("NomaTV v4.5 [REVENDEDORES] Erro em resetarSenhaRevendedor: " . $e->getMessage());
        http_response_code(500);
        standardResponse(false, null, 'Erro ao resetar senha.');
    }
}

/**
 * =================================================================
 * FUNÇÕES AUXILIARES
 * =================================================================
 */

/**
 * Verifica se o usuário logado tem permissão para agir sobre um revendedor específico
 */
function verificarPermissaoRevendedor(PDO $db, string $loggedInRevendedorId, string $loggedInUserType, string $targetRevendedorId): bool
{
    if ($loggedInUserType === 'admin') {
        return true; // Admin pode tudo
    }

    if ($loggedInRevendedorId === $targetRevendedorId) {
        return true; // Pode agir sobre si mesmo
    }

    if ($loggedInUserType === 'sim') {
        // Master pode agir sobre sua rede descendente
        $redeCompleta = buscarRedeCompleta($db, $loggedInRevendedorId);
        return in_array($targetRevendedorId, $redeCompleta);
    }

    return false; // Sub-revendedor só pode agir sobre si mesmo
}

/**
 * Gerar ID hierárquico único
 */
function gerarIdHierarquico(PDO $db, string $creatorId): string
{
    $prefixo = substr($creatorId, -4);
    $attempts = 0;

    while ($attempts < 100) {
        $sequencial = str_pad(mt_rand(1000, 9999), 4, '0', STR_PAD_LEFT);
        $newId = $prefixo . $sequencial;

        // Verificar unicidade
        $stmt = $db->prepare("SELECT COUNT(*) FROM revendedores WHERE id_revendedor = ?");
        $stmt->execute([$newId]);

        if ($stmt->fetchColumn() == 0) {
            return $newId;
        }

        $attempts++;
    }

    throw new Exception("Não foi possível gerar um ID único após {$attempts} tentativas.");
}
?>