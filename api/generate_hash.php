<?php
// Gerar hash correto para admin123
$password = 'admin123';
$hash = password_hash($password, PASSWORD_DEFAULT);
echo "Hash para senha '$password': " . $hash . PHP_EOL;

// Verificar se o hash funciona
$verify = password_verify($password, $hash);
echo "Verificação do hash: " . ($verify ? 'OK' : 'FALHA') . PHP_EOL;
?>