<?php
try {
    $db = new PDO('sqlite:db.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Executando migração da tabela provedores...\n";

    // Verificar se os campos já existem antes de adicionar
    $result = $db->query('PRAGMA table_info(provedores)');
    $existingFields = [];
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $existingFields[] = $row['name'];
    }

    // Adicionar campos faltantes se não existirem
    if (!in_array('tipo', $existingFields)) {
        $db->exec("ALTER TABLE provedores ADD COLUMN tipo VARCHAR(50) DEFAULT 'xtream'");
        echo "✓ Campo 'tipo' adicionado\n";
    } else {
        echo "- Campo 'tipo' já existe\n";
    }

    if (!in_array('usuario', $existingFields)) {
        $db->exec("ALTER TABLE provedores ADD COLUMN usuario VARCHAR(100) DEFAULT 'admin'");
        echo "✓ Campo 'usuario' adicionado\n";
    } else {
        echo "- Campo 'usuario' já existe\n";
    }

    if (!in_array('senha', $existingFields)) {
        $db->exec("ALTER TABLE provedores ADD COLUMN senha VARCHAR(255) DEFAULT 'dadomockado'");
        echo "✓ Campo 'senha' adicionado\n";
    } else {
        echo "- Campo 'senha' já existe\n";
    }

    if (!in_array('atualizado_em', $existingFields)) {
        $db->exec("ALTER TABLE provedores ADD COLUMN atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP");
        echo "✓ Campo 'atualizado_em' adicionado\n";
    } else {
        echo "- Campo 'atualizado_em' já existe\n";
    }

    echo "Migração concluída!\n";

    // Verificar estrutura final
    echo "\nEstrutura final da tabela provedores:\n";
    $result2 = $db->query('PRAGMA table_info(provedores)');
    while ($row = $result2->fetch(PDO::FETCH_ASSOC)) {
        echo "  {$row['name']} ({$row['type']})\n";
    }

} catch (Exception $e) {
    echo 'Erro na migração: ' . $e->getMessage() . "\n";
}
?>