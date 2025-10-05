<?php
/**
 * =================================================================
 * ENDPOINT DE SEGURANÇA - NomaTV API v4.3
 * =================================================================
 * 
 * ARQUIVO: /api/seguranca.php
 * VERSÃO: 4.3 - Aprimorado com Verificador de Força de Senha
 * 
 * RESPONSABILIDADES:
 * ✅ Alteração segura de senhas para o administrador.
 * ✅ ✨ NOVO: Verificação de força de senha em tempo real.
 * ✅ ✨ NOVO: Fornecimento de informações básicas do servidor.
 * ✅ Validação robusta e logs de auditoria.
 * 
 * =================================================================
 */

// Configuração de erro reporting
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Headers de segurança e CORS
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Tratar requisições OPTIONS (CORS preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Dependências obrigatórias
require_once __DIR__ . '/config/database_sqlite.php';

/**
 * Função auxiliar para padronizar respostas JSON.
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

/**
 * Função auxiliar para registrar logs de auditoria.
 */
function logAction(PDO $db, string $userId, string $action, string $details): void
{
    try {
        $stmt = $db->prepare("
            INSERT INTO auditoria (id_revendedor, acao, detalhes, ip, user_agent, timestamp) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $userId,
            $action,
            $details,
            $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
            date('Y-m-d H:i:s')
        ]);
    } catch (Exception $e) {
        error_log("NomaTV v4.3 [SEGURANCA] Erro ao registrar log: " . $e->getMessage());
    }
}

// ✅ AUTENTICAÇÃO PADRÃO (SUBSTITUI auth_helper.php)
session_start();
if (empty($_SESSION['id_revendedor']) || empty($_SESSION['master'])) {
    http_response_code(401);
    exit('{"success":false,"message":"Usuário não autenticado"}');
}
$loggedInRevendedorId = $_SESSION['id_revendedor'];
$loggedInUserType = $_SESSION['master'];

/**
 * Roteamento principal
 */
$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true) ?? [];

try {
    if ($method === 'GET') {
        getServerInfo($db, $loggedInRevendedorId);
    } elseif ($method === 'POST') {
        $action = $input['action'] ?? '';
        switch ($action) {
            case 'alterar_senha_admin':
                alterarSenhaAdmin($db, $loggedInRevendedorId, $input);
                break;
            case 'verificar_forca_senha':
                verificarForcaSenha($input);
                break;
            default:
                http_response_code(400);
                standardResponse(false, null, 'Ação inválida.');
                break;
        }
    } else {
        http_response_code(405);
        standardResponse(false, null, 'Método não permitido.');
    }
} catch (Exception $e) {
    http_response_code(500);
    error_log("NomaTV v4.3 [SEGURANCA] Erro geral: " . $e->getMessage());
    standardResponse(false, null, 'Erro interno do servidor.');
}

/**
 * Altera a senha do administrador.
 */
function alterarSenhaAdmin(PDO $db, string $userId, array $input): void {
    if (empty($input['senha_atual']) || empty($input['nova_senha']) || empty($input['confirmar_senha'])) {
        http_response_code(400);
        standardResponse(false, null, 'Todos os campos de senha são obrigatórios.');
        return;
    }
    if ($input['nova_senha'] !== $input['confirmar_senha']) {
        http_response_code(400);
        standardResponse(false, null, 'A nova senha e a confirmação não correspondem.');
        return;
    }

    try {
        $stmt = $db->prepare("SELECT senha FROM revendedores WHERE id_revendedor = ? AND master = 'admin'");
        $stmt->execute([$userId]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$admin || !password_verify($input['senha_atual'], $admin['senha'])) {
            http_response_code(401);
            standardResponse(false, null, 'A senha atual está incorreta.');
            return;
        }

        $validacao = validarForcaSenhaCompleta($input['nova_senha']);
        if ($validacao['score'] < 40) { // Exige no mínimo 'Média'
            http_response_code(400);
            standardResponse(false, null, 'A nova senha é muito fraca. ' . implode(', ', $validacao['sugestoes']));
            return;
        }

        $novaSenhaHash = password_hash($input['nova_senha'], PASSWORD_DEFAULT);
        $updateStmt = $db->prepare("UPDATE revendedores SET senha = ? WHERE id_revendedor = ?");
        $updateStmt->execute([$novaSenhaHash, $userId]);
        
        logAction($db, $userId, 'alterar_senha_admin', 'Senha de administrador alterada com sucesso.');
        standardResponse(true, null, 'Senha alterada com sucesso!');

    } catch (Exception $e) {
        http_response_code(500);
        error_log("NomaTV v4.3 [SEGURANCA] Erro em alterarSenhaAdmin: " . $e->getMessage());
        standardResponse(false, null, 'Erro ao alterar a senha.');
    }
}

/**
 * Verifica a força de uma senha fornecida.
 */
function verificarForcaSenha(array $input): void {
    $senha = $input['senha'] ?? '';
    $resultado = validarForcaSenhaCompleta($senha);
    standardResponse(true, $resultado);
}

/**
 * Retorna informações básicas do servidor.
 */
function getServerInfo(PDO $db, string $userId): void {
    $serverIp = $_SERVER['SERVER_ADDR'] ?? gethostbyname($_SERVER['SERVER_NAME']);
    $info = [
        'server_ip' => $serverIp,
        'php_version' => phpversion(),
        'sqlite_version' => $db->query('select sqlite_version()')->fetchColumn(),
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'N/A'
    ];
    standardResponse(true, $info);
}

/**
 * Função auxiliar para validar a força da senha.
 */
function validarForcaSenhaCompleta(string $senha): array {
    $score = 0;
    $sugestoes = [];
    
    // Comprimento
    if (strlen($senha) >= 8) $score += 25; else $sugestoes[] = 'Use pelo menos 8 caracteres.';
    // Maiúscula
    if (preg_match('/[A-Z]/', $senha)) $score += 25; else $sugestoes[] = 'Adicione uma letra maiúscula.';
    // Minúscula
    if (preg_match('/[a-z]/', $senha)) $score += 15; else $sugestoes[] = 'Adicione uma letra minúscula.';
    // Número
    if (preg_match('/[0-9]/', $senha)) $score += 20; else $sugestoes[] = 'Adicione um número.';
    // Símbolo
    if (preg_match('/[^A-Za-z0-9]/', $senha)) $score += 15; else $sugestoes[] = 'Adicione um símbolo (ex: !@#$).';

    $score = min(100, $score);
    $nivel = 'Muito Fraca';
    if ($score >= 80) $nivel = 'Forte';
    elseif ($score >= 60) $nivel = 'Boa';
    elseif ($score >= 40) $nivel = 'Média';
    elseif ($score >= 25) $nivel = 'Fraca';

    return ['score' => $score, 'nivel' => $nivel, 'sugestoes' => $sugestoes];
}
?>