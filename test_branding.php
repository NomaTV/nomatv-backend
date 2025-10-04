<?php
/**
 * TEST_BRANDING.PHP - Debug do sistema de branding
 */

// Remover header JSON temporariamente para debug
header('Content-Type: text/plain; charset=utf-8');

echo "=== TESTE DO SISTEMA DE BRANDING ===\n\n";

// 1. Testar conexão com banco
echo "1. TESTANDO CONEXÃO COM BANCO...\n";
$possiblePaths = [
    __DIR__ . '/db.db',
    __DIR__ . '/nomatv.db'
];

$dbPath = null;
foreach ($possiblePaths as $path) {
    if (file_exists($path)) {
        $dbPath = $path;
        echo "   ✅ Banco encontrado: $path\n";
        break;
    }
}

if (!$dbPath) {
    die("   ❌ ERRO: Banco não encontrado!\n");
}

try {
    $db = new PDO("sqlite:$dbPath");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "   ✅ Conexão estabelecida\n\n";
} catch (Exception $e) {
    die("   ❌ ERRO: " . $e->getMessage() . "\n");
}

// 2. Verificar tabela revendedores
echo "2. VERIFICANDO TABELA REVENDEDORES...\n";
try {
    $stmt = $db->query("SELECT id_revendedor, nome, master, id_pai, ativo FROM revendedores WHERE ativo = 1");
    $revendedores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "   ✅ Encontrados: " . count($revendedores) . " revendedores ativos\n";
    foreach ($revendedores as $rev) {
        echo "      - ID: {$rev['id_revendedor']} | Nome: {$rev['nome']} | Master: {$rev['master']} | Pai: " . ($rev['id_pai'] ?? 'NULL') . "\n";
    }
    echo "\n";
} catch (Exception $e) {
    echo "   ❌ ERRO: " . $e->getMessage() . "\n\n";
}

// 3. Verificar pasta uploads/logos
echo "3. VERIFICANDO PASTA UPLOADS/LOGOS...\n";
$uploadDir = __DIR__ . '/uploads/logos/';
if (!is_dir($uploadDir)) {
    echo "   ⚠️  Pasta não existe. Criando...\n";
    mkdir($uploadDir, 0755, true);
    echo "   ✅ Pasta criada\n\n";
} else {
    echo "   ✅ Pasta existe\n";
    $files = glob($uploadDir . '*.png');
    echo "   📁 Arquivos encontrados: " . count($files) . "\n";
    foreach ($files as $file) {
        $filename = basename($file);
        $size = filesize($file);
        echo "      - $filename (" . round($size/1024, 2) . " KB)\n";
    }
    echo "\n";
}

// 4. Testar logo_proxy.php
echo "4. TESTANDO LOGO_PROXY.PHP...\n";
if (!file_exists(__DIR__ . '/logo_proxy.php')) {
    echo "   ❌ ERRO: logo_proxy.php não encontrado\n\n";
} else {
    echo "   ✅ Arquivo existe\n\n";
}

// 5. Simular busca de logo
echo "5. SIMULANDO BUSCA DE LOGO (ID 4689)...\n";
$testId = 4689;
$logoFile = $uploadDir . $testId . '.png';
if (file_exists($logoFile)) {
    echo "   ✅ Logo encontrada: $logoFile\n";
    echo "   📊 Tamanho: " . round(filesize($logoFile)/1024, 2) . " KB\n";
    echo "   🔗 URL: https://webnoma.space/uploads/logos/{$testId}.png\n\n";
} else {
    echo "   ⚠️  Logo não encontrada para ID $testId\n";
    echo "   🔄 Fallback: https://webnoma.shop/logos/nomaapp.png\n\n";
}

echo "=== FIM DOS TESTES ===\n";
?>
