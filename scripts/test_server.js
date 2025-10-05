const express = require('express');
const app = express();
const PORT = 8080;

app.get('/', (req, res) => {
    res.send('Server OK!');
});

app.listen(PORT, '0.0.0.0', () => {
    console.log(`✅ Servidor teste rodando em http://localhost:${PORT}`);
});

// Mantém o processo vivo
process.on('SIGINT', () => {
    console.log('\n🛑 Servidor encerrado');
    process.exit(0);
});
