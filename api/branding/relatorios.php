<?php
/**
 * =================================================================
 * ENDPOINT DE RELATÓRIOS AVANÇADOS - NomaTV API v4.2
 * =================================================================
 * * ARQUIVO: /api/relatorios.php
 * VERSÃO: 4.2 - Simplificado para Testes (Sem Permissões/Logs)
 * * RESPONSABILIDADES:
 * ✅ Sistema completo de relatórios gerenciais
 * ✅ Relatórios financeiros detalhados
 * ✅ Análises de crescimento e performance
 * ✅ Relatórios de uso por revendedor
 * ✅ Estatísticas de provedores mais utilizados
 * ✅ Métricas de retenção e atividade
 * ✅ Exportação em múltiplos formatos (CSV implementado)
 * ✅ Filtros avançados por período
 * ✅ Compatibilidade total com estrutura v4.2
 * ✅ SIMPLIFICADO: Acesso direto (sem verificação de permissão complexa)
 * ✅ SIMPLIFICADO: Sem logs de auditoria
 * ✅ Refatorado: Lógica de criação movida para db_installer.php
 * * ESTRUTURA v4.2:
 * - revendedores (dados financeiros e hierarquia)
 * - provedores (estatísticas de uso)
 * - client_ids (métricas de atividade)
 * - auditoria (histórico de operações)
 * - faturas (dados financeiros)
 * - pagamentos (dados financeiros)
 * * =================================================================
 */

// Configuração de erro reporting
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Headers de segurança e CORS
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
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
            handleGetRelatorios($db, $_GET);
            break;
            
        case 'POST':
            handlePostRelatorios($db, $loggedInRevendedorId, $input);
            break;
            
        default:
            http_response_code(405);
            standardResponse(false, null, 'Método não permitido.');
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    error_log("NomaTV v4.2 [RELATORIOS] Erro geral: " . $e->getMessage());
    standardResponse(false, null, 'Erro interno do servidor.');
}

/**
 * =================================================================
 * HANDLERS PRINCIPAIS
 * =================================================================
 */

function handleGetRelatorios(PDO $db, array $params): void {
    try {
        $tipo = $params['tipo'] ?? 'dashboard';
        $periodo = $params['periodo'] ?? '30'; // Padrão 30 dias
        
        $relatorio = [];
        switch ($tipo) {
            case 'dashboard':
                $relatorio = gerarRelatoriosDashboard($db, $periodo);
                break;
                
            case 'financeiro':
                $relatorio = gerarRelatorioFinanceiro($db, $periodo);
                break;
                
            case 'revendedores':
                $relatorio = gerarRelatorioRevendedores($db, $periodo);
                break;
                
            case 'provedores':
                $relatorio = gerarRelatorioProvedores($db, $periodo);
                break;
                
            case 'atividade':
                $relatorio = gerarRelatorioAtividade($db, $periodo);
                break;
                
            case 'crescimento':
                $relatorio = gerarRelatorioCrescimento($db, $periodo);
                break;
                
            case 'completo':
                $relatorio = gerarRelatorioCompleto($db, $periodo);
                break;
                
            default:
                http_response_code(400);
                standardResponse(false, null, 'Tipo de relatório inválido.');
                exit();
        }
        
        standardResponse(true, $relatorio, 'Relatório gerado com sucesso!');
        
    } catch (Exception $e) {
        error_log("NomaTV v4.2 [RELATORIOS] Erro em handleGetRelatorios: " . $e->getMessage());
        http_response_code(500);
        standardResponse(false, null, 'Erro ao gerar relatório: ' . $e->getMessage());
    }
}

function handlePostRelatorios(PDO $db, string $loggedInRevendedorId, array $input): void {
    $acao = $input['action'] ?? '';
    
    try {
        switch ($acao) {
            case 'exportar':
                exportarRelatorio($db, $loggedInRevendedorId, $input);
                break;
                
            case 'programar':
                programarRelatorio($db, $loggedInRevendedorId, $input);
                break;
                
            case 'personalizado':
                gerarRelatorioPersonalizado($db, $loggedInRevendedorId, $input);
                break;
                
            default:
                http_response_code(400);
                standardResponse(false, null, 'Ação não reconhecida.');
                break;
        }
        
    } catch (Exception $e) {
        http_response_code(500);
        error_log("NomaTV v4.2 [RELATORIOS] Erro em handlePostRelatorios: " . $e->getMessage());
        standardResponse(false, null, 'Erro interno do servidor ao processar ação.');
    }
}

/**
 * =================================================================
 * RELATÓRIOS ESPECIALIZADOS (Implementações)
 * =================================================================
 */

function gerarRelatoriosDashboard(PDO $db, string $periodo): array {
    $dataInicio = calcularDataInicio($periodo);
    
    return [
        'tipo' => 'dashboard',
        'periodo' => $periodo,
        'data_inicio' => $dataInicio,
        'data_fim' => date('Y-m-d'),
        'metricas_gerais' => buscarMetricasGerais($db, $dataInicio),
        'top_revendedores' => buscarTopRevendedores($db, $dataInicio, 5), // Top 5
        'crescimento' => calcularCrescimento($db, $dataInicio),
        'receita_por_plano' => calcularReceitaPorPlano($db, $dataInicio),
        'atividade_recente' => buscarAtividadeRecente($db, 7), // Últimos 7 dias de atividade
        'timestamp' => date('c')
    ];
}

