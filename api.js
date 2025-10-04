// =================================================================
// 📦 api.js – Ponte REST Central do NomaApp
// Versão: 4.5 - ALINHADO COM AUTENTICAÇÃO POR SESSÃO
// =================================================================

const API_BASE = '/api/'; // Base para todos os endpoints

/**
 * Monta os headers padrão para as requisições, incluindo dados de autenticação.
 * @param {boolean} isFormData - Indica se a requisição é para um FormData.
 * @returns {object} Objeto com os headers.
 */
function getAuthHeaders(isFormData = false) {
    const headers = {};
    if (!isFormData) {
        headers['Content-Type'] = 'application/json';
    }
    // A autenticação agora é gerenciada por cookies de sessão pelo navegador.
    // Não precisamos mais de tokens ou dados específicos nos headers.
    return headers;
}

/**
 * Função base para realizar TODAS as chamadas fetch, garantindo padronização e robustez.
 * @param {string} endpoint - O caminho do endpoint da API (ex: 'auth.php').
 * @param {object} options - As opções da requisição fetch (method, body, etc.).
 * @param {boolean} expectBlob - Se verdadeiro, espera uma resposta blob (arquivo) em vez de JSON.
 * @returns {Promise<object|Blob>} - A resposta da API em formato JSON ou Blob.
 */
async function apiFetch(endpoint, options = {}, expectBlob = false) {
    const isFormData = options.body instanceof FormData;
    
    // A lógica de sessão do servidor é tratada automaticamente pelo navegador via cookies.
    // O backend irá verificar o estado da sessão. Se não houver sessão válida, ele retornará 401.

    let config = {
        ...options,
        headers: {
            ...getAuthHeaders(isFormData),
            ...options.headers
        },
        credentials: 'include' // ✅ GARANTIR QUE COOKIES SEMPRE SEJAM ENVIADOS
    };

    if (config.body && !isFormData) {
        config.body = JSON.stringify(config.body);
    }
    
    try {
        const response = await fetch(`${API_BASE}${endpoint}`, config);
        
        // Se o servidor retornar 401, o usuário não está autenticado.
        // Redireciona para a página de login.
        if (response.status === 401) {
            handleLogout();
            throw new Error('Sessão expirada ou não autenticada.');
        }

        if (expectBlob) {
            if (!response.ok) {
                const errorText = await response.text();
                throw {
                    error: `Erro HTTP ${response.status}: ${response.statusText}`,
                    message: errorText || `Não foi possível processar a resposta do servidor como blob.`
                };
            }
            return await response.blob();
        }

        const responseText = await response.text();
        let responseBody;
        try {
            responseBody = JSON.parse(responseText);
        } catch {
            responseBody = {
                success: false,
                error: `Erro HTTP ${response.status}: ${response.statusText}`,
                message: `Resposta inválida do servidor: ` + (responseText || `Não foi possível processar a resposta.`)
            };
        }

        if (!response.ok) {
            throw responseBody;
        }
        
        return responseBody;

    } catch (error) {
        console.error('Erro na chamada da API:', error);
        return Promise.reject(error.error || error.message ? error : { error: 'Erro desconhecido na API.', message: 'Ocorreu um erro inesperado. Tente novamente.' });
    }
}

// --- 🔐 AUTENTICAÇÃO E NAVEGAÇÃO ---

/**
 * Lógica de login ROBUSTA e PADRONIZADA.
 * Trata diferentes formatos de resposta do backend e garante dados consistentes.
 * @param {string} username
 * @param {string} password
 */
