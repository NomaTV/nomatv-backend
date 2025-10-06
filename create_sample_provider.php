<?php
try {
    $pdo = new PDO('sqlite:api/db.db');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Verificar se já existe algum provedor
    $result = $pdo->query('SELECT COUNT(*) as total FROM provedores');
    $row = $result->fetch(PDO::FETCH_ASSOC);
    echo "Total de provedores antes: " . $row['total'] . "\n";

    if ($row['total'] == 0) {
        // Inserir um provedor de exemplo
        $stmt = $pdo->prepare("INSERT INTO provedores (nome, dns, tipo, ativo, id_revendedor, criado_em, atualizado_em) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            'Provedor Exemplo NomaTV',
            'http://exemplo.nomatv.com:8080',
            'xtream',
            1,
            12345678, // ID do admin
            date('Y-m-d H:i:s'),
            date('Y-m-d H:i:s')
        ]);

        echo "✅ Provedor de exemplo criado com sucesso!\n";

        // Verificar novamente
        $result = $pdo->query('SELECT COUNT(*) as total FROM provedores');
        $row = $result->fetch(PDO::FETCH_ASSOC);
        echo "Total de provedores depois: " . $row['total'] . "\n";
    } else {
        echo "Já existem provedores no banco.\n";
    }

} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
?>