try {
    $pdo = new PDO('sqlite:api/db.db');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Criar revendedor admin
    $sql = "INSERT OR REPLACE INTO revendedores (id_revendedor, usuario, nome, master, ativo, criado_em, atualizado_em, email, senha)
             VALUES (?, ?, ?, ?, 1, datetime('now'), datetime('now'), ?, ?)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['12345678', 'admin', 'Administrador', 'sim', 'admin@nomatv.com', password_hash('admin123', PASSWORD_DEFAULT)]);

    echo 'Revendedor admin criado com sucesso!' . PHP_EOL;

    // Verificar
    $stmt = $pdo->query('SELECT id_revendedor, usuario, master FROM revendedores');
    $revs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo 'Revendedores no banco:' . PHP_EOL;
    print_r($revs);

} catch (Exception $e) {
    echo 'Erro: ' . $e->getMessage() . PHP_EOL;
}