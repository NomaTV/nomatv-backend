<?php
try {
    require_once __DIR__ . '/config/database_sqlite.php';
    $db = getDatabaseConnection();
    echo "Conexão OK\n";

    // Verificar tabelas
    $result = $db->query("SELECT name FROM sqlite_master WHERE type='table'");
    echo "Tabelas:\n";
    foreach($result as $row) {
        echo "- " . $row['name'] . "\n";
    }

    // Verificar se há usuários
    $stmt = $db->query("SELECT COUNT(*) as total FROM revendedores");
    $count = $stmt->fetch();
    echo "Total de revendedores: " . $count['total'] . "\n";

    // Listar usuários
    $stmt = $db->query("SELECT id, usuario, tipo FROM revendedores");
    echo "Usuários:\n";
    while($user = $stmt->fetch()) {
        echo "- ID: {$user['id']}, Usuario: {$user['usuario']}, Tipo: {$user['tipo']}\n";
    }

} catch(Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
?>