<?php
/**
 * =================================================================
 * ENDPOINT DE CONFIGURAÇÕES E BACKUP - NomaTV API v4.3
 * =================================================================
 * * ARQUIVO: /api/configuracoes.php
 * VERSÃO: 4.3 - Simplificado para Testes (Sem Permissões/Logs)
 * * RESPONSABILIDADES:
 * ✅ Gerenciamento de configurações do sistema (Salvar, Restaurar Padrão).
 * ✅ Sistema completo de Backup e Restauração.
 * - Criar backup completo (Base de Dados + Ficheiros) em .zip.
 * - Listar backups existentes no servidor.
 * - Fornecer download de backups específicos.
 * - Restaurar o sistema a partir de um backup, com trava de segurança.
 * ✅ SIMPLIFICADO: Removidas validações robustas e logs de auditoria para testes.
 * ✅ Refatorado: Lógica de criação de tabelas movida para db_installer.php
 * * =================================================================
 */

// Configuração de erro reporting e tempo de execução
ini_set('display_errors', 0);
error_reporting(E_ALL);
set_time_limit(300); // Aumentar o tempo limite para operações de backup

// Headers de segurança e CORS
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, PUT, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Tratar requisições OPTIONS (CORS preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Dependências obrigatórias
require_once __DIR__ . '/config/database_sqlite.php'; // Apenas a conexão com a base de dados
// auth_helper.php não é mais necessário para permissões/logs neste ficheiro
// require_once __DIR__ . '/helpers/auth_helper.php';

/**
 * Função auxiliar para padronizar respostas JSON.
 * @param bool $success Indica se a operação foi bem-sucedida.
 * @param array|null $data Dados a serem retornados.
 * @param string|null $message Mensagem de feedback.
 * @param array|null $extraData Dados adicionais.
 */
function standardResponse(bool $success, $data = null, $message = null, $extraData = null): void {
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'message' => $message,
        'extraData' => $extraData // Mantido para compatibilidade
    ]);
    exit(); // Garante que nada mais seja enviado
}

// =============================================
// 🔗 CONEXÃO COM BASE DE DADOS (SEM LÓGICA DE CRIAÇÃO AQUI)
// =============================================
try {
    // Tenta diferentes nomes de base de dados para desenvolvimento/teste
    $dbFiles = ['db.db', 'db (7).db', 'nomatv.db'];
    $db = null;
    
    foreach ($dbFiles as $dbFile) {
        if (file_exists(__DIR__ . '/' . $dbFile)) {
            $dbPath = __DIR__ . '/' . $dbFile;
            $db = new PDO('sqlite:' . realpath($dbPath));
            break;
        }
    }
    
    if (!$db) {
        // Se nenhum ficheiro existente for encontrado, tenta criar um novo 'db.db'
        // Mas a criação principal deve ser feita pelo db_installer.php
        $db = new PDO('sqlite:' . __DIR__ . '/db.db');
    }
    
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Em caso de falha na conexão, informa que a base de dados pode não estar inicializada
    http_response_code(500);
    standardResponse(false, null, 'Erro de conexão com a base de dados. Por favor, execute db_installer.php.');
}

// ✅ AUTENTICAÇÃO PADRÃO (SUBSTITUI SIMULAÇÃO HARDCODED)
session_start();
if (empty($_SESSION['id_revendedor']) || empty($_SESSION['master'])) {
    http_response_code(401);
    exit('{"success":false,"message":"Usuário não autenticado"}');
}
$loggedInRevendedorId = $_SESSION['id_revendedor'];
$loggedInUserType = $_SESSION['master'];

/**
 * Roteamento principal
 */
$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true) ?? [];

