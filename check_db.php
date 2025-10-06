<?php
try {
    $db = new PDO('sqlite:db.db');
    $result = $db->query('SELECT name FROM sqlite_master WHERE type="table"');
    echo "Tabelas encontradas:\n";
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        echo '- ' . $row['name'] . "\n";
    }

    // Verificar estrutura da tabela provedores
    echo "\nEstrutura da tabela provedores:\n";
    $result2 = $db->query('PRAGMA table_info(provedores)');
    while ($row = $result2->fetch(PDO::FETCH_ASSOC)) {
        echo "  {$row['name']} ({$row['type']})\n";
    }

} catch (Exception $e) {
    echo 'Erro: ' . $e->getMessage() . "\n";
}
?>