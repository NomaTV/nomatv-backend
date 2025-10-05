<?php
require_once 'api/config/database_sqlite.php';
require_once 'config/session.php';

try {
    $db = getDatabaseConnection();

    // Testar login diretamente
    $username = 'admin';
    $password = 'admin123';

    echo "Testando login com usuário: $username, senha: $password" . PHP_EOL;

    $stmt = $db->prepare("
        SELECT id_revendedor, usuario, senha, nome, master, ativo
        FROM revendedores
        WHERE usuario = ?
    ");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    echo "Usuário encontrado: " . ($user ? 'SIM' : 'NÃO') . PHP_EOL;

    if ($user) {
        echo "Dados do usuário:" . PHP_EOL;
        echo "- ID: " . $user['id_revendedor'] . PHP_EOL;
        echo "- Usuário: " . $user['usuario'] . PHP_EOL;
        echo "- Nome: " . $user['nome'] . PHP_EOL;
        echo "- Master: " . $user['master'] . PHP_EOL;
        echo "- Ativo: " . ($user['ativo'] ? 'Sim' : 'Não') . PHP_EOL;
        echo "- Hash da senha: " . $user['senha'] . PHP_EOL;

        // Testar password_verify
        $passwordValid = password_verify($password, $user['senha']);
        echo "Senha válida: " . ($passwordValid ? 'SIM' : 'NÃO') . PHP_EOL;

        if ($passwordValid) {
            // Simular criação da sessão
            $_SESSION['id_revendedor'] = $user['id_revendedor'];
            $_SESSION['master'] = $user['master'];
            $_SESSION['usuario'] = $user['usuario'];

            echo "Sessão criada com sucesso!" . PHP_EOL;
            echo "Dados da sessão: " . json_encode($_SESSION) . PHP_EOL;
        }
    }

} catch (Exception $e) {
    echo 'Erro: ' . $e->getMessage() . PHP_EOL;
}
?>