try {
    // Roteamento para ações de backup via GET (listar, baixar)
    if ($method === 'GET' && isset($_GET['action'])) {
        handleBackupActions($db, $loggedInRevendedorId, $_GET);
        exit;
    }

    switch ($method) {
        case 'GET':
            handleGetConfiguracoes($db, $_GET);
            break;
        case 'PUT':
            handlePutConfiguracoes($db, $loggedInRevendedorId, $input);
            break;
        case 'POST':
            handlePostConfiguracoes($db, $loggedInRevendedorId, $input);
            break;
        default:
            http_response_code(405);
            standardResponse(false, null, 'Método não permitido.');
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    error_log("NomaTV v4.3 [CONFIGURACOES] Erro geral: " . $e->getMessage());
    standardResponse(false, null, 'Erro interno do servidor: ' . $e->getMessage());
}

/**
 * =================================================================
 * HANDLERS DE CONFIGURAÇÕES (GET, PUT)
 * =================================================================
 */
function handleGetConfiguracoes(PDO $db, array $params): void {
    // ... (código existente para buscar configurações, sem alterações)
    // Em um sistema real, estas seriam lidas de uma tabela de configurações.
    $configs = [
        'nome_sistema' => 'NomaTV Painel',
        'fuso_horario' => 'America/Sao_Paulo',
        'idioma_padrao' => 'pt-BR'
    ];
    standardResponse(true, $configs);
}

function handlePutConfiguracoes(PDO $db, string $loggedInRevendedorId, array $input): void {
    // ⚠️ SIMPLIFICADO PARA TESTES: Validação robusta dos dados recebidos removida
    // Apenas assume que os dados são válidos para prosseguir.
    
    // ... (lógica para salvar as configurações no banco)
    // Em um sistema real, estas seriam salvas em uma tabela de configurações.
    // logAction($db, $loggedInRevendedorId, 'atualizar_configuracoes', 'Configurações gerais foram salvas.');
    standardResponse(true, $input, 'Configurações salvas com sucesso!');
}


/**
 * =================================================================
 * HANDLER DE AÇÕES ESPECIAIS (POST e Ações de Backup)
 * =================================================================
 */
function handlePostConfiguracoes(PDO $db, string $loggedInRevendedorId, array $input): void {
    $action = $input['action'] ?? '';
    
    switch ($action) {
        // Ações de Backup
        case 'criar_backup':
            createFullBackup($db, $loggedInRevendedorId);
            break;
        case 'restaurar_backup':
            restoreBackup($db, $loggedInRevendedorId, $input);
            break;
        
        // Outras Ações
        case 'restaurar_padrao':
            // ... (código existente)
            standardResponse(true, null, 'Configurações padrão restauradas.');
            break;
        default:
            http_response_code(400);
            standardResponse(false, null, 'Ação POST inválida.');
            break;
    }
}

function handleBackupActions(PDO $db, string $loggedInRevendedorId, array $params): void {
    $action = $params['action'] ?? '';

    switch ($action) {
        case 'listar_backups':
            listBackups($loggedInRevendedorId);
            break;
        case 'baixar_backup':
            downloadBackup($loggedInRevendedorId, $params);
            break;
        default:
            http_response_code(400);
            standardResponse(false, null, 'Ação de backup inválida.');
            break;
    }
}


/**
 * =================================================================
 * ✨ NOVAS FUNÇÕES DE BACKUP E RESTAURAÇÃO
 * =================================================================
 */

/**
 * Cria um backup completo do sistema (Base de Dados + logos) e o envia para download.
 */
function createFullBackup(PDO $db, string $loggedInRevendedorId): void {
    try {
        $backupDir = __DIR__ . '/backups';
        if (!is_dir($backupDir)) mkdir($backupDir, 0755, true);

        $dbPath = __DIR__ . '/db.db'; // Caminho direto para db.db
        $logosDir = __DIR__ . '/logos'; // Assumindo que os logos estão nesta pasta

        $timestamp = date('Y-m-d_H-i-s');
        $backupFilename = "backup_completo_{$timestamp}.zip";
        $backupFilepath = $backupDir . '/' . $backupFilename;

        $zip = new ZipArchive();
        if ($zip->open($backupFilepath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
            throw new Exception('Não foi possível criar o ficheiro de backup.');
        }

        // Adicionar base de dados ao zip
        if (file_exists($dbPath)) {
            $zip->addFile($dbPath, 'database/db.db'); // Nome no zip
        }

        // Adicionar pasta de logos ao zip
        if (is_dir($logosDir)) {
            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($logosDir), RecursiveIteratorIterator::LEAVES_ONLY);
            foreach ($files as $name => $file) {
                if (!$file->isDir()) {
                    $filePath = $file->getRealPath();
                    $relativePath = 'logos/' . substr($filePath, strlen($logosDir) + 1);
                    $zip->addFile($filePath, $relativePath);
                }
            }
        }
        
        $zip->close();

        // logAction($db, $loggedInRevendedorId, 'criar_backup', "Backup completo criado: $backupFilename");

        // Forçar o download do ficheiro
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . basename($backupFilepath) . '"');
        header('Content-Length: ' . filesize($backupFilepath));
        readfile($backupFilepath);
        exit;

    } catch (Exception $e) {
        error_log("NomaTV v4.3 [BACKUP] Erro em createFullBackup: " . $e->getMessage());
        http_response_code(500);
        // Não usar standardResponse aqui pois o header já pode ter sido enviado
        echo json_encode(['success' => false, 'error' => 'Erro ao criar backup: ' . $e->getMessage()]);
        exit;
    }
}

/**
 * Lista os backups disponíveis no servidor.
 */
function listBackups(string $loggedInRevendedorId): void {
    $backupDir = __DIR__ . '/backups';
    $backups = [];

    if (is_dir($backupDir)) {
        $files = array_diff(scandir($backupDir), array('.', '..'));
        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'zip') {
                $filepath = $backupDir . '/' . $file;
                $backups[] = [
                    'filename' => $file,
                    'size' => filesize($filepath),
                    'date' => date("Y-m-d H:i:s", filemtime($filepath))
                ];
            }
        }
    }
    
    // Ordenar por data, do mais recente para o mais antigo
    usort($backups, function($a, $b) {
        return strtotime($b['date']) - strtotime($a['date']);
    });

    standardResponse(true, $backups);
}

