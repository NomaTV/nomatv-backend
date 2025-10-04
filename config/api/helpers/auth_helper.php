<?php
/**
 * Helper de Autenticação - NomaTV v4.2
 * Gerado automaticamente pelo setup
 */

function verificarLogin() {
    // Implementação básica - será expandida na Fase 2
    return ['id' => 1, 'username' => 'admin', 'tipo' => 'admin'];
}

function verificarPermissao($permissoesRequeridas) {
    return verificarLogin();
}

function standardResponse($success, $data = null, $message = null, $error = null) {
    $response = [
        'success' => $success,
        'data' => $data,
        'message' => $message,
        'error' => $error
    ];
    
    header('Content-Type: application/json');
    echo json_encode($response);
}

function logAction($db, $acao, $detalhes = '') {
    // Log básico - será expandido na Fase 2
    try {
        $stmt = $db->prepare("INSERT INTO auditoria (usuario, acao, detalhes, ip) VALUES (?, ?, ?, ?)");
        $stmt->execute(['system', $acao, $detalhes, $_SERVER['REMOTE_ADDR'] ?? 'localhost']);
    } catch (Exception $e) {
        error_log("Erro no log: " . $e->getMessage());
    }
}
?>