<?php
/**
 * LOGO_PROXY.PHP - Sistema de Branding NomaTV v4.5
 * 
 * FUNÇÃO: Proxy inteligente com fallback em cascata
 * 
 * LÓGICA:
 * 1. Recebe revendedor_id via GET (?id=102)
 * 2. Busca logo em uploads/logos/{id}.png
 * 3. Se não encontrar e for sub → busca logo do pai
 * 4. Fallback final → https://webnoma.shop/logos/nomaapp.png
 * 5. Retorna URL como TEXTO (não binário)
 * 
 * CHAMADO POR: index_casca.html (sessionStorage)
 * 
 * LOCALIZAÇÃO: /api/logo_proxy.php
 */

// Headers para texto simples com cache
header('Content-Type: text/plain; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Cache-Control: public, max-age=3600'); // Cache 1 hora

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ✅ CONFIGURAÇÕES
define('UPLOAD_DIR', __DIR__ . '/uploads/logos/');
define('FALLBACK_URL', 'https://webnoma.shop/logos/nomaapp.png');
define('BASE_URL', 'https://webnoma.space'); // Servidor principal

// ✅ VALIDAÇÃO DE MÉTODO
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo FALLBACK_URL;
    exit();
}

// ✅ OBTER REVENDEDOR_ID
$revendedorId = $_GET['id'] ?? null;

// Validar se é numérico
if (!$revendedorId || !is_numeric($revendedorId) || $revendedorId <= 0) {
    // ID inválido → fallback imediato
    echo FALLBACK_URL;
    exit();
}

// ✅ CONEXÃO COM BANCO DE DADOS
try {
    // Tentar múltiplos caminhos para o banco
    $possiblePaths = [
        __DIR__ . '/db.db',
        __DIR__ . '/nomatv.db',
        __DIR__ . '/../db.db',
        __DIR__ . '/../nomatv.db'
    ];
    
    $dbPath = null;
    foreach ($possiblePaths as $path) {
        if (file_exists($path) && is_readable($path)) {
            $dbPath = $path;
            break;
        }
    }
    
    if (!$dbPath) {
        // Sem banco → fallback
        echo FALLBACK_URL;
        exit();
    }
    
    $db = new PDO("sqlite:$dbPath");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    // Erro de conexão → fallback
    error_log("NomaTV [LOGO_PROXY] Erro de conexão: " . $e->getMessage());
    echo FALLBACK_URL;
    exit();
}

// ✅ BUSCAR REVENDEDOR NO BANCO
try {
    $stmt = $db->prepare("
        SELECT 
            id_revendedor, 
            nome, 
            master,
            id_pai
        FROM revendedores 
        WHERE id_revendedor = ? AND ativo = 1
    ");
    $stmt->execute([$revendedorId]);
    $revendedor = $stmt->fetch();
    
    if (!$revendedor) {
        // Revendedor não existe ou inativo → fallback
        echo FALLBACK_URL;
        exit();
    }
    
    // ✅ TENTAR ENCONTRAR LOGO (CASCATA)
    $logoUrl = buscarLogoCascata($revendedor, $db);
    
    // Retornar URL encontrada
    echo $logoUrl;
    
} catch (Exception $e) {
    error_log("NomaTV [LOGO_PROXY] Erro na busca: " . $e->getMessage());
    echo FALLBACK_URL;
}

$db = null;
exit();

/**
 * =================================================================
 * FUNÇÃO: BUSCA EM CASCATA
 * =================================================================
 */

/**
 * Busca logo seguindo hierarquia: SUB → PAI → BACKUP
 */
function buscarLogoCascata($revendedor, $db) {
    $revendedorId = $revendedor['id_revendedor'];
    $isMaster = $revendedor['master'] === 'sim';
    $paiId = $revendedor['id_pai'];
    
    // ✅ PASSO 1: Tentar logo do próprio revendedor
    $logoFile = UPLOAD_DIR . $revendedorId . '.png';
    
    if (file_exists($logoFile) && filesize($logoFile) > 0) {
        // Logo encontrada! Retornar URL
        return BASE_URL . '/uploads/logos/' . $revendedorId . '.png';
    }
    
    // ✅ PASSO 2: Se for SUB e não tem logo → buscar do PAI
    if (!$isMaster && $paiId) {
        try {
            // Buscar info do pai
            $stmt = $db->prepare("
                SELECT id_revendedor, nome 
                FROM revendedores 
                WHERE id_revendedor = ? AND ativo = 1
            ");
            $stmt->execute([$paiId]);
            $pai = $stmt->fetch();
            
            if ($pai) {
                $logoPaiFile = UPLOAD_DIR . $paiId . '.png';
                
                if (file_exists($logoPaiFile) && filesize($logoPaiFile) > 0) {
                    // Logo do pai encontrada!
                    return BASE_URL . '/uploads/logos/' . $paiId . '.png';
                }
            }
        } catch (Exception $e) {
            error_log("NomaTV [LOGO_PROXY] Erro ao buscar pai: " . $e->getMessage());
        }
    }
    
    // ✅ PASSO 3: Fallback final → Logo NomaTV do servidor de backup
    return FALLBACK_URL;
}

?>