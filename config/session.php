<?php
/**
 * =================================================================
 * AUTENTICAÇÃO SIMPLIFICADA - SEM COOKIES
 * =================================================================
 *
 * Sistema mais simples: usa Authorization header com token
 * Não precisa de arquivos de sessão
 *
 * =================================================================
 */

// Função para gerar token simples
function gerarToken($userId, $username) {
    return base64_encode($userId . ':' . $username . ':' . time());
}

// Função para validar token
function validarToken($token) {
    if (!$token) return false;

    $decoded = base64_decode($token);
    $parts = explode(':', $decoded);

    if (count($parts) !== 3) return false;

    list($userId, $username, $timestamp) = $parts;

    // Token válido por 24 horas
    if (time() - $timestamp > 86400) return false;

    return ['id' => $userId, 'usuario' => $username];
}

// Função para verificar autenticação via header
function verificarAutenticacao() {
    // Tentar getallheaders() primeiro (para servidor web)
    if (function_exists('getallheaders')) {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    } else {
        // Fallback para $_SERVER (para linha de comando ou outros contextos)
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['Authorization'] ?? '';
    }

    if (empty($authHeader)) return false;

    // Remove "Bearer " se existir
    $token = str_replace('Bearer ', '', $authHeader);

    return validarToken($token);
}

// Função para resposta não autenticado
function respostaNaoAutenticado() {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Não autenticado']);
    exit();
}
