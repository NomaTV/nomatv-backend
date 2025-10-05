<?php
require_once 'api/config/database_sqlite.php';
try {
    $db = getDatabaseConnection();
    $sql = file_get_contents('init.sql');
    $db->exec($sql);
    echo 'Tabelas criadas com sucesso!' . PHP_EOL;

    // Verificar tabelas criadas
    $stmt = $db->query('SELECT name FROM sqlite_master WHERE type="table"');
    $tables = $stmt->fetchAll();
    echo 'Tabelas encontradas:' . PHP_EOL;
    foreach ($tables as $table) {
        echo '- ' . $table['name'] . PHP_EOL;
    }
} catch (Exception $e) {
    echo 'Erro: ' . $e->getMessage() . PHP_EOL;
}
?>