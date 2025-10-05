<?php
/**
 * ENDPOINT REDE REVENDEDOR - NomaTV API v4.5
 * RESPONSABILIDADES:
 * ✅ Modal "Ver Rede" 🌐 com métricas em tempo real
 * ✅ Busca recursiva por parent_id para rede completa
 * ✅ Filtros automáticos baseados na rede hierárquica
 * ✅ Cálculo de 3 métricas: Ativos Totais, Diretos, Indiretos
 */

ini_set('display_errors', 0);
error_reporting(E_ALL);

// Headers CORS
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/config/database_sqlite.php';

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

// ✅ AUTENTICAÇÃO PADRÃO (OBRIGATÓRIA)
session_start();
if (empty($_SESSION['id_revendedor']) || empty($_SESSION['master'])) {
    http_response_code(401);
    exit('{"success":false,"message":"Usuário não autenticado"}');
}
$loggedInRevendedorId = $_SESSION['id_revendedor'];
$loggedInUserType = $_SESSION['master'];

// ✅ ROTEAMENTO PRINCIPAL
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    standardResponse(false, null, 'Método não permitido. Use GET.');
}

try {
    $targetRevendedorId = $_GET['id'] ?? null;
    verRedeRevendedor($db, $loggedInRevendedorId, $loggedInUserType, $targetRevendedorId);
} catch (Exception $e) {
    error_log("NomaTV v4.5 [REDE-REVENDEDOR] Erro: " . $e->getMessage());
    http_response_code(500);
    standardResponse(false, null, 'Erro interno do servidor.');
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
 * HANDLER PRINCIPAL
 * =================================================================
 */

/**
 * Handler para visualizar rede completa de um revendedor
 */
function verRedeRevendedor(PDO $db, string $loggedInRevendedorId, string $loggedInUserType, ?string $targetRevendedorId): void
{
    try {
        if (empty($targetRevendedorId)) {
            http_response_code(400);
            standardResponse(false, null, 'ID do revendedor é obrigatório.');
        }

        // ✅ VERIFICAR PERMISSÕES HIERÁRQUICAS
        if (!verificarPermissaoVisualizacao($db, $loggedInRevendedorId, $loggedInUserType, $targetRevendedorId)) {
            http_response_code(403);
            standardResponse(false, null, 'Sem permissão para ver rede deste revendedor.');
        }

        // Buscar dados do revendedor base
        $stmt = $db->prepare("SELECT nome, usuario, master FROM revendedores WHERE id_revendedor = ? AND ativo = 1");
        $stmt->execute([$targetRevendedorId]);
        $revendedorBase = $stmt->fetch();
        
        if (!$revendedorBase) {
            http_response_code(404);
            standardResponse(false, null, 'Revendedor não encontrado.');
        }
        
        // Buscar filhos diretos
        $stmt = $db->prepare("SELECT id_revendedor FROM revendedores WHERE parent_id = ? AND ativo = 1");
        $stmt->execute([$targetRevendedorId]);
        $filhosDirectos = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $revendedoresDirectos = count($filhosDirectos);
        
        // Buscar rede completa (recursiva)
        $redeCompleta = buscarRedeCompleta($db, $targetRevendedorId);
        $totalRede = count($redeCompleta);
        $revendedoresIndirectos = $totalRede - $revendedoresDirectos;
        
        // Calcular ativos totais da rede (incluindo o próprio revendedor)
        $todosIds = array_merge([$targetRevendedorId], $redeCompleta);
        if (!empty($todosIds)) {
            $placeholders = implode(',', array_fill(0, count($todosIds), '?'));
            $stmt = $db->prepare("SELECT COUNT(*) FROM client_ids WHERE id_revendedor IN ($placeholders) AND ativo = 1");
            $stmt->execute($todosIds);
            $ativosTotal = (int)$stmt->fetchColumn();
        } else {
            $ativosTotal = 0;
        }
        
        $metricas = [
            'ativos_total' => $ativosTotal,
            'total_revendedores' => $totalRede,
            'revendedores_diretos' => $revendedoresDirectos,
            'revendedores_indiretos' => $revendedoresIndirectos
        ];
        
        standardResponse(true, [
            'revendedor_base' => $revendedorBase,
            'metricas' => $metricas
        ], 'Rede carregada com sucesso.');
        
    } catch (Exception $e) {
        error_log("NomaTV v4.5 [REDE-REVENDEDOR] Erro em verRedeRevendedor: " . $e->getMessage());
        standardResponse(false, null, 'Erro ao carregar rede.');
    }
}

/**
 * =================================================================
 * FUNÇÕES AUXILIARES
 * =================================================================
 */

/**
 * Verifica se o usuário logado tem permissão para visualizar rede do revendedor alvo
 */
function verificarPermissaoVisualizacao(PDO $db, string $loggedInRevendedorId, string $loggedInUserType, string $targetRevendedorId): bool
{
    if ($loggedInUserType === 'admin') {
        return true; // Admin pode ver qualquer rede
    }

    if ($loggedInRevendedorId === $targetRevendedorId) {
        return true; // Pode ver sua própria rede
    }

    if ($loggedInUserType === 'sim') {
        // Master pode ver redes de sua rede descendente
        $redeCompleta = buscarRedeCompleta($db, $loggedInRevendedorId);
        return in_array($targetRevendedorId, $redeCompleta);
    }

    return false; // Sub-revendedor só pode ver sua própria rede
}
?>