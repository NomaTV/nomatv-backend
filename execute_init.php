<?php
$db = new PDO('sqlite:db.db');
$sql = file_get_contents('config/init.sql');
$statements = array_filter(array_map('trim', explode(';', $sql)));
foreach ($statements as $statement) {
    if (!empty($statement) && !preg_match('/^--/', $statement)) {
        try {
            $db->exec($statement);
            echo 'Executado: ' . substr($statement, 0, 50) . "...\n";
        } catch (Exception $e) {
            echo 'Erro: ' . $e->getMessage() . "\n";
        }
    }
}
echo 'Init.sql executado\n';
?>