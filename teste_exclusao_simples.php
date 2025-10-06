<?php
// Teste de exclusão do provedor criado

echo "=== TESTE DE EXCLUSÃO ===\n";

// Simular autenticação
$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer MTIzNDU2Nzg6YWRtaW46MTc1OTcxMjI3OA==';

// Ver provedores antes
$db = new PDO('sqlite:db.db');
$stmt = $db->query('SELECT COUNT(*) as total FROM provedores');
$antes = $stmt->fetch()['total'];
echo "Provedores antes: $antes\n";

// Pegar o último provedor criado
$stmt = $db->query('SELECT id_provedor, nome FROM provedores ORDER BY id_provedor DESC LIMIT 1');
$provedor = $stmt->fetch();

if ($provedor) {
    $id = $provedor['id_provedor'];
    echo "Excluindo provedor ID $id: {$provedor['nome']}\n";

    // Simular requisição DELETE
    $_SERVER['REQUEST_METHOD'] = 'DELETE';
    $_GET['id'] = $id;

    require_once __DIR__ . '/api/provedores.php';

} else {
    echo "Nenhum provedor encontrado para excluir.\n";
}
?>