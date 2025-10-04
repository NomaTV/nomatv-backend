<?php
/**
 * =================================================================
 * ENDPOINT DE CONTROLE DE PERMISSÕES - NomaTV API v4.2
 * =================================================================
 * * ARQUIVO: /api/permissoes.php
 * VERSÃO: 4.3 - Simplificado para Testes (Sem Permissões/Logs)
 * * RESPONSABILIDADES:
 * ✅ Gerenciamento de permissões por tipo de utilizador.
 * ✅ SIMPLIFICADO: Acesso direto (sem verificação de permissão complexa)
 * ✅ SIMPLIFICADO: Sem logs de auditoria
 * ✅ Refatorado: Lógica de criação movida para db_installer.php
 * * =================================================================
 */

// Configuração de erro reporting
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Headers de segurança e CORS
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Tratar requisições OPTIONS (CORS preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Dependências obrigatórias
require_once __DIR__ . '/config/database_sqlite.php'; // Apenas a conexão com o banco de dados
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
// 🔗 CONEXÃO COM BANCO DE DADOS (SEM LÓGICA DE CRIAÇÃO AQUI)
// =============================================
try {
    // Tenta diferentes nomes de banco para desenvolvimento/teste
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
        // Se nenhum arquivo existente for encontrado, tenta criar um novo 'db.db'
        // Mas a criação principal deve ser feita pelo db_installer.php
        $db = new PDO('sqlite:' . __DIR__ . '/db.db');
    }
    
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Em caso de falha na conexão, informa que o banco pode não estar inicializado
    http_response_code(500);
    standardResponse(false, null, 'Erro de conexão com o banco de dados. Por favor, execute db_installer.php.');
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
    switch ($method) {
        case 'GET':
            listPermissoes($db);
            break;
            
        case 'PUT':
            updatePermissoes($db, $loggedInRevendedorId, $input); // Passa o ID do admin logado
            break;
            
        default:
            http_response_code(405);
            standardResponse(false, null, 'Método não permitido.');
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    error_log("NomaTV v4.2 [PERMISSOES] Erro geral: " . $e->getMessage());
    standardResponse(false, null, 'Erro interno do servidor.');
}

/**
 * Lista todas as permissões do sistema.
 */
function listPermissoes(PDO $db): void {
    try {
        $stmt = $db->query("
            SELECT id, funcionalidade, descricao, categoria, admin, master, sub, ativo 
            FROM permissoes 
            ORDER BY categoria, funcionalidade
        ");
        $permissoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Converte valores para booleano para consistência no frontend
        foreach ($permissoes as &$p) {
            $p['admin'] = (bool)$p['admin'];
            $p['master'] = (bool)$p['master'];
            $p['sub'] = (bool)$p['sub'];
            $p['ativo'] = (bool)$p['ativo']; // Garante que 'ativo' também seja booleano
        }

        standardResponse(true, $permissoes);

    } catch (Exception $e) {
        http_response_code(500);
        error_log("NomaTV v4.2 [PERMISSOES] Erro em listPermissoes: " . $e->getMessage());
        standardResponse(false, null, 'Erro ao listar permissões.');
    }
}

/**
 * Atualiza um conjunto de permissões.
 */
function updatePermissoes(PDO $db, string $loggedInRevendedorId, array $input): void {
    if (!isset($input['permissoes']) || !is_array($input['permissoes'])) {
        http_response_code(400);
        standardResponse(false, null, 'Formato de dados inválido. O campo "permissoes" é esperado como um array.');
    }

    try {
        $db->beginTransaction();

        $stmt = $db->prepare(
            "UPDATE permissoes SET admin = :admin, master = :master, sub = :sub, ativo = :ativo, id_revendedor_configurador = :config_por, atualizado_em = CURRENT_TIMESTAMP 
             WHERE funcionalidade = :funcionalidade"
        );

        $count = 0;
        foreach ($input['permissoes'] as $p) {
            // Validações básicas para garantir que os campos esperados existam e sejam do tipo correto
            if (!isset($p['funcionalidade']) || !is_string($p['funcionalidade']) ||
                !isset($p['admin']) || !is_bool($p['admin']) ||
                !isset($p['master']) || !is_bool($p['master']) ||
                !isset($p['sub']) || !is_bool($p['sub']) ||
                !isset($p['ativo']) || !is_bool($p['ativo'])) {
                
                error_log("NomaTV v4.2 [PERMISSOES] Dados de permissão inválidos: " . json_encode($p));
                // Continua o loop, mas pode-se optar por lançar uma exceção ou retornar um erro mais específico
                continue; 
            }

            $stmt->execute([
                ':admin' => (int)$p['admin'],
                ':master' => (int)$p['master'],
                ':sub' => (int)$p['sub'],
                ':ativo' => (int)$p['ativo'], // Adicionado o campo 'ativo' para atualização
                ':config_por' => $loggedInRevendedorId, // Usa o ID do admin logado
                ':funcionalidade' => $p['funcionalidade']
            ]);
            $count++;
        }

        $db->commit();
        
        // logAction($db, $loggedInRevendedorId, 'atualizar_permissoes', "$count permissões foram atualizadas.");
        standardResponse(true, null, 'Permissões atualizadas com sucesso!');

    } catch (Exception $e) {
        $db->rollBack();
        http_response_code(500);
        error_log("NomaTV v4.2 [PERMISSOES] Erro em updatePermissoes: " . $e->getMessage());
        standardResponse(false, null, 'Erro ao salvar as permissões.');
    }
}
?>