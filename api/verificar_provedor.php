<?php
/**
 * VERIFICAR_PROVEDOR.PHP - NomaTV v4.2 PORTEIRO/DECISOR - CORRIGIDO
 * 
 * FUNÇÃO: "O Porteiro/Decisor" - Controla roteamento (home ou login)
 * 
 * CHAMADO POR: index.html do App SPA
 * 
 * RESPONSABILIDADE ÚNICA:
 * 1. ✅ Recebe nome do provedor
 * 2. ✅ Verifica se está ativo no banco (consulta SQL SIMPLES)
 * 3. ✅ Retorna DNS + revendedor_id (decide roteamento)
 * 4. ❌ NÃO cria nem atualiza client_ids
 * 
 * INPUT: {"provedor": "zeusott"}
 * OUTPUT SUCESSO: {"success": true, "data": {"dns": "http://...", "revendedor_id": 1234}}
 * OUTPUT FALHA: {"success": false, "error": "Provedor inativo ou não encontrado"}
 */

header('Content-Type: application/json; charset=utf-8');
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
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro: Falha na conexão com banco de dados',
        'timestamp' => date('c')
    ]);
    exit();
}

// ✅ VALIDAÇÃO DO MÉTODO
try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método não permitido. Use POST.');
    }
} catch (Exception $e) {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Erro: Método HTTP inválido',
        'timestamp' => date('c')
    ]);
    exit();
}

// ✅ VALIDAÇÃO DOS DADOS DE ENTRADA
try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Dados JSON inválidos');
    }
    
    $provedor = trim($input['provedor'] ?? '');
    
    if (empty($provedor)) {
        throw new Exception('Parâmetro "provedor" é obrigatório');
    }
    
    // Limpar nome do provedor
    $provedor = strtolower(trim($provedor));
    
    if (strlen($provedor) < 2) {
        throw new Exception('Nome do provedor deve ter pelo menos 2 caracteres');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Erro: Dados de entrada inválidos',
        'detalhes' => $e->getMessage(),
        'timestamp' => date('c')
    ]);
    exit();
}

// ✅ CONSULTA SQL SIMPLES - Estrutura v4.2 (conforme documentação)
try {
    // CONSULTA EXATA da documentação MD v4.2
    $stmt = $db->prepare("
        SELECT p.dns, p.id_revendedor
        FROM provedores p
        JOIN revendedores r ON p.id_revendedor = r.id_revendedor  
        WHERE LOWER(p.nome) = LOWER(?) AND p.ativo = 1 AND r.ativo = 1
    ");
    $stmt->execute([$provedor]);
    $resultado = $stmt->fetch();
    
    // ✅ DECISÃO DE ROTEAMENTO
    if ($resultado) {
        // SUCESSO → Frontend vai para HOME
        echo json_encode([
            'success' => true,
            'data' => [
                'dns' => $resultado['dns'],
                'revendedor_id' => (int)$resultado['id_revendedor']
            ],
            'timestamp' => date('c')
        ]);
        
        // Log de sucesso
        error_log("NomaTV v4.2 [PORTEIRO] Provedor '$provedor' liberado → HOME | Revendedor: {$resultado['id_revendedor']} | IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        
    } else {
        // FALHA → Frontend vai para LOGIN
        echo json_encode([
            'success' => false,
            'error' => 'Provedor inativo ou não encontrado',
            'timestamp' => date('c')
        ]);
        
        // Log de falha
        error_log("NomaTV v4.2 [PORTEIRO] Provedor '$provedor' negado → LOGIN | IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro: Falha na consulta do banco de dados',
        'timestamp' => date('c')
    ]);
    error_log("NomaTV v4.2 [PORTEIRO] Erro SQL: " . $e->getMessage());
    exit();
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro: Falha no processamento',
        'timestamp' => date('c')
    ]);
    error_log("NomaTV v4.2 [PORTEIRO] Erro geral: " . $e->getMessage());
    exit();
}

// ✅ FECHAR CONEXÃO
$db = null;
exit();
?>