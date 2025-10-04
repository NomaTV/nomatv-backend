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
    console.log('🔄 Iniciando recriação da tabela revendedores...');

    // 1. Criar tabela temporária com dados atuais
    db.run(`
        CREATE TABLE revendedores_temp AS
        SELECT
            id_revendedor as id,
            usuario,
            senha,
            nome,
            CASE
                WHEN master = 'admin' THEN 'admin'
                WHEN master = 'sim' THEN 'revendedor'
                WHEN master = 'nao' THEN 'sub_revendedor'
                ELSE 'revendedor'
            END as tipo,
            CASE WHEN ativo = 1 THEN 'ativo' ELSE 'inativo' END as status,
            parent_id as revendedor_pai_id,
            NULL as logo_filename,
            criado_em as data_criacao
        FROM revendedores
    `, (err) => {
        if (err) {
            console.error('Erro ao criar tabela temp:', err.message);
            return;
        }
        console.log('✅ Tabela temporária criada');
    });

    // 2. Dropar tabela original
    db.run(`DROP TABLE revendedores`, (err) => {
        if (err) {
            console.error('Erro ao dropar tabela:', err.message);
            return;
        }
        console.log('✅ Tabela original removida');
    });

    // 3. Criar nova tabela com schema correto
    db.run(`
        CREATE TABLE revendedores (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            usuario VARCHAR(50) UNIQUE NOT NULL,
            senha VARCHAR(255) NOT NULL,
            nome VARCHAR(100) NOT NULL,
            email VARCHAR(100),
            telefone VARCHAR(20),
            master VARCHAR(3) DEFAULT 'nao',
            revendedor_pai_id INTEGER,
            tipo VARCHAR(20) DEFAULT 'revendedor',
            status VARCHAR(20) DEFAULT 'ativo',
            logo_filename VARCHAR(50),
            data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (revendedor_pai_id) REFERENCES revendedores(id)
        )
    `, (err) => {
        if (err) {
            console.error('Erro ao criar nova tabela:', err.message);
            return;
        }
        console.log('✅ Nova tabela criada');
    });

    // 4. Migrar dados
    db.run(`
        INSERT INTO revendedores (id, usuario, senha, nome, tipo, status, revendedor_pai_id, data_criacao)
        SELECT id, usuario, senha, nome, tipo, status, revendedor_pai_id, data_criacao
        FROM revendedores_temp
    `, (err) => {
        if (err) {
            console.error('Erro ao migrar dados:', err.message);
            return;
        }
        console.log('✅ Dados migrados');
    });

    // 5. Remover tabela temporária
    db.run(`DROP TABLE revendedores_temp`, (err) => {
        if (err) {
            console.error('Erro ao remover tabela temp:', err.message);
            return;
        }
        console.log('✅ Tabela temporária removida');
    });

    // 6. Verificar resultado
    db.all("SELECT id, usuario, tipo, status, revendedor_pai_id FROM revendedores ORDER BY id", [], (err, rows) => {
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