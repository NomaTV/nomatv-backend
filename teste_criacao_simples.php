<?php
// Teste simples de criação de provedor

echo "=== TESTE SIMPLES DE CRIAÇÃO ===\n";

try {
    $db = new PDO('sqlite:db.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Ver antes
    $stmt = $db->query('SELECT COUNT(*) as total FROM provedores');
    $antes = $stmt->fetch()['total'];
    echo "Antes: $antes\n";

    // Inserir diretamente
    $stmt = $db->prepare("INSERT INTO provedores (nome, dns, tipo, usuario, senha, id_revendedor, ativo, criado_em, atualizado_em)
                         VALUES (?, ?, ?, ?, ?, ?, 1, datetime('now'), datetime('now'))");

    $result = $stmt->execute(['Teste Direto', 'teste.com', 'xtream', 'admin', '123', 12345678]);

    echo "Insert executado: " . ($result ? 'SIM' : 'NAO') . "\n";
    echo "Linhas afetadas: " . $stmt->rowCount() . "\n";

    // Ver depois
    $stmt = $db->query('SELECT COUNT(*) as total FROM provedores');
    $depois = $stmt->fetch()['total'];
    echo "Depois: $depois\n";

    if ($depois > $antes) {
        echo "✅ SUCESSO: Provedor criado!\n";

        // Mostrar o provedor criado
        $stmt = $db->query('SELECT * FROM provedores ORDER BY id_provedor DESC LIMIT 1');
        $provedor = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "ID: {$provedor['id_provedor']}, Nome: {$provedor['nome']}\n";
    } else {
        echo "❌ FALHA: Provedor não foi criado!\n";
    }

} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
}
?>