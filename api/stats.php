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
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/helpers/response_helper.php';

// =============================================
// 🔗 CONEXÃO COM BANCO DE DADOS
// =============================================
try {
    $db = getDatabaseConnection();
} catch (Exception $e) {
    respostaErroPadronizada('Erro de conexão com banco de dados', 500);
}

// ✅ AUTENTICAÇÃO USANDO SESSION COMUM
$user = verificarAutenticacao();
if (!$user) {
    respostaNaoAutenticadoPadronizada();
}

// Buscar dados completos do revendedor logado
$revendedorId = $user['id'] ?? 0;
$dadosRevendedor = getRevendedorCompleto($db, $revendedorId);

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        getDashboardStats($db, $dadosRevendedor);
    } else {
        http_response_code(405);
        respostaErroPadronizada('Método não permitido.', 405);
    }
} catch (Exception $e) {
    http_response_code(500);
    error_log("NomaTV v4.4 [STATS] Erro: " . $e->getMessage());
    respostaErroPadronizada('Erro interno do servidor.');
}

/**
 * Coleta e retorna as estatísticas principais do sistema.
 */
function getDashboardStats(PDO $db, array $dadosRevendedor): void {
    try {
        // Estatísticas do dashboard
        $stats = [];

        // Total de Revendedores (excluindo o admin)
        $stmtRevendedores = $db->query("SELECT COUNT(id_revendedor) FROM revendedores WHERE master != 'admin' AND ativo = 1");
        $stats['total_revendedores'] = (int)$stmtRevendedores->fetchColumn();

        // Total de Clientes (se existir tabela clientes)
        try {
            $stmtClientes = $db->query("SELECT COUNT(*) FROM clientes WHERE ativo = 1");
            $stats['total_clientes'] = (int)$stmtClientes->fetchColumn();
        } catch (Exception $e) {
            $stats['total_clientes'] = 0;
        }

        // Receita Total (se existir tabela vendas/financeiro)
        try {
            $stmtReceita = $db->query("SELECT SUM(valor) FROM financeiro WHERE tipo = 'receita' AND status = 'pago'");
            $stats['receita_total'] = (float)$stmtReceita->fetchColumn();
        } catch (Exception $e) {
            $stats['receita_total'] = 0.00;
        }

        // Vendas do mês atual
        try {
            $stmtVendasMes = $db->query("SELECT COUNT(*) FROM financeiro WHERE tipo = 'receita' AND strftime('%Y-%m', criado_em) = strftime('%Y-%m', 'now')");
            $stats['vendas_mes'] = (int)$stmtVendasMes->fetchColumn();
        } catch (Exception $e) {
            $stats['vendas_mes'] = 0;
        }

        // Status do sistema
        $stats['status_sistema'] = 'online';
        $stats['ultima_atualizacao'] = date('Y-m-d H:i:s');

        // Combinar dados do revendedor com estatísticas
        $responseData = array_merge($dadosRevendedor, [
            'stats' => $stats,
            'dashboard' => $stats // Para compatibilidade
        ]);

        respostaSucessoPadronizada($responseData, 'Estatísticas carregadas com sucesso', [
            'timestamp' => date('Y-m-d H:i:s'),
            'versao_api' => '4.5'
        ]);

    } catch (Exception $e) {
        error_log("Erro ao coletar estatísticas: " . $e->getMessage());
        respostaErroPadronizada('Erro ao carregar estatísticas do dashboard.');
    }
}
?>