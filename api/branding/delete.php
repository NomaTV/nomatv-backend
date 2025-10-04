<?php
/**
 * BRANDING/DELETE.PHP - Deletar Logo NomaTV v4.5
 *
 * FUNÇÃO: Remover logo personalizado do revendedor
 *
 * LÓGICA:
 * 1. Validar sessão e permissões
 * 2. Deletar arquivo físico
 * 3. Limpar logo_filename no banco
 * 4. Retornar status
 *
 * CHAMADO POR: api.js (deleteBrandingLogo)
 *
 * LOCALIZAÇÃO: /api/branding/delete.php
 */

// Headers CORS
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: DELETE, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Incluir helpers
require_once __DIR__ . '/../helpers/auth_helper.php';

// ✅ CONFIGURAÇÕES
define('UPLOAD_DIR', __DIR__ . '/../uploads/logos/');

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

// ✅ VALIDAÇÃO DE MÉTODO
if (!in_array($_SERVER['REQUEST_METHOD'], ['DELETE', 'POST'])) {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Método não permitido'
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

// ✅ PROCESSAR DELETE
try {
    // Buscar logo atual
    $stmt = $db->prepare("
        SELECT logo_filename
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

    $logoFilename = $revendedor['logo_filename'];

    // Se não tem logo, já está limpo
    if (empty($logoFilename)) {
        echo json_encode([
            'success' => true,
            'message' => 'Nenhum logo para deletar'
        ]);
        exit();
    }

    // Deletar arquivo físico
    $filepath = UPLOAD_DIR . $logoFilename;
    if (file_exists($filepath)) {
        @unlink($filepath);
    }

    // Limpar no banco
    $stmt = $db->prepare("
        UPDATE revendedores
        SET logo_filename = NULL
        WHERE id_revendedor = ? AND ativo = 1
    ");
    $stmt->execute([$revendedorId]);

    // Sucesso
    echo json_encode([
        'success' => true,
        'message' => 'Logo removida com sucesso'
    ]);

} catch (Exception $e) {
    error_log("NomaTV [BRANDING DELETE] Erro: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro interno do servidor'
    ]);
}

$db = null;
?>