async function login(username, password) {
    try {
        const response = await apiFetch('auth.php', {
            method: 'POST',
            body: { action: 'login', username, password }
        });

        if (response.success && response.data) {
            const userData = response.data;
            
            // ✅ NORMALIZAR DADOS (tratando diferentes formatos possíveis)
            const revendedorId = userData.id_revendedor || userData.id || userData.revendedorId || 'unknown';
            const masterType = userData.master || userData.masterType || 'nao';
            const userName = userData.nome || userData.usuario || userData.username || userData.name || 'Usuário';
            const userEmail = userData.email || '';
            
            // ✅ DETERMINAR TIPO DE USUÁRIO de forma robusta
            let userType = userData.type || userData.userType;
            if (!userType) {
                // Fallback: determinar tipo baseado no master
                if (masterType === 'admin') {
                    userType = 'admin';
                } else if (masterType === 'sim') {
                    userType = 'revendedor';
                } else {
                    userType = 'sub_revendedor';
                }
            }
            
            // ✅ SALVAR DADOS PADRONIZADOS no sessionStorage
            sessionStorage.setItem('revendedorId', revendedorId);
            sessionStorage.setItem('masterType', masterType);
            sessionStorage.setItem('userName', userName);
            sessionStorage.setItem('userType', userType);
            sessionStorage.setItem('userEmail', userEmail);
            sessionStorage.setItem('loginTime', new Date().toISOString());
            
            // ✅ LOG para verificar dados salvos
            console.log('✅ Dados padronizados salvos no sessionStorage:', {
                revendedorId: revendedorId,
                masterType: masterType,
                userName: userName,
                userType: userType,
                userEmail: userEmail
            });
            
            // ✅ DEBUG: Mostrar dados originais vs padronizados
            console.log('📋 Dados originais do backend:', userData);
            
            // ✅ REDIRECIONAR baseado no tipo padronizado
            switch (userType) {
                case 'admin':
                    console.log('🔄 Redirecionando para admin.html...');
                    window.location.href = 'admin.html';
                    break;
                case 'revendedor':
                    console.log('🔄 Redirecionando para revendedor.html...');
                    window.location.href = 'revendedor.html';
                    break;
                case 'sub_revendedor':
                    console.log('🔄 Redirecionando para sub_revendedor.html...');
                    window.location.href = 'sub_revendedor.html';
                    break;
                default:
                    console.warn('⚠️ Tipo de usuário desconhecido:', userType);
                    // Fallback baseado no masterType
                    if (masterType === 'admin') {
                        window.location.href = 'admin.html';
                    } else if (masterType === 'sim') {
                        window.location.href = 'revendedor.html';
                    } else {
                        window.location.href = 'sub_revendedor.html';
                    }
            }
        } else {
            throw new Error(response.message || 'Credenciais inválidas.');
        }

    } catch (error) {
        console.error('❌ Erro no login:', error);
        throw error; // Re-throw para o index.html tratar
    }
}

/**
 * Lógica de logout.
 * Chama o endpoint de logout para destruir a sessão no servidor.
 */
async function handleLogout() {
    try {
        await apiFetch('auth.php', {
            method: 'POST',
            body: { action: 'logout' }
        });
    } finally {
        // Limpa o sessionStorage.
        sessionStorage.clear();
        // Redireciona para a página de login.
        window.location.href = 'index.html';
    }
}

/**
 * Verifica o estado de autenticação no frontend e redireciona se necessário.
 * Esta função deve ser chamada no início do script de cada página de painel.
 */
function checkAuthentication() {
    const loggedIn = sessionStorage.getItem('revendedorId') && sessionStorage.getItem('masterType');
    if (!loggedIn) {
        // Se não houver dados, o usuário não está logado. Redireciona para o login.
        window.location.href = 'index.html';
    }
}


// --- 📊 DASHBOARD E ESTATÍSTICAS ---

function getDashboardStats() {
    return apiFetch('stats.php', { method: 'GET' });
}

// --- 🏢 REVENDEDORES ---

function listarRevendedores(filtros = {}) {
    const params = new URLSearchParams(filtros).toString();
    return apiFetch(`revendedores.php?${params}`, { method: 'GET' });
}

function criarRevendedor(data) {
    return apiFetch('revendedores.php', {
        method: 'POST',
        body: { action: 'criar', ...data }
    });
}

function atualizarRevendedor(id, data) {
    return apiFetch(`revendedores.php?id=${id}`, {
        method: 'PUT',
        body: data
    });
}

function deletarRevendedor(id) {
    return apiFetch(`revendedores.php?id=${id}`, {
        method: 'DELETE'
    });
}

function resetarSenhaRevendedor(id) {
    return apiFetch('revendedores.php', {
        method: 'POST',
        body: { action: 'reset_senha', id: id }
    });
}

function toggleStatusRevendedor(id) {
    return apiFetch(`revendedores.php?id=${id}`, {
        method: 'PUT',
        body: { action: 'toggle_status' }
    }).then(response => {
        if (response && response.success) {
            if (response.data && response.data.novo_status === undefined) {
                response.data.novo_status = true;
            }
            if (!response.data) {
                response.data = { novo_status: true };
            }
        }
        return response;
    }).catch(error => {
        console.error('Erro no toggle status:', error);
        throw {
            error: error.message || error.error || 'Erro ao alterar status. Tente novamente.',
            originalError: error
        };
    });
}

