-- =================================================================
-- NomaTV - Script de Inicialização do Banco de Dados
-- Versão: 4.5
-- Data: 04/10/2025
-- =================================================================

-- Tabela principal: revendedores
CREATE TABLE IF NOT EXISTS revendedores (
    id_revendedor INTEGER PRIMARY KEY,
    usuario VARCHAR(50) UNIQUE NOT NULL,
    senha VARCHAR(255) NOT NULL,
    nome VARCHAR(100) NOT NULL,
    master VARCHAR(10) NOT NULL,
    parent_id INTEGER,
    ativo BOOLEAN DEFAULT 1,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES revendedores(id_revendedor)
);

-- Tabela: provedores
CREATE TABLE IF NOT EXISTS provedores (
    id_provedor INTEGER PRIMARY KEY AUTOINCREMENT,
    nome VARCHAR(100) UNIQUE NOT NULL,
    dns VARCHAR(255) NOT NULL,
    id_revendedor INTEGER NOT NULL,
    ativo BOOLEAN DEFAULT 1,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_revendedor) REFERENCES revendedores(id_revendedor)
);

-- Tabela: client_ids (Ativos)
CREATE TABLE IF NOT EXISTS client_ids (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    client_id VARCHAR(36) UNIQUE NOT NULL,
    provedor_id INTEGER NOT NULL,
    id_revendedor INTEGER NOT NULL,
    usuario VARCHAR(100),
    ultima_atividade DATETIME DEFAULT CURRENT_TIMESTAMP,
    ativo BOOLEAN DEFAULT 1,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (provedor_id) REFERENCES provedores(id_provedor),
    FOREIGN KEY (id_revendedor) REFERENCES revendedores(id_revendedor)
);

-- Tabela: planos
CREATE TABLE IF NOT EXISTS planos (
    id_plano INTEGER PRIMARY KEY AUTOINCREMENT,
    nome VARCHAR(100) NOT NULL,
    preco DECIMAL(10,2) NOT NULL,
    duracao_dias INTEGER NOT NULL,
    ativo BOOLEAN DEFAULT 1,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Tabela: faturas
CREATE TABLE IF NOT EXISTS faturas (
    id_fatura INTEGER PRIMARY KEY AUTOINCREMENT,
    id_revendedor INTEGER NOT NULL,
    id_plano INTEGER NOT NULL,
    valor DECIMAL(10,2) NOT NULL,
    vencimento DATE NOT NULL,
    status VARCHAR(20) DEFAULT 'pendente',
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_revendedor) REFERENCES revendedores(id_revendedor),
    FOREIGN KEY (id_plano) REFERENCES planos(id_plano)
);

-- Tabela: pagamentos
CREATE TABLE IF NOT EXISTS pagamentos (
    id_pagamento INTEGER PRIMARY KEY AUTOINCREMENT,
    id_fatura INTEGER NOT NULL,
    valor_pago DECIMAL(10,2) NOT NULL,
    metodo_pagamento VARCHAR(50),
    comprovante TEXT,
    pago_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_fatura) REFERENCES faturas(id_fatura)
);

-- =================================================================
-- Inserir usuário admin padrão (senha: admin123)
-- Hash bcrypt gerado: $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi
-- =================================================================
INSERT OR IGNORE INTO revendedores (id_revendedor, usuario, senha, nome, master, ativo)
VALUES (12345678, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrador', 'admin', 1);

-- =================================================================
-- Inserir planos padrão para testes
-- =================================================================
INSERT OR IGNORE INTO planos (id_plano, nome, preco, duracao_dias, ativo)
VALUES 
(1, 'Mensal', 29.90, 30, 1),
(2, 'Trimestral', 79.90, 90, 1),
(3, 'Semestral', 149.90, 180, 1),
(4, 'Anual', 269.90, 365, 1);

-- =================================================================
-- Fim do script
-- =================================================================
