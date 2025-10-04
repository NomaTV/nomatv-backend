<?php
/**
 * =================================================================
 * ENDPOINT DE AUTENTICAÇÃO - NomaTV API v4.5
 * =================================================================
 *
 * ARQUIVO: /api/auth.php
 * VERSÃO: 4.5 - Autenticação por Sessão
 *
 * RESPONSABILIDADES:
 * ✅ Gerenciar o fluxo de login e logout do sistema.
 * ✅ Validar credenciais (usuário e senha) contra a tabela `revendedores`.
 * ✅ Criar e destruir sessões seguras no servidor.
 *
 * =================================================================
 */

ini_set('display_errors', 0);
error_reporting(E_ALL);

// Inicia a sessão. Isso cria ou retoma a sessão do usuário.
session_start();

// Headers de segurança e CORS
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Inclusão do conector de banco de dados e do helper de resposta padrão.
// O conector está na pasta 'config' e o helper está na pasta 'helpers'.
require_once __DIR__ . '/config/database_sqlite.php';

/**
 * Resposta padronizada JSON
 */
function standardResponse(bool $success, $data = null, $message = null, $extraData = null): void
{
    // Garante que a resposta seja formatada em JSON e o script seja encerrado.
    ob_end_clean();
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'message' => $message,
        'extraData' => $extraData
    ]);
    exit();
}

// =============================================
// 🎯 ROTEAMENTO PRINCIPAL
// =============================================
$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents(getenv('INPUT_FILE')), true) ?? [];
$action = $input['action'] ?? '';

try {
    switch ($method) {
        case 'POST':
            if ($action === 'login') {
                loginUser($db, $input['usuario'] ?? '', $input['senha'] ?? '');
            } elseif ($action === 'logout') {
                logoutUser();
            } else {
                http_response_code(400);
                standardResponse(false, null, 'Ação inválida.');
            }
            break;
        default:
            http_response_code(405);
            standardResponse(false, null, 'Método não permitido.');
            break;
    }
} catch (Exception $e) {
    error_log("NomaTV v4.5 [AUTH] Erro: " . $e->getMessage());
    http_response_code(500);
    standardResponse(false, null, 'Erro interno do servidor.');
}

/**
 * ✅ LOGIN ALINHADO COM DOCUMENTAÇÃO v4.5
 * Baseado na coluna 'master' para redirecionamento e criação de sessão
 */
function loginUser(PDO $db, string $username, string $password): void
{
    try {
        $stmt = $db->prepare("
            SELECT id_revendedor, usuario, senha, nome, master, ativo
            FROM revendedores
            WHERE usuario = ?
        ");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Validação de credenciais
        if (!$user || !password_verify($password, $user['senha'])) {
            http_response_code(401);
            standardResponse(false, null, 'Usuário ou senha inválidos.');
            return;
        }

        // Validação de status ativo
        if (!$user['ativo']) {
            http_response_code(403);
            standardResponse(false, null, 'Painel inativo. Contate o administrador.');
            return;
        }

        // ✅ CRIAÇÃO DA SESSÃO
        $_SESSION['id_revendedor'] = $user['id_revendedor'];
        $_SESSION['master'] = $user['master'];
        $_SESSION['usuario'] = $user['usuario'];

        // Resposta para o frontend
        standardResponse(true, [
            'id' => $user['id_revendedor'],
            'usuario' => $user['usuario'],
            'nome' => $user['nome'],
            'master' => $user['master'],
            'tipo' => determinarTipoUsuario($user['master']),
            'redirect' => determinarRedirect($user['master'])
        ], 'Login realizado com sucesso!');

    } catch (Exception $e) {
        error_log("NomaTV v4.5 [AUTH] Erro em login: " . $e->getMessage());
        http_response_code(500);
        standardResponse(false, null, 'Erro interno no login.');
    }
}

/**
 * ✅ DETERMINA TIPO DE USUÁRIO baseado na coluna 'master'
 */
function determinarTipoUsuario(string $master): string
{
    return match($master) {
        'admin' => 'admin',           // → admin.html
        'sim' => 'revendedor',        // → revendedor.html
        'nao' => 'sub_revendedor',    // → sub_revendedor.html
        default => throw new Exception('Tipo de usuário inválido.')
    };
}

function determinarRedirect(string $master): string
{
    return match($master) {
        'admin' => '/admin.html',
        'sim' => '/revendedor.html',
        'nao' => '/sub_revendedor.html',
        default => '/index.html'
    };
}

/**
 * Logout - Destrói a sessão
 */
function logoutUser(): void
{
    // Destrói todos os dados da sessão
    $_SESSION = [];
    session_destroy();
    standardResponse(true, null, 'Logout realizado com sucesso.');
}
