<?php
// Script de teste para API de provedores
echo "=== TESTE DA API DE PROVEDORES ===\n\n";

// Simular login
echo "1. Fazendo login...\n";
$loginData = [
    'action' => 'login',
    'usuario' => 'admin',
    'senha' => 'admin123'
];

// Configurar sessão
$sessionPath = __DIR__ . '/sessions';
if (!file_exists($sessionPath)) {
    mkdir($sessionPath, 0777, true);
}
ini_set('session.save_path', $sessionPath);
ini_set('session.use_cookies', 0);
ini_set('session.use_only_cookies', 0);

session_start();
$_SESSION['revendedor_id'] = 12345678;
$_SESSION['master'] = 'admin';

echo "✓ Sessão criada para admin\n";

// Testar listagem de provedores
echo "\n2. Testando listagem de provedores...\n";

$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET = ['page' => 1, 'limit' => 10];

require_once 'api/provedores.php';

echo "\n=== FIM DO TESTE ===\n";
?>