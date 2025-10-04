<?php
/**
 * =================================================================
 * SCRIPT DE LIMPEZA AUTOMÁTICA - NomaTV API v4.2
 * =================================================================
 * 
 * ARQUIVO: /api/cleanup.php
 * VERSÃO: 4.2 - Reescrito Completamente
 * 
 * RESPONSABILIDADES:
 * ✅ Limpeza automática de client_ids inativos (sistema de cobrança inteligente)
 * ✅ Remoção de registros órfãos e inconsistentes
 * ✅ Otimização do banco de dados
 * ✅ Logs detalhados de todas as operações
 * ✅ Compatibilidade total com estrutura v4.2
 * ✅ Execução via Cron Job ou manual
 * ✅ Relatórios de limpeza
 * 
 * LÓGICA DE LIMPEZA v4.2:
 * - 7 dias sem atividade → ativo = 0 (para de cobrar)
 * - 60 dias inativo → DELETE (limpa banco)
 * - Logs → 90 dias (manter histórico)
 * - Arquivos branding órfãos → remover
 * 
 * EXECUÇÃO RECOMENDADA:
 * Cron Job diário às 3h: 0 3 * * * /usr/bin/php /path/to/cleanup.php
 * 
 * =================================================================
 */

// Configuração de erro reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);
ini_set('max_execution_time', 300); // 5 minutos max

// Dependências obrigatórias
require_once __DIR__ . '/config/database_sqlite.php';

// Log de início
error_log("NomaTV v4.2 [CLEANUP] Iniciando limpeza automática - " . date('Y-m-d H:i:s'));

try {
    // Executar todas as rotinas de limpeza
    $relatorio = [
        'inicio' => date('Y-m-d H:i:s'),
        'operacoes' => [],
        'erros' => [],
        'estatisticas' => []
    ];
    
    // 1. Inativar client_ids sem atividade há 7 dias
    $relatorio['operacoes']['inativar_7_dias'] = inativarClientIds7Dias($db);
    
    // 2. Remover client_ids inativos há 60 dias
    $relatorio['operacoes']['remover_60_dias'] = removerClientIds60Dias($db);
    
    // 3. Limpar logs antigos (90 dias)
    $relatorio['operacoes']['limpar_logs'] = limparLogsAntigos($db);
    
    // 4. Remover arquivos órfãos de branding
    $relatorio['operacoes']['limpar_branding'] = limparArquivosBrandingOrfaos();
    
    // 5. Otimizar banco de dados
    $relatorio['operacoes']['otimizar_banco'] = otimizarBancoDados($db);
    
    // 6. Gerar estatísticas finais
    $relatorio['estatisticas'] = gerarEstatisticasFinais($db);
    
    $relatorio['fim'] = date('Y-m-d H:i:s');
    $relatorio['duracao'] = round((strtotime($relatorio['fim']) - strtotime($relatorio['inicio'])) / 60, 2) . ' minutos';
    $relatorio['sucesso'] = true;
    
    // Log de sucesso
    error_log("NomaTV v4.2 [CLEANUP] Limpeza concluída com sucesso - Duração: {$relatorio['duracao']}");
    
    // Salvar relatório no banco (para consulta via admin)
    salvarRelatorioLimpeza($db, $relatorio);
    
    // Resposta JSON para chamadas manuais
    if (isset($_SERVER['REQUEST_METHOD'])) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Limpeza automática executada com sucesso!',
            'relatorio' => $relatorio
        ], JSON_PRETTY_PRINT);
    }
    
} catch (Exception $e) {
    $erro = "Erro na limpeza automática: " . $e->getMessage();
    error_log("NomaTV v4.2 [CLEANUP] $erro");
    
    if (isset($_SERVER['REQUEST_METHOD'])) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $erro,
            'timestamp' => date('c')
        ]);
    }
}

/**
 * =================================================================
 * FUNÇÕES DE LIMPEZA
 * =================================================================
 */

/**
 * Inativa client_ids sem atividade há 7 dias (para de cobrar)
 */
