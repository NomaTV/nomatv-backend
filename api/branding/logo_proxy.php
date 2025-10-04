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

// Headers para imagem com cache
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Cache-Control: public, max-age=3600'); // Cache 1 hora

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ✅ CONFIGURAÇÕES
define('UPLOAD_DIR', __DIR__ . '/../uploads/logos/');
define('FALLBACK_URL', 'https://webnoma.shop/logos/nomaapp.png');

// ✅ VALIDAÇÃO DE MÉTODO
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    exit();
}

// ✅ OBTER REVENDEDOR_ID
$revendedorId = $_GET['r'] ?? null;

// Validar se é numérico
if (!$revendedorId || !is_numeric($revendedorId) || $revendedorId <= 0) {
    // ID inválido → fallback imediato
    proxyFallbackImage();
    exit();
}

// ✅ CONEXÃO COM BANCO DE DADOS
try {
    require_once __DIR__ . '/../config/database_sqlite.php';
    $db = getDatabaseConnection();
} catch (Exception $e) {
    // Erro de conexão → fallback
    proxyFallbackImage();
    exit();
}

// ✅ BUSCAR REVENDEDOR NO BANCO
try {
    $stmt = $db->prepare("
        SELECT id_revendedor, master, parent_id, logo_filename
        FROM revendedores
        WHERE id_revendedor = ? AND ativo = 1
    ");
    $stmt->execute([$revendedorId]);
    $revendedor = $stmt->fetch();

    if (!$revendedor) {
        // Revendedor não existe ou inativo → fallback
        proxyFallbackImage();
        exit();
    }

    // ✅ TENTAR ENCONTRAR LOGO (CASCATA)
    $logoPath = buscarLogoCascata($revendedor, $db);

    if ($logoPath) {
        // Logo encontrada - retornar binário
        returnImage($logoPath);
    } else {
        // Fallback
        proxyFallbackImage();
    }

} catch (Exception $e) {
    error_log("NomaTV [LOGO_PROXY] Erro na busca: " . $e->getMessage());
    proxyFallbackImage();
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
 * Retorna caminho do arquivo ou null
 */
function buscarLogoCascata($revendedor, $db) {
    $revendedorId = $revendedor['id_revendedor'];
    $isMaster = $revendedor['master'] === 'sim';
    $paiId = $revendedor['parent_id'];

    // ✅ PASSO 1: Tentar logo do próprio revendedor
    if (!empty($revendedor['logo_filename'])) {
        $logoFile = UPLOAD_DIR . $revendedor['logo_filename'];
        if (file_exists($logoFile) && filesize($logoFile) > 0) {
            return $logoFile; // Logo própria encontrada
        }
    }

    // ✅ PASSO 2: Se for SUB e não tem logo → buscar do PAI
    if (!$isMaster && $paiId) {
        try {
            // Buscar info do pai
            $stmt = $db->prepare("
                SELECT logo_filename
                FROM revendedores
                WHERE id_revendedor = ? AND ativo = 1
            ");
            $stmt->execute([$paiId]);
            $pai = $stmt->fetch();

            if ($pai && !empty($pai['logo_filename'])) {
                $logoPaiFile = UPLOAD_DIR . $pai['logo_filename'];
                if (file_exists($logoPaiFile) && filesize($logoPaiFile) > 0) {
                    return $logoPaiFile; // Logo do pai encontrada
                }
            }
        } catch (Exception $e) {
            error_log("NomaTV [LOGO_PROXY] Erro ao buscar pai: " . $e->getMessage());
        }
    }

    // ✅ PASSO 3: Fallback final → null (será tratado pela proxyFallbackImage)
    return null;
}

/**
 * =================================================================
 * FUNÇÕES DE RETORNO DE IMAGEM
 * =================================================================
 */

/**
 * Retorna imagem do arquivo local
 */
function returnImage($filePath) {
    // Determinar tipo MIME baseado na extensão
    $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    $mimeTypes = [
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'webp' => 'image/webp'
    ];

    $mimeType = $mimeTypes[$extension] ?? 'image/png';

    // Headers para imagem
    header("Content-Type: $mimeType");
    header('Cache-Control: public, max-age=3600');

    // Ler e retornar arquivo binário
    readfile($filePath);
    exit();
}

/**
 * Proxy para imagem de fallback externa
 */
function proxyFallbackImage() {
    // Tentar fazer proxy para a imagem externa
    $context = stream_context_create([
        'http' => [
            'timeout' => 5,
            'user_agent' => 'NomaTV-LogoProxy/1.0'
        ]
    ]);

    $imageData = @file_get_contents(FALLBACK_URL, false, $context);

    if ($imageData !== false) {
        // Determinar tipo MIME da resposta
        $contentType = 'image/png'; // fallback
        if (isset($http_response_header)) {
            foreach ($http_response_header as $header) {
                if (stripos($header, 'Content-Type:') === 0) {
                    $contentType = trim(str_replace('Content-Type:', '', $header));
                    break;
                }
            }
        }

        header("Content-Type: $contentType");
        header('Cache-Control: public, max-age=3600');
        echo $imageData;
    } else {
        // Fallback final - imagem 1x1 pixel transparente
        header('Content-Type: image/png');
        header('Cache-Control: public, max-age=3600');
        echo base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==');
    }

    exit();
}
