const http = require('http');

// Função para fazer requisições HTTP
function makeRequest(options, data = null) {
    return new Promise((resolve, reject) => {
        const req = http.request(options, (res) => {
            let body = '';
            res.on('data', (chunk) => {
                body += chunk;
            });
            res.on('end', () => {
                try {
                    const jsonResponse = JSON.parse(body);
                    resolve({ statusCode: res.statusCode, data: jsonResponse });
                } catch (e) {
                    resolve({ statusCode: res.statusCode, data: body });
                }
            });
        });

        req.on('error', (err) => {
            reject(err);
        });

        if (data) {
            req.write(JSON.stringify(data));
        }
        req.end();
    });
}

async function testAPIs() {
    console.log('🧪 Iniciando testes da API...\n');

    try {
        // 1. Testar autenticação
        console.log('1. Testando autenticação...');
        const authOptions = {
            hostname: 'localhost',
            port: 8080,
            path: '/api/auth.php',
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            }
        };

        const authResponse = await makeRequest(authOptions, {
            action: 'login',
            usuario: 'admin',
            senha: 'admin123'
        });

        console.log('Status:', authResponse.statusCode);
        console.log('Resposta:', JSON.stringify(authResponse.data, null, 2));

        // 2. Testar provedores (usando cookie de sessão se disponível)
        console.log('\n2. Testando API de provedores...');
        const provedoresOptions = {
            hostname: 'localhost',
            port: 8080,
            path: '/api/provedores.php',
            method: 'GET',
            headers: {}
        };

        const provedoresResponse = await makeRequest(provedoresOptions);
        console.log('Status:', provedoresResponse.statusCode);
        console.log('Resposta:', JSON.stringify(provedoresResponse.data, null, 2));

        // 3. Testar stats
        console.log('\n3. Testando API de stats...');
        const statsOptions = {
            hostname: 'localhost',
            port: 8080,
            path: '/api/stats.php',
            method: 'GET'
        };

        const statsResponse = await makeRequest(statsOptions);
        console.log('Status:', statsResponse.statusCode);
        console.log('Resposta:', JSON.stringify(statsResponse.data, null, 2));

    } catch (error) {
        console.error('❌ Erro no teste:', error.message);
    }
}

testAPIs();