-- =================================================================
-- Migração da tabela provedores - Adicionar campos faltantes
-- Versão: 4.5.1
-- Data: 05/10/2025
-- =================================================================

-- Adicionar campos faltantes na tabela provedores
ALTER TABLE provedores ADD COLUMN tipo VARCHAR(50) DEFAULT 'xtream';
ALTER TABLE provedores ADD COLUMN usuario VARCHAR(100) DEFAULT 'admin';
ALTER TABLE provedores ADD COLUMN senha VARCHAR(255) DEFAULT 'dadomockado';
ALTER TABLE provedores ADD COLUMN atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP;

-- Verificar se a migração foi aplicada
-- SELECT sql FROM sqlite_master WHERE name='provedores';