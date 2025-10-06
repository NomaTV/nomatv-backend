<?php
// Teste de criação via API simulando frontend

echo "=== TESTE DE CRIAÇÃO VIA API ===\n";

// Simular variáveis que o servidor Node.js define
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['CONTENT_TYPE'] = 'application/json';
$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer MTIzNDU2Nzg6YWRtaW46MTc1OTcxMjI3OA==';

// Simular como o servidor Node.js envia os dados
$inputData = [
    'action' => 'criar',
    'nome' => 'Provedor via API ' . time(),
    'dns' => 'api' . time() . '.com',
    'tipo' => 'xtream',
    'usuario' => 'admin',
    'senha' => '123456'
];

$_SERVER['REQUEST_BODY'] = json_encode($inputData);

echo "Dados enviados: " . $_SERVER['REQUEST_BODY'] . "\n\n";

// Chamar a função diretamente
require_once 'api/provedores.php';

// Verificar se foi criado
$db = new PDO('sqlite:db.db');
$stmt = $db->query('SELECT COUNT(*) as total FROM provedores');
$total = $stmt->fetch()['total'];
echo "Total de provedores após: $total\n";

if ($total > 0) {
    $stmt = $db->query('SELECT * FROM provedores ORDER BY id_provedor DESC LIMIT 1');
    $provedor = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Último provedor: {$provedor['nome']} (ID: {$provedor['id_provedor']})\n";
}
?>