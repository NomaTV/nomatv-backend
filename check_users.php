<?php
$db = new PDO('sqlite:db.db');
$result = $db->query('SELECT usuario, senha FROM revendedores');
$users = $result->fetchAll(PDO::FETCH_ASSOC);
echo 'Usuários no banco:\n';
foreach ($users as $user) {
    echo 'Usuário: ' . $user['usuario'] . ', Senha hash: ' . $user['senha'] . "\n";
}
?>