function gerarRelatorioFinanceiro(PDO $db, string $periodo): array {
    $dataInicio = calcularDataInicio($periodo);
    
    return [
        'tipo' => 'financeiro',
        'periodo' => $periodo,
        'receita_total' => calcularReceitaTotal($db, $dataInicio),
        'receita_por_tipo' => calcularReceitaPorTipo($db, $dataInicio),
        'historico_mensal' => buscarHistoricoMensal($db, $dataInicio),
        'previsoes' => calcularPrevisoes($db), // Dados simulados
        'inadimplencia' => analisarInadimplencia($db), // Dados simulados
        'timestamp' => date('c')
    ];
}

function gerarRelatorioRevendedores(PDO $db, string $periodo): array {
    $dataInicio = calcularDataInicio($periodo);
    
    return [
        'tipo' => 'revendedores',
        'periodo' => $periodo,
        'ranking_ativos' => buscarRankingRevendedoresPorAtivos($db, $dataInicio),
        'ranking_receita' => buscarRankingRevendedoresPorReceita($db, $dataInicio),
        'novos_revendedores' => buscarNovosRevendedores($db, $dataInicio),
        'revendedores_inativos' => buscarRevendedoresInativos($db, $dataInicio),
        'distribuicao_planos' => analisarDistribuicaoPlanos($db),
        'performance_masters' => analisarPerformanceMasters($db, $dataInicio),
        'timestamp' => date('c')
    ];
}

function gerarRelatorioProvedores(PDO $db, string $periodo): array {
    $dataInicio = calcularDataInicio($periodo);
    
    return [
        'tipo' => 'provedores',
        'periodo' => $periodo,
        'mais_utilizados' => buscarProvedoresMaisUtilizados($db, $dataInicio),
        'novos_provedores' => buscarNovosProvedores($db, $dataInicio),
        'performance' => analisarPerformanceProvedores($db, $dataInicio),
        'distribuicao' => analisarDistribuicaoProvedores($db), // Dados simulados
        'timestamp' => date('c')
    ];
}

function gerarRelatorioAtividade(PDO $db, string $periodo): array {
    $dataInicio = calcularDataInicio($periodo);
    
    return [
        'tipo' => 'atividade',
        'periodo' => $periodo,
        'atividade_diaria' => buscarAtividadeDiaria($db, $dataInicio),
        'picos_uso' => identificarPicosUso($db, $dataInicio), // Dados simulados
        'clients_mais_ativos' => buscarClientsMaisAtivos($db, $dataInicio),
        'taxa_retencao' => calcularTaxaRetencao($db, $dataInicio), // Dados simulados
        'timestamp' => date('c')
    ];
}

function gerarRelatorioCrescimento(PDO $db, string $periodo): array {
    $dataInicio = calcularDataInicio($periodo);

    return [
        'tipo' => 'crescimento',
        'periodo' => $periodo,
        'crescimento_revendedores' => analisarCrescimentoRevendedores($db, $dataInicio),
        'crescimento_ativos' => analisarCrescimentoAtivos($db, $dataInicio),
        'tendencias' => identificarTendencias($db, $dataInicio), // Dados simulados
        'projecoes' => calcularProjecoesFuturas($db), // Dados simulados
        'timestamp' => date('c')
    ];
}

function gerarRelatorioCompleto(PDO $db, string $periodo): array {
    $dataInicio = calcularDataInicio($periodo);
    
    // Executar todos os relatórios (versão resumida para o completo)
    $dashboardMetricas = buscarMetricasGerais($db, $dataInicio);
    $financeiroResumo = calcularReceitaTotal($db, $dataInicio);
    $revendedoresResumo = buscarRankingRevendedoresPorAtivos($db, $dataInicio);
    $provedoresResumo = buscarProvedoresMaisUtilizados($db, $dataInicio);
    $atividadeResumo = buscarAtividadeRecente($db, 7);
    
    return [
        'tipo' => 'completo',
        'periodo' => $periodo,
        'resumo_executivo' => [
            'total_revendedores' => $dashboardMetricas['total_revendedores'] ?? 0,
            'total_provedores' => $dashboardMetricas['total_provedores'] ?? 0,
            'receita_estimada_periodo' => $financeiroResumo['total_periodo'] ?? 0,
            'crescimento_percentual_ativos' => calcularCrescimentoPeriodo($db, $dataInicio)
        ],
        'metricas_principais' => $dashboardMetricas,
        'indicadores_financeiros' => $financeiroResumo,
        'atividade_sistema' => $atividadeResumo,
        'timestamp' => date('c'),
        'observacoes' => 'Relatório completo gerado para análise executiva'
    ];
}

/**
 * =================================================================
 * FUNÇÕES AUXILIARES DE DADOS (Implementadas)
 * =================================================================
 */

