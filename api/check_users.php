<?php
require_once 'api/config/database_sqlite.php';
try {
    $db = getDatabaseConnection();
    $stmt = $db->query('SELECT * FROM revendedores LIMIT 5');
    $users = $stmt->fetchAll();
    echo 'Usuários encontrados: ' . count($users) . PHP_EOL;
    foreach ($users as $user) {
        echo 'ID: ' . $user['id_revendedor'] . ', Usuario: ' . $user['usuario'] . ', Senha: ' . substr($user['senha'], 0, 10) . '...' . PHP_EOL;
    }
} catch (Exception $e) {
    echo 'Erro: ' . $e->getMessage() . PHP_EOL;
}
?>