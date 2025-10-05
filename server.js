const express = require('express');
const cookieParser = require('cookie-parser');
const { spawn } = require('child_process');
const path = require('path');
const fs = require('fs');

const app = express();
const PORT = 8080;

// Middleware para parsear JSON e cookies
app.use(express.json());
app.use(cookieParser());

// Middleware para servir arquivos estáticos (painéis HTML)
app.use(express.static(path.join(__dirname, 'public'))); // Servir arquivos da pasta public

// Rota para /api/*.php - Executa PHP real
app.all('/api/*.php', (req, res) => {
    const phpScript = req.path.replace('/api/', ''); // Ex: auth.php
    const phpPath = path.join(__dirname, 'api', phpScript); // Caminho para api/auth.php
    
    // 🔍 DEBUG: Log da requisição
    console.log(`\n📡 ${req.method} ${req.path}`);
    console.log(`🍪 Cookie: ${req.headers.cookie || '(nenhum)'}`);
    if (req.body && Object.keys(req.body).length > 0) {
        console.log(`📦 Body:`, JSON.stringify(req.body));
    }
    
    if (!fs.existsSync(phpPath)) {
        return res.status(404).json({ success: false, message: `Endpoint ${phpScript} não encontrado` });
    }
    
    // Caminho para PHP
    const phpExecutable = 'C:\\Users\\Asus\\Downloads\\_public_html (21)\\_api (12)\\php\\php.exe';
    
    // Preparar body como string JSON para enviar via variável de ambiente
    const bodyJson = JSON.stringify(req.body);
    
    // Spawn PHP com dados via variável de ambiente
    const php = spawn(phpExecutable, [phpPath], {
        cwd: path.join(__dirname, 'api'),
        env: { 
            ...process.env,
            REQUEST_METHOD: req.method,
            QUERY_STRING: new URLSearchParams(req.query).toString(),
            CONTENT_TYPE: req.headers['content-type'] || 'application/json',
            HTTP_COOKIE: req.headers.cookie || '',
            HTTP_AUTHORIZATION: req.headers.authorization || '', // Token de autenticação
            REQUEST_BODY: bodyJson, // Enviar body via variável de ambiente
            CONTENT_LENGTH: bodyJson.length.toString()
        }
    });
    
    // Não usar stdin, usar variável de ambiente REQUEST_BODY
    php.stdin.end();
    
    let output = '';
    let errorOutput = '';
    
    php.stdout.on('data', (data) => output += data.toString());
    php.stderr.on('data', (data) => errorOutput += data.toString());
    
    php.on('close', (code) => {
        if (code === 0 && output.trim()) {
            try {
                // Tenta parsear como JSON
                const response = JSON.parse(output);
                
                console.log(`✅ Resposta PHP:`, response.success ? 'SUCCESS' : 'FAILED');

                res.json(response);
            } catch (e) {
                console.error('❌ Erro ao parsear JSON:', e.message);
                console.error('Output recebido:', output.substring(0, 500));
                // Se não for JSON, retorna como texto
                res.send(output);
            }
        } else {
            console.error(`❌ Erro no PHP ${phpScript} (código ${code}):`, errorOutput);
            res.status(500).json({ 
                success: false, 
                message: 'Erro ao processar requisição PHP',
                error: errorOutput || 'Erro desconhecido'
            });
        }
    });
});

// Rota padrão para index.html
app.get('/', (req, res) => {
    res.sendFile(path.join(__dirname, 'public', 'index.html'));
});

const server = app.listen(PORT, '0.0.0.0', () => {
    console.log(`✅ Servidor NomaTV rodando em http://localhost:${PORT}`);
    console.log(`📡 Aguardando conexões...`);
});

// Tratamento de erros do servidor
server.on('error', (err) => {
    console.error('❌ Erro no servidor:', err.message);
    if (err.code === 'EADDRINUSE') {
        console.error(`❌ Porta ${PORT} já está em uso!`);
        process.exit(1);
    }
});

// ✅ IMPORTANTE: Evitar crash por erros não capturados
process.on('uncaughtException', (err) => {
    console.error('❌ ERRO NÃO CAPTURADO:', err.message);
    console.error(err.stack);
    // Não fazer exit, apenas logar
});

process.on('unhandledRejection', (reason, promise) => {
    console.error('❌ PROMISE REJEITADA:', reason);
    // Não fazer exit, apenas logar
});