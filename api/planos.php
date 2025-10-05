<?php
/**
 * ENDPOINT DE PLANOS - NomaTV API v4.5
 * RESPONSABILIDADES:
 * ✅ CRUD completo de planos
 * ✅ Busca recursiva por parent_id para filtros hierárquicos
 * ✅ Filtros automáticos baseados na rede completa do revendedor
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

// ✅ FUNÇÃO RESPOSTA PADRONIZADA
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

// ✅ AUTENTICAÇÃO USANDO SESSION COMUM
$user = verificarAutenticacao();
if (!$user) {
    respostaNaoAutenticado();
}
$loggedInRevendedorId = $user['id'];
$loggedInUserType = $user['master'];

// ✅ ROTEAMENTO PRINCIPAL
$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true) ?? [];
$resourceId = $_GET['id'] ?? null;

try {
    switch ($method) {
        case 'GET': listarPlanos($db, $_GET, $loggedInRevendedorId, $loggedInUserType); break;
        case 'POST': criarPlano($db, $loggedInRevendedorId, $loggedInUserType, $input); break;
        case 'PUT': atualizarPlano($db, $loggedInRevendedorId, $loggedInUserType, $resourceId, $input); break;
        case 'DELETE': deletarPlano($db, $loggedInRevendedorId, $loggedInUserType, $resourceId); break;
        default: 
            http_response_code(405);
            standardResponse(false, null, 'Método não permitido.');
    }
} catch (Exception $e) {
    error_log("NomaTV v4.5 [PLANOS] Erro: " . $e->getMessage());
    http_response_code(500);
    standardResponse(false, null, 'Erro interno do servidor.');
}

/**
 * ✅ BUSCA RECURSIVA PADRÃO - Sistema Hierárquico parent_id
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
 * ✅ LISTAR PLANOS - Com filtros hierárquicos automáticos
 */
function listarPlanos(PDO $db, array $filters, string $loggedInRevendedorId, string $loggedInUserType): void
{
    try {
        $whereConditions = [];
        $queryParams = [];
        
        // ✅ LÓGICA HIERÁRQUICA PADRÃO
        if ($loggedInUserType === "admin") {
            // Admin vê todos os planos
            // Pode filtrar por revendedor específico
            if (!empty($filters['id_revendedor'])) {
                $whereConditions[] = "p.id_revendedor_criador = ?";
                $queryParams[] = $filters['id_revendedor'];
            }
            
        } elseif ($loggedInUserType === "sim") {
            // Revendedor Master vê planos de toda sua rede descendente
            $redeCompleta = buscarRedeCompleta($db, $loggedInRevendedorId);
            $idsPermitidos = array_merge([$loggedInRevendedorId], $redeCompleta);
            
            $placeholders = implode(',', array_fill(0, count($idsPermitidos), '?'));
            $whereConditions[] = "p.id_revendedor_criador IN ($placeholders)";
            $queryParams = array_merge($queryParams, $idsPermitidos);
            
        } else {
            // Sub-revendedor vê apenas seus próprios planos
            $whereConditions[] = "p.id_revendedor_criador = ?";
            $queryParams[] = $loggedInRevendedorId;
        }
        
        // Filtros adicionais
        if (!empty($filters['ativo'])) {
            $whereConditions[] = "p.ativo = ?";
            $queryParams[] = ($filters['ativo'] === 'true' || $filters['ativo'] === '1') ? 1 : 0;
        }
        
        if (!empty($filters['tipo_cobranca'])) {
            $whereConditions[] = "p.tipo_cobranca = ?";
            $queryParams[] = $filters['tipo_cobranca'];
        }
        
        if (!empty($filters['search'])) {
            $whereConditions[] = "(p.nome LIKE ? OR p.descricao LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $queryParams[] = $searchTerm;
            $queryParams[] = $searchTerm;
        }
        
        // Construir query
        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
        
        $sql = "
            SELECT
                p.id,
                p.nome,
                p.descricao,
                p.tipo_cobranca,
                p.valor,
                p.limite_ativos,
                p.limite_provedores,
                p.recursos,
                p.ativo,
                p.ordem,
                p.cor,
                p.icone,
                p.id_revendedor_criador,
                p.criado_em,
                p.atualizado_em,
                r.nome as criador_nome,
                (SELECT COUNT(rev.id_revendedor) 
                 FROM revendedores rev 
                 WHERE rev.plano = p.nome AND rev.master != 'admin') as revendedores_usando
            FROM planos p
            LEFT JOIN revendedores r ON p.id_revendedor_criador = r.id_revendedor
            $whereClause
            ORDER BY p.ordem ASC, p.nome ASC
        ";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($queryParams);
        $planos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Processar recursos JSON
        foreach ($planos as &$plano) {
            $plano['recursos'] = json_decode($plano['recursos'] ?? '[]', true);
        }
        
        standardResponse(true, $planos, 'Planos listados com sucesso.');
        
    } catch (Exception $e) {
        error_log("NomaTV v4.5 [PLANOS] Erro em listarPlanos: " . $e->getMessage());
        standardResponse(false, null, 'Erro ao listar planos.');
    }
}