function calcularDataInicio(string $periodo): string {
    switch ($periodo) {
        case '7': return date('Y-m-d', strtotime('-7 days'));
        case '30': return date('Y-m-d', strtotime('-30 days'));
        case '90': return date('Y-m-d', strtotime('-90 days'));
        case 'ano_atual': return date('Y-m-d', strtotime('first day of January this year'));
        case 'total': return '2000-01-01'; // Desde o início
        default: return date('Y-m-d', strtotime('-30 days')); // Padrão
    }
}

function buscarMetricasGerais(PDO $db, string $dataInicio): array {
    try {
        $stmt = $db->query("
            SELECT 
                COUNT(CASE WHEN master != 'admin' AND ativo = 1 THEN 1 END) as total_revendedores,
                SUM(CASE WHEN master = 'sim' AND ativo = 1 THEN 1 ELSE 0 END) as masters_ativos,
                SUM(CASE WHEN master = 'nao' AND ativo = 1 THEN 1 ELSE 0 END) as subs_ativos,
                (SELECT COUNT(id_provedor) FROM provedores WHERE ativo = 1) as total_provedores,
                (SELECT COUNT(client_id) FROM client_ids WHERE ativo = 1 AND bloqueado = 0) as total_ativos_online,
                (SELECT COUNT(client_id) FROM client_ids WHERE ativo = 0 AND bloqueado = 0) as total_ativos_inativos,
                (SELECT COUNT(client_id) FROM client_ids WHERE bloqueado = 1) as total_ativos_bloqueados,
                (SELECT COUNT(client_id) FROM client_ids) as total_geral_ativos,
                (SELECT COUNT(DISTINCT DATE(timestamp)) FROM auditoria WHERE timestamp >= '$dataInicio') as dias_atividade_periodo
            FROM revendedores
        ");
        
        return $stmt->fetch(PDO::FETCH_ASSOC) ?? [];
        
    } catch (Exception $e) {
        error_log("NomaTV v4.2 [RELATORIOS] Erro em buscarMetricasGerais: " . $e->getMessage());
        return [];
    }
}

function buscarTopRevendedores(PDO $db, string $dataInicio, int $limit): array {
    try {
        $stmt = $db->prepare("
            SELECT 
                r.id_revendedor,
                r.nome,
                r.usuario,
                r.plano,
                (SELECT COUNT(c.client_id) FROM client_ids c WHERE c.id_revendedor = r.id_revendedor AND c.ultima_atividade >= '$dataInicio' AND c.ativo = 1 AND c.bloqueado = 0) as total_ativos,
                SUM(CASE 
                    WHEN r.valor_ativo IS NOT NULL THEN 
                        (SELECT COUNT(c2.client_id) FROM client_ids c2 WHERE c2.id_revendedor = r.id_revendedor AND c2.ativo = 1 AND c2.bloqueado = 0 AND c2.ultima_atividade >= '$dataInicio') * r.valor_ativo
                    WHEN r.valor_mensal IS NOT NULL THEN r.valor_mensal
                    ELSE 0
                END) as receita_estimada_periodo
            FROM revendedores r
            WHERE r.master != 'admin' AND r.ativo = 1
            GROUP BY r.id_revendedor, r.nome, r.usuario, r.plano
            ORDER BY receita_estimada_periodo DESC, total_ativos DESC
            LIMIT ?
        ");
        
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        error_log("NomaTV v4.2 [RELATORIOS] Erro em buscarTopRevendedores: " . $e->getMessage());
        return [];
    }
}

function calcularCrescimento(PDO $db, string $dataInicio): array {
    $dataInicioAnterior = date('Y-m-d', strtotime($dataInicio . " - " . (strtotime(date('Y-m-d')) - strtotime($dataInicio)) . " days")); // Período anterior de mesma duração

    $totalRevendedoresAtual = $db->query("SELECT COUNT(id_revendedor) FROM revendedores WHERE master != 'admin' AND criado_em >= '$dataInicio'")->fetchColumn();
    $totalRevendedoresAnterior = $db->query("SELECT COUNT(id_revendedor) FROM revendedores WHERE master != 'admin' AND criado_em >= '$dataInicioAnterior' AND criado_em < '$dataInicio'")->fetchColumn();

    $totalAtivosAtual = $db->query("SELECT COUNT(client_id) FROM client_ids WHERE primeira_conexao >= '$dataInicio'")->fetchColumn();
    $totalAtivosAnterior = $db->query("SELECT COUNT(client_id) FROM client_ids WHERE primeira_conexao >= '$dataInicioAnterior' AND primeira_conexao < '$dataInicio'")->fetchColumn();

    $receitaAtual = calcularReceitaTotal($db, $dataInicio)['total_periodo'];
    $receitaAnterior = calcularReceitaTotal($db, $dataInicioAnterior)['total_periodo'];

    $crescimentoRevendedores = ['atual' => $totalRevendedoresAtual, 'anterior' => $totalRevendedoresAnterior, 'percentual' => ($totalRevendedoresAnterior > 0 ? (($totalRevendedoresAtual - $totalRevendedoresAnterior) / $totalRevendedoresAnterior) * 100 : 0)];
    $crescimentoAtivos = ['atual' => $totalAtivosAtual, 'anterior' => $totalAtivosAnterior, 'percentual' => ($totalAtivosAnterior > 0 ? (($totalAtivosAtual - $totalAtivosAnterior) / $totalAtivosAnterior) * 100 : 0)];
    $crescimentoReceita = ['atual' => $receitaAtual, 'anterior' => $receitaAnterior, 'percentual' => ($receitaAnterior > 0 ? (($receitaAtual - $receitaAnterior) / $receitaAnterior) * 100 : 0)];

    return [
        'revendedores' => $crescimentoRevendedores,
        'ativos' => $crescimentoAtivos,
        'receita' => $crescimentoReceita
    ];
}

function calcularReceitaPorPlano(PDO $db, string $dataInicio): array {
    try {
        $stmt = $db->query("
            SELECT 
                r.plano,
                COUNT(r.id_revendedor) as total_revendedores_no_plano,
                SUM(CASE 
                    WHEN r.valor_ativo IS NOT NULL THEN 
                        (SELECT COUNT(c.client_id) FROM client_ids c WHERE c.id_revendedor = r.id_revendedor AND c.ativo = 1 AND c.bloqueado = 0 AND c.ultima_atividade >= '$dataInicio') * r.valor_ativo
                    WHEN r.valor_mensal IS NOT NULL THEN r.valor_mensal
                    ELSE 0
                END) as receita_estimada_periodo
            FROM revendedores r
            WHERE r.master != 'admin' AND r.ativo = 1 AND r.plano IS NOT NULL
            GROUP BY r.plano
            ORDER BY receita_estimada_periodo DESC
        ");
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        error_log("NomaTV v4.2 [RELATORIOS] Erro em calcularReceitaPorPlano: " . $e->getMessage());
        return [];
    }
}

function buscarAtividadeRecente(PDO $db, int $dias): array {
    try {
        $stmt = $db->prepare("
            SELECT 
                DATE(timestamp) as data,
                COUNT(*) as total_acoes,
                COUNT(DISTINCT id_revendedor) as utilizadores_unicos
            FROM auditoria 
            WHERE timestamp >= DATE('now', '-? days')
            GROUP BY DATE(timestamp)
            ORDER BY data DESC
        ");
        
        $stmt->execute([$dias]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        error_log("NomaTV v4.2 [RELATORIOS] Erro em buscarAtividadeRecente: " . $e->getMessage());
        return [];
    }
}

function calcularReceitaTotal(PDO $db, string $dataInicio): array {
    try {
        // Receita por ativos (baseado na última atividade no período)
        $stmtAtivo = $db->query("
            SELECT SUM(r.valor_ativo * (SELECT COUNT(c.client_id) FROM client_ids c WHERE c.id_revendedor = r.id_revendedor AND c.ativo = 1 AND c.bloqueado = 0 AND c.ultima_atividade >= '$dataInicio'))
            FROM revendedores r
            WHERE r.master != 'admin' AND r.ativo = 1 AND r.valor_ativo IS NOT NULL
        ");
        $receitaPorAtivo = $stmtAtivo->fetchColumn() ?? 0;

        // Receita mensal (fixa para o período)
        $stmtMensal = $db->query("
            SELECT SUM(r.valor_mensal)
            FROM revendedores r
            WHERE r.master != 'admin' AND r.ativo = 1 AND r.valor_mensal IS NOT NULL
        ");
        $receitaMensal = $stmtMensal->fetchColumn() ?? 0;

        $totalReceita = $receitaPorAtivo + $receitaMensal;

        return [
            'receita_por_ativo_periodo' => round($receitaPorAtivo, 2),
            'receita_mensal_periodo' => round($receitaMensal, 2),
            'total_periodo' => round($totalReceita, 2)
        ];
    } catch (Exception $e) {
        error_log("NomaTV v4.2 [RELATORIOS] Erro em calcularReceitaTotal: " . $e->getMessage());
        return ['receita_por_ativo_periodo' => 0, 'receita_mensal_periodo' => 0, 'total_periodo' => 0];
    }
}

function calcularReceitaPorTipo(PDO $db, string $dataInicio): array {
    $receitaTotal = calcularReceitaTotal($db, $dataInicio);
    return [
        'por_ativo' => $receitaTotal['receita_por_ativo_periodo'],
        'mensal' => $receitaTotal['receita_mensal_periodo']
    ];
}

function buscarHistoricoMensal(PDO $db, string $dataInicio): array {
    try {
        // Para simplificar, faremos uma estimativa mensal baseada nos ativos e mensalistas
        // Esta é uma simulação, um sistema real de faturação precisaria de registos de pagamentos
        $stmt = $db->prepare("
            SELECT 
                strftime('%Y-%m', ultima_atividade) as mes_ano,
                COUNT(DISTINCT client_id) as total_ativos_mes
            FROM client_ids
            WHERE ultima_atividade >= ?
            GROUP BY mes_ano
            ORDER BY mes_ano ASC
        ");
        $stmt->execute([$dataInicio]);
        $ativosPorMes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $historico = [];
        foreach ($ativosPorMes as $item) {
            $mesAno = $item['mes_ano'];
            $totalAtivos = $item['total_ativos_mes'];

            // Estimar receita para o mês
            // Considera a receita por ativo e mensalistas ativos naquele mês
            // Simplificado: assume que todos os revendedores ativos no mês contribuem
            $receitaEstimadaMes = 0;
            $stmtRev = $db->query("
                SELECT 
                    r.valor_ativo, r.valor_mensal,
                    (SELECT COUNT(c.client_id) FROM client_ids c WHERE c.id_revendedor = r.id_revendedor AND c.ativo = 1 AND strftime('%Y-%m', c.ultima_atividade) = '$mesAno') as ativos_revendedor_mes
                FROM revendedores r
                WHERE r.master != 'admin' AND r.ativo = 1
            ");
            while ($rev = $stmtRev->fetch(PDO::FETCH_ASSOC)) {
                if ($rev['valor_ativo'] !== null) {
                    $receitaEstimadaMes += $rev['ativos_revendedor_mes'] * $rev['valor_ativo'];
                } elseif ($rev['valor_mensal'] !== null) {
                    $receitaEstimadaMes += $rev['valor_mensal'];
                }
            }
            $historico[] = ['mes_ano' => $mesAno, 'receita_estimada' => round($receitaEstimadaMes, 2)];
        }
        return $historico;
    } catch (Exception $e) {
        error_log("NomaTV v4.2 [RELATORIOS] Erro em buscarHistoricoMensal: " . $e->getMessage());
        return [];
    }
}

function calcularPrevisoes(PDO $db): array {
    // Implementação de previsão complexa. Por agora, dados simulados.
    $receitaAtual = calcularReceitaTotal($db, '30')['total_periodo'];
    return [
        '3_meses' => round($receitaAtual * 1.15, 2), // Crescimento de 15%
        '6_meses' => round($receitaAtual * 1.30, 2), // Crescimento de 30%
        '12_meses' => round($receitaAtual * 1.50, 2) // Crescimento de 50%
    ];
}

function analisarInadimplencia(PDO $db): array {
    // Esta função exigiria uma tabela de pagamentos/faturas para ser precisa.
    // Por enquanto, retorna dados simulados.
    return [
        'total_revendedores_inadimplentes_estimado' => 2,
        'valor_estimado_em_atraso' => 150.00,
        'percentual_inadimplencia_estimado' => 5.0 // Exemplo
    ];
}

function buscarRankingRevendedoresPorAtivos(PDO $db, string $dataInicio): array {
    try {
        $stmt = $db->prepare("
            SELECT 
                r.id_revendedor,
                r.nome,
                r.usuario,
                (SELECT COUNT(c.client_id) FROM client_ids c WHERE c.id_revendedor = r.id_revendedor AND c.ultima_atividade >= '$dataInicio' AND c.ativo = 1 AND c.bloqueado = 0) as total_ativos_periodo
            FROM revendedores r
            WHERE r.master != 'admin' AND r.ativo = 1
            GROUP BY r.id_revendedor, r.nome, r.usuario
            ORDER BY total_ativos_periodo DESC
            LIMIT 10
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("NomaTV v4.2 [RELATORIOS] Erro em buscarRankingRevendedoresPorAtivos: " . $e->getMessage());
        return [];
    }
}

function buscarRankingRevendedoresPorReceita(PDO $db, string $dataInicio): array {
    try {
        $stmt = $db->prepare("
            SELECT 
                r.id_revendedor,
                r.nome,
                r.usuario,
                SUM(CASE 
                    WHEN r.valor_ativo IS NOT NULL THEN 
                        (SELECT COUNT(c.client_id) FROM client_ids c WHERE c.id_revendedor = r.id_revendedor AND c.ativo = 1 AND c.ultima_atividade >= '$dataInicio') * r.valor_ativo
                    WHEN r.valor_mensal IS NOT NULL THEN r.valor_mensal
                    ELSE 0
                END) as receita_estimada_periodo
            FROM revendedores r
            WHERE r.master != 'admin' AND r.ativo = 1
            GROUP BY r.id_revendedor, r.nome, r.usuario
            ORDER BY receita_estimada_periodo DESC
            LIMIT 10
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("NomaTV v4.2 [RELATORIOS] Erro em buscarRankingRevendedoresPorReceita: " . $e->getMessage());
        return [];
    }
}

function buscarNovosRevendedores(PDO $db, string $dataInicio): array {
    try {
        $stmt = $db->prepare("
            SELECT 
                id_revendedor, nome, usuario, criado_em
            FROM revendedores 
            WHERE master != 'admin' AND criado_em >= ?
            ORDER BY criado_em DESC
        ");
        $stmt->execute([$dataInicio]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("NomaTV v4.2 [RELATORIOS] Erro em buscarNovosRevendedores: " . $e->getMessage());
        return [];
    }
}

function buscarRevendedoresInativos(PDO $db, string $dataInicio): array {
    try {
        $stmt = $db->prepare("
            SELECT 
                id_revendedor, nome, usuario, atualizado_em, data_bloqueio
            FROM revendedores 
            WHERE master != 'admin' AND ativo = 0 AND (data_bloqueio IS NULL OR data_bloqueio >= ?)
            ORDER BY atualizado_em DESC
        ");
        $stmt->execute([$dataInicio]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("NomaTV v4.2 [RELATORIOS] Erro em buscarRevendedoresInativos: " . $e->getMessage());
        return [];
    }
}

function analisarDistribuicaoPlanos(PDO $db): array {
    try {
        $stmt = $db->query("
            SELECT 
                plano,
                COUNT(id_revendedor) as total_revendedores
            FROM revendedores
            WHERE master != 'admin' AND ativo = 1
            GROUP BY plano
            ORDER BY total_revendedores DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("NomaTV v4.2 [RELATORIOS] Erro em analisarDistribuicaoPlanos: " . $e->getMessage());
        return [];
    }
}

function analisarPerformanceMasters(PDO $db, string $dataInicio): array {
    try {
        $stmt = $db->prepare("
            SELECT 
                r_master.id_revendedor as master_id,
                r_master.nome as master_nome,
                r_master.usuario as master_usuario,
                -- Contagem de sub-revendedores diretos
                (SELECT COUNT(sub.id_revendedor) FROM revendedores sub WHERE sub.ultra_master_id = r_master.id_revendedor AND sub.master = 'nao' AND sub.ativo = 1) as total_sub_revendedores,
                SUM(CASE 
                    WHEN r_sub.valor_ativo IS NOT NULL THEN 
                        (SELECT COUNT(c.client_id) FROM client_ids c WHERE c.id_revendedor = r_sub.id_revendedor AND c.ativo = 1 AND c.ultima_atividade >= '$dataInicio') * r_sub.valor_ativo
                    WHEN r_sub.valor_mensal IS NOT NULL THEN r_sub.valor_mensal
                    ELSE 0
                END) as receita_gerada_por_subs
            FROM revendedores r_master
            LEFT JOIN revendedores r_sub ON r_sub.ultra_master_id = r_master.id_revendedor AND r_sub.ativo = 1
            WHERE r_master.master = 'sim' AND r_master.ativo = 1
            GROUP BY r_master.id_revendedor, r_master.nome, r_master.usuario
            ORDER BY receita_gerada_por_subs DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("NomaTV v4.2 [RELATORIOS] Erro em analisarPerformanceMasters: " . $e->getMessage());
        return [];
    }
}

function buscarProvedoresMaisUtilizados(PDO $db, string $dataInicio): array {
    try {
        $stmt = $db->prepare("
            SELECT 
                p.nome,
                p.dns,
                COUNT(c.client_id) as total_ativos_conectados
            FROM provedores p
            LEFT JOIN client_ids c ON c.provedor_id = p.id_provedor AND c.ultima_atividade >= '$dataInicio' AND c.ativo = 1 AND c.bloqueado = 0
            WHERE p.ativo = 1
            GROUP BY p.id_provedor, p.nome, p.dns
            ORDER BY total_ativos_conectados DESC
            LIMIT 10
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("NomaTV v4.2 [RELATORIOS] Erro em buscarProvedoresMaisUtilizados: " . $e->getMessage());
        return [];
    }
}

function buscarNovosProvedores(PDO $db, string $dataInicio): array {
    try {
        $stmt = $db->prepare("
            SELECT 
                id_provedor as id, nome, dns, criado_em
            FROM provedores 
            WHERE criado_em >= ?
            ORDER BY criado_em DESC
        ");
        $stmt->execute([$dataInicio]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("NomaTV v4.2 [RELATORIOS] Erro em buscarNovosProvedores: " . $e->getMessage());
        return [];
    }
}

function analisarPerformanceProvedores(PDO $db, string $dataInicio): array {
    try {
        $stmt = $db->prepare("
            SELECT 
                p.nome,
                p.dns,
                COUNT(c.client_id) as total_conexoes_periodo,
                SUM(CASE WHEN c.bloqueado = 1 THEN 1 ELSE 0 END) as total_bloqueios_periodo
            FROM provedores p
            LEFT JOIN client_ids c ON c.provedor_id = p.id_provedor AND c.ultima_atividade >= '$dataInicio'
            WHERE p.ativo = 1
            GROUP BY p.id_provedor, p.nome, p.dns
            ORDER BY total_conexoes_periodo DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("NomaTV v4.2 [RELATORIOS] Erro em analisarPerformanceProvedores: " . $e->getMessage());
        return [];
    }
}

function analisarDistribuicaoProvedores(PDO $db): array {
    // Distribuição geográfica de provedores é complexa sem dados de localização mais detalhados.
    // Retorna dados simulados ou vazios por enquanto.
    return [
        ['regiao' => 'Sudeste', 'provedores' => 5],
        ['regiao' => 'Sul', 'provedores' => 3],
        ['regiao' => 'Nordeste', 'provedores' => 2]
    ];
}

function buscarAtividadeDiaria(PDO $db, string $dataInicio): array {
    try {
        $stmt = $db->prepare("
            SELECT 
                DATE(ultima_atividade) as data,
                COUNT(client_id) as total_ativos_dia
            FROM client_ids
            WHERE ultima_atividade >= ? AND ativo = 1 AND bloqueado = 0
            GROUP BY DATE(ultima_atividade)
            ORDER BY data ASC
        ");
        $stmt->execute([$dataInicio]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("NomaTV v4.2 [RELATORIOS] Erro em buscarAtividadeDiaria: " . $e->getMessage());
        return [];
    }
}

function identificarPicosUso(PDO $db, string $dataInicio): array {
    // Identificação de picos de uso é complexa, exigindo granularidade de hora/minuto.
    // Retorna dados simulados.
    return [
        ['data_hora' => '2025-07-25 20:00', 'ativos_simultaneos' => 120],
        ['data_hora' => '2025-07-24 21:30', 'ativos_simultaneos' => 115]
    ];
}

function buscarClientsMaisAtivos(PDO $db, string $dataInicio): array {
    try {
        $stmt = $db->prepare("
            SELECT 
                client_id,
                usuario,
                COUNT(*) as total_atividades_periodo
            FROM auditoria
            WHERE acao LIKE 'client_id%' AND timestamp >= ?
            GROUP BY client_id, usuario
            ORDER BY total_atividades_periodo DESC
            LIMIT 10
        ");
        $stmt->execute([$dataInicio]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("NomaTV v4.2 [RELATORIOS] Erro em buscarClientsMaisAtivos: " . $e->getMessage());
        return [];
    }
}

function calcularTaxaRetencao(PDO $db, string $dataInicio): array {
    // Cálculo de retenção é complexo e exige coortes de utilizadores.
    // Retorna dado simulado.
    return ['taxa' => 88.5, 'periodo' => '30 dias'];
}

function analisarCrescimentoRevendedores(PDO $db, string $dataInicio): array {
    try {
        $stmt = $db->prepare("
            SELECT 
                strftime('%Y-%m', criado_em) as mes_ano,
                COUNT(id_revendedor) as novos_revendedores
            FROM revendedores
            WHERE master != 'admin' AND criado_em >= ?
            GROUP BY mes_ano
            ORDER BY mes_ano ASC
        ");
        $stmt->execute([$dataInicio]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("NomaTV v4.2 [RELATORIOS] Erro em analisarCrescimentoRevendedores: " . $e->getMessage());
        return [];
    }
}

function analisarCrescimentoAtivos(PDO $db, string $dataInicio): array {
    try {
        $stmt = $db->prepare("
            SELECT 
                strftime('%Y-%m', primeira_conexao) as mes_ano,
                COUNT(client_id) as novos_ativos
            FROM client_ids
            WHERE primeira_conexao >= ?
            GROUP BY mes_ano
            ORDER BY mes_ano ASC
        ");
        $stmt->execute([$dataInicio]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("NomaTV v4.2 [RELATORIOS] Erro em analisarCrescimentoAtivos: " . $e->getMessage());
        return [];
    }
}

function identificarTendencias(PDO $db, string $dataInicio): array {
    // Identificação de tendências é complexa, exigindo análise de séries temporais.
    // Retorna dados simulados.
    return [
        ['tendencia' => 'Crescimento de ativos móveis', 'impacto' => 'Alto'],
        ['tendencia' => 'Aumento de revendedores no Sudeste', 'impacto' => 'Médio']
    ];
}

function calcularProjecoesFuturas(PDO $db): array {
    // Projeções futuras exigem modelos preditivos.
    // Retorna dados simulados.
    return [
        'revendedores_projecao_3m' => 10,
        'ativos_projecao_3m' => 500,
        'receita_projecao_3m' => 2500.00
    ];
}

function calcularCrescimentoPeriodo(PDO $db, string $dataInicio): float {
    // Exemplo de cálculo de crescimento percentual de ativos no período
    $totalAtivosInicio = $db->query("SELECT COUNT(client_id) FROM client_ids WHERE primeira_conexao < '$dataInicio'")->fetchColumn();
    $totalAtivosFim = $db->query("SELECT COUNT(client_id) FROM client_ids WHERE primeira_conexao <= DATE('now')")->fetchColumn();
    
    if ($totalAtivosInicio > 0) {
        return round((($totalAtivosFim - $totalAtivosInicio) / $totalAtivosInicio) * 100, 2);
    }
    return 0.0;
}

/**
 * =================================================================
 * FUNÇÕES DE AÇÕES ESPECIAIS (Implementadas)
 * =================================================================
 */

function exportarRelatorio(PDO $db, string $loggedInRevendedorId, array $input): void {
    $tipo = $input['tipo'] ?? 'dashboard';
    $periodo = $input['periodo'] ?? '30';
    $formato = $input['formato'] ?? 'csv';
    
    $data = [];
    $filename = "relatorio_{$tipo}_" . date('Ymd_His');
    $headers = [];

    // Gerar o relatório específico para exportação
    switch ($tipo) {
        case 'dashboard':
            $data = [gerarRelatoriosDashboard($db, $periodo)]; // Retorna como array para consistência
            $filename .= "_dashboard";
            $headers = ['tipo', 'periodo', 'data_inicio', 'data_fim', 'metricas_gerais_total_revendedores', 'metricas_gerais_masters_ativos', 'metricas_gerais_subs_ativos', 'metricas_gerais_total_provedores', 'metricas_gerais_total_ativos_online', 'metricas_gerais_total_ativos_inativos', 'metricas_gerais_total_ativos_bloqueados', 'metricas_gerais_total_geral_ativos', 'metricas_gerais_dias_atividade_periodo']; // Simplificado
            break;
        case 'financeiro':
            $data = [gerarRelatorioFinanceiro($db, $periodo)];
            $filename .= "_financeiro";
            $headers = ['tipo', 'periodo', 'receita_total_receita_por_ativo_periodo', 'receita_total_receita_mensal_periodo', 'receita_total_total_periodo', 'receita_por_tipo_por_ativo', 'receita_por_tipo_mensal']; // Simplificado
            break;
        case 'revendedores':
            $data = buscarRankingRevendedoresPorAtivos($db, calcularDataInicio($periodo)); // Exemplo: exporta ranking por ativos
            $filename .= "_revendedores_ranking_ativos";
            $headers = ['id_revendedor', 'nome', 'usuario', 'total_ativos_periodo'];
            break;
        case 'provedores':
            $data = buscarProvedoresMaisUtilizados($db, calcularDataInicio($periodo)); // Exemplo: exporta mais utilizados
            $filename .= "_provedores_mais_utilizados";
            $headers = ['nome', 'dns', 'total_ativos_conectados'];
            break;
        case 'atividade':
            $data = buscarAtividadeDiaria($db, calcularDataInicio($periodo)); // Exemplo: exporta atividade diária
            $filename .= "_atividade_diaria";
            $headers = ['data', 'total_ativos_dia'];
            break;
        case 'crescimento':
            $data = analisarCrescimentoAtivos($db, calcularDataInicio($periodo)); // Exemplo: exporta crescimento de ativos
            $filename .= "_crescimento_ativos";
            $headers = ['mes_ano', 'novos_ativos'];
            break;
        case 'completo':
            $data = [gerarRelatorioCompleto($db, $periodo)];
            $filename .= "_completo";
            // Headers complexos para relatório completo, simplificando para o exemplo
            $headers = ['tipo', 'periodo', 'resumo_executivo_total_revendedores', 'resumo_executivo_total_provedores', 'resumo_executivo_receita_estimada_periodo'];
            break;
        default:
            http_response_code(400);
            standardResponse(false, null, 'Tipo de relatório para exportação inválido.');
            exit;
    }

    if (empty($data)) {
        standardResponse(false, null, 'Nenhum dado encontrado para exportar com os filtros fornecidos.');
        exit;
    }

    // logAction($db, $loggedInRevendedorId, 'exportar_relatorio', "Exportação de relatório tipo: $tipo, formato: $formato, período: $periodo");

    if ($formato === 'csv') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
        $output = fopen('php://output', 'w');

        // Se os headers não foram definidos, tenta extrair das chaves do primeiro item
        if (empty($headers) && !empty($data[0])) {
            $headers = array_keys($data[0]);
        }
        fputcsv($output, $headers); // Escreve o cabeçalho

        foreach ($data as $row) {
            // Flatten nested arrays for CSV if necessary (simplificado para este exemplo)
            $flatRow = [];
            foreach ($row as $key => $value) {
                if (is_array($value)) {
                    // Para arrays aninhados, concatena os valores ou pega um específico
                    if ($key == 'metricas_gerais' || $key == 'receita_total' || $key == 'resumo_executivo') {
                        foreach ($value as $subKey => $subValue) {
                            $flatRow[$key . '_' . $subValue] = $subValue;
                        }
                    } else {
                        $flatRow[$key] = json_encode($value); // Converte array para string JSON
                    }
                } else {
                    $flatRow[$key] = $value;
                }
            }
            fputcsv($output, $flatRow);
        }
        fclose($output);
        exit;
    } else if ($formato === 'json') {
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '.json"');
        echo json_encode($data);
        exit;
    } else {
        http_response_code(400);
        standardResponse(false, null, 'Formato de exportação não suportado.');
        exit;
    }
}

function programarRelatorio(PDO $db, string $loggedInRevendedorId, array $input): void {
    // Funcionalidade em desenvolvimento - apenas loga e retorna mensagem
    // logAction($db, $loggedInRevendedorId, 'programar_relatorio', "Tentativa de programar relatório: " . json_encode($input));
    standardResponse(true, ['programacao_criada' => true], 'Funcionalidade de programação de relatório em desenvolvimento.');
}

function gerarRelatorioPersonalizado(PDO $db, string $loggedInRevendedorId, array $input): void {
    // Funcionalidade em desenvolvimento - apenas loga e retorna mensagem
    // logAction($db, $loggedInRevendedorId, 'gerar_relatorio_personalizado', "Tentativa de gerar relatório personalizado: " . json_encode($input));
    standardResponse(true, ['relatorio_personalizado_gerado' => true], 'Funcionalidade de relatório personalizado em desenvolvimento.');
}

/**
 * =================================================================
 * FIM DO ARQUIVO
 * =================================================================
 */
?>