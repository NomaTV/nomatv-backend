<?php
$db = new PDO('sqlite:api/db.db');
$result = $db->query('SELECT COUNT(*) as total FROM provedores');
$row = $result->fetch(PDO::FETCH_ASSOC);
echo 'Total provedores: ' . $row['total'] . "\n";

$result = $db->query('PRAGMA table_info(provedores)');
$columns = $result->fetchAll(PDO::FETCH_ASSOC);
echo 'Colunas presentes: ';
foreach ($columns as $col) {
    echo $col['name'] . ', ';
}
echo "\n";

// Testar query específica
try {
    $stmt = $db->query('SELECT p.id_provedor, p.nome, p.dns, p.tipo, p.usuario, p.senha FROM provedores p LIMIT 1');
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo 'Query testada com sucesso\n';
} catch (Exception $e) {
    echo 'Erro na query: ' . $e->getMessage() . "\n";
}
?>