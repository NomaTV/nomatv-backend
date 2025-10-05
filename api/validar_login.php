<?php
/**
 * VALIDAR_LOGIN.PHP - NomaTV v4.2 VINCULADOR - BANCO NA MESMA PASTA
 * 
 * FUNÇÃO: "O Vinculador" - Valida provedor e vincula client_id
 * 
 * CHAMADO POR: login.html (sessão de login)
 * 
 * RESPONSABILIDADE ÚNICA:
 * 1. ✅ Recebe 4 dados (provedor, username, password, client_id)
 * 2. ✅ Valida se provedor existe e está ativo
 * 3. ✅ Vincula client_id ao provedor/revendedor
 * 4. ✅ Retorna 5 variáveis para sessionStorage
 * 
 * INPUT: {"provedor": "zeuss", "username": "teste", "password": "123", "client_id": "abc..."}
 * OUTPUT: {"success": true, "data": {"provedor", "username", "password", "dns", "revendedor_id"}}
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ✅ CONEXÃO COM BANCO NA MESMA PASTA (/api/db.db)
try {
    // Banco está na mesma pasta que o validar_login.php
    $dbPath = __DIR__ . '/db.db';
    
    if (!file_exists($dbPath)) {
        throw new Exception("Banco de dados não encontrado em: $dbPath");
    }
    
    if (!is_readable($dbPath)) {
        throw new Exception("Banco de dados não é legível. Verifique permissões: $dbPath");
    }
    
    $db = new PDO("sqlite:$dbPath");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $db->exec("PRAGMA foreign_keys = ON");
    
    // Log de sucesso na conexão
    error_log("NomaTV [DB] Banco conectado com sucesso: $dbPath");
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro de conexão com banco de dados',
        'detalhes' => $e->getMessage(),
        'debug_info' => [
            'caminho_tentado' => __DIR__ . '/db.db',
            'diretorio_atual' => __DIR__,
            'arquivos_na_pasta' => scandir(__DIR__)
        ]
    ]);
    exit();
}

// ✅ VALIDAÇÃO DO MÉTODO
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Método HTTP inválido',
        'detalhes' => 'Use POST'
    ]);
    exit();
}

// ✅ VALIDAÇÃO DOS DADOS DE ENTRADA
try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Dados JSON inválidos ou malformados');
    }
    
    $clientId = trim($input['client_id'] ?? '');
    $provedor = trim($input['provedor'] ?? '');
    $username = trim($input['username'] ?? '');
    $password = trim($input['password'] ?? '');
    
    if (empty($clientId)) {
        throw new Exception('Client ID é obrigatório');
    }
    if (empty($provedor)) {
        throw new Exception('Nome do provedor é obrigatório');
    }
    if (empty($username)) {
        throw new Exception('Usuário é obrigatório');
    }
    if (empty($password)) {
        throw new Exception('Senha é obrigatória');
    }
    
    // Validação básica do CLIENT_ID (formato UUID)
    if (!preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-4[a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}$/i', $clientId)) {
        throw new Exception('Client ID em formato inválido');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Dados de entrada inválidos',
        'detalhes' => $e->getMessage()
    ]);
    exit();
}

// ✅ VERIFICAR ESTRUTURA DO BANCO
try {
    // Verificar se as tabelas existem
    $tables = $db->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array('provedores', $tables) || !in_array('revendedores', $tables)) {
        throw new Exception('Tabelas necessárias não encontradas. Tabelas encontradas: ' . implode(', ', $tables));
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Estrutura do banco inválida',
        'detalhes' => $e->getMessage(),
        'debug_tables' => $tables ?? []
    ]);
    exit();
}

// ✅ CONSULTA SQL BASEADA NA ESTRUTURA REAL DO BANCO
try {
    // Consulta adaptada para a estrutura real do banco
    $stmt = $db->prepare("
        SELECT p.id_provedor, p.nome, p.dns, p.id_revendedor 
        FROM provedores p 
        JOIN revendedores r ON p.id_revendedor = r.id_revendedor
        WHERE LOWER(p.nome) = LOWER(?) AND p.ativo = 1 AND r.ativo = 1
    ");
    $stmt->execute([$provedor]);
    $dadosProvedor = $stmt->fetch();
    
    if (!$dadosProvedor) {
        throw new Exception("Provedor '$provedor' não encontrado no sistema ou está inativo");
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Falha na consulta SQL dos provedores',
        'detalhes' => 'Problema na consulta do banco de dados: ' . $e->getMessage()
    ]);
    exit();
} catch (Exception $e) {
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'error' => 'Provedor não encontrado ou inativo',
        'detalhes' => $e->getMessage()
    ]);
    exit();
}

// ✅ VINCULAÇÃO CLIENT_ID → PROVEDOR → REVENDEDOR
try {
    // Verificar se tabela client_ids existe
    if (in_array('client_ids', $tables)) {
        // Verificar se client_id já existe
        $stmt = $db->prepare("SELECT id, provedor_id FROM client_ids WHERE client_id = ?");
        $stmt->execute([$clientId]);
        $clientIdExistente = $stmt->fetch();
        
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        
        if ($clientIdExistente) {
            // Client_id JÁ EXISTE
            if ($clientIdExistente['provedor_id'] != $dadosProvedor['id_provedor']) {
                // OUTRO PROVEDOR → SUBSTITUIR vinculação
                $stmt = $db->prepare("
                    UPDATE client_ids 
                    SET provedor_id = ?, id_revendedor = ?, usuario = ?, senha = ?, ip = ?, atualizado_em = CURRENT_TIMESTAMP
                    WHERE client_id = ?
                ");
                $stmt->execute([$dadosProvedor['id_provedor'], $dadosProvedor['id_revendedor'], $username, $password, $ip, $clientId]);
            } else {
                // MESMO PROVEDOR → Só atualizar atividade
                $stmt = $db->prepare("
                    UPDATE client_ids 
                    SET id_revendedor = ?, usuario = ?, senha = ?, ip = ?, atualizado_em = CURRENT_TIMESTAMP
                    WHERE client_id = ?
                ");
                $stmt->execute([$dadosProvedor['id_revendedor'], $username, $password, $ip, $clientId]);
            }
        } else {
            // Client_id NÃO EXISTE → CRIAR nova vinculação
            $stmt = $db->prepare("
                INSERT INTO client_ids (client_id, provedor_id, id_revendedor, usuario, senha, ip, ativo, criado_em, atualizado_em) 
                VALUES (?, ?, ?, ?, ?, ?, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
            ");
            $stmt->execute([$clientId, $dadosProvedor['id_provedor'], $dadosProvedor['id_revendedor'], $username, $password, $ip]);
        }
    }
    // Se tabela client_ids não existe, continuar sem erro (opcional)
    
} catch (PDOException $e) {
    // Log do erro mas não falhar (client_ids é opcional)
    error_log("NomaTV [WARNING] Falha na vinculação client_id: " . $e->getMessage());
}

// ✅ RETORNO DAS 5 VARIÁVEIS (conforme documentação)
try {
    $revendedorId = $dadosProvedor['id_revendedor'];
    
    // Estrutura EXATA conforme documentação MD v4.2
    $responseData = [
        'provedor' => $provedor,                    // Nome do provedor
        'username' => $username,                    // Usuário IPTV
        'password' => $password,                    // Senha IPTV
        'dns' => $dadosProvedor['dns'],            // DNS do servidor
        'revendedor_id' => (int)$revendedorId      // ID para branding
    ];
    
    // Validar se todas as 5 variáveis estão presentes
    if (!$responseData['provedor'] || !$responseData['username'] || !$responseData['password'] || 
        !$responseData['dns'] || !$responseData['revendedor_id']) {
        throw new Exception('Erro interno: Dados de resposta incompletos');
    }
    
    echo json_encode([
        'success' => true,
        'data' => $responseData
    ]);
    
    // Log de sucesso
    error_log("NomaTV v4.2 [VINCULADOR] Provedor '$provedor' validado | Client: " . substr($clientId, 0, 8) . "... | Revendedor: $revendedorId | IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Falha na preparação da resposta final',
        'detalhes' => $e->getMessage()
    ]);
    exit();
}

// ✅ FECHAR CONEXÃO
$db = null;
exit();
?>