function inativarClientIds7Dias(PDO $db): array {
    try {
        $stmt = $db->prepare("
            UPDATE client_ids 
            SET ativo = 0 
            WHERE ativo = 1 
            AND ultima_atividade < DATE('now', '-7 days')
        ");
        $stmt->execute();
        
        $count = $stmt->rowCount();
        
        // Log detalhado
        if ($count > 0) {
            error_log("NomaTV v4.2 [CLEANUP] Inativados $count client_ids sem atividade há 7+ dias");
        }
        
        return [
            'executado' => true,
            'registros_afetados' => $count,
            'descricao' => 'Client IDs inativados (param de cobrar após 7 dias inativo)'
        ];
        
    } catch (Exception $e) {
        error_log("NomaTV v4.2 [CLEANUP] Erro ao inativar client_ids 7 dias: " . $e->getMessage());
        return [
            'executado' => false,
            'erro' => $e->getMessage()
        ];
    }
}

/**
 * Remove client_ids inativos há 60 dias (limpeza final)
 */
function removerClientIds60Dias(PDO $db): array {
    try {
        // Buscar registros que serão removidos para log
        $stmtSelect = $db->prepare("
            SELECT client_id, id_revendedor, usuario 
            FROM client_ids 
            WHERE ativo = 0 
            AND ultima_atividade < DATE('now', '-60 days')
        ");
        $stmtSelect->execute();
        $registrosRemover = $stmtSelect->fetchAll();
        
        // Remover registros
        $stmtDelete = $db->prepare("
            DELETE FROM client_ids 
            WHERE ativo = 0 
            AND ultima_atividade < DATE('now', '-60 days')
        ");
        $stmtDelete->execute();
        
        $count = $stmtDelete->rowCount();
        
        // Log detalhado
        if ($count > 0) {
            error_log("NomaTV v4.2 [CLEANUP] Removidos $count client_ids inativos há 60+ dias");
            
            // Log individual para auditoria
            foreach ($registrosRemover as $registro) {
                error_log("NomaTV v4.2 [CLEANUP] Removido: {$registro['client_id']} - Revendedor: {$registro['id_revendedor']} - Usuario: " . ($registro['usuario'] ?: 'N/A'));
            }
        }
        
        return [
            'executado' => true,
            'registros_afetados' => $count,
            'descricao' => 'Client IDs removidos permanentemente (60+ dias inativos)',
            'detalhes' => $registrosRemover
        ];
        
    } catch (Exception $e) {
        error_log("NomaTV v4.2 [CLEANUP] Erro ao remover client_ids 60 dias: " . $e->getMessage());
        return [
            'executado' => false,
            'erro' => $e->getMessage()
        ];
    }
}

/**
 * Limpa logs de auditoria antigos (90 dias)
 */
function limparLogsAntigos(PDO $db): array {
    try {
        $stmt = $db->prepare("
            DELETE FROM auditoria 
            WHERE timestamp < DATE('now', '-90 days')
        ");
        $stmt->execute();
        
        $count = $stmt->rowCount();
        
        if ($count > 0) {
            error_log("NomaTV v4.2 [CLEANUP] Removidos $count logs de auditoria antigos (90+ dias)");
        }
        
        return [
            'executado' => true,
            'registros_afetados' => $count,
            'descricao' => 'Logs de auditoria antigos removidos (90+ dias)'
        ];
        
    } catch (Exception $e) {
        error_log("NomaTV v4.2 [CLEANUP] Erro ao limpar logs: " . $e->getMessage());
        return [
            'executado' => false,
            'erro' => $e->getMessage()
        ];
    }
}

/**
 * Remove arquivos órfãos de branding
 */
function limparArquivosBrandingOrfaos(): array {
    try {
        $logoDir = __DIR__ . '/../uploads/logos/';
        
        if (!is_dir($logoDir)) {
            return [
                'executado' => false,
                'erro' => 'Diretório de logos não encontrado'
            ];
        }
        
        // Buscar revendedores com logo no banco
        global $db;
        $stmt = $db->query("SELECT id_revendedor FROM revendedores WHERE logo_filename IS NOT NULL");
        $revendedoresComLogo = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Buscar arquivos físicos
        $arquivos = glob($logoDir . '*.*');
        $arquivosOrfaos = [];
        $removidos = 0;
        
        foreach ($arquivos as $arquivo) {
            $nomeArquivo = basename($arquivo);
            
            // Extrair ID do revendedor do nome do arquivo (formato: 1234.png)
            if (preg_match('/^(\d+)\./', $nomeArquivo, $matches)) {
                $idRevendedor = (int)$matches[1];
                
                // Verificar se revendedor ainda existe e tem logo
                if (!in_array($idRevendedor, $revendedoresComLogo)) {
                    if (unlink($arquivo)) {
                        $arquivosOrfaos[] = $nomeArquivo;
                        $removidos++;
                        error_log("NomaTV v4.2 [CLEANUP] Arquivo órfão removido: $nomeArquivo");
                    }
                }
            }
        }
        
        return [
            'executado' => true,
            'registros_afetados' => $removidos,
            'descricao' => 'Arquivos de branding órfãos removidos',
            'arquivos_removidos' => $arquivosOrfaos
        ];
        
    } catch (Exception $e) {
        error_log("NomaTV v4.2 [CLEANUP] Erro ao limpar branding: " . $e->getMessage());
        return [
            'executado' => false,
            'erro' => $e->getMessage()
        ];
    }
}

/**
 * Otimiza banco de dados SQLite
 */
function otimizarBancoDados(PDO $db): array {
    try {
        // VACUUM para otimizar SQLite
        $db->exec("VACUUM");
        
        // ANALYZE para atualizar estatísticas
        $db->exec("ANALYZE");
        
        error_log("NomaTV v4.2 [CLEANUP] Banco de dados otimizado (VACUUM + ANALYZE)");
        
        return [
            'executado' => true,
            'descricao' => 'Banco de dados otimizado (VACUUM + ANALYZE)'
        ];
        
    } catch (Exception $e) {
        error_log("NomaTV v4.2 [CLEANUP] Erro ao otimizar banco: " . $e->getMessage());
        return [
            'executado' => false,
            'erro' => $e->getMessage()
        ];
    }
}

/**
 * Gera estatísticas finais após limpeza
 */
function gerarEstatisticasFinais(PDO $db): array {
    try {
        $stats = [];
        
        // Client IDs
        $stmt = $db->query("
            SELECT 
                COUNT(*) as total_client_ids,
                COUNT(CASE WHEN ativo = 1 THEN 1 END) as client_ids_ativos,
                COUNT(CASE WHEN ativo = 0 THEN 1 END) as client_ids_inativos
            FROM client_ids
        ");
        $stats['client_ids'] = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Revendedores
        $stmt = $db->query("
            SELECT 
                COUNT(*) as total_revendedores,
                COUNT(CASE WHEN ativo = 1 THEN 1 END) as revendedores_ativos,
                COUNT(CASE WHEN master = 'admin' THEN 1 END) as admins,
                COUNT(CASE WHEN master = 'sim' THEN 1 END) as masters,
                COUNT(CASE WHEN master = 'nao' THEN 1 END) as subs
            FROM revendedores
        ");
        $stats['revendedores'] = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Provedores
        $stmt = $db->query("
            SELECT 
                COUNT(*) as total_provedores,
                COUNT(CASE WHEN ativo = 1 THEN 1 END) as provedores_ativos
            FROM provedores
        ");
        $stats['provedores'] = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Logs
        $stmt = $db->query("SELECT COUNT(*) as total_logs FROM auditoria");
        $stats['logs'] = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Tamanho do banco (SQLite)
        if (file_exists(__DIR__ . '/db.db')) {
            $stats['tamanho_banco_mb'] = round(filesize(__DIR__ . '/db.db') / 1024 / 1024, 2);
        }
        
        return $stats;
        
    } catch (Exception $e) {
        error_log("NomaTV v4.2 [CLEANUP] Erro ao gerar estatísticas: " . $e->getMessage());
        return ['erro' => $e->getMessage()];
    }
}

/**
 * Salva relatório de limpeza no banco
 */
function salvarRelatorioLimpeza(PDO $db, array $relatorio): void {
    try {
        // Criar tabela se não existir
        $db->exec("
            CREATE TABLE IF NOT EXISTS cleanup_relatorios (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                data_execucao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                duracao_minutos REAL,
                relatorio_json TEXT,
                sucesso BOOLEAN DEFAULT 1
            )
        ");
        
        // Inserir relatório
        $stmt = $db->prepare("
            INSERT INTO cleanup_relatorios (duracao_minutos, relatorio_json, sucesso) 
            VALUES (?, ?, ?)
        ");
        $stmt->execute([
            $relatorio['duracao'] ?? 0,
            json_encode($relatorio),
            $relatorio['sucesso'] ? 1 : 0
        ]);
        
        // Manter apenas os últimos 30 relatórios
        $db->exec("
            DELETE FROM cleanup_relatorios 
            WHERE id NOT IN (
                SELECT id FROM cleanup_relatorios 
                ORDER BY data_execucao DESC 
                LIMIT 30
            )
        ");
        
    } catch (Exception $e) {
        error_log("NomaTV v4.2 [CLEANUP] Erro ao salvar relatório: " . $e->getMessage());
    }
}

/**
 * =================================================================
 * FIM DO ARQUIVO
 * =================================================================
 */
?>