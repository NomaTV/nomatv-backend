<?php
/**
 * =================================================================
 * SESSÃO COMUM - NomaTV API v4.5
 * =================================================================
 * 
 * Arquivo para inicializar sessão de forma consistente em todos os endpoints
 * Deve ser incluído no início de cada arquivo PHP que precisa de autenticação
 * 
 * =================================================================
 */

// ✅ CONFIGURAR SESSÕES PHP PARA FUNCIONAR COM SPAWN
$sessionPath = __DIR__ . '/sessions';
if (!file_exists($sessionPath)) {
    mkdir($sessionPath, 0777, true);
}
ini_set('session.save_path', $sessionPath);
ini_set('session.use_cookies', 0);
ini_set('session.use_only_cookies', 0);

// 🔍 LOG de debug
error_log("=== Iniciando sessão em " . basename($_SERVER['PHP_SELF']) . " ===");
error_log("HTTP_COOKIE: " . ($_SERVER['HTTP_COOKIE'] ?? '(vazio)'));

// Se tiver cookie PHPSESSID, usar ele
$sessionIdFromCookie = null;
if (!empty($_SERVER['HTTP_COOKIE'])) {
    preg_match('/PHPSESSID=([a-zA-Z0-9]+)/', $_SERVER['HTTP_COOKIE'], $matches);
    if (!empty($matches[1])) {
        $sessionIdFromCookie = $matches[1];
        session_id($sessionIdFromCookie);
        error_log("Session ID extraído do cookie: " . $sessionIdFromCookie);
    } else {
        error_log("Cookie presente mas PHPSESSID não encontrado");
    }
} else {
    error_log("Nenhum cookie HTTP_COOKIE presente");
}

// Iniciar sessão
session_start();
error_log("Session ID após session_start: " . session_id());
error_log("Dados da sessão: " . json_encode($_SESSION));

/**
 * Verifica se o usuário está autenticado
 * @return array|false Retorna os dados do usuário ou false
 */
function verificarAutenticacao() {
    if (empty($_SESSION['revendedor_id'])) {
        error_log("Sessão inválida - revendedor_id não encontrado");
        return false;
    }
    
    return [
        'id' => $_SESSION['revendedor_id'],
        'master' => $_SESSION['master'] ?? 'nao',
        'usuario' => $_SESSION['usuario'] ?? 'unknown',
        'tipo' => $_SESSION['tipo'] ?? 'sub_revendedor'
    ];
}

/**
 * Retorna resposta de não autenticado e encerra script
 */
function respostaNaoAutenticado() {
    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'message' => 'Usuário não autenticado - sessão inválida'
    ]);
    exit();
}
