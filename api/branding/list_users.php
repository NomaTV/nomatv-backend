<?php
try {
    require_once __DIR__ . '/config/database_sqlite.php';
    $db = getDatabaseConnection();
    $stmt = $db->query("SELECT usuario, senha, master FROM revendedores WHERE ativo = 1");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($users);
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
?>