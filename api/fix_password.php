<?php
require_once 'api/config/database_sqlite.php';
try {
    $db = getDatabaseConnection();

    // Hash correto para admin123
    $correctHash = '$2y$10$teil76guC9bIw8Nt4PRelO7ORqboz7xNQc8GJK60b3SIkxwyraUx.';

    // Atualizar senha do admin
    $stmt = $db->prepare('UPDATE revendedores SET senha = ? WHERE usuario = ?');
    $stmt->execute([$correctHash, 'admin']);

    echo 'Senha do usuário admin atualizada com sucesso!' . PHP_EOL;

    // Verificar se funcionou
    $stmt = $db->prepare('SELECT senha FROM revendedores WHERE usuario = ?');
    $stmt->execute(['admin']);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $verify = password_verify('admin123', $user['senha']);
        echo 'Verificação da nova senha: ' . ($verify ? 'OK' : 'FALHA') . PHP_EOL;
    }

} catch (Exception $e) {
    echo 'Erro: ' . $e->getMessage() . PHP_EOL;
}
?>