/**
 * ✅ CRIAR PLANO - Com validações e permissões
 */
function criarPlano(PDO $db, string $loggedInRevendedorId, string $loggedInUserType, array $input): void
{
    // Validação de entrada
    $nome = trim($input['nome'] ?? '');
    $descricao = trim($input['descricao'] ?? '');
    $tipoCobranca = $input['tipo_cobranca'] ?? 'mensal';
    $valor = floatval($input['valor'] ?? 0.0);
    $limiteAtivos = intval($input['limite_ativos'] ?? 0);
    $limiteProvedores = intval($input['limite_provedores'] ?? 0);
    $recursos = $input['recursos'] ?? [];
    $ativo = (bool)($input['ativo'] ?? 1);
    $ordem = intval($input['ordem'] ?? 0);
    $cor = $input['cor'] ?? '#007bff';
    $icone = $input['icone'] ?? '📦';

    if (empty($nome) || $valor < 0) {
        http_response_code(400);
        standardResponse(false, null, 'Nome e valor são obrigatórios.');
    }

    // Verificar tipos de cobrança válidos
    $tiposValidos = ['mensal', 'anual', 'unico'];
    if (!in_array($tipoCobranca, $tiposValidos)) {
        http_response_code(400);
        standardResponse(false, null, 'Tipo de cobrança inválido.');
    }

    try {
        $db->beginTransaction();

        // Verificar se já existe plano com mesmo nome
        $stmt = $db->prepare("SELECT COUNT(*) FROM planos WHERE nome = ?");
        $stmt->execute([$nome]);
        if ($stmt->fetchColumn() > 0) {
            $db->rollBack();
            http_response_code(400);
            standardResponse(false, null, 'Já existe um plano com este nome.');
        }

        $stmt = $db->prepare("
            INSERT INTO planos (
                nome, descricao, tipo_cobranca, valor, limite_ativos, 
                limite_provedores, recursos, ativo, ordem, cor, icone, 
                id_revendedor_criador
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $nome, $descricao, $tipoCobranca, $valor, $limiteAtivos, 
            $limiteProvedores, json_encode($recursos), (int)$ativo, 
            $ordem, $cor, $icone, $loggedInRevendedorId
        ]);

        $newId = $db->lastInsertId();
        $db->commit();
        
        standardResponse(true, ['id' => $newId], 'Plano criado com sucesso!');

    } catch (Exception $e) {
        $db->rollBack();
        error_log("NomaTV v4.5 [PLANOS] Erro em criarPlano: " . $e->getMessage());
        standardResponse(false, null, 'Erro ao criar plano.');
    }
}

/**
 * ✅ ATUALIZAR PLANO - Com verificação de permissões
 */