function verRedeRevendedor(id) {
    return apiFetch(`rede_revendedor_endpoint.php?id=${id}`, { method: 'GET' });
}


// --- ⚙️ CONFIGURAÇÕES E BACKUP ---

function getConfiguracoes() {
    return apiFetch('configuracoes.php', { method: 'GET' });
}

function salvarConfiguracoes(dados) {
    return apiFetch('configuracoes.php', { method: 'PUT', body: dados });
}

function criarBackupCompleto() {
    return apiFetch('configuracoes.php', {
        method: 'POST',
        body: { action: 'criar_backup' }
    }, true);
}

function listarBackups() {
    return apiFetch('configuracoes.php?action=listar_backups', { method: 'GET' });
}

function baixarBackup(filename) {
    return apiFetch(`configuracoes.php?action=baixar_backup&filename=${encodeURIComponent(filename)}`, {
        method: 'GET'
    }, true);
}

function restaurarBackup(filename) {
    return apiFetch('configuracoes.php', {
        method: 'POST',
        body: { action: 'restaurar_backup', filename: filename }
    });
}

// --- 🔗 PROVEDORES ---

function listarProvedores(filtros = {}) {
    const params = new URLSearchParams(filtros).toString();
    return apiFetch(`provedores.php?${params}`, { method: 'GET' });
}

function criarProvedor(data) {
    return apiFetch('provedores.php', {
        method: 'POST',
        body: { action: 'criar', ...data }
    });
}

function atualizarProvedor(id, data) {
    return apiFetch(`provedores.php?id=${id}`, {
        method: 'PUT',
        body: data
    });
}

function deletarProvedor(id) {
    return apiFetch(`provedores.php?id=${id}`, {
        method: 'DELETE'
    });
}

// --- 📱 CLIENT IDS / ATIVOS ---

function getClientIds(params = {}) {
    const queryString = new URLSearchParams(params).toString();
    return apiFetch(`client_ids.php?${queryString}`, { method: 'GET' });
}

function addClientId(data) {
    return apiFetch('client_ids.php', {
        method: 'POST',
        body: { ...data, action: 'criar' }
    });
}

function editClientId(clientId, data) {
    return apiFetch(`client_ids.php?id=${clientId}`, { // Ação PUT para atualização
        method: 'PUT',
        body: { ...data, action: 'atualizar' }
    });
}

function updateClientIdStatus(clientIds, action) {
    const idsArray = Array.isArray(clientIds) ? clientIds : [clientIds];
    return apiFetch('client_ids.php', {
        method: 'PUT',
        body: { client_ids: idsArray, acao: action }
    });
}

function deleteClientId(clientIds) {
    const idsArray = Array.isArray(clientIds) ? clientIds : [clientIds];
    return apiFetch('client_ids.php', {
        method: 'DELETE',
        body: { client_ids: idsArray }
    });
}

function exportarClientIds(formato = 'csv', filtros = {}) {
    return apiFetch('client_ids.php', {
        method: 'POST',
        body: { action: 'exportar', formato: formato, filtros: filtros }
    }, true);
}

// --- 📦 PLANOS E PACOTES ---

function listarPlanos() {
    return apiFetch('planos.php', { method: 'GET' });
}

function criarPlano(data) {
    return apiFetch('planos.php', { method: 'POST', body: data });
}

function atualizarPlano(id, data) {
    return apiFetch(`planos.php?id=${id}`, {
        method: 'PUT',
        body: data
    });
}

function deletarPlano(id) {
    return apiFetch(`planos.php?id=${id}`, {
        method: 'DELETE'
    });
}

// --- 💰 FINANCEIRO ---

function getFinanceiro() {
    return apiFetch('financeiro.php', { method: 'GET' });
}

function processarCobrancaAPI() {
    return apiFetch('financeiro.php', {
        method: 'POST',
        body: { action: 'processar_cobranca' }
    });
}

function marcarComoPago(revendedorId, valorPago, observacoes = '', faturaId = null, metodo = 'manual') {
    return apiFetch('financeiro.php', {
        method: 'POST',
        body: {
            action: 'marcar_pago',
            id_revendedor: revendedorId,
            valor_pago: valorPago,
            observacoes: observacoes,
            id_fatura: faturaId,
            metodo_pagamento: metodo
        }
    });
}

function bloquearRevendedoresVencidosAPI() {
    return apiFetch('financeiro.php', {
        method: 'POST',
        body: { action: 'bloquear_vencidos' }
    });
}

