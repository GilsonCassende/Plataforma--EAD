<?php

/**
 * Model: Module
 * Gerencia módulos do curso e compatibilidade com cursos legados.
 */

class Module
{
    private $pdo;
    private $columnCache = [];

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
        $this->ensureSchema();
    }

    public function listarPorCurso($courseId)
    {
        $courseId = (int)$courseId;
        if ($courseId <= 0) {
            return [];
        }

        $this->sincronizarCurso($courseId);
        return $this->buscarPorCurso($courseId);
    }

    public function obterPorId($moduleId)
    {
        $stmt = $this->pdo->prepare('SELECT * FROM course_modules WHERE id = ?');
        $stmt->execute([(int)$moduleId]);
        return $stmt->fetch();
    }

    public function criar($courseId, $titulo, $descricao = '', $ordem = null, $isDefault = 0)
    {
        $courseId = (int)$courseId;
        $titulo = trim((string)$titulo);
        $descricao = trim((string)$descricao);

        if ($courseId <= 0 || $titulo === '') {
            return ['sucesso' => false, 'mensagem' => 'Curso e título do módulo são obrigatórios'];
        }

        try {
            if ($ordem === null) {
                $ordem = $this->obterProximaOrdem($courseId);
            }

            $stmt = $this->pdo->prepare(
                'INSERT INTO course_modules (course_id, titulo, descricao, ordem, is_default)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $stmt->execute([$courseId, $titulo, $descricao, (int)$ordem, (int)$isDefault]);

            return ['sucesso' => true, 'id' => (int)$this->pdo->lastInsertId()];
        } catch (Throwable $exception) {
            return ['sucesso' => false, 'mensagem' => $exception->getMessage()];
        }
    }

    public function atualizar($moduleId, $titulo, $descricao = '')
    {
        $moduleId = (int)$moduleId;
        $titulo = trim((string)$titulo);
        $descricao = trim((string)$descricao);

        if ($moduleId <= 0 || $titulo === '') {
            return ['sucesso' => false, 'mensagem' => 'Título do módulo é obrigatório'];
        }

        try {
            $stmt = $this->pdo->prepare(
                'UPDATE course_modules
                 SET titulo = ?, descricao = ?
                 WHERE id = ?'
            );
            $stmt->execute([$titulo, $descricao, $moduleId]);
            return ['sucesso' => true];
        } catch (Throwable $exception) {
            return ['sucesso' => false, 'mensagem' => $exception->getMessage()];
        }
    }

    public function mover($moduleId, $direction)
    {
        $module = $this->obterPorId($moduleId);
        if (!$module) {
            return ['sucesso' => false, 'mensagem' => 'Módulo não encontrado'];
        }

        $operator = $direction === 'up' ? '<' : '>';
        $sort = $direction === 'up' ? 'DESC' : 'ASC';
        $stmt = $this->pdo->prepare(
            "SELECT *
             FROM course_modules
             WHERE course_id = ? AND ordem {$operator} ?
             ORDER BY ordem {$sort}, id {$sort}
             LIMIT 1"
        );
        $stmt->execute([(int)$module['course_id'], (int)$module['ordem']]);
        $swap = $stmt->fetch();

        if (!$swap) {
            return ['sucesso' => true, 'mensagem' => 'Módulo já está na posição limite'];
        }

        try {
            $this->pdo->beginTransaction();
            $update = $this->pdo->prepare('UPDATE course_modules SET ordem = ? WHERE id = ?');
            $update->execute([(int)$swap['ordem'], (int)$module['id']]);
            $update->execute([(int)$module['ordem'], (int)$swap['id']]);
            $this->pdo->commit();
            return ['sucesso' => true];
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return ['sucesso' => false, 'mensagem' => $exception->getMessage()];
        }
    }

    public function obterOuCriarModuloPadrao($courseId, $courseTitle = null)
    {
        $courseId = (int)$courseId;
        if ($courseId <= 0) {
            return null;
        }

        $stmt = $this->pdo->prepare(
            'SELECT *
             FROM course_modules
             WHERE course_id = ? AND is_default = 1
             ORDER BY ordem ASC, id ASC
             LIMIT 1'
        );
        $stmt->execute([$courseId]);
        $module = $stmt->fetch();
        if ($module) {
            return $module;
        }

        $all = $this->buscarPorCurso($courseId);
        if (!empty($all)) {
            return $all[0];
        }

        $title = trim((string)$courseTitle);
        if ($title === '') {
            $title = 'Módulo principal';
        } else {
            $title = 'Módulo principal';
        }

        $create = $this->criar($courseId, $title, 'Estrutura inicial do curso.', 1, 1);
        if (empty($create['sucesso'])) {
            return null;
        }

        return $this->obterPorId((int)$create['id']);
    }

    public function sincronizarCurso($courseId, $courseTitle = null, $courseStructure = null)
    {
        $courseId = (int)$courseId;
        if ($courseId <= 0) {
            return;
        }

        $structure = $this->normalizarEstrutura($courseStructure);
        if ($structure === null) {
            $stmt = $this->pdo->prepare('SELECT titulo, course_structure FROM courses WHERE id = ?');
            $stmt->execute([$courseId]);
            $courseRow = $stmt->fetch();
            $courseTitle = $courseTitle ?? ($courseRow['titulo'] ?? null);
            $structure = $this->normalizarEstrutura($courseRow['course_structure'] ?? null);
        }

        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM course_modules WHERE course_id = ?');
        $stmt->execute([$courseId]);
        $count = (int)$stmt->fetchColumn();

        if ($count === 0 || $structure === 'single_module') {
            $defaultModule = $this->obterOuCriarModuloPadrao($courseId, $courseTitle);
            if ($defaultModule) {
                $this->pdo->prepare('UPDATE course_modules SET is_default = CASE WHEN id = ? THEN 1 ELSE is_default END WHERE course_id = ?')
                    ->execute([(int)$defaultModule['id'], $courseId]);
            }
        }

        $defaultModule = $this->obterOuCriarModuloPadrao($courseId, $courseTitle);
        if (!$defaultModule) {
            return;
        }

        if ($this->hasColumn('lessons', 'module_id')) {
            $stmt = $this->pdo->prepare(
                'UPDATE lessons
                 SET module_id = ?
                 WHERE course_id = ? AND (module_id IS NULL OR module_id = 0)'
            );
            $stmt->execute([(int)$defaultModule['id'], $courseId]);
        }

        if ($this->hasColumn('quizzes', 'module_id')) {
            $stmt = $this->pdo->prepare(
                'UPDATE quizzes q
                 JOIN lessons l ON l.id = q.lesson_id
                 SET q.module_id = l.module_id
                 WHERE l.course_id = ? AND (q.module_id IS NULL OR q.module_id = 0) AND q.lesson_id IS NOT NULL'
            );
            $stmt->execute([$courseId]);
        }
    }

    private function obterProximaOrdem($courseId)
    {
        $stmt = $this->pdo->prepare('SELECT COALESCE(MAX(ordem), 0) + 1 FROM course_modules WHERE course_id = ?');
        $stmt->execute([(int)$courseId]);
        return (int)$stmt->fetchColumn();
    }

    private function buscarPorCurso($courseId)
    {
        $stmt = $this->pdo->prepare(
            'SELECT *
             FROM course_modules
             WHERE course_id = ?
             ORDER BY ordem ASC, id ASC'
        );
        $stmt->execute([(int)$courseId]);
        return $stmt->fetchAll();
    }

    private function ensureSchema()
    {
        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS course_modules (
                id INT AUTO_INCREMENT PRIMARY KEY,
                course_id INT NOT NULL,
                titulo VARCHAR(255) NOT NULL,
                descricao TEXT NULL,
                ordem INT NOT NULL DEFAULT 1,
                is_default TINYINT(1) NOT NULL DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_course_modules_course (course_id),
                INDEX idx_course_modules_order (course_id, ordem)
            )'
        );

        $this->addColumnIfMissing('courses', 'course_structure', "VARCHAR(30) NOT NULL DEFAULT 'single_module' AFTER categoria");
        $this->addColumnIfMissing('lessons', 'module_id', 'INT NULL AFTER course_id');
        $this->addColumnIfMissing('quizzes', 'module_id', 'INT NULL AFTER course_id');
    }

    private function addColumnIfMissing($table, $column, $definition)
    {
        if ($this->hasColumn($table, $column)) {
            return;
        }

        try {
            $this->pdo->exec("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
        } catch (Throwable $exception) {
            $fallback = preg_replace('/\s+AFTER\s+`?[a-z0-9_]+`?$/i', '', trim($definition));
            if ($fallback === trim($definition)) {
                throw $exception;
            }
            $this->pdo->exec("ALTER TABLE `$table` ADD COLUMN `$column` $fallback");
        }

        $this->columnCache[$table][$column] = true;
    }

    private function hasColumn($table, $column)
    {
        if (isset($this->columnCache[$table][$column])) {
            return $this->columnCache[$table][$column];
        }

        $stmt = $this->pdo->prepare(
            'SELECT 1
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND COLUMN_NAME = ?
             LIMIT 1'
        );
        $stmt->execute([$table, $column]);
        $exists = (bool)$stmt->fetchColumn();
        $this->columnCache[$table][$column] = $exists;
        return $exists;
    }

    private function normalizarEstrutura($value)
    {
        $value = trim((string)$value);
        if ($value === 'multi_module') {
            return 'multi_module';
        }
        if ($value === 'single_module') {
            return 'single_module';
        }
        return null;
    }
}
