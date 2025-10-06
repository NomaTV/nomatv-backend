<?php
require_once 'api/config/database_sqlite.php';
try {
    $db = getDatabaseConnection();
    $stmt = $db->query('SELECT id_revendedor, usuario, nome FROM revendedores LIMIT 5');
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo 'Revendedores encontrados: ' . count($result) . PHP_EOL;
    foreach($result as $r) {
        echo $r['id_revendedor'] . ': ' . $r['usuario'] . ' (' . $r['nome'] . ')' . PHP_EOL;
    }
} catch(Exception $e) {
    echo 'Erro: ' . $e->getMessage() . PHP_EOL;
}
?>