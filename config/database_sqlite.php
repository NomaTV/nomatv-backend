<?php
// =================================================================
// 📁 api/config/database_sqlite.php - Conector v4.2
// RESPONSABILIDADE: Apenas conectar ao banco de dados.
// =================================================================

// O nome do arquivo do banco de dados que o instalador cria.
$dbFile = __DIR__ . '/../db.db';

try {
    // Tenta se conectar ao arquivo do banco de dados.
    $db = new PDO("sqlite:$dbFile");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Habilita o suporte a chaves estrangeiras, importante para a integridade dos dados.
    $db->exec('PRAGMA foreign_keys = ON;');

} catch (PDOException $e) {
    // Se a conexão falhar (ex: arquivo não encontrado, permissões incorretas),
    // o script para e retorna um erro JSON padronizado.
    http_response_code(500);
    // Garante que o header seja JSON para o frontend entender o erro.
    header('Content-Type: application/json; charset=utf-8');
    exit(json_encode([
        'success' => false, 
        'error' => 'Falha crítica na conexão com o banco de dados: ' . $e->getMessage()
    ]));
}
?>