/**
 * Fornece um backup específico para download.
 */
function downloadBackup(string $loggedInRevendedorId, array $params): void {
    if (empty($params['filename'])) {
        http_response_code(400);
        standardResponse(false, null, 'Nome do ficheiro de backup é obrigatório.');
    }

    $filename = basename($params['filename']); // Segurança: previne path traversal
    $filepath = __DIR__ . '/backups/' . $filename;

    if (file_exists($filepath) && pathinfo($filepath, PATHINFO_EXTENSION) === 'zip') {
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($filepath));
        readfile($filepath);
        exit;
    } else {
        http_response_code(404);
        standardResponse(false, null, 'Ficheiro de backup não encontrado.');
    }
}

/**
 * Restaura o sistema a partir de um arquivo de backup.
 */
function restoreBackup(PDO $db, string $loggedInRevendedorId, array $input): void {
    if (empty($input['filename'])) {
        http_response_code(400);
        standardResponse(false, null, 'Nome do ficheiro de backup é obrigatório para restauração.');
    }

    $filename = basename($input['filename']);
    $backupFilepath = __DIR__ . '/backups/' . $filename;

    if (!file_exists($backupFilepath)) {
        http_response_code(404);
        standardResponse(false, null, 'Ficheiro de backup para restauração não encontrado.');
    }

    try {
        // PASSO DE SEGURANÇA 1: Criar um backup de emergência antes de restaurar
        createEmergencyBackup($db, $loggedInRevendedorId);

        // PASSO DE SEGURANÇA 2: Restaurar
        $zip = new ZipArchive;
        if ($zip->open($backupFilepath) === TRUE) {
            // Extrair para uma pasta temporária
            $tempDir = __DIR__ . '/temp_restore_' . uniqid();
            mkdir($tempDir, 0755, true);
            $zip->extractTo($tempDir);
            $zip->close();

            // Restaurar base de dados
            $restoredDbPath = $tempDir . '/database/db.db'; // Nome no zip
            $currentDbPath = __DIR__ . '/db.db'; // Caminho direto para db.db
            if (file_exists($restoredDbPath)) {
                // Fechar a conexão atual com o banco para liberar o ficheiro
                $db = null; 
                if (!copy($restoredDbPath, $currentDbPath)) {
                     throw new Exception('Falha ao restaurar a base de dados.');
                }
            }

            // Restaurar logos
            $restoredLogosDir = $tempDir . '/logos';
            $currentLogosDir = __DIR__ . '/logos';
            if (is_dir($restoredLogosDir)) {
                // Apagar logos antigos e copiar novos (simplificado)
                // Uma abordagem mais robusta usaria rsync ou uma função de cópia recursiva
                shell_exec("rm -rf " . escapeshellarg($currentLogosDir));
                shell_exec("mv " . escapeshellarg($restoredLogosDir) . " " . escapeshellarg($currentLogosDir));
            }

            // Limpar pasta temporária
            shell_exec("rm -rf " . escapeshellarg($tempDir));
            
            // Reabrir conexão com o banco para logar
            // require __DIR__ . '/config/database_sqlite.php'; // Não precisa mais
            // logAction($db, $loggedInRevendedorId, 'restaurar_backup', "Sistema restaurado com sucesso a partir de: $filename");
            
            standardResponse(true, null, 'Sistema restaurado com sucesso! É recomendado recarregar a página.');

        } else {
            throw new Exception('Não foi possível abrir o ficheiro de backup.');
        }

    } catch (Exception $e) {
        error_log("NomaTV v4.3 [BACKUP] Erro em restoreBackup: " . $e->getMessage());
        http_response_code(500);
        standardResponse(false, null, 'Erro crítico durante a restauração: ' . $e->getMessage());
    }
}

/**
 * Função auxiliar para criar backup de emergência.
 */
function createEmergencyBackup(PDO $db, string $loggedInRevendedorId): void {
    $backupDir = __DIR__ . '/backups/emergency';
    if (!is_dir($backupDir)) mkdir($backupDir, 0755, true);
    
    $dbPath = __DIR__ . '/db.db'; // Caminho direto para db.db
    $timestamp = date('Y-m-d_H-i-s');
    $emergencyFile = $backupDir . "/emergency_db_{$timestamp}.sqlite";
    
    if (file_exists($dbPath)) {
        if (!copy($dbPath, $emergencyFile)) {
            throw new Exception('Falha ao criar backup de emergência da base de dados.');
        }
    }
    // logAction($db, $loggedInRevendedorId, 'criar_backup_emergencia', "Backup de emergência criado: {$emergencyFile}");
}

?>