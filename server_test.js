const express = require('express');
const cookieParser = require('cookie-parser');
const path = require('path');

const app = express();
const PORT = 8080;

// Middleware para parsear JSON e cookies
app.use(express.json());
app.use(cookieParser());

// Middleware para servir arquivos estáticos (painéis HTML)
app.use(express.static(path.join(__dirname, 'public'))); // Servir arquivos da pasta public

// Rota de teste para provedores - retorna dados mock
app.get('/api/provedores.php', (req, res) => {
    console.log('📡 GET /api/provedores.php - Retornando dados mock');

    const mockData = [{
        id: 1,
        nome: 'Provedor Exemplo NomaTV',
        dns: 'http://exemplo.nomatv.com:8080',
        tipo: 'xtream',
        ativo: true,
        ativos_count: 0,
        proprietario_nome: 'Administrador',
        proprietario_usuario: 'admin',
        id_revendedor: 12345678
    }];

    res.json({
        success: true,
        message: 'Provedores listados com sucesso (dados mock).',
        data: mockData,
        timestamp: new Date().toISOString(),
        extraData: {
            pagination: {
                page: 1,
                limit: 25,
                totalRecords: 1,
                totalPages: 1,
                hasNext: false,
                hasPrev: false
            },
            stats: {
                total: 1,
                ativos: 1,
                inativos: 0
            }
        }
    });
});

// Rota padrão para index.html
app.get('/', (req, res) => {
    res.sendFile(path.join(__dirname, 'public', 'index.html'));
});

const server = app.listen(PORT, '0.0.0.0', () => {
    console.log(`✅ Servidor NomaTV rodando em http://localhost:${PORT} (MODO TESTE)`);
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