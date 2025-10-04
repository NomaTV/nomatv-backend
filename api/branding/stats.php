<?php
/**
 * =================================================================
 * ENDPOINT DE ESTATÍSTICAS (DASHBOARD) - NomaTV API v4.4
 * =================================================================
 * * ARQUIVO: /api/stats.php
 * VERSÃO: 4.4 - Simplificado para Testes (Sem Permissões/Logs)
 * * RESPONSABILIDADES:
 * ✅ Coletar e fornecer as principais métricas do sistema para o Dashboard.
 * ✅ SIMPLIFICADO: Acesso direto (sem verificação de permissão complexa)
 * ✅ SIMPLIFICADO: Sem logs de auditoria
 * ✅ Refatorado: Lógica de criação de tabelas movida para db_installer.php
 * ✅ CORRIGIDO: Erros de sintaxe e dependências removidas.
 * * =================================================================
 */

// Configuração de erro reporting
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Dependências obrigatórias
require_once __DIR__ . '/config/database_sqlite.php';
// auth_helper.php não é mais necessário para permissões/logs neste ficheiro
// require_once __DIR__ . '/helpers/auth_helper.php';

/**
 * Função auxiliar para padronizar respostas JSON.
 * @param bool $success Indica se a operação foi bem-sucedida.
 * @param array|null $data Dados a serem retornados.
 * @param string|null $message Mensagem de feedback.
 * @param array|null $extraData Dados adicionais (e.g., pagination, stats).
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

// =============================================
// ✅ AUTENTICAÇÃO REAL: session_start igual ao auth.php
// =============================================
session_start();
if (empty($_SESSION['id_revendedor']) || empty($_SESSION['master'])) {
    http_response_code(401);
    exit('{"success":false,"message":"Usuário não autenticado"}');
}
$loggedInRevendedorId = $_SESSION['id_revendedor'];
$loggedInUserType = $_SESSION['master'];

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        getDashboardStats($db);
    } else {
        http_response_code(405);
        standardResponse(false, null, 'Método não permitido.');
    }
} catch (Exception $e) {
    http_response_code(500);
    error_log("NomaTV v4.4 [STATS] Erro: " . $e->getMessage()); // Mantido para log interno do servidor
    standardResponse(false, null, 'Erro interno do servidor.');
}

/**
 * Coleta e retorna as estatísticas principais do sistema.
 */
function getDashboardStats(PDO $db): void {
    try {
        // Total de Revendedores (excluindo o admin)
        $stmtRevendedores = $db->query("SELECT COUNT(id_revendedor) FROM revendedores WHERE master != 'admin' AND ativo = 1");
        $totalRevendedores = (int)$stmtRevendedores->fetchColumn();

        // Total de Ativos (clientes)
        $stmtAtivos = $db->query("SELECT COUNT(client_id) FROM client_ids WHERE ativo = 1");
        $totalAtivos = (int)$stmtAtivos->fetchColumn();

        // Total de Provedores
        $stmtProvedores = $db->query("SELECT COUNT(id_provedor) FROM provedores WHERE ativo = 1");
        $totalProvedores = (int)$stmtProvedores->fetchColumn();

        // Revendedores com pagamento vencido
        $hoje = date('Y-m-d');
        $stmtVencidos = $db->prepare("SELECT COUNT(id_revendedor) FROM revendedores WHERE data_vencimento < ? AND master != 'admin' AND ativo = 1");
        $stmtVencidos->execute([$hoje]);
        $revendedoresVencidos = (int)$stmtVencidos->fetchColumn();

        $stats = [
            'totalRevendedores' => $totalRevendedores,
            'totalAtivos' => $totalAtivos,
            'totalProvedores' => $totalProvedores,
            'revendedoresVencidos' => $revendedoresVencidos
        ];

        standardResponse(true, $stats);

    } catch (Exception $e) {
        // http_response_code(500); // Já definido no bloco try/catch principal
        error_log("NomaTV v4.4 [STATS] Erro em getDashboardStats: " . $e->getMessage()); // Mantido para log interno
        standardResponse(false, null, 'Erro ao buscar estatísticas.');
    }
}
?>