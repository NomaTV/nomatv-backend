<?php
/**
 * AUTH_HELPER.PHP - Autenticação NomaTV v4.5
 *
 * FUNÇÃO: Validar sessões de revendedores
 *
 * LOCALIZAÇÃO: /api/helpers/auth_helper.php
 */

/**
 * Valida sessão do revendedor usando sessões PHP nativas
 * @return array ['success' => bool, 'revendedor_id' => int|null, 'tipo' => string|null, 'message' => string]
 */
function validateSession() {
    // Iniciar sessão se não estiver ativa
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Verificar se usuário está logado
    if (!isset($_SESSION['revendedor_id']) || !isset($_SESSION['tipo'])) {
        return [
            'success' => false,
            'revendedor_id' => null,
            'tipo' => null,
            'message' => 'Sessão não encontrada ou expirada'
        ];
    }

        // Conectar ao banco para validar se o revendedor ainda existe e está ativo
    try {
        require_once __DIR__ . '/../config/database_sqlite.php';
        $db = getDatabaseConnection();

        // Buscar revendedor
        $stmt = $db->prepare("
            SELECT id_revendedor, nome, master, ativo
            FROM revendedores
            WHERE id_revendedor = ? AND ativo = 1
        ");
        $stmt->execute([$_SESSION['revendedor_id']]);
        $revendedor = $stmt->fetch();

        if (!$revendedor) {
            // Revendedor não existe mais ou foi desativado, destruir sessão
            session_destroy();
            return [
                'success' => false,
                'revendedor_id' => null,
                'tipo' => null,
                'message' => 'Revendedor não encontrado ou inativo'
            ];
        }

        return [
            'success' => true,
            'revendedor_id' => $_SESSION['revendedor_id'],
            'tipo' => $_SESSION['tipo'],
            'nome' => $revendedor['nome'],
            'message' => 'Sessão válida'
        ];    } catch (Exception $e) {
        error_log("NomaTV [AUTH] Erro na validação: " . $e->getMessage());
        return [
            'success' => false,
            'revendedor_id' => null,
            'tipo' => null,
            'message' => 'Erro interno na validação'
        ];
    }
}

/**
 * Gera novo session_id (mantido para compatibilidade)
 * @return string
 */
function generateSessionId() {
    return bin2hex(random_bytes(32));
}

/**
 * Função de compatibilidade para verificarAutenticacao
 * Retorna dados do usuário logado ou false se não autenticado
 * @return array|false
 */
function verificarAutenticacao() {
    $sessionData = validateSession();

    if (!$sessionData['success']) {
        return false;
    }

    // Retornar dados no formato esperado pelo provedores.php
    return [
        'id' => $sessionData['revendedor_id'],
        'tipo' => $sessionData['tipo'],
        'nome' => $sessionData['nome'] ?? 'Usuário'
    ];
}

/**
 * Identifica o revendedor dono de um provedor (direto ou via sub)
 * @param PDO $db Conexão com banco
 * @param int $provedor_id ID do provedor
 * @return array ['revendedor_id', 'tipo', 'revendedor_pai_id']
 */
function identificarRevendedorDono($db, $provedor_id) {
    $stmt = $db->prepare("
        SELECT revendedor_id, sub_revendedor_id 
        FROM provedores 
        WHERE id = ?
    ");
    $stmt->execute([$provedor_id]);
    $provedor = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$provedor) {
        return ['revendedor_id' => null, 'tipo' => 'admin', 'revendedor_pai_id' => null];
    }
    
    if ($provedor['sub_revendedor_id']) {
        // É sub-revendedor
        $stmt = $db->prepare("
            SELECT revendedor_pai_id 
            FROM revendedores 
            WHERE id = ?
        ");
        $stmt->execute([$provedor['sub_revendedor_id']]);
        $pai = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            'revendedor_id' => $provedor['sub_revendedor_id'],
            'tipo' => 'sub',
            'revendedor_pai_id' => $pai['revendedor_pai_id'] ?? null
        ];
    } elseif ($provedor['revendedor_id']) {
        // É revendedor direto
        return [
            'revendedor_id' => $provedor['revendedor_id'],
            'tipo' => 'master',
            'revendedor_pai_id' => null
        ];
    }
    
    // Admin ou erro
    return ['revendedor_id' => null, 'tipo' => 'admin', 'revendedor_pai_id' => null];
}
?>