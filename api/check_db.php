<?php
require_once 'api/config/database_sqlite.php';
try {
    $db = getDatabaseConnection();

    // Verificar usuário admin
    $stmt = $db->prepare('SELECT id_revendedor, usuario, nome, master, ativo FROM revendedores WHERE usuario = ?');
    $stmt->execute(['admin']);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        echo 'Usuário admin encontrado:' . PHP_EOL;
        echo 'ID: ' . $user['id_revendedor'] . PHP_EOL;
        echo 'Usuário: ' . $user['usuario'] . PHP_EOL;
        echo 'Nome: ' . $user['nome'] . PHP_EOL;
        echo 'Master: ' . $user['master'] . PHP_EOL;
        echo 'Ativo: ' . ($user['ativo'] ? 'Sim' : 'Não') . PHP_EOL;
    } else {
        echo 'Usuário admin NÃO encontrado!' . PHP_EOL;
    }

    // Verificar planos
    $stmt = $db->query('SELECT COUNT(*) as total FROM planos');
    $planos = $stmt->fetch(PDO::FETCH_ASSOC);
    echo 'Total de planos: ' . $planos['total'] . PHP_EOL;

} catch (Exception $e) {
    echo 'Erro: ' . $e->getMessage() . PHP_EOL;
}
?>