<?php
try {
    $db = new PDO('sqlite:db.db');
    $result = $db->query('SELECT p.id_provedor, p.nome, p.dns, p.tipo FROM provedores p LIMIT 1');
    $row = $result->fetch(PDO::FETCH_ASSOC);
    echo "Query funcionou: ";
    var_dump($row);
} catch (Exception $e) {
    echo 'Erro: ' . $e->getMessage() . "\n";
}
?>