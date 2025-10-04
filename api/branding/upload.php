<?php
/**
 * BRANDING/UPLOAD.PHP - Upload de Logo NomaTV v4.5
 *
 * FUNÇÃO: Upload e armazenamento de logo personalizado
 *
 * LÓGICA:
 * 1. Validar sessão e permissões
 * 2. Validar arquivo (PNG, tamanho, dimensões)
 * 3. Salvar em uploads/logos/{id}.png
 * 4. Atualizar logo_filename no banco
 * 5. Retornar status
 *
 * CHAMADO POR: api.js (uploadBrandingLogo)
 *
 * LOCALIZAÇÃO: /api/branding/upload.php
 */

// Headers CORS
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Incluir helpers
require_once __DIR__ . '/../helpers/auth_helper.php';

// ✅ CONFIGURAÇÕES DE UPLOAD
define('UPLOAD_DIR', __DIR__ . '/../uploads/logos/');
define('MAX_FILE_SIZE', 150 * 1024); // 150KB conforme MD
define('ALLOWED_TYPES', ['image/png', 'image/jpeg', 'image/jpg', 'image/webp']);
define('ALLOWED_EXTENSIONS', ['png', 'jpg', 'jpeg', 'webp']);
define('RECOMMENDED_WIDTH', 300);
define('RECOMMENDED_HEIGHT', 100);

// Criar diretório se não existir
if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}

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

// ✅ VERIFICAR SE É ADMIN (NÃO PODE FAZER UPLOAD)
if ($authResult['tipo'] === 'admin') {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Administradores não podem fazer upload de logos'
    ]);
    exit();
}

// ✅ VALIDAÇÃO DE MÉTODO
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Método não permitido'
    ]);
    exit();
}

// ✅ VERIFICAR SE TEM ARQUIVO
if (!isset($_FILES['logo']) || $_FILES['logo']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Arquivo de logo não enviado ou com erro'
    ]);
    exit();
}

$file = $_FILES['logo'];

// ✅ VALIDAÇÕES DO ARQUIVO
// Tipo MIME
if (!in_array($file['type'], ALLOWED_TYPES)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Formato não permitido. Use PNG, JPG, JPEG ou WebP'
    ]);
    exit();
}

// Tamanho
if ($file['size'] > MAX_FILE_SIZE) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Arquivo muito grande (máximo 150KB)'
    ]);
    exit();
}

// Verificar se é imagem válida
$imageInfo = getimagesize($file['tmp_name']);
$validTypes = [IMAGETYPE_PNG, IMAGETYPE_JPEG, IMAGETYPE_WEBP];
if (!$imageInfo || !in_array($imageInfo[2], $validTypes)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Arquivo não é uma imagem válida (PNG, JPG, JPEG ou WebP)'
    ]);
    exit();
}

// Dimensões recomendadas (bloquear se maior)
$width = $imageInfo[0];
$height = $imageInfo[1];
if ($width > RECOMMENDED_WIDTH || $height > RECOMMENDED_HEIGHT) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => "Imagem muito grande. Dimensões recomendadas: {$RECOMMENDED_WIDTH}x{$RECOMMENDED_HEIGHT}px (atual: {$width}x{$height}px)"
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

// ✅ PROCESSAR UPLOAD
try {
    // Determinar extensão baseada no tipo MIME
    $extension = '';
    switch ($imageInfo[2]) {
        case IMAGETYPE_PNG:
            $extension = 'png';
            break;
        case IMAGETYPE_JPEG:
            $extension = 'jpg';
            break;
        case IMAGETYPE_WEBP:
            $extension = 'webp';
            break;
        default:
            $extension = 'png'; // fallback
    }

    // Nome do arquivo final
    $filename = "logo_{$revendedorId}.{$extension}";
    $filepath = UPLOAD_DIR . $filename;

    // Deletar logo anterior se existir
    $oldFiles = glob(UPLOAD_DIR . "logo_{$revendedorId}.*");
    foreach ($oldFiles as $oldFile) {
        @unlink($oldFile);
    }

    // Mover arquivo para destino
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Erro ao salvar arquivo'
        ]);
        exit();
    }

    // Atualizar banco de dados
    $stmt = $db->prepare("
        UPDATE revendedores
        SET logo_filename = ?
        WHERE id_revendedor = ? AND ativo = 1
    ");
    $stmt->execute([$filename, $revendedorId]);

    // Verificar se atualizou
    if ($stmt->rowCount() === 0) {
        // Tentar deletar arquivo se não conseguiu atualizar
        @unlink($filepath);
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Revendedor não encontrado ou inativo'
        ]);
        exit();
    }

    // Sucesso
    echo json_encode([
        'success' => true,
        'message' => 'Logo enviada com sucesso',
        'data' => [
            'filename' => $filename,
            'url' => "/api/logo_proxy.php?r={$revendedorId}"
        ]
    ]);

} catch (Exception $e) {
    error_log("NomaTV [BRANDING UPLOAD] Erro: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro interno do servidor'
    ]);
}

$db = null;
?>