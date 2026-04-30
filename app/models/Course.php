<?php
/**
 * Model: Course
 * Gerencia operações com cursos
 */

class Course {
    private $pdo;
    private $columnCache = [];

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->ensureSchema();
    }

    /**
     * Criar novo curso
     */
    public function criar($titulo, $descricao, $teacher_id, $categoria, $thumbnail = null, $status = 'ativo', $courseStructure = 'single_module') {
        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO courses (titulo, descricao, teacher_id, categoria, course_structure, thumbnail, status) 
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([$titulo, $descricao, $teacher_id, $categoria, $this->normalizarEstrutura($courseStructure), $thumbnail, $status]);
            return ['sucesso' => true, 'id' => $this->pdo->lastInsertId()];
        } catch (Exception $e) {
            return ['sucesso' => false, 'mensagem' => $e->getMessage()];
        }
    }

    /**
     * Obter curso por ID
     */
    public function obterPorId($id) {
        $stmt = $this->pdo->prepare(
            'SELECT c.*, u.nome as professor_nome FROM courses c 
             LEFT JOIN users u ON c.teacher_id = u.id WHERE c.id = ?'
        );
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Listar todos os cursos
     */
    public function listar($status = 'ativo', $limit = null, $offset = null, $busca = '') {
        $sql = 'SELECT c.*, u.nome as professor_nome, COUNT(e.id) as total_alunos 
                FROM courses c
                LEFT JOIN users u ON c.teacher_id = u.id
                LEFT JOIN enrollments e ON c.id = e.course_id';
        $conditions = [];
        $params = [];

        if ($status !== null) {
            $conditions[] = 'c.status = ?';
            $params[] = $status;
        }

        $busca = trim((string)$busca);
        if ($busca !== '') {
            $conditions[] = '(c.titulo LIKE ? OR c.descricao LIKE ? OR c.categoria LIKE ? OR u.nome LIKE ?)';
            $termo = '%' . $busca . '%';
            $params[] = $termo;
            $params[] = $termo;
            $params[] = $termo;
            $params[] = $termo;
        }

        if (!empty($conditions)) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $sql .= ' GROUP BY c.id ORDER BY c.created_at DESC';

        if ($limit && $offset !== null) {
            $sql .= ' LIMIT ? OFFSET ?';
            $params[] = (int)$limit;
            $params[] = (int)$offset;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Listar cursos por professor
     */
    public function listarPorProfessor($teacher_id) {
        $stmt = $this->pdo->prepare(
            'SELECT c.*, COUNT(e.id) as total_alunos FROM courses c
             LEFT JOIN enrollments e ON c.id = e.course_id
             WHERE c.teacher_id = ? GROUP BY c.id ORDER BY c.created_at DESC'
        );
        $stmt->execute([$teacher_id]);
        return $stmt->fetchAll();
    }

    /**
     * Atualizar curso
     */
    public function atualizar($id, $titulo, $descricao, $categoria, $status = null, $thumbnail = null) {
        try {
            $sql = 'UPDATE courses SET titulo = ?, descricao = ?, categoria = ?';
            $params = [$titulo, $descricao, $categoria];

            if ($status) {
                $sql .= ', status = ?';
                $params[] = $status;
            }
            if ($thumbnail) {
                $sql .= ', thumbnail = ?';
                $params[] = $thumbnail;
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
     * Deletar curso
     */
    public function deletar($id) {
        try {
            $stmt = $this->pdo->prepare('DELETE FROM courses WHERE id = ?');
            return $stmt->execute([$id]);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Buscar cursos por palavra-chave
     */
    public function buscar($termo) {
        return $this->listar('ativo', null, null, $termo);
    }

    /**
     * Contar cursos
     */
    public function contar($status = null, $busca = '') {
        $sql = 'SELECT COUNT(*) as total FROM courses c';
        $conditions = [];
        $params = [];

        $busca = trim((string)$busca);
        if ($busca !== '') {
            $sql .= ' LEFT JOIN users u ON c.teacher_id = u.id';
        }

        if ($status !== null) {
            $conditions[] = 'c.status = ?';
            $params[] = $status;
        }

        if ($busca !== '') {
            $conditions[] = '(c.titulo LIKE ? OR c.descricao LIKE ? OR c.categoria LIKE ? OR u.nome LIKE ?)';
            $termo = '%' . $busca . '%';
            $params[] = $termo;
            $params[] = $termo;
            $params[] = $termo;
            $params[] = $termo;
        }

        if (!empty($conditions)) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        return $result['total'] ?? 0;
    }

    private function ensureSchema()
    {
        $this->addColumnIfMissing('courses', 'course_structure', "VARCHAR(30) NOT NULL DEFAULT 'single_module' AFTER categoria");
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

    private function normalizarEstrutura($structure)
    {
        return trim((string)$structure) === 'multi_module' ? 'multi_module' : 'single_module';
    }
}
?>
