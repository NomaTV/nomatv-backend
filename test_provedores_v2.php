<?php
// Script de teste para API de provedores - Versão corrigida
echo "=== TESTE DA API DE PROVEDORES ===\n\n";

// Configurar sessão ANTES de qualquer output
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

// Testar conexão com banco
echo "\n1. Testando conexão com banco...\n";
try {
    require_once 'api/config/database_sqlite.php';
    echo "✓ Conexão com banco estabelecida\n";
} catch (Exception $e) {
    echo "✗ Erro na conexão: " . $e->getMessage() . "\n";
    exit(1);
}

// Testar listagem de provedores
echo "\n2. Testando listagem de provedores...\n";

$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET = ['page' => 1, 'limit' => 10];

ob_start(); // Capturar output
require_once 'api/provedores.php';
$output = ob_get_clean();

echo "Resposta da API:\n";
echo $output . "\n";

echo "\n=== FIM DO TESTE ===\n";
?>