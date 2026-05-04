ALTER TABLE lessons
    ADD COLUMN lesson_transcript LONGTEXT NULL AFTER conteudo,
    ADD COLUMN transcript_generated_at DATETIME NULL AFTER lesson_transcript;

CREATE TABLE IF NOT EXISTS lesson_ai_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    lesson_id INT NOT NULL,
    question_text TEXT NOT NULL,
    answer_text LONGTEXT NULL,
    status VARCHAR(32) NOT NULL DEFAULT 'success',
    error_message VARCHAR(1000) NULL,
    response_time_ms INT NULL,
    created_at DATETIME NOT NULL,
    INDEX idx_lesson_ai_logs_user_created (user_id, created_at),
    INDEX idx_lesson_ai_logs_lesson_created (lesson_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
