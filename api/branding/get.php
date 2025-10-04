<?php
/**
 * BRANDING/GET.PHP - Consultar Branding NomaTV v4.5
 *
 * FUNÇÃO: Retornar informações de branding do revendedor
 *
 * LÓGICA:
 * 1. Validar sessão do revendedor
 * 2. Buscar dados completos do revendedor (tipo, pai, logo)
 * 3. Determinar qual logo está sendo usado (próprio/pai/nomaapp)
 * 4. Retornar JSON detalhado conforme MD
 *
 * CHAMADO POR: api.js (getBrandingInfo)
 *
 * LOCALIZAÇÃO: /api/branding/get.php
 */

// Headers CORS
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Incluir helpers
require_once __DIR__ . '/../helpers/auth_helper.php';

// ✅ VALIDAÇÃO DE AUTENTICAÇÃO
$authResult = validateSession();
if (!$authResult['success']) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => $authResult['message']
    ]);
    exit();
}

$revendedorId = $authResult['revendedor_id'];

// ✅ VALIDAÇÃO DE REQUEST
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['action']) || $_POST['action'] !== 'get_info') {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Requisição inválida. Use POST com action=get_info'
    ]);
    exit();
}

// ✅ CONEXÃO COM BANCO DE DADOS
try {
    require_once __DIR__ . '/../config/database_sqlite.php';
    $db = getDatabaseConnection();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro de conexão com banco de dados'
    ]);
    exit();
}

// ✅ BUSCAR DADOS COMPLETOS DO REVENDEDOR
try {
    $stmt = $db->prepare("
        SELECT id_revendedor, master, parent_id, logo_filename, ativo
        FROM revendedores
        WHERE id_revendedor = ? AND ativo = 1
    ");
    $stmt->execute([$revendedorId]);
    $revendedor = $stmt->fetch();

    if (!$revendedor) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Revendedor não encontrado ou inativo'
        ]);
        exit();
    }

    // ✅ DETERMINAR QUAL LOGO ESTÁ SENDO USADO
    $usandoLogoDe = 'nomaapp'; // fallback padrão
    $logoUrl = '/logos/nomaapp.png'; // fallback
    $temLogoProprio = !empty($revendedor['logo_filename']);

    if ($temLogoProprio) {
        // Tem logo próprio
        $usandoLogoDe = 'proprio';
        $logoUrl = "/api/logo_proxy.php?r={$revendedorId}";
    } elseif ($revendedor['master'] === 'nao' && $revendedor['parent_id']) {
        // É sub-revendedor, verificar se pai tem logo
        $stmtPai = $db->prepare("
            SELECT logo_filename
            FROM revendedores
            WHERE id_revendedor = ? AND ativo = 1
        ");
        $stmtPai->execute([$revendedor['parent_id']]);
        $pai = $stmtPai->fetch();

        if ($pai && !empty($pai['logo_filename'])) {
            $usandoLogoDe = 'pai';
            $logoUrl = "/api/logo_proxy.php?r={$revendedor['parent_id']}";
        }
    }

    // ✅ DETERMINAR SE PODE FAZER UPLOAD
    $podeFazerUpload = ($revendedor['master'] !== 'admin');

    // Retornar dados detalhados conforme MD
    echo json_encode([
        'success' => true,
        'data' => [
            'revendedor_id' => $revendedor['id_revendedor'],
            'tipo' => $revendedor['master'],
            'tem_logo' => $temLogoProprio,
            'logo_url' => $logoUrl,
            'logo_filename' => $revendedor['logo_filename'],
            'usando_logo_de' => $usandoLogoDe,
            'revendedor_pai_id' => $revendedor['parent_id'],
            'pode_fazer_upload' => $podeFazerUpload
        ]
    ]);

} catch (Exception $e) {
    error_log("NomaTV [BRANDING GET] Erro: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro interno do servidor'
    ]);
}

$db = null;
?>