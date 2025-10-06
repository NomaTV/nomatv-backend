const http = require('http');

const options = {
    hostname: 'localhost',
    port: 8080,
    path: '/api/provedores.php',
    method: 'POST',
    headers: {
        'Authorization': 'Bearer MTIzNDU2Nzg6YWRtaW46MTc1OTcxMjI3OA==',
        'Content-Type': 'application/json'
    }
};

const req = http.request(options, (res) => {
    console.log(`Status: ${res.statusCode}`);
    console.log(`Headers:`, res.headers);

    res.on('data', (chunk) => {
        console.log(`Body: ${chunk}`);
    });
});

req.on('error', (e) => {
    console.error(`Problem with request: ${e.message}`);
});

const body = JSON.stringify({
    action: 'criar',
    nome: 'Teste Provedor',
    dns: 'http://teste.com:8080',
    tipo: 'xtream',
    ativo: true,
    id_revendedor: '1'
});

console.log('Sending request with body:', body);
req.write(body);
req.end();