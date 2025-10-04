<?php
/**
 * DATABASE_SQLITE.PHP - Conexão com SQLite NomaTV v4.5
 *
 * FUNÇÃO: Fornecer conexão PDO com SQLite
 *
 * LOCALIZAÇÃO: /api/config/database_sqlite.php
 */

/**
 * Retorna conexão PDO com SQLite
 * @return PDO
 * @throws Exception
 */
function getDatabaseConnection() {
    // Tentar múltiplos caminhos para o banco
    $possiblePaths = [
        __DIR__ . '/../db.db',
        __DIR__ . '/../nomatv.db',
        __DIR__ . '/../../db.db',
        __DIR__ . '/db.db'
    ];

    $dbPath = null;
    foreach ($possiblePaths as $path) {
        if (file_exists($path) && is_readable($path)) {
            $dbPath = $path;
            break;
        }
    }

    if (!$dbPath) {
        throw new Exception('Arquivo de banco de dados não encontrado');
    }

    $db = new PDO("sqlite:$dbPath");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    return $db;
}
?>