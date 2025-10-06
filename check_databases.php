<?php
$possiblePaths = [
    'api/db.db',
    'api/nomatv.db',
    'db.db'
];

foreach ($possiblePaths as $path) {
    if (file_exists($path)) {
        echo "Arquivo encontrado: $path\n";
        try {
            $db = new PDO("sqlite:$path");
            $result = $db->query('SELECT COUNT(*) as total FROM provedores');
            $row = $result->fetch(PDO::FETCH_ASSOC);
            echo "Registros em provedores: " . $row['total'] . "\n";

            $result = $db->query('PRAGMA table_info(provedores)');
            $columns = $result->fetchAll(PDO::FETCH_ASSOC);
            echo "Colunas: ";
            foreach ($columns as $col) {
                echo $col['name'] . ', ';
            }
            echo "\n\n";
        } catch (Exception $e) {
            echo "Erro ao acessar $path: " . $e->getMessage() . "\n\n";
        }
    } else {
        echo "Arquivo não encontrado: $path\n\n";
    }
}
?>