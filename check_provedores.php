<?php
$db = new PDO('sqlite:db.db');
$stmt = $db->query('SELECT id_provedor, nome FROM provedores');
$provedores = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo 'Provedores no banco: ' . count($provedores) . PHP_EOL;
foreach ($provedores as $p) {
    echo $p['id_provedor'] . ' - ' . $p['nome'] . PHP_EOL;
}
?>