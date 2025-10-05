<?php
/**
 * VERIFICAR_SESSAO.PHP - NomaTV v4.2 RESETADOR SILENCIOSO - CORRIGIDO
 * 
 * FUNÇÃO: "O Resetador Silencioso" - Trabalha nos bastidores sem resposta JSON
 * 
 * CHAMADO POR: home.html e outras sessões (background)
 * 
 * RESPONSABILIDADE ÚNICA:
 * - Reset cronômetro de atividade (prevenção de cobrança)
 * - Vinculação inteligente client_id → provedor → revendedor
 * - Trabalho 100% SILENCIOSO (sem output)
 * 
 * LÓGICA (4 CENÁRIOS):
 * 1. ✅ Provedor existe + client_id existe → SÓ RESETA cronômetro
 * 2. ✅ Provedor vazio + client_id existe → NÃO faz nada (primeiro acesso)
 * 3. ✅ Provedor existe + client_id NÃO existe → CRIA client_id
 * 4. ✅ Provedor não existe + client_id não existe → NÃO faz nada
 * 
 * INPUT: {"client_id": "abc-123...", "provedor": "zeusott"}
 * OUTPUT: NENHUM - Trabalha silenciosamente
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ✅ CONEXÃO DIRETA (sem auth_helper) - Arquitetura v4.2
try {
    require_once __DIR__ . '/config/database_sqlite.php';
} catch (Exception $e) {
    error_log("NomaTV v4.2 [RESETADOR] Erro conexão banco: " . $e->getMessage());
    exit(); // Silencioso mesmo com erro
}

// ✅ FUNÇÃO DE LOG SILENCIOSO
function logSilencioso($cenario, $provedor, $clientId, $status, $detalhes = '') {
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $clientShort = substr($clientId, 0, 8) . '...';
    $logMessage = "NomaTV v4.2 [RESETADOR] [$cenario] Provedor: $provedor | CLIENT_ID: $clientShort | Status: $status | Detalhes: $detalhes | IP: $ip | Time: $timestamp";
    error_log($logMessage);
}

// ✅ VALIDAÇÃO SILENCIOSA DOS DADOS
try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        exit(); // Silencioso
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        exit(); // Silencioso
    }
    
    $clientId = trim($input['client_id'] ?? '');
    $provedor = trim($input['provedor'] ?? '');
    
    // ✅ CENÁRIO 2: Provedor vazio → NÃO faz nada (primeiro acesso)
    if (empty($provedor)) {
        logSilencioso('CENARIO_2', 'vazio', $clientId, 'provedor_vazio_primeiro_acesso');
        exit(); // Silencioso
    }
    
    if (empty($clientId)) {
        exit(); // Silencioso - dados inválidos
    }
    
    // Validação básica do CLIENT_ID (formato UUID)
    if (!preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-4[a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}$/i', $clientId)) {
        logSilencioso('ERRO', $provedor, $clientId, 'formato_client_id_invalido');
        exit(); // Silencioso
    }
    
    // Limpar nome do provedor
    $provedor = strtolower(trim($provedor));
    
    if (empty($provedor)) {
        exit(); // Silencioso
    }
    
} catch (Exception $e) {
    logSilencioso('ERRO', $provedor ?? 'unknown', $clientId ?? 'unknown', 'validacao_dados', $e->getMessage());
    exit(); // Silencioso mesmo com erro
}

// ✅ VERIFICAR SE PROVEDOR EXISTE - Estrutura v4.2 (consulta SIMPLES)
try {
    // CONSULTA SIMPLES da documentação MD v4.2
    $stmt = $db->prepare("
        SELECT p.id, p.id_revendedor
        FROM provedores p
        JOIN revendedores r ON p.id_revendedor = r.id_revendedor
        WHERE LOWER(p.nome) = LOWER(?) AND p.ativo = 1 AND r.ativo = 1
    ");
    $stmt->execute([$provedor]);
    $dadosProvedor = $stmt->fetch();
    
    // ✅ CENÁRIO 4: Provedor não existe → NÃO faz nada
    if (!$dadosProvedor) {
        logSilencioso('CENARIO_4', $provedor, $clientId, 'provedor_nao_existe_nao_faz_nada');
        exit(); // Silencioso - verificar_provedor.php mandará para login
    }
    
} catch (Exception $e) {
    logSilencioso('ERRO', $provedor, $clientId, 'consulta_provedor', $e->getMessage());
    exit(); // Silencioso mesmo com erro
}

// ✅ VERIFICAR SE CLIENT_ID JÁ EXISTE
try {
    $stmt = $db->prepare("SELECT id, ativo FROM client_ids WHERE client_id = ?");
    $stmt->execute([$clientId]);
    $clientIdExistente = $stmt->fetch();
    
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'NomaTV App';
    
    if ($clientIdExistente) {
        // ✅ CENÁRIO 1: Provedor existe + client_id existe → SÓ RESETA cronômetro
        $stmt = $db->prepare("
            UPDATE client_ids 
            SET ultima_atividade = CURRENT_TIMESTAMP,
                ativo = 1,
                ip = ?,
                user_agent = ?
            WHERE client_id = ?
        ");
        $stmt->execute([$ip, $userAgent, $clientId]);
        
        logSilencioso('CENARIO_1', $provedor, $clientId, 'cronometro_resetado', 'Cliente ativo - cronômetro reiniciado');
        
    } else {
        // ✅ CENÁRIO 3: Provedor existe + client_id NÃO existe → CRIA client_id
        $stmt = $db->prepare("
            INSERT INTO client_ids (
                client_id, provedor_id, id_revendedor, usuario,
                primeira_conexao, ultima_atividade, ativo, 
                ip, user_agent
            ) VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, 1, ?, ?)
        ");
        $stmt->execute([
            $clientId, 
            $dadosProvedor['id'], 
            $dadosProvedor['id_revendedor'],
            '', // Usuário vazio no resetador (preenchido no validar_login)
            $ip, 
            $userAgent
        ]);
        
        logSilencioso('CENARIO_3', $provedor, $clientId, 'client_id_criado', 'Primeira vinculação ou recriação após limpeza');
    }
    
} catch (Exception $e) {
    logSilencioso('ERRO', $provedor, $clientId, 'manipulacao_client_id', $e->getMessage());
    exit(); // Silencioso mesmo com erro
}

// ✅ FECHAR CONEXÃO E TRABALHO SILENCIOSO CONCLUÍDO
$db = null;
exit(); // NENHUMA RESPOSTA JSON - 100% SILENCIOSO

// ==========================================
// ✅ ARQUITETURA v4.2 CORRIGIDA:
// 
// ❌ REMOVIDO: auth_helper (sem dependências)
// ❌ REMOVIDO: Estrutura antiga (sub_revendedores)
// ❌ REMOVIDO: Consultas SQL complexas
// ❌ REMOVIDO: Campos inexistentes
// 
// ✅ ADICIONADO: Conexão direta database_sqlite.php
// ✅ ADICIONADO: Estrutura de tabelas v4.2
// ✅ ADICIONADO: Consultas SQL simples (conforme MD)
// ✅ ADICIONADO: Trabalho 100% silencioso
// ✅ ADICIONADO: 4 cenários implementados corretamente
// ✅ ADICIONADO: Logs detalhados para debug
// 
// 🎯 RESPONSABILIDADE ÚNICA: Reset de cronômetro silencioso
// ==========================================
?>