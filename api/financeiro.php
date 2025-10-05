<?php
/**
 * ENDPOINT DE DADOS FINANCEIROS - NomaTV API v4.5
 * RESPONSABILIDADES:
 * ✅ Dashboard financeiro completo baseado em hierarquia parent_id
 * ✅ Cálculo automático de receitas baseado na estrutura faturas/pagamentos
 * ✅ Suporte a cobrança por ativo (valor_ativo) e mensal (valor_mensal)
 * ✅ Processamento de cobranças automatizadas (geração de faturas)
 * ✅ Marcação de pagamentos recebidos com atualização de faturas
 * ✅ Controle de inadimplência com lógica de bloqueio baseada em faturas
 * ✅ Projeções e análises financeiras detalhadas
 * ✅ Filtros automáticos baseados na rede completa do revendedor
 */

ini_set('display_errors', 0);
error_reporting(E_ALL);

// Headers CORS
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/config/database_sqlite.php';

// ✅ FUNÇÃO RESPOSTA PADRONIZADA
function standardResponse(bool $success, $data = null, $message = null, $extraData = null): void
{
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'message' => $message,
        'extraData' => $extraData
    ]);
    exit();
}

require_once __DIR__ . '/config/session.php';

// ✅ AUTENTICAÇÃO USANDO SESSION COMUM
$user = verificarAutenticacao();
if (!$user) {
    respostaNaoAutenticado();
}
$loggedInRevendedorId = $user['id'];
$loggedInUserType = $user['master'];

// ✅ ROTEAMENTO PRINCIPAL
$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true) ?? [];

try {
    switch ($method) {
        case 'GET':
            handleGetFinanceiro($db, $loggedInRevendedorId, $loggedInUserType);
            break;
        case 'POST':
            handlePostFinanceiro($db, $loggedInRevendedorId, $loggedInUserType, $input);
            break;
        default:
            http_response_code(405);
            standardResponse(false, null, 'Método não permitido.');
    }
} catch (Exception $e) {
    error_log("NomaTV v4.5 [FINANCEIRO] Erro: " . $e->getMessage());
    http_response_code(500);
    standardResponse(false, null, 'Erro interno do servidor.');
}

/**
 * =================================================================
 * HANDLERS PRINCIPAIS
 * =================================================================
 */

/**
 * Handler para requisições GET - Dashboard Financeiro
 */
function handleGetFinanceiro(PDO $db, string $loggedInRevendedorId, string $loggedInUserType): void
{
    try {
        // Buscar dados financeiros com filtros hierárquicos
        $dadosFinanceiros = buscarDadosFinanceiros($db, $loggedInRevendedorId, $loggedInUserType);

        // Calcular estatísticas gerais
        $estatisticasGerais = calcularEstatisticasGerais($db, $dadosFinanceiros);

        // Gerar histórico de cobranças
        $historicoCobrancas = gerarHistoricoCobrancas($dadosFinanceiros);

        // Análises avançadas
        $analises = gerarAnalises($db, $dadosFinanceiros);

        // Resumo do período
        $resumoPeriodo = gerarResumoPeriodo($dadosFinanceiros);

        // Projeções
        $projecoes = gerarProjecoes($dadosFinanceiros);

        // Resposta consolidada
        $responseData = [
            'stats' => $estatisticasGerais,
            'historico' => $historicoCobrancas,
            'analises' => $analises,
            'resumo_periodo' => $resumoPeriodo,
            'projecoes' => $projecoes,
            'timestamp' => date('c')
        ];

        standardResponse(true, $responseData, 'Dados financeiros carregados com sucesso.');
    } catch (Exception $e) {
        error_log("NomaTV v4.5 [FINANCEIRO] Erro em handleGetFinanceiro: " . $e->getMessage());
        http_response_code(500);
        standardResponse(false, null, 'Erro ao buscar dados financeiros.');
    }
}

/**
 * Handler para requisições POST - Ações financeiras
 */
function handlePostFinanceiro(PDO $db, string $loggedInRevendedorId, string $loggedInUserType, array $input): void
{
    $action = $input['action'] ?? '';

    try {
        switch ($action) {
            case 'processar_cobranca':
                processarCobrancaGeral($db, $loggedInRevendedorId, $loggedInUserType);
                break;

            case 'marcar_pago':
                marcarPagamentoRecebido($db, $loggedInRevendedorId, $loggedInUserType, $input);
                break;

            case 'bloquear_vencidos':
                bloquearRevendedoresVencidos($db, $loggedInRevendedorId, $loggedInUserType);
                break;

            case 'atualizar_vencimento':
                atualizarVencimentoRevendedor($db, $loggedInRevendedorId, $loggedInUserType, $input);
                break;

            case 'gerar_fatura_manual':
                gerarFaturaManual($db, $loggedInRevendedorId, $loggedInUserType, $input);
                break;

            default:
                http_response_code(400);
                standardResponse(false, null, 'Ação financeira inválida.');
        }
    } catch (Exception $e) {
        error_log("NomaTV v4.5 [FINANCEIRO] Erro em handlePostFinanceiro: " . $e->getMessage());
        http_response_code(500);
        standardResponse(false, null, 'Erro ao processar ação financeira.');
    }
}

/**
 * =================================================================
 * BUSCA RECURSIVA PADRÃO
 * =================================================================
 */

