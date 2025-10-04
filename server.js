const express = require('express');
const { spawn } = require('child_process');
const path = require('path');
const fs = require('fs');

const app = express();
const PORT = 8080;

// Middleware para servir arquivos estáticos (painéis HTML)
app.use(express.static(path.join(__dirname, 'public'))); // Pasta com admin.html, revendedor.html, etc.

// Middleware para parsear JSON
app.use(express.json());

// Rota para /api/*.php - Spawn PHP
app.all('/api/*.php', (req, res) => {
    const phpScript = req.path.replace('/api/', ''); // Ex: revendedores.php
    const phpPath = path.join(__dirname, 'api', phpScript); // Caminho para _api/phpScript
    
    if (!fs.existsSync(phpPath)) {
        return res.status(404).json({ success: false, message: 'Endpoint não encontrado' });
    }
    
    // Spawn PHP com query string e body
    const php = spawn('php', ['-f', phpPath], {
        cwd: __dirname,
        env: { ...process.env, REQUEST_METHOD: req.method, QUERY_STRING: req.url.split('?')[1] || '' }
    });
    
    // Passar body para stdin se POST/PUT
    if (req.method === 'POST' || req.method === 'PUT') {
        php.stdin.write(JSON.stringify(req.body));
        php.stdin.end();
    }
    
    let output = '';
    php.stdout.on('data', (data) => output += data);
    php.stderr.on('data', (data) => console.error('PHP Error:', data.toString()));
    
    php.on('close', (code) => {
        if (code === 0) {
            try {
                const response = JSON.parse(output);
                res.json(response);
            } catch (e) {
                res.status(500).json({ success: false, message: 'Erro no parse JSON' });
            }
        } else {
            res.status(500).json({ success: false, message: 'Erro no PHP' });
        }
    });
});

// Rota padrão para index.html
app.get('/', (req, res) => {
    res.sendFile(path.join(__dirname, 'public', 'index.html'));
});

app.listen(PORT, () => {
    console.log(`Servidor rodando em http://localhost:${PORT}`);
});

<?php
session_start();
if (!isset($_SESSION['id_revendedor'])) {
    echo json_encode(['success' => false, 'message' => 'Não autenticado']);
    exit;
}