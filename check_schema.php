<?php
try {
    $db = new PDO('sqlite:db.db');

    // Verificar estrutura completa da tabela provedores
    echo "Estrutura completa da tabela provedores:\n";
    $result = $db->query('PRAGMA table_info(provedores)');
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        echo "  {$row['name']} ({$row['type']}) - {$row['dflt_value']} - {$row['pk']}\n";
    }

    // Verificar se há dados
    echo "\nDados na tabela provedores:\n";
    $result2 = $db->query('SELECT COUNT(*) as total FROM provedores');
    $row = $result2->fetch(PDO::FETCH_ASSOC);
    echo "Total de registros: {$row['total']}\n";

    // Verificar campos que deveriam existir segundo o schema
    echo "\nVerificando campos obrigatórios:\n";
    $expectedFields = ['id_provedor', 'nome', 'dns', 'tipo', 'usuario', 'senha', 'id_revendedor', 'ativo', 'criado_em', 'atualizado_em'];
    $result3 = $db->query('PRAGMA table_info(provedores)');
    $existingFields = [];
    while ($row = $result3->fetch(PDO::FETCH_ASSOC)) {
        $existingFields[] = $row['name'];
    }

    foreach ($expectedFields as $field) {
        if (in_array($field, $existingFields)) {
            echo "✓ $field - OK\n";
        } else {
            echo "✗ $field - FALTANDO\n";
        }
    }

} catch (Exception $e) {
    echo 'Erro: ' . $e->getMessage() . "\n";
}
?>