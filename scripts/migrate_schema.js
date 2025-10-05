const sqlite3 = require('sqlite3').verbose();

// Conectar ao banco
const db = new sqlite3.Database('./db.db', (err) => {
    if (err) {
        console.error('Erro ao conectar:', err.message);
        return;
    }
    console.log('Conectado ao banco SQLite.');
});

// Executar migração
db.serialize(() => {
    console.log('🔄 Iniciando migração do schema...');

    // 1. Renomear colunas da tabela revendedores
    console.log('📝 Migrando tabela revendedores...');

    db.run(`
        ALTER TABLE revendedores
        ADD COLUMN id INTEGER;
    `, (err) => {
        if (err && !err.message.includes('duplicate column')) {
            console.error('Erro ao adicionar coluna id:', err.message);
        }
    });

    db.run(`
        ALTER TABLE revendedores
        ADD COLUMN tipo VARCHAR(20) DEFAULT 'revendedor';
    `, (err) => {
        if (err && !err.message.includes('duplicate column')) {
            console.error('Erro ao adicionar coluna tipo:', err.message);
        }
    });

    db.run(`
        ALTER TABLE revendedores
        ADD COLUMN status VARCHAR(20) DEFAULT 'ativo';
    `, (err) => {
        if (err && !err.message.includes('duplicate column')) {
            console.error('Erro ao adicionar coluna status:', err.message);
        }
    });

    db.run(`
        ALTER TABLE revendedores
        ADD COLUMN revendedor_pai_id INTEGER;
    `, (err) => {
        if (err && !err.message.includes('duplicate column')) {
            console.error('Erro ao adicionar coluna revendedor_pai_id:', err.message);
        }
    });

    db.run(`
        ALTER TABLE revendedores
        ADD COLUMN logo_filename VARCHAR(50);
    `, (err) => {
        if (err && !err.message.includes('duplicate column')) {
            console.error('Erro ao adicionar coluna logo_filename:', err.message);
        }
    });

    db.run(`
        ALTER TABLE revendedores
        ADD COLUMN data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP;
    `, (err) => {
        if (err && !err.message.includes('duplicate column')) {
            console.error('Erro ao adicionar coluna data_criacao:', err.message);
        }
    });

    // Migrar dados
    db.run(`
        UPDATE revendedores
        SET
            id = id_revendedor,
            tipo = CASE
                WHEN master = 'admin' THEN 'admin'
                WHEN master = 'sim' THEN 'revendedor'
                WHEN master = 'nao' THEN 'sub_revendedor'
                ELSE 'revendedor'
            END,
            status = CASE WHEN ativo = 1 THEN 'ativo' ELSE 'inativo' END,
            revendedor_pai_id = parent_id,
            data_criacao = criado_em
        WHERE id IS NULL OR id = 0
    `, (err) => {
        if (err) {
            console.error('Erro ao migrar dados:', err.message);
        } else {
            console.log('✅ Dados migrados com sucesso');
        }
    });

    // Verificar resultado
    db.all("SELECT id, usuario, tipo, status, revendedor_pai_id, logo_filename FROM revendedores LIMIT 5", [], (err, rows) => {
        if (err) {
            console.error('Erro ao consultar:', err.message);
        } else {
            console.log('📊 Dados após migração:');
            console.log(JSON.stringify(rows, null, 2));
        }
    });

    console.log('🎉 Migração concluída!');
});

db.close((err) => {
    if (err) {
        console.error('Erro ao fechar conexão:', err.message);
    } else {
        console.log('Conexão fechada.');
    }
});