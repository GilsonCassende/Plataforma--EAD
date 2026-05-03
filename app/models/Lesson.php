<?php

/**
 * Model: Lesson
 * Gerencia operações com aulas
 */

class Lesson
{
    private $pdo;
    private $columnCache = [];

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
        $this->ensureSchema();
    }

    /**
     * Criar nova aula
     */
    public function criar($course_id, $titulo, $descricao, $tipo, $conteudo, $url_arquivo = null, $video_id = null, $ordem = 1, $module_id = null, $resumo = null, $audio_url = null, $audio_storage_disk = null, $audio_storage_key = null)
    {
        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO lessons (course_id, module_id, titulo, descricao, tipo, conteudo, resumo, url_arquivo, audio_url, audio_storage_disk, audio_storage_key, video_id, ordem) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([$course_id, $module_id ?: null, $titulo, $descricao, $tipo, $conteudo, $resumo, $url_arquivo, $audio_url, $audio_storage_disk, $audio_storage_key, $video_id, $ordem]);
            return ['sucesso' => true, 'id' => $this->pdo->lastInsertId()];
        } catch (Exception $e) {
            return ['sucesso' => false, 'mensagem' => $e->getMessage()];
        }
    }

    /**
     * Obter aula por ID
     */
    public function obterPorId($id)
    {
        $stmt = $this->pdo->prepare(
            'SELECT l.*, m.titulo as module_title, m.ordem as module_ordem
             FROM lessons l
             LEFT JOIN course_modules m ON m.id = l.module_id
             WHERE l.id = ?'
        );
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Listar aulas de um curso
     */
    public function listarPorCurso($course_id)
    {
        $stmt = $this->pdo->prepare(
            'SELECT l.*, m.titulo as module_title, m.ordem as module_ordem
             FROM lessons l
             LEFT JOIN course_modules m ON m.id = l.module_id
             WHERE l.course_id = ?
             ORDER BY COALESCE(m.ordem, 999999) ASC, l.ordem ASC, l.id ASC'
        );
        $stmt->execute([$course_id]);
        return $stmt->fetchAll();
    }

    public function listarPorModulo($module_id)
    {
        $stmt = $this->pdo->prepare(
            'SELECT l.*, m.titulo as module_title, m.ordem as module_ordem
             FROM lessons l
             LEFT JOIN course_modules m ON m.id = l.module_id
             WHERE l.module_id = ?
             ORDER BY l.ordem ASC, l.id ASC'
        );
        $stmt->execute([(int)$module_id]);
        return $stmt->fetchAll();
    }

    /**
     * Atualizar aula
     */
    public function atualizar($id, $titulo, $descricao, $tipo, $conteudo, $url_arquivo = null, $video_id = null, $ordem = null, $module_id = null, $resumo = null, $audio_url = null, $audio_storage_disk = null, $audio_storage_key = null)
    {
        try {
            $sql = 'UPDATE lessons SET titulo = ?, descricao = ?, tipo = ?, conteudo = ?, resumo = ?, audio_url = ?, audio_storage_disk = ?, audio_storage_key = ?';
            $params = [$titulo, $descricao, $tipo, $conteudo, $resumo, $audio_url, $audio_storage_disk, $audio_storage_key];

            if ($url_arquivo) {
                $sql .= ', url_arquivo = ?';
                $params[] = $url_arquivo;
            }
            if ($video_id) {
                $sql .= ', video_id = ?';
                $params[] = $video_id;
            }
            if ($ordem !== null) {
                $sql .= ', ordem = ?';
                $params[] = $ordem;
            }
            if ($module_id !== null) {
                $sql .= ', module_id = ?';
                $params[] = $module_id ?: null;
            }

            $sql .= ' WHERE id = ?';
            $params[] = $id;

            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($params);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Deletar aula
     */
    public function deletar($id)
    {
        try {
            $stmt = $this->pdo->prepare('DELETE FROM lessons WHERE id = ?');
            return $stmt->execute([$id]);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Contar aulas de um curso
     */
    public function contar($course_id)
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) as total FROM lessons WHERE course_id = ?');
        $stmt->execute([$course_id]);
        $result = $stmt->fetch();
        return $result['total'] ?? 0;
    }

    /**
     * Reordenar aulas por array de ids (ordem definida pelo array)
     */
    public function reordenar(array $orderedIds)
    {
        try {
            $this->pdo->beginTransaction();
            $stmt = $this->pdo->prepare('UPDATE lessons SET ordem = ? WHERE id = ?');
            $ord = 1;
            foreach ($orderedIds as $id) {
                $stmt->execute([$ord, $id]);
                $ord++;
            }
            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            return false;
        }
    }

    private function ensureSchema()
    {
        $this->addColumnIfMissing('lessons', 'module_id', 'INT NULL AFTER course_id');
        $this->addColumnIfMissing('lessons', 'resumo', 'TEXT NULL AFTER conteudo');
        $this->addColumnIfMissing('lessons', 'audio_url', 'VARCHAR(255) NULL AFTER url_arquivo');
        $this->addColumnIfMissing('lessons', 'audio_storage_disk', 'VARCHAR(32) NULL AFTER audio_url');
        $this->addColumnIfMissing('lessons', 'audio_storage_key', 'VARCHAR(255) NULL AFTER audio_storage_disk');
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
}
