<?php
require_once 'api/config/database_sqlite.php';

try {
    $pdo = getDatabaseConnection();
    $stmt = $pdo->query('SELECT * FROM provedores ORDER BY id_provedor DESC LIMIT 5');

    echo "Últimos 5 provedores no banco:\n";
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo 'ID: ' . $row['id_provedor'] . ' - Nome: ' . $row['nome'] . ' - DNS: ' . $row['dns'] . ' - Ativo: ' . $row['ativo'] . "\n";
    }
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
?>