function atualizarPlano(PDO $db, string $loggedInRevendedorId, string $loggedInUserType, ?string $resourceId, array $input): void
{
    if (!$resourceId) {
        http_response_code(400);
        standardResponse(false, null, 'ID do plano é obrigatório.');
    }

    try {
        // Verificar se o plano existe e se o usuário tem permissão
        $stmt = $db->prepare("SELECT id_revendedor_criador FROM planos WHERE id = ?");
        $stmt->execute([$resourceId]);
        $plano = $stmt->fetch();
        
        if (!$plano) {
            http_response_code(404);
            standardResponse(false, null, 'Plano não encontrado.');
        }
        
        // Verificar permissões
        if ($loggedInUserType !== 'admin' && $plano['id_revendedor_criador'] !== $loggedInRevendedorId) {
            // Para revendedores master, verificar se o plano pertence à sua rede
            if ($loggedInUserType === 'sim') {
                $redeCompleta = buscarRedeCompleta($db, $loggedInRevendedorId);
                if (!in_array($plano['id_revendedor_criador'], $redeCompleta)) {
                    http_response_code(403);
                    standardResponse(false, null, 'Sem permissão para editar este plano.');
                }
            } else {
                http_response_code(403);
                standardResponse(false, null, 'Sem permissão para editar este plano.');
            }
        }

        $fields = [];
        $params = [];

        // Campos atualizáveis
        $allowedFields = [
            'nome', 'descricao', 'tipo_cobranca', 'valor', 'limite_ativos',
            'limite_provedores', 'recursos', 'ativo', 'ordem', 'cor', 'icone'
        ];

        foreach ($allowedFields as $field) {
            if (isset($input[$field])) {
                if ($field === 'recursos') {
                    $fields[] = "recursos = ?";
                    $params[] = json_encode($input[$field]);
                } elseif ($field === 'ativo') {
                    $fields[] = "ativo = ?";
                    $params[] = (int)(bool)$input[$field];
                } else {
                    $fields[] = "$field = ?";
                    $params[] = $input[$field];
                }
            }
        }

        if (empty($fields)) {
            http_response_code(400);
            standardResponse(false, null, 'Nenhum campo para atualizar.');
        }

        $params[] = $resourceId;
        $sql = "UPDATE planos SET " . implode(', ', $fields) . ", atualizado_em = CURRENT_TIMESTAMP WHERE id = ?";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        if ($stmt->rowCount() === 0) {
            standardResponse(false, null, 'Nenhum dado alterado.');
        }

        standardResponse(true, null, 'Plano atualizado com sucesso!');

    } catch (Exception $e) {
        error_log("NomaTV v4.5 [PLANOS] Erro em atualizarPlano: " . $e->getMessage());
        standardResponse(false, null, 'Erro ao atualizar o plano.');
    }
}

/**
 * ✅ DELETAR PLANO - Com verificações de uso e permissões
 */
function deletarPlano(PDO $db, string $loggedInRevendedorId, string $loggedInUserType, ?string $resourceId): void
{
    if (!$resourceId) {
        http_response_code(400);
        standardResponse(false, null, 'ID do plano é obrigatório.');
    }

    try {
        $db->beginTransaction();

        // Verificar se o plano existe e se o usuário tem permissão
        $stmt = $db->prepare("SELECT id_revendedor_criador, nome FROM planos WHERE id = ?");
        $stmt->execute([$resourceId]);
        $plano = $stmt->fetch();
        
        if (!$plano) {
            $db->rollBack();
            http_response_code(404);
            standardResponse(false, null, 'Plano não encontrado.');
        }
        
        // Verificar permissões (igual ao update)
        if ($loggedInUserType !== 'admin' && $plano['id_revendedor_criador'] !== $loggedInRevendedorId) {
            if ($loggedInUserType === 'sim') {
                $redeCompleta = buscarRedeCompleta($db, $loggedInRevendedorId);
                if (!in_array($plano['id_revendedor_criador'], $redeCompleta)) {
                    $db->rollBack();
                    http_response_code(403);
                    standardResponse(false, null, 'Sem permissão para deletar este plano.');
                }
            } else {
                $db->rollBack();
                http_response_code(403);
                standardResponse(false, null, 'Sem permissão para deletar este plano.');
            }
        }

        // Verificar se o plano está em uso
        $stmt = $db->prepare("SELECT COUNT(*) FROM revendedores WHERE plano = ? AND master != 'admin'");
        $stmt->execute([$plano['nome']]);
        $emUso = $stmt->fetchColumn();
        
        if ($emUso > 0) {
            $db->rollBack();
            http_response_code(400);
            standardResponse(false, null, 'Não é possível deletar plano em uso por revendedores.');
        }

        $stmt = $db->prepare("DELETE FROM planos WHERE id = ?");
        $stmt->execute([$resourceId]);

        if ($stmt->rowCount() === 0) {
            $db->rollBack();
            standardResponse(false, null, 'Plano não encontrado para exclusão.');
        }

        $db->commit();
        standardResponse(true, null, 'Plano removido com sucesso!');

    } catch (Exception $e) {
        $db->rollBack();
        error_log("NomaTV v4.5 [PLANOS] Erro em deletarPlano: " . $e->getMessage());
        standardResponse(false, null, 'Erro ao remover o plano.');
    }
}
?>