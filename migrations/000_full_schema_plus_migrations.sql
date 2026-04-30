-- Consolidated schema + migrations for import in XAMPP
-- Generated: 000_full_schema_plus_migrations.sql

-- Base schema
-- ========================================
CREATE DATABASE IF NOT EXISTS ead_platform;
USE ead_platform;

-- ========================================
-- TABELA: USUÁRIOS
-- ========================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    senha_hash VARCHAR(255) NOT NULL,
    role ENUM('aluno', 'professor', 'admin') DEFAULT 'aluno',
    fotografia VARCHAR(255),
    email_verified TINYINT(1) NOT NULL DEFAULT 0,
    verification_token VARCHAR(255) NULL,
    verification_token_expires_at DATETIME NULL,
    reset_token VARCHAR(255) NULL,
    reset_token_expires_at DATETIME NULL,
    login_attempts INT NOT NULL DEFAULT 0,
    locked_until DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX (email),
    INDEX (role),
    INDEX (verification_token),
    INDEX (reset_token)
);

-- ========================================
-- TABELA: CURSOS
-- ========================================
CREATE TABLE IF NOT EXISTS courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    descricao TEXT,
    teacher_id INT NOT NULL,
    thumbnail VARCHAR(255),
    categoria VARCHAR(100),
    status ENUM('ativo', 'inativo', 'rascunho') DEFAULT 'ativo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX (teacher_id),
    INDEX (status)
);

-- ========================================
-- TABELA: AULAS
-- ========================================
CREATE TABLE IF NOT EXISTS lessons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    titulo VARCHAR(255) NOT NULL,
    descricao TEXT,
    tipo ENUM('video', 'pdf', 'texto', 'arquivo') DEFAULT 'texto',
    conteudo LONGTEXT,
    url_arquivo VARCHAR(255),
    ordem INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    INDEX (course_id),
    INDEX (ordem)
);

-- ========================================
-- TABELA: MATRÍCULAS
-- ========================================
CREATE TABLE IF NOT EXISTS enrollments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    course_id INT NOT NULL,
    progress INT DEFAULT 0,
    data_inscricao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_conclusao DATETIME,
    UNIQUE KEY unique_enrollment (user_id, course_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    INDEX (user_id),
    INDEX (course_id)
);

-- ========================================
-- TABELA: QUIZZES
-- ========================================
CREATE TABLE IF NOT EXISTS quizzes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lesson_id INT NOT NULL,
    titulo VARCHAR(255) NOT NULL,
    descricao TEXT,
    tentativas_maximas INT DEFAULT 3,
    pontos_totais INT DEFAULT 10,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE,
    INDEX (lesson_id)
);

-- ========================================
-- TABELA: QUESTÕES
-- ========================================
CREATE TABLE IF NOT EXISTS questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    quiz_id INT NOT NULL,
    texto TEXT NOT NULL,
    tipo ENUM('multipla', 'verdadeiro_falso', 'dissertativa') DEFAULT 'multipla',
    opcoes JSON,
    resposta_correta VARCHAR(255),
    ordem INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE,
    INDEX (quiz_id),
    INDEX (ordem)
);

-- ========================================
-- TABELA: RESULTADOS DOS QUIZZES
-- ========================================
CREATE TABLE IF NOT EXISTS quiz_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    quiz_id INT NOT NULL,
    score DECIMAL(5, 2),
    resposta_usuario JSON,
    tentativa INT DEFAULT 1,
    tempo_gasto INT,
    data_realizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE,
    INDEX (user_id),
    INDEX (quiz_id),
    INDEX (data_realizacao)
);

-- ========================================
-- TABELA: PROGRESSO DE AULAS
-- ========================================
CREATE TABLE IF NOT EXISTS lesson_progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    lesson_id INT NOT NULL,
    concluida BOOLEAN DEFAULT FALSE,
    data_conclusao DATETIME,
    tempo_assistido INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_progress (user_id, lesson_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE,
    INDEX (user_id),
    INDEX (lesson_id)
);

-- ========================================
-- TABELA: MENSAGENS
-- ========================================
CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    remetente_id INT NOT NULL,
    destinatario_id INT NOT NULL,
    assunto VARCHAR(255),
    corpo TEXT NOT NULL,
    lida BOOLEAN DEFAULT FALSE,
    data_envio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (remetente_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (destinatario_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX (destinatario_id),
    INDEX (lida)
);

-- ========================================
-- TABELA: CERTIFICADOS
-- ========================================
CREATE TABLE IF NOT EXISTS certificates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    course_id INT NOT NULL,
    module_id INT NULL,
    type VARCHAR(20) NOT NULL DEFAULT 'course',
    certificate_code VARCHAR(100) UNIQUE,
    grade DECIMAL(5,2) NULL,
    issued_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_emissao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    codigo_certificado VARCHAR(100) UNIQUE,
    nota_final DECIMAL(5,2) NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    INDEX (user_id),
    INDEX (course_id),
    INDEX (module_id)
);

-- ========================================
-- Migrations adicionais
-- ========================================

-- 009_add_video_id_to_lessons.sql
ALTER TABLE lessons
    ADD COLUMN video_id VARCHAR(64) NULL AFTER url_arquivo;

CREATE INDEX IF NOT EXISTS idx_lessons_video_id ON lessons(video_id(32));

-- ========================================
-- DADOS DE TESTE (OPCIONAL)
-- ========================================
-- Admin
INSERT INTO users (nome, email, senha_hash, role) VALUES 
('Admin Sistema', 'admin@ead.com', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcg7b3XeKeUxWdeS86AGR0Ky/.G', 'admin');

-- Professores
INSERT INTO users (nome, email, senha_hash, role) VALUES 
('Prof. João Silva', 'joao@ead.com', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcg7b3XeKeUxWdeS86AGR0Ky/.G', 'professor'),
('Prof. Maria Santos', 'maria@ead.com', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcg7b3XeKeUxWdeS86AGR0Ky/.G', 'professor');

-- Alunos
INSERT INTO users (nome, email, senha_hash, role) VALUES 
('Carlos Pereira', 'carlos@ead.com', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcg7b3XeKeUxWdeS86AGR0Ky/.G', 'aluno'),
('Ana Costa', 'ana@ead.com', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcg7b3XeKeUxWdeS86AGR0Ky/.G', 'aluno'),
('Bruno Oliveira', 'bruno@ead.com', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcg7b3XeKeUxWdeS86AGR0Ky/.G', 'aluno');