/**
 * Busca toda a rede descendente de um revendedor (recursiva)
 */
function buscarRedeCompleta(PDO $db, string $idRevendedor): array
{
    $idsParaBuscar = [$idRevendedor];
    $todosDescendentes = [];
    $indice = 0;
    
    while ($indice < count($idsParaBuscar)) {
        $idAtual = $idsParaBuscar[$indice];
        
        $stmt = $db->prepare("
            SELECT id_revendedor
            FROM revendedores 
            WHERE parent_id = ? AND ativo = 1
        ");
        $stmt->execute([$idAtual]);
        $filhos = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (!empty($filhos)) {
            $todosDescendentes = array_merge($todosDescendentes, $filhos);
            $idsParaBuscar = array_merge($idsParaBuscar, $filhos);
        }
        
        $indice++;
    }
    
    return array_diff($todosDescendentes, [$idRevendedor]);
}

/**
 * =================================================================
 * FUNÇÕES DE DADOS FINANCEIROS COM FILTROS HIERÁRQUICOS
 * =================================================================
 */

/**
 * Busca dados financeiros com filtros hierárquicos baseados na rede
 */
function buscarDadosFinanceiros(PDO $db, string $loggedInRevendedorId, string $loggedInUserType): array
{
    try {
        $whereConditions = ["r.master != 'admin'"];
        $queryParams = [];

        // ✅ APLICAR FILTROS HIERÁRQUICOS
        if ($loggedInUserType === "admin") {
            // Admin vê todos os dados
            // Sem filtros adicionais
            
        } elseif ($loggedInUserType === "sim") {
            // Revendedor Master vê toda sua rede descendente
            $redeCompleta = buscarRedeCompleta($db, $loggedInRevendedorId);
            $idsPermitidos = array_merge([$loggedInRevendedorId], $redeCompleta);
            
            if (!empty($idsPermitidos)) {
                $placeholders = implode(',', array_fill(0, count($idsPermitidos), '?'));
                $whereConditions[] = "r.id_revendedor IN ($placeholders)";
                $queryParams = array_merge($queryParams, $idsPermitidos);
            }
            
        } else {
            // Sub-revendedor vê apenas seus próprios dados
            $whereConditions[] = "r.id_revendedor = ?";
            $queryParams[] = $loggedInRevendedorId;
        }

        $whereClause = implode(' AND ', $whereConditions);

        $stmt = $db->prepare("
            SELECT
                r.id_revendedor,
                r.usuario,
                r.nome,
                r.email,
                r.master,
                r.valor_ativo,
                r.valor_mensal,
                r.limite_ativos,
                r.ativo as revendedor_ativo,
                r.data_vencimento,
                r.criado_em,
                r.data_bloqueio,

                (SELECT COUNT(c.client_id) FROM client_ids c
                 WHERE c.id_revendedor = r.id_revendedor AND c.ativo = 1 AND c.bloqueado = 0) as ativos_online_count,

                (SELECT COUNT(c.client_id) FROM client_ids c
                 WHERE c.id_revendedor = r.id_revendedor) as ativos_total_count,

                (SELECT COUNT(p.id_provedor) FROM provedores p
                 WHERE p.id_revendedor = r.id_revendedor AND p.ativo = 1) as provedores_count,

                (SELECT MAX(c.ultima_atividade) FROM client_ids c
                 WHERE c.id_revendedor = r.id_revendedor) as ultima_atividade,

                -- Contagem de sub-revendedores baseada na hierarquia parent_id
                (SELECT COUNT(sub.id_revendedor) FROM revendedores sub
                 WHERE sub.parent_id = r.id_revendedor AND sub.ativo = 1) as subs_count,
                
                -- Buscar a fatura mais recente pendente/vencida
                (SELECT id_fatura FROM faturas
                 WHERE id_revendedor = r.id_revendedor
                 AND status IN ('pendente', 'vencida', 'parcialmente_paga')
                 ORDER BY data_vencimento ASC, data_emissao DESC
                 LIMIT 1) as fatura_pendente_id,

                (SELECT status FROM faturas
                 WHERE id_revendedor = r.id_revendedor
                 AND status IN ('pendente', 'vencida', 'parcialmente_paga')
                 ORDER BY data_vencimento ASC, data_emissao DESC
                 LIMIT 1) as fatura_pendente_status,

                (SELECT data_vencimento FROM faturas
                 WHERE id_revendedor = r.id_revendedor
                 AND status IN ('pendente', 'vencida', 'parcialmente_paga')
                 ORDER BY data_vencimento ASC, data_emissao DESC
                 LIMIT 1) as fatura_pendente_data_vencimento,

                (SELECT valor_total FROM faturas
                 WHERE id_revendedor = r.id_revendedor
                 AND status IN ('pendente', 'vencida', 'parcialmente_paga')
                 ORDER BY data_vencimento ASC, data_emissao DESC
                 LIMIT 1) as fatura_pendente_valor

            FROM revendedores r
            WHERE $whereClause
            ORDER BY r.nome ASC
        ");

        $stmt->execute($queryParams);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("NomaTV v4.5 [FINANCEIRO] Erro em buscarDadosFinanceiros: " . $e->getMessage());
        return [];
    }
}

/**
 * Calcula estatísticas gerais do sistema financeiro
 */
function calcularEstatisticasGerais(PDO $db, array $dadosFinanceiros): array
{
    $receitaTotalMensalPrevista = 0;
    $totalRevendedoresAtivos = 0;
    $totalAtivosCobraveis = 0;
    $revendedoresPorAtivo = 0;
    $revendedoresMensalistas = 0;
    $revendedoresVencidos = 0;
    $revendedoresProximoVencimento = 0;

    foreach ($dadosFinanceiros as $revendedor) {
        $statusPagamento = determinarStatusPagamentoFatura($revendedor['fatura_pendente_status'], $revendedor['fatura_pendente_data_vencimento']);

        if (!(bool)$revendedor['revendedor_ativo']) {
            $revendedoresVencidos++;
            continue;
        }

        $totalRevendedoresAtivos++;
        $ativosCount = (int)$revendedor['ativos_online_count'];

        if ($revendedor['valor_ativo'] !== null) {
            $revendedoresPorAtivo++;
            $valorCalculado = $ativosCount * (float)$revendedor['valor_ativo'];
            $totalAtivosCobraveis += $ativosCount;
        } elseif ($revendedor['valor_mensal'] !== null) {
            $revendedoresMensalistas++;
            $valorCalculado = (float)$revendedor['valor_mensal'];
        } else {
            $valorCalculado = 0;
        }

        $receitaTotalMensalPrevista += $valorCalculado;

        if ($statusPagamento === 'vencido') {
            $revendedoresVencidos++;
        } elseif ($statusPagamento === 'proximo_vencimento') {
            $revendedoresProximoVencimento++;
        }
    }

    $ticketMedio = $totalRevendedoresAtivos > 0 ?
        round($receitaTotalMensalPrevista / $totalRevendedoresAtivos, 2) : 0;

    $projecaoAnual = $receitaTotalMensalPrevista * 12;
    $crescimentoMensal = 5.0;

    return [
        'receita_mensal_prevista' => round($receitaTotalMensalPrevista, 2),
        'total_revendedores_ativos' => $totalRevendedoresAtivos,
        'total_ativos_cobraveis' => $totalAtivosCobraveis,
        'revendedores_por_ativo' => $revendedoresPorAtivo,
        'revendedores_mensalistas' => $revendedoresMensalistas,
        'ticket_medio' => $ticketMedio,
        'crescimento_mensal' => $crescimentoMensal,
        'projecao_anual' => round($projecaoAnual, 2),
        'revendedores_vencidos' => $revendedoresVencidos,
        'revendedores_proximo_vencimento' => $revendedoresProximoVencimento
    ];
}

/**
 * Gera histórico de cobranças detalhado baseado em faturas
 */
function gerarHistoricoCobrancas(array $dadosFinanceiros): array
{
    $historico = [];

    foreach ($dadosFinanceiros as $revendedor) {
        $ativosOnlineCount = (int)$revendedor['ativos_online_count'];
        $ativosTotalCount = (int)$revendedor['ativos_total_count'];
        $provedoresCount = (int)$revendedor['provedores_count'];

        // Determinar tipo de cobrança e calcular valor
        $tipoCobranca = 'indefinido';
        $valorUnitario = 0;
        $valorCalculado = 0;
        if ($revendedor['valor_ativo'] !== null) {
            $tipoCobranca = 'por_ativo';
            $valorUnitario = (float)$revendedor['valor_ativo'];
            $valorCalculado = $ativosOnlineCount * $valorUnitario;
        } elseif ($revendedor['valor_mensal'] !== null) {
            $tipoCobranca = 'mensal';
            $valorUnitario = (float)$revendedor['valor_mensal'];
            $valorCalculado = $valorUnitario;
        }

        // Status de pagamento baseado na fatura mais recente
        $statusPagamento = determinarStatusPagamentoFatura($revendedor['fatura_pendente_status'], $revendedor['fatura_pendente_data_vencimento']);
        $diasParaVencimento = calcularDiasParaVencimentoFatura($revendedor['fatura_pendente_data_vencimento']);
        $diasDesdeUltimaAtividade = calcularDiasDesdeUltimaAtividade($revendedor['ultima_atividade']);

        $historico[] = [
            'id_revendedor' => $revendedor['id_revendedor'],
            'revendedor_nome' => $revendedor['nome'],
            'revendedor_usuario' => $revendedor['usuario'],
            'revendedor_email' => $revendedor['email'],
            'tipo_cobranca_configurada' => $tipoCobranca,
            'valor_cobranca_unitario_configurado' => round($valorUnitario, 2),
            'ativos_online_count' => $ativosOnlineCount,
            'ativos_total_count' => $ativosTotalCount,
            'provedores_count' => $provedoresCount,
            'subs_count' => (int)$revendedor['subs_count'],
            'valor_calculado_atual' => round($valorCalculado, 2),
            'fatura_pendente_id' => $revendedor['fatura_pendente_id'],
            'fatura_pendente_status' => $revendedor['fatura_pendente_status'],
            'fatura_pendente_valor' => round((float)$revendedor['fatura_pendente_valor'], 2),
            'fatura_pendente_data_vencimento' => $revendedor['fatura_pendente_data_vencimento'],
            'status_pagamento_consolidado' => $statusPagamento,
            'dias_para_vencimento_consolidado' => $diasParaVencimento,
            'ultima_atividade' => $revendedor['ultima_atividade'],
            'dias_desde_ultima_atividade' => $diasDesdeUltimaAtividade,
            'data_cadastro' => $revendedor['criado_em'],
            'revendedor_ativo_status' => (bool)$revendedor['revendedor_ativo'],
            'limite_ativos' => (int)$revendedor['limite_ativos'],
            'uso_limite_percentual' => $revendedor['limite_ativos'] > 0 ?
                round(($ativosTotalCount / $revendedor['limite_ativos']) * 100, 1) : 0
        ];
    }

    // Ordenar por status de pagamento e valor
    usort($historico, function ($a, $b) {
        $order = ['vencido' => 1, 'proximo_vencimento' => 2, 'pendente' => 3, 'parcialmente_paga' => 4, 'em_dia' => 5, 'paga' => 6, 'indefinido' => 7, 'erro' => 8];
        if ($order[$a['status_pagamento_consolidado']] !== $order[$b['status_pagamento_consolidado']]) {
            return $order[$a['status_pagamento_consolidado']] <=> $order[$b['status_pagamento_consolidado']];
        }
        return $b['valor_calculado_atual'] <=> $a['valor_calculado_atual'];
    });

    return $historico;
}

/**
 * =================================================================
 * AÇÕES FINANCEIRAS COM VERIFICAÇÃO DE PERMISSÕES
 * =================================================================
 */

/**
 * Processa cobrança geral do sistema, gerando faturas
 */
function processarCobrancaGeral(PDO $db, string $loggedInRevendedorId, string $loggedInUserType): void
{
    // ✅ VERIFICAR PERMISSÕES
    if ($loggedInUserType !== 'admin' && $loggedInUserType !== 'sim') {
        http_response_code(403);
        standardResponse(false, null, 'Sem permissão para processar cobranças.');
    }

    try {
        $db->beginTransaction();

        $whereConditions = ["ativo = 1", "master != 'admin'", "(valor_ativo IS NOT NULL OR valor_mensal IS NOT NULL)"];
        $queryParams = [];

        // ✅ FILTROS HIERÁRQUICOS PARA COBRANÇA
        if ($loggedInUserType === "sim") {
            // Master só pode processar cobrança de sua rede
            $redeCompleta = buscarRedeCompleta($db, $loggedInRevendedorId);
            $idsPermitidos = array_merge([$loggedInRevendedorId], $redeCompleta);
            
            if (!empty($idsPermitidos)) {
                $placeholders = implode(',', array_fill(0, count($idsPermitidos), '?'));
                $whereConditions[] = "id_revendedor IN ($placeholders)";
                $queryParams = array_merge($queryParams, $idsPermitidos);
            }
        }

        $whereClause = implode(' AND ', $whereConditions);

        $stmt = $db->prepare("
            SELECT id_revendedor, nome, valor_ativo, valor_mensal, data_vencimento
            FROM revendedores
            WHERE $whereClause
        ");
        $stmt->execute($queryParams);

        $revendedores = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $faturasGeradas = 0;
        $valorTotalGerado = 0;

        foreach ($revendedores as $revendedor) {
            // Verificar se já existe uma fatura pendente
            $stmtFaturaExistente = $db->prepare("
                SELECT id_fatura FROM faturas
                WHERE id_revendedor = ? AND status IN ('pendente', 'vencida', 'parcialmente_paga')
                ORDER BY data_vencimento DESC LIMIT 1
            ");
            $stmtFaturaExistente->execute([$revendedor['id_revendedor']]);

            if ($stmtFaturaExistente->fetch()) {
                continue; // Já existe fatura pendente
            }

            $valorCobranca = 0;
            $ativosNoPeriodo = 0;
            $tipoCobranca = 'indefinido';

            if ($revendedor['valor_ativo'] !== null) {
                $ativosNoPeriodo = contarClientIds($db, $revendedor['id_revendedor']);
                $valorCobranca = $ativosNoPeriodo * (float)$revendedor['valor_ativo'];
                $tipoCobranca = 'por_ativo';
            } elseif ($revendedor['valor_mensal'] !== null) {
                $valorCobranca = (float)$revendedor['valor_mensal'];
                $tipoCobranca = 'mensal';
            }

            $dataVencimentoFatura = date('Y-m-d', strtotime('+30 days'));

            $stmtInsertFatura = $db->prepare("
                INSERT INTO faturas (id_revendedor, data_emissao, data_vencimento, valor_total, tipo_cobranca, status, ativos_no_periodo, id_revendedor_criador_fatura)
                VALUES (?, CURRENT_DATE, ?, ?, ?, 'pendente', ?, ?)
            ");
            $stmtInsertFatura->execute([
                $revendedor['id_revendedor'],
                $dataVencimentoFatura,
                $valorCobranca,
                $tipoCobranca,
                $ativosNoPeriodo,
                $loggedInRevendedorId
            ]);

            $faturasGeradas++;
            $valorTotalGerado += $valorCobranca;
        }

        $db->commit();

        standardResponse(true, [
            'faturas_geradas' => $faturasGeradas,
            'valor_total_gerado' => round($valorTotalGerado, 2),
            'data_processamento' => date('Y-m-d H:i:s')
        ], 'Processamento de cobrança concluído com sucesso.');
    } catch (Exception $e) {
        $db->rollBack();
        error_log("NomaTV v4.5 [FINANCEIRO] Erro em processarCobrancaGeral: " . $e->getMessage());
        http_response_code(500);
        standardResponse(false, null, 'Erro ao processar cobrança.');
    }
}

/**
 * Gera uma fatura manualmente para um revendedor
 */
function gerarFaturaManual(PDO $db, string $loggedInRevendedorId, string $loggedInUserType, array $input): void
{
    $idRevendedor = $input['id_revendedor'] ?? null;
    $valorTotal = $input['valor_total'] ?? null;
    $dataVencimento = $input['data_vencimento'] ?? null;
    $observacoes = $input['observacoes'] ?? '';
    $tipoCobranca = $input['tipo_cobranca'] ?? 'manual';

    if (empty($idRevendedor) || !is_numeric($valorTotal) || empty($dataVencimento)) {
        http_response_code(400);
        standardResponse(false, null, 'Dados incompletos para gerar fatura manual.');
    }

    // ✅ VERIFICAR PERMISSÕES HIERÁRQUICAS
    if (!verificarPermissaoRevendedor($db, $loggedInRevendedorId, $loggedInUserType, $idRevendedor)) {
        http_response_code(403);
        standardResponse(false, null, 'Sem permissão para gerar fatura para este revendedor.');
    }

    try {
        $stmtRev = $db->prepare("SELECT nome FROM revendedores WHERE id_revendedor = ?");
        $stmtRev->execute([$idRevendedor]);
        $revendedor = $stmtRev->fetch();
        if (!$revendedor) {
            http_response_code(404);
            standardResponse(false, null, 'Revendedor não encontrado.');
        }

        $ativosNoPeriodo = 0;
        if ($tipoCobranca === 'por_ativo') {
            $ativosNoPeriodo = contarClientIds($db, $idRevendedor);
        }

        $stmtInsertFatura = $db->prepare("
            INSERT INTO faturas (id_revendedor, data_emissao, data_vencimento, valor_total, tipo_cobranca, status, ativos_no_periodo, observacoes, id_revendedor_criador_fatura)
            VALUES (?, CURRENT_DATE, ?, ?, ?, 'pendente', ?, ?, ?)
        ");
        $stmtInsertFatura->execute([
            $idRevendedor,
            $dataVencimento,
            $valorTotal,
            $tipoCobranca,
            $ativosNoPeriodo,
            $observacoes,
            $loggedInRevendedorId
        ]);

        $idFatura = $db->lastInsertId();

        standardResponse(true, ['id_fatura' => $idFatura], 'Fatura manual gerada com sucesso.');
    } catch (Exception $e) {
        error_log("NomaTV v4.5 [FINANCEIRO] Erro em gerarFaturaManual: " . $e->getMessage());
        http_response_code(500);
        standardResponse(false, null, 'Erro ao gerar fatura manual.');
    }
}

/**
 * Marca pagamento como recebido
 */
function marcarPagamentoRecebido(PDO $db, string $loggedInRevendedorId, string $loggedInUserType, array $input): void
{
    $idFatura = $input['id_fatura'] ?? null;
    $idRevendedor = $input['id_revendedor'] ?? null;
    $valorPago = (float)($input['valor_pago'] ?? 0);
    $observacoes = $input['observacoes'] ?? '';
    $metodoPagamento = $input['metodo_pagamento'] ?? 'manual';

    if (empty($idRevendedor) || $valorPago <= 0) {
        http_response_code(400);
        standardResponse(false, null, 'ID do revendedor e valor pago são obrigatórios.');
    }

    // ✅ VERIFICAR PERMISSÕES HIERÁRQUICAS
    if (!verificarPermissaoRevendedor($db, $loggedInRevendedorId, $loggedInUserType, $idRevendedor)) {
        http_response_code(403);
        standardResponse(false, null, 'Sem permissão para marcar pagamento deste revendedor.');
    }

    try {
        $db->beginTransaction();

        $stmtRev = $db->prepare("SELECT nome FROM revendedores WHERE id_revendedor = ?");
        $stmtRev->execute([$idRevendedor]);
        $revendedor = $stmtRev->fetch();
        if (!$revendedor) {
            $db->rollBack();
            http_response_code(404);
            standardResponse(false, null, 'Revendedor não encontrado.');
        }

        // Registar o pagamento
        $stmtInsertPagamento = $db->prepare("
            INSERT INTO pagamentos (id_fatura, id_revendedor, valor_pago, metodo_pagamento, observacoes, id_revendedor_registrador)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmtInsertPagamento->execute([
            $idFatura,
            $idRevendedor,
            $valorPago,
            $metodoPagamento,
            $observacoes,
            $loggedInRevendedorId
        ]);
        $idPagamento = $db->lastInsertId();

        // Atualizar status da fatura se fornecida
        if (!empty($idFatura)) {
            $stmtFatura = $db->prepare("SELECT valor_total FROM faturas WHERE id_fatura = ? AND id_revendedor = ?");
            $stmtFatura->execute([$idFatura, $idRevendedor]);
            $fatura = $stmtFatura->fetch();

            if ($fatura) {
                $novoStatusFatura = ($valorPago >= (float)$fatura['valor_total']) ? 'paga' : 'parcialmente_paga';

                $stmtUpdateFatura = $db->prepare("
                    UPDATE faturas SET status = ?, atualizado_em = CURRENT_TIMESTAMP WHERE id_fatura = ?
                ");
                $stmtUpdateFatura->execute([$novoStatusFatura, $idFatura]);

                // Se fatura foi paga, reativar painel se necessário
                if ($novoStatusFatura === 'paga') {
                    $stmtUpdateRevendedor = $db->prepare("
                        UPDATE revendedores SET ativo = 1, data_bloqueio = NULL, atualizado_em = CURRENT_TIMESTAMP
                        WHERE id_revendedor = ? AND ativo = 0
                    ");
                    $stmtUpdateRevendedor->execute([$idRevendedor]);
                }
            }
        }

        $db->commit();
        standardResponse(true, ['id_pagamento' => $idPagamento], 'Pagamento marcado como recebido com sucesso.');
    } catch (Exception $e) {
        $db->rollBack();
        error_log("NomaTV v4.5 [FINANCEIRO] Erro em marcarPagamentoRecebido: " . $e->getMessage());
        http_response_code(500);
        standardResponse(false, null, 'Erro ao marcar pagamento.');
    }
}

/**
 * Bloqueia revendedores com faturas vencidas
 */
function bloquearRevendedoresVencidos(PDO $db, string $loggedInRevendedorId, string $loggedInUserType): void
{
    // ✅ VERIFICAR PERMISSÕES
    if ($loggedInUserType !== 'admin' && $loggedInUserType !== 'sim') {
        http_response_code(403);
        standardResponse(false, null, 'Sem permissão para bloquear revendedores.');
    }

    try {
        $db->beginTransaction();
        $hoje = date('Y-m-d');

        $whereConditions = ["f.data_vencimento < ?", "f.status IN ('pendente', 'parcialmente_paga')"];
        $queryParams = [$hoje];

        // ✅ FILTROS HIERÁRQUICOS PARA BLOQUEIO
        if ($loggedInUserType === "sim") {
            $redeCompleta = buscarRedeCompleta($db, $loggedInRevendedorId);
            $idsPermitidos = array_merge([$loggedInRevendedorId], $redeCompleta);
            
            if (!empty($idsPermitidos)) {
                $placeholders = implode(',', array_fill(0, count($idsPermitidos), '?'));
                $whereConditions[] = "f.id_revendedor IN ($placeholders)";
                $queryParams = array_merge($queryParams, $idsPermitidos);
            }
        }

        $whereClause = implode(' AND ', $whereConditions);

        $stmtFaturasVencidas = $db->prepare("
            SELECT f.id_fatura, f.id_revendedor, f.valor_total
            FROM faturas f
            WHERE $whereClause
        ");
        $stmtFaturasVencidas->execute($queryParams);
        $faturasVencidas = $stmtFaturasVencidas->fetchAll(PDO::FETCH_ASSOC);

        $revendedoresBloqueadosCount = 0;

        foreach ($faturasVencidas as $fatura) {
            $idRevendedor = $fatura['id_revendedor'];

            $stmtBloquearRevendedor = $db->prepare("
                UPDATE revendedores
                SET ativo = 0, data_bloqueio = CURRENT_TIMESTAMP, atualizado_em = CURRENT_TIMESTAMP
                WHERE id_revendedor = ? AND ativo = 1 AND master != 'admin'
            ");
            $stmtBloquearRevendedor->execute([$idRevendedor]);

            if ($stmtBloquearRevendedor->rowCount() > 0) {
                $revendedoresBloqueadosCount++;

                $stmtUpdateFaturaStatus = $db->prepare("
                    UPDATE faturas SET status = 'vencida', atualizado_em = CURRENT_TIMESTAMP WHERE id_fatura = ?
                ");
                $stmtUpdateFaturaStatus->execute([$fatura['id_fatura']]);
            }
        }

        $db->commit();

        standardResponse(true, ['revendedores_bloqueados' => $revendedoresBloqueadosCount],
            "{$revendedoresBloqueadosCount} revendedor(es) vencido(s) foram bloqueado(s).");
    } catch (Exception $e) {
        $db->rollBack();
        error_log("NomaTV v4.5 [FINANCEIRO] Erro em bloquearRevendedoresVencidos: " . $e->getMessage());
        http_response_code(500);
        standardResponse(false, null, 'Erro ao bloquear revendedores vencidos.');
    }
}

/**
 * Atualiza vencimento de revendedor
 */
function atualizarVencimentoRevendedor(PDO $db, string $loggedInRevendedorId, string $loggedInUserType, array $input): void
{
    if (empty($input['id_revendedor']) || empty($input['nova_data_vencimento'])) {
        http_response_code(400);
        standardResponse(false, null, 'ID do revendedor e nova data de vencimento são obrigatórios.');
    }

    $idRevendedor = $input['id_revendedor'];
    $novaDataVencimento = $input['nova_data_vencimento'];

    // ✅ VERIFICAR PERMISSÕES HIERÁRQUICAS
    if (!verificarPermissaoRevendedor($db, $loggedInRevendedorId, $loggedInUserType, $idRevendedor)) {
        http_response_code(403);
        standardResponse(false, null, 'Sem permissão para atualizar vencimento deste revendedor.');
    }

    try {
        $stmt = $db->prepare("SELECT nome, data_vencimento, ativo FROM revendedores WHERE id_revendedor = ?");
        $stmt->execute([$idRevendedor]);
        $revendedor = $stmt->fetch();

        if (!$revendedor) {
            http_response_code(404);
            standardResponse(false, null, 'Revendedor não encontrado.');
        }

        $db->beginTransaction();

        $ativarPainel = false;
        if (new DateTime($novaDataVencimento) >= new DateTime(date('Y-m-d')) && !(bool)$revendedor['ativo']) {
            $ativarPainel = true;
        }

        $stmtUpdate = $db->prepare("
            UPDATE revendedores
            SET data_vencimento = ?, atualizado_em = CURRENT_TIMESTAMP" .
            ($ativarPainel ? ", ativo = 1, data_bloqueio = NULL" : "") . "
            WHERE id_revendedor = ?
        ");
        $stmtUpdate->execute([$novaDataVencimento, $idRevendedor]);

        $db->commit();
        standardResponse(true, [
            'id_revendedor' => $idRevendedor,
            'nova_data_vencimento' => $novaDataVencimento,
            'data_atualizacao' => date('Y-m-d H:i:s')
        ], 'Vencimento atualizado com sucesso.');
    } catch (Exception $e) {
        $db->rollBack();
        error_log("NomaTV v4.5 [FINANCEIRO] Erro em atualizarVencimentoRevendedor: " . $e->getMessage());
        http_response_code(500);
        standardResponse(false, null, 'Erro ao atualizar vencimento.');
    }
}

/**
 * =================================================================
 * FUNÇÕES AUXILIARES
 * =================================================================
 */

/**
 * Verifica se o usuário logado tem permissão para agir sobre um revendedor específico
 */
function verificarPermissaoRevendedor(PDO $db, string $loggedInRevendedorId, string $loggedInUserType, string $targetRevendedorId): bool
{
    if ($loggedInUserType === 'admin') {
        return true; // Admin pode tudo
    }

    if ($loggedInRevendedorId === $targetRevendedorId) {
        return true; // Pode agir sobre si mesmo
    }

    if ($loggedInUserType === 'sim') {
        // Master pode agir sobre sua rede descendente
        $redeCompleta = buscarRedeCompleta($db, $loggedInRevendedorId);
        return in_array($targetRevendedorId, $redeCompleta);
    }

    return false; // Sub-revendedor só pode agir sobre si mesmo
}

/**
 * Conta client_ids ativos para um revendedor
 */
function contarClientIds(PDO $db, string $idRevendedor): int
{
    try {
        $stmt = $db->prepare("SELECT COUNT(client_id) FROM client_ids WHERE id_revendedor = ? AND ativo = 1 AND bloqueado = 0");
        $stmt->execute([$idRevendedor]);
        return (int)$stmt->fetchColumn();
    } catch (Exception $e) {
        error_log("NomaTV v4.5 [FINANCEIRO] Erro em contarClientIds: " . $e->getMessage());
        return 0;
    }
}

/**
 * Determina status de pagamento de uma fatura
 */
function determinarStatusPagamentoFatura(?string $faturaStatus, ?string $faturaDataVencimento): string
{
    if ($faturaStatus === 'paga') {
        return 'paga';
    }
    if ($faturaStatus === 'cancelada') {
        return 'cancelada';
    }
    if (empty($faturaDataVencimento) || empty($faturaStatus)) {
        return 'indefinido';
    }

    try {
        $dataVencimento = new DateTime($faturaDataVencimento);
        $hoje = new DateTime();

        if ($hoje > $dataVencimento) {
            return 'vencido';
        } else {
            $diferenca = $hoje->diff($dataVencimento);
            if ($diferenca->days <= 7) {
                return 'proximo_vencimento';
            } else {
                return 'em_dia';
            }
        }
    } catch (Exception $e) {
        error_log("Erro ao determinar status de pagamento da fatura: " . $e->getMessage());
        return 'erro';
    }
}

/**
 * Calcula dias para o vencimento de uma fatura
 */
function calcularDiasParaVencimentoFatura(?string $dataVencimento): int
{
    if (!$dataVencimento) {
        return 999;
    }
    try {
        $dataVenc = new DateTime($dataVencimento);
        $hoje = new DateTime();
        $diferenca = $hoje->diff($dataVenc);
        return $diferenca->days * ($diferenca->invert ? -1 : 1);
    } catch (Exception $e) {
        return 999;
    }
}

/**
 * Calcula dias desde última atividade
 */
function calcularDiasDesdeUltimaAtividade(?string $ultimaAtividade): int
{
    if (!$ultimaAtividade) {
        return 999;
    }

    try {
        $dataAtividade = new DateTime($ultimaAtividade);
        $hoje = new DateTime();
        $diferenca = $hoje->diff($dataAtividade);
        return $diferenca->days;
    } catch (Exception $e) {
        return 999;
    }
}

/**
 * Gera análises financeiras avançadas
 */
function gerarAnalises(PDO $db, array $dadosFinanceiros): array
{
    $totalRevendedores = count($dadosFinanceiros);
    $revendedoresAtivosNoSistema = array_filter($dadosFinanceiros, fn ($r) => $r['revendedor_ativo']);
    $revendedoresComAtivosOnline = array_filter($dadosFinanceiros, fn ($r) => $r['ativos_online_count'] > 0);

    $topRevendedoresReceita = $dadosFinanceiros;
    usort($topRevendedoresReceita, function ($a, $b) {
        $valA = (float)$a['fatura_pendente_valor'] > 0 ? (float)$a['fatura_pendente_valor'] : (($a['valor_ativo'] !== null) ? $a['ativos_online_count'] * $a['valor_ativo'] : (($a['valor_mensal'] !== null) ? $a['valor_mensal'] : 0));
        $valB = (float)$b['fatura_pendente_valor'] > 0 ? (float)$b['fatura_pendente_valor'] : (($b['valor_ativo'] !== null) ? $b['ativos_online_count'] * $b['valor_ativo'] : (($b['valor_mensal'] !== null) ? $b['valor_mensal'] : 0));
        return $valB <=> $valA;
    });
    $topRevendedoresReceita = array_slice($topRevendedoresReceita, 0, 5);

    $receitaTotalMensalPrevista = 0;
    $revendedoresAtivosParaTicket = 0;
    foreach ($dadosFinanceiros as $rev) {
        if ((bool)$rev['revendedor_ativo']) {
            $revendedoresAtivosParaTicket++;
            if ((float)$rev['fatura_pendente_valor'] > 0) {
                $receitaTotalMensalPrevista += (float)$rev['fatura_pendente_valor'];
            } elseif ($rev['valor_ativo'] !== null) {
                $receitaTotalMensalPrevista += $rev['ativos_online_count'] * (float)$rev['valor_ativo'];
            } elseif ($rev['valor_mensal'] !== null) {
                $receitaTotalMensalPrevista += (float)$rev['valor_mensal'];
            }
        }
    }
    $ticketMedio = $revendedoresAtivosParaTicket > 0 ? round($receitaTotalMensalPrevista / $revendedoresAtivosParaTicket, 2) : 0;

    return [
        'taxa_ativacao_revendedores' => $totalRevendedores > 0 ?
            round((count($revendedoresAtivosNoSistema) / $totalRevendedores) * 100, 1) : 0,
        'taxa_utilizacao_ativos' => $totalRevendedores > 0 ?
            round((count($revendedoresComAtivosOnline) / $totalRevendedores) * 100, 1) : 0,
        'ticket_medio' => $ticketMedio,
        'top_revendedores_receita' => array_map(function ($r) {
            $valor = (float)$r['fatura_pendente_valor'] > 0 ? (float)$r['fatura_pendente_valor'] : (($r['valor_ativo'] !== null) ? $r['ativos_online_count'] * $r['valor_ativo'] : (($r['valor_mensal'] !== null) ? $r['valor_mensal'] : 0));
            return [
                'id_revendedor' => $r['id_revendedor'],
                'nome' => $r['nome'],
                'usuario' => $r['usuario'],
                'receita_estimada' => round($valor, 2)
            ];
        }, $topRevendedoresReceita)
    ];
}

/**
 * Gera resumo do período atual
 */
function gerarResumoPeriodo(array $dadosFinanceiros): array
{
    $hoje = date('Y-m-d');
    $inicioMes = date('Y-m-01');
    $fimMes = date('Y-m-t');

    $revendedoresAtivosNoPeriodo = array_filter($dadosFinanceiros, fn ($r) => $r['revendedor_ativo']);

    return [
        'periodo_atual' => date('m/Y'),
        'inicio_periodo' => $inicioMes,
        'fim_periodo' => $fimMes,
        'dias_restantes_no_mes' => (int)date('t') - (int)date('d'),
        'total_revendedores_ativos_no_periodo' => count($revendedoresAtivosNoPeriodo)
    ];
}

/**
 * Gera projeções financeiras
 */
function gerarProjecoes(array $dadosFinanceiros): array
{
    $receitaAtualEstimada = 0;
    foreach ($dadosFinanceiros as $rev) {
        if ((bool)$rev['revendedor_ativo']) {
            if ((float)$rev['fatura_pendente_valor'] > 0) {
                $receitaAtualEstimada += (float)$rev['fatura_pendente_valor'];
            } elseif ($rev['valor_ativo'] !== null) {
                $receitaAtualEstimada += $rev['ativos_online_count'] * (float)$rev['valor_ativo'];
            } elseif ($rev['valor_mensal'] !== null) {
                $receitaAtualEstimada += (float)$rev['valor_mensal'];
            }
        }
    }

    $crescimentoEstimadoMensal = 5.0;

    return [
        'projecao_1_mes' => round($receitaAtualEstimada * (1 + ($crescimentoEstimadoMensal / 100)), 2),
        'projecao_3_meses' => round($receitaAtualEstimada * pow((1 + ($crescimentoEstimadoMensal / 100)), 3), 2),
        'projecao_6_meses' => round($receitaAtualEstimada * pow((1 + ($crescimentoEstimadoMensal / 100)), 6), 2),
        'projecao_12_meses' => round($receitaAtualEstimada * pow((1 + ($crescimentoEstimadoMensal / 100)), 12), 2),
        'crescimento_estimado_mensal_percentual' => $crescimentoEstimadoMensal
    ];
}
?>