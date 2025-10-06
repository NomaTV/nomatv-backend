const http = require('http');

async function testAPI() {
    console.log('🧪 Testando APIs corrigidas...\n');

    // Teste 1: Autenticação
    console.log('1. Testando autenticação...');
    const authData = JSON.stringify({
        action: 'login',
        usuario: 'admin',
        senha: 'admin123'
    });

    const authOptions = {
        hostname: 'localhost',
        port: 8080,
        path: '/api/auth.php',
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Content-Length': authData.length
        }
    };

    try {
        const authResponse = await makeRequest(authOptions, authData);
        console.log('✅ Autenticação:', authResponse.statusCode);

        // Teste 2: Stats
        console.log('\n2. Testando stats...');
        const statsOptions = {
            hostname: 'localhost',
            port: 8080,
            path: '/api/stats.php',
            method: 'GET'
        };

        const statsResponse = await makeRequest(statsOptions);
        console.log('✅ Stats:', statsResponse.statusCode);

        // Teste 3: Revendedores
        console.log('\n3. Testando revendedores...');
        const revOptions = {
            hostname: 'localhost',
            port: 8080,
            path: '/api/revendedores.php?limit=10',
            method: 'GET'
        };

        const revResponse = await makeRequest(revOptions);
        console.log('✅ Revendedores:', revResponse.statusCode);

        // Teste 4: Provedores
        console.log('\n4. Testando provedores...');
        const provOptions = {
            hostname: 'localhost',
            port: 8080,
            path: '/api/provedores.php',
            method: 'GET'
        };

        const provResponse = await makeRequest(provOptions);
        console.log('✅ Provedores:', provResponse.statusCode);

        console.log('\n🎉 Todas as APIs testadas!');

    } catch (error) {
        console.error('❌ Erro:', error.message);
    }
}

function makeRequest(options, data = null) {
    return new Promise((resolve, reject) => {
        const req = http.request(options, (res) => {
            let body = '';
            res.on('data', (chunk) => body += chunk);
            res.on('end', () => resolve({ statusCode: res.statusCode, body }));
        });
        req.on('error', reject);
        if (data) req.write(data);
        req.end();
    });
}

testAPI();
