-- Migration: adicionar coluna video_id à tabela lessons
ALTER TABLE lessons
    ADD COLUMN video_id VARCHAR(64) NULL AFTER url_arquivo;

-- Opcional: index para consultas
CREATE INDEX idx_lessons_video_id ON lessons(video_id(32));
