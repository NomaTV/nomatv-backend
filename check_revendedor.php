try {
    $pdo = new PDO('sqlite:api/db.db');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->prepare('SELECT * FROM revendedores WHERE id_revendedor = ?');
    $stmt->execute(['12345678']);
    $revendedor = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($revendedor) {
        echo 'Revendedor encontrado:' . PHP_EOL;
        print_r($revendedor);
    } else {
        echo 'Revendedor com ID 12345678 NÃO encontrado' . PHP_EOL;
        
        // Verificar todos os revendedores
        $stmt = $pdo->query('SELECT id_revendedor, usuario, master FROM revendedores LIMIT 5');
        $revendedores = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo 'Revendedores existentes:' . PHP_EOL;
        print_r($revendedores);
    }
} catch (Exception $e) {
    echo 'Erro: ' . $e->getMessage() . PHP_EOL;
}