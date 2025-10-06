<?php
// Teste completo: criar provedor e depois excluir

echo "=== TESTE COMPLETO: CRIAR + EXCLUIR PROVEDOR ===\n\n";

// Simular autenticação
$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer MTIzNDU2Nzg6YWRtaW46MTc1OTcxMjI3OA==';

// 1. Verificar provedores existentes
echo "1. PROVEDORES ANTES:\n";
$db = new PDO('sqlite:db.db');
$stmt = $db->query('SELECT COUNT(*) as total FROM provedores');
$antes = $stmt->fetch()['total'];
echo "Total: $antes\n\n";

// 2. Criar provedor
echo "2. CRIANDO PROVEDOR...\n";
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = [
    'action' => 'criar',
    'nome' => 'Provedor Teste ' . time(),
    'dns' => 'teste' . time() . '.com',
    'tipo' => 'xtream',
    'usuario' => 'admin',
    'senha' => '123456'
];

// Simular o processamento do provedores.php
require_once __DIR__ . '/api/provedores.php';

echo "\n3. PROVEDORES APÓS CRIAÇÃO:\n";
$db = new PDO('sqlite:db.db');
$stmt = $db->query('SELECT COUNT(*) as total FROM provedores');
$depoisCriacao = $stmt->fetch()['total'];
echo "Total: $depoisCriacao\n";

if ($depoisCriacao > $antes) {
    echo "✅ PROVEDOR CRIADO COM SUCESSO!\n\n";

    // 4. Pegar ID do provedor criado
    $stmt = $db->query('SELECT id_provedor FROM provedores ORDER BY id_provedor DESC LIMIT 1');
    $provedor = $stmt->fetch();
    $idParaExcluir = $provedor['id_provedor'];

    echo "4. EXCLUINDO PROVEDOR ID: $idParaExcluir\n";
    $_SERVER['REQUEST_METHOD'] = 'DELETE';
    $_GET['id'] = $idParaExcluir;

    // Limpar POST para DELETE
    $_POST = [];

    // Simular exclusão
    require_once __DIR__ . '/api/provedores.php';

    echo "\n5. PROVEDORES APÓS EXCLUSÃO:\n";
    $db = new PDO('sqlite:db.db');
    $stmt = $db->query('SELECT COUNT(*) as total FROM provedores');
    $depoisExclusao = $stmt->fetch()['total'];
    echo "Total: $depoisExclusao\n";

    if ($depoisExclusao < $depoisCriacao) {
        echo "✅ PROVEDOR EXCLUÍDO COM SUCESSO!\n";
        echo "🎉 TESTE COMPLETO PASSOU!\n";
    } else {
        echo "❌ FALHA: Provedor não foi excluído!\n";
    }

} else {
    echo "❌ FALHA: Provedor não foi criado!\n";
}
?>