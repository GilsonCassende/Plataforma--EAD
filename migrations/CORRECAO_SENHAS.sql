-- ========================================
-- SCRIPT DE CORREÇÃO - Atualizar Senhas
-- ========================================
-- Execute este script se já importou o banco antes

USE ead_platform;

-- Atualizar hash de todas as contas de teste para "senha123"
UPDATE users SET senha_hash = '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcg7b3XeKeUxWdeS86AGR0Ky/.G' 
WHERE email IN (
    'admin@ead.com',
    'joao@ead.com',
    'maria@ead.com',
    'carlos@ead.com',
    'ana@ead.com',
    'bruno@ead.com'
);

-- Verificar se atualizou
SELECT email, role FROM users WHERE role IN ('admin', 'professor', 'aluno');