function atualizarVencimentoRevendedorAPI(revendedorId, novaDataVencimento) {
    return apiFetch('financeiro.php', {
        method: 'POST',
        body: { action: 'atualizar_vencimento', id_revendedor: revendedorId, nova_data_vencimento: novaDataVencimento }
    });
}

function gerarFaturaManualAPI(idRevendedor, valorTotal, dataVencimento, observacoes, tipoCobranca) {
    return apiFetch('financeiro.php', {
        method: 'POST',
        body: {
            action: 'gerar_fatura_manual',
            id_revendedor: idRevendedor,
            valor_total: valorTotal,
            data_vencimento: dataVencimento,
            observacoes: observacoes,
            tipo_cobranca: tipoCobranca
        }
    });
}

// --- 🌐 CONTROLE DE IPs ---

function listarIPs(filtros = {}) {
    const params = new URLSearchParams(filtros).toString();
    return apiFetch('ips.php', { method: 'GET' });
}

function salvarIP(dados) {
    return apiFetch('ips.php', { method: 'POST', body: dados });
}

// --- 📜 LOGS E RELATÓRIOS ---

function listarLogs(filtros = {}) {
    const params = new URLSearchParams(filtros).toString();
    return apiFetch('logs.php', { method: 'GET' });
}

function getRelatorio(tipo = 'dashboard', periodo = '30') {
    const params = new URLSearchParams({ tipo, periodo }).toString();
    return apiFetch('relatorios.php', { method: 'GET' });
}

function exportarRelatorioAPI(tipo = 'dashboard', periodo = '30', formato = 'csv') {
    return apiFetch('relatorios.php', {
        method: 'POST',
        body: { action: 'exportar', formato: formato, filtros: { tipo: tipo, periodo: periodo } }
    }, true);
}

// --- ⚖️ PERMISSÕES ---

function listarPermissoes() {
    return apiFetch('permissoes.php', { method: 'GET' });
}

function salvarPermissoes(permissoes) {
    return apiFetch('permissoes.php', { method: 'PUT', body: { permissoes } });
}

// --- 🛡️ SEGURANÇA ---

function alterarSenhaAdmin(dados) {
    return apiFetch('seguranca.php', {
        method: 'POST',
        body: { action: 'alterar_senha_admin', ...dados }
    });
}

function verificarForcaSenha(senha) {
    return apiFetch('seguranca.php', {
        method: 'POST',
        body: { action: 'verificar_forca_senha', senha: senha }
    });
}

function getServerInfo() {
    return apiFetch('seguranca.php', {
        method: 'POST',
        body: { action: 'server_info' }
    });
}


// --- 🎨 BRANDING ---

/**
 * Carrega URL do logo via proxy inteligente com fallback.
 * USADO PELO SMART TV (index_casca.html).
 * @param {number} revendedorId - ID do revendedor (do sessionStorage)
 * @returns {Promise<string>} URL do logo
 */
function loadBrandingLogo(revendedorId) {
    if (!revendedorId) {
        return Promise.resolve('https://webnoma.shop/logos/nomaapp.png'); // Fallback padrão
    }
    // Logo proxy retorna URL em texto puro (não JSON)
    return fetch(`${API_BASE}logo_proxy.php?id=${revendedorId}`)
        .then(response => response.text())
        .catch(() => 'https://webnoma.shop/logos/nomaapp.png');
}

/**
 * Consulta status do logo do revendedor (para painéis de administração).
 * @param {number} revendedorId - ID do revendedor (opcional - usa sessão se não informado)
 * @returns {Promise<object>} Dados do logo
 */
function getBrandingLogo(revendedorId = null) {
    // Se tiver ID, passa via GET, senão o backend usa a sessão
    const params = revendedorId ? `?revendedor_id=${revendedorId}` : '';
    return apiFetch(`branding/get.php${params}`, { method: 'GET' });
}

/**
 * Faz upload do logo do revendedor logado.
 * @param {File} file - Arquivo de imagem
 * @returns {Promise<object>} Resposta do servidor
 */
function uploadBrandingLogo(file) {
    const formData = new FormData();
    formData.append('logo', file);

    return apiFetch('branding/upload.php', {
        method: 'POST',
        body: formData
    });
}

/**
 * Remove o logo do revendedor logado.
 * @returns {Promise<object>} Resposta do servidor
 */
function deleteBrandingLogo() {
    return apiFetch('branding/delete.php', {
        method: 'POST'
    });
}
