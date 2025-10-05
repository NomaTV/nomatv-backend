const express = require('express');
const cookieParser = require('cookie-parser');

const app = express();
const PORT = 8080;

app.use(express.json());
app.use(cookieParser());

app.get('/test', (req, res) => {
    res.json({ status: 'OK', message: 'Servidor funcionando!' });
});

const server = app.listen(PORT, '0.0.0.0', () => {
    console.log(`✅ Servidor TEST rodando em http://localhost:${PORT}`);
});

// Manter o processo vivo
setInterval(() => {
    // Noop - apenas mantém o event loop ativo
}, 1000);

console.log('Servidor configurado e aguardando...');
