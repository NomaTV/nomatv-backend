<?php
/**
 * Configuração do Banco SQLite - NomaTV v4.2
 * Gerado automaticamente pelo setup
 */

$dbFile = __DIR__ . '/../db.db';

try {
    $db = new PDO("sqlite:$dbFile");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Habilitar foreign keys no SQLite
    $db->exec('PRAGMA foreign_keys = ON');
    
} catch (PDOException $e) {
    http_response_code(500);
    if (function_exists('json_encode')) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Falha na conexão com SQLite: ' . $e->getMessage()]);
    } else {
        echo 'Erro de conexão com o banco de dados.';
    }
    exit();
}
?>