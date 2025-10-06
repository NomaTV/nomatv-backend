<?php
$db = new PDO('sqlite:db.db');

// Verificar estrutura da tabela
echo "Estrutura da tabela provedores:\n";
$result = $db->query('PRAGMA table_info(provedores)');
$columns = $result->fetchAll(PDO::FETCH_ASSOC);
foreach ($columns as $col) {
    echo "- {$col['name']} ({$col['type']})" . ($col['notnull'] ? ' NOT NULL' : '') . ($col['pk'] ? ' PRIMARY KEY' : '') . "\n";
}

echo "\n";

// Verificar se há dados na tabela
$result = $db->query('SELECT COUNT(*) as total FROM provedores');
$row = $result->fetch(PDO::FETCH_ASSOC);
echo "Total de registros na tabela provedores: " . $row['total'] . "\n";

// Testar a query completa do provedores.php
echo "\nTestando query completa do provedores.php:\n";
$query = "SELECT p.id_provedor, p.nome, p.dns, p.id_revendedor, p.ativo, p.criado_em, p.tipo, p.usuario, p.senha, p.atualizado_em,
                 r.nome as nome_revendedor
          FROM provedores p
          LEFT JOIN revendedores r ON p.id_revendedor = r.id_revendedor
          ORDER BY p.criado_em DESC";

try {
    $stmt = $db->query($query);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Query executada com sucesso. " . count($results) . " resultados encontrados.\n";
    if (count($results) > 0) {
        echo "Primeiro resultado: " . json_encode($results[0]) . "\n";
    }
} catch (Exception $e) {
    echo "Erro na query: " . $e->getMessage() . "\n";
}
?>