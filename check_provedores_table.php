try {
    $pdo = new PDO('sqlite:api/db.db');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $result = $pdo->query('SELECT name FROM sqlite_master WHERE type="table" AND name="provedores"');
    if ($result->fetch()) {
        echo "Tabela provedores existe\n";

        $columns = $pdo->query('PRAGMA table_info(provedores)')->fetchAll(PDO::FETCH_ASSOC);
        echo "Colunas da tabela provedores:\n";
        foreach ($columns as $col) {
            echo "- {$col['name']}: {$col['type']}\n";
        }
    } else {
        echo "Tabela provedores NÃO existe\n";
    }
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}