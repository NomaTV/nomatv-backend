<?php
/**
 * =================================================================
 * HELPERS DE RESPOSTA PADRONIZADA - NomaTV API v4.5
 * =================================================================
 *
 * Sistema de respostas padronizadas com fallbacks para garantir
 * que o frontend nunca quebre por falta de dados.
 *
 * =================================================================
 */

/**
 * Dados padrão de fallback para revendedor
 */
function getDefaultRevendedorData() {
    return [
        'id_revendedor' => 0,
        'usuario' => 'usuario_padrao',
        'nome' => 'Usuário Padrão',
        'master' => 'nao',
        'parent_id' => null,
        'ativo' => 1,
        'criado_em' => date('Y-m-d H:i:s'),
        'atualizado_em' => date('Y-m-d H:i:s'),
        'email' => 'usuario@padrao.com',
        'telefone' => '(00) 00000-0000',
        'credito' => 0.00,
        'limite_credito' => 100.00,
        'status' => 'ativo',
        'ultimo_acesso' => date('Y-m-d H:i:s'),
        'total_clientes' => 0,
        'total_vendas' => 0,
        'comissao' => 0.00
    ];
}

/**
 * Mescla dados do revendedor com fallbacks
 */
function mergeRevendedorData($dadosReais = []) {
    $padrao = getDefaultRevendedorData();
    return array_merge($padrao, $dadosReais);
}

/**
 * Resposta padronizada para sucesso com dados do revendedor
 */
function respostaSucessoPadronizada($dadosReais = [], $message = 'Operação realizada com sucesso', $extraData = null) {
    $dadosCompletos = mergeRevendedorData($dadosReais);

    echo json_encode([
        'success' => true,
        'message' => $message,
        'data' => $dadosCompletos,
        'extraData' => $extraData,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit();
}

/**
 * Resposta padronizada para erro
 */
function respostaErroPadronizada($message = 'Erro interno do servidor', $errorCode = 500, $extraData = null) {
    http_response_code($errorCode);
    echo json_encode([
        'success' => false,
        'message' => $message,
        'data' => getDefaultRevendedorData(), // Sempre retorna dados padrão em erro
        'extraData' => $extraData,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit();
}

/**
 * Resposta padronizada para não autenticado
 */
function respostaNaoAutenticadoPadronizada($message = 'Não autenticado') {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => $message,
        'data' => getDefaultRevendedorData(),
        'extraData' => null,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit();
}

/**
 * Busca dados completos do revendedor por ID
 */
function getRevendedorCompleto(PDO $db, $revendedorId) {
    try {
        $stmt = $db->prepare("SELECT * FROM revendedores WHERE id_revendedor = ?");
        $stmt->execute([$revendedorId]);
        $dados = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($dados) {
            // Adicionar campos calculados se necessário
            $dados['email'] = $dados['email'] ?? 'usuario@padrao.com';
            $dados['telefone'] = $dados['telefone'] ?? '(00) 00000-0000';
            $dados['credito'] = $dados['credito'] ?? 0.00;
            $dados['limite_credito'] = $dados['limite_credito'] ?? 100.00;
            $dados['status'] = $dados['ativo'] ? 'ativo' : 'inativo';
            $dados['ultimo_acesso'] = $dados['ultimo_acesso'] ?? $dados['atualizado_em'];
            $dados['total_clientes'] = $dados['total_clientes'] ?? 0;
            $dados['total_vendas'] = $dados['total_vendas'] ?? 0;
            $dados['comissao'] = $dados['comissao'] ?? 0.00;

            return $dados;
        }

        return getDefaultRevendedorData();
    } catch (Exception $e) {
        error_log("Erro ao buscar revendedor: " . $e->getMessage());
        return getDefaultRevendedorData();
    }
}

/**
 * Resposta padronizada genérica para qualquer entidade
 * Compatível com o padrão usado em provedores.php e outras APIs
 */
function standardResponse($success = true, $data = null, $message = '', $extraData = null) {
    $response = [
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('Y-m-d H:i:s')
    ];

    if ($extraData !== null) {
        $response['extraData'] = $extraData;
    }

    echo json_encode($response);
    exit();
}

?>
