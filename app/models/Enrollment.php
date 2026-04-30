<?php
/**
 * Model: Enrollment
 * Gerencia matriculas dos alunos em cursos
 */

class Enrollment {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Matricular aluno em curso
     */
    public function matricular($user_id, $course_id) {
        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO enrollments (user_id, course_id, progress) VALUES (?, ?, 0)
                 ON DUPLICATE KEY UPDATE progress = progress'
            );
            $stmt->execute([$user_id, $course_id]);
            return ['sucesso' => true];
        } catch (Exception $e) {
            return ['sucesso' => false, 'mensagem' => $e->getMessage()];
        }
    }

    /**
     * Verificar se aluno está matriculado
     */
    public function estaMatriculado($user_id, $course_id) {
        $stmt = $this->pdo->prepare(
            'SELECT id FROM enrollments WHERE user_id = ? AND course_id = ?'
        );
        $stmt->execute([$user_id, $course_id]);
        return $stmt->fetch() ? true : false;
    }

    /**
     * Obter cursos do aluno
     */
    public function obterCursosAluno($user_id) {
        $stmt = $this->pdo->prepare(
            'SELECT c.*, e.progress, e.data_inscricao, e.data_conclusao, u.nome as professor_nome 
             FROM enrollments e
             JOIN courses c ON e.course_id = c.id
             LEFT JOIN users u ON c.teacher_id = u.id
             WHERE e.user_id = ? ORDER BY e.data_inscricao DESC'
        );
        $stmt->execute([$user_id]);
        return $stmt->fetchAll();
    }

    /**
     * Obter alunos de um curso
     */
    public function obterAlunosCurso($course_id) {
        $stmt = $this->pdo->prepare(
            'SELECT
                u.*,
                e.progress,
                e.data_inscricao,
                e.data_conclusao,
                lessons.total_lessons,
                progress_stats.completed_lessons,
                progress_stats.last_lesson_at,
                quiz_stats.total_quizzes,
                quiz_stats.approved_quizzes,
                quiz_stats.total_attempts,
                quiz_stats.average_score,
                quiz_stats.last_attempt_at,
                certificate_stats.total_certificates,
                certificate_stats.last_certificate_at
             FROM enrollments e
             JOIN users u ON e.user_id = u.id
             LEFT JOIN (
                SELECT
                    l.course_id,
                    COUNT(*) AS total_lessons
                FROM lessons l
                GROUP BY l.course_id
             ) lessons ON lessons.course_id = e.course_id
             LEFT JOIN (
                SELECT
                    lp.user_id,
                    l.course_id,
                    SUM(CASE WHEN lp.concluida = 1 THEN 1 ELSE 0 END) AS completed_lessons,
                    MAX(lp.data_conclusao) AS last_lesson_at
                FROM lesson_progress lp
                JOIN lessons l ON l.id = lp.lesson_id
                GROUP BY lp.user_id, l.course_id
             ) progress_stats ON progress_stats.user_id = e.user_id AND progress_stats.course_id = e.course_id
             LEFT JOIN (
                SELECT
                    qa.user_id,
                    course_map.course_id,
                    COUNT(DISTINCT qa.quiz_id) AS total_quizzes,
                    COUNT(DISTINCT CASE WHEN qa.aprovado = 1 THEN qa.quiz_id END) AS approved_quizzes,
                    COUNT(*) AS total_attempts,
                    ROUND(AVG(qa.percentual), 1) AS average_score,
                    MAX(qa.data_realizacao) AS last_attempt_at
                FROM quiz_attempts qa
                JOIN (
                    SELECT q.id AS quiz_id, COALESCE(q.course_id, l.course_id) AS course_id
                    FROM quizzes q
                    LEFT JOIN lessons l ON l.id = q.lesson_id
                ) course_map ON course_map.quiz_id = qa.quiz_id
                GROUP BY qa.user_id, course_map.course_id
             ) quiz_stats ON quiz_stats.user_id = e.user_id AND quiz_stats.course_id = e.course_id
             LEFT JOIN (
                SELECT
                    cert.user_id,
                    cert.course_id,
                    COUNT(*) AS total_certificates,
                    MAX(COALESCE(cert.issued_at, cert.data_emissao)) AS last_certificate_at
                FROM certificates cert
                GROUP BY cert.user_id, cert.course_id
             ) certificate_stats ON certificate_stats.user_id = e.user_id AND certificate_stats.course_id = e.course_id
             WHERE e.course_id = ?
             ORDER BY e.progress DESC, e.data_inscricao DESC, u.nome ASC'
        );
        $stmt->execute([$course_id]);
        return $stmt->fetchAll();
    }

    /**
     * Atualizar progresso
     */
    public function atualizarProgresso($user_id, $course_id, $progress) {
        try {
            $stmt = $this->pdo->prepare(
                'UPDATE enrollments
                 SET progress = ?,
                     data_conclusao = CASE
                         WHEN ? = 100 THEN COALESCE(data_conclusao, NOW())
                         ELSE NULL
                     END
                 WHERE user_id = ? AND course_id = ?'
            );
            return $stmt->execute([$progress, $progress, $user_id, $course_id]);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Marcar como concluído
     */
    public function marcarConcluido($user_id, $course_id) {
        try {
            $stmt = $this->pdo->prepare(
                'UPDATE enrollments SET progress = 100, data_conclusao = NOW() WHERE user_id = ? AND course_id = ?'
            );
            return $stmt->execute([$user_id, $course_id]);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Remover matricula
     */
    public function remover($user_id, $course_id) {
        try {
            $stmt = $this->pdo->prepare(
                'DELETE FROM enrollments WHERE user_id = ? AND course_id = ?'
            );
            return $stmt->execute([$user_id, $course_id]);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Contar alunos em curso
     */
    public function contarAlunos($course_id) {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) as total FROM enrollments WHERE course_id = ?');
        $stmt->execute([$course_id]);
        $result = $stmt->fetch();
        return $result['total'] ?? 0;
    }

    /**
     * Obter alunos de todos os cursos de um professor com paginação e busca
     */
    public function obterAlunosPorProfessor($teacher_id, $limit = 12, $offset = 0, $search = '') {
        $sql = 'SELECT
                    u.*,
                    student_courses.courses_count,
                    student_courses.progress,
                    student_courses.data_inscricao,
                    student_courses.data_conclusao,
                    student_courses.enrolled_courses,
                    lessons.total_lessons,
                    progress_stats.completed_lessons,
                    progress_stats.last_lesson_at,
                    quiz_stats.total_quizzes,
                    quiz_stats.approved_quizzes,
                    quiz_stats.total_attempts,
                    quiz_stats.average_score,
                    quiz_stats.last_attempt_at,
                    certificate_stats.total_certificates,
                    certificate_stats.last_certificate_at
                FROM users u
                JOIN (
                    SELECT
                        e.user_id,
                        COUNT(DISTINCT e.course_id) AS courses_count,
                        ROUND(AVG(e.progress), 0) AS progress,
                        MIN(e.data_inscricao) AS data_inscricao,
                        MAX(e.data_conclusao) AS data_conclusao,
                        GROUP_CONCAT(DISTINCT CONCAT(c.id, "::", c.titulo) ORDER BY c.titulo SEPARATOR "||") AS enrolled_courses
                    FROM enrollments e
                    JOIN courses c ON c.id = e.course_id
                    WHERE c.teacher_id = ?
                    GROUP BY e.user_id
                ) student_courses ON student_courses.user_id = u.id
                LEFT JOIN (
                    SELECT
                        e.user_id,
                        SUM(COALESCE(lesson_counts.total_lessons, 0)) AS total_lessons
                    FROM enrollments e
                    JOIN courses c ON c.id = e.course_id
                    LEFT JOIN (
                        SELECT course_id, COUNT(*) AS total_lessons
                        FROM lessons
                        GROUP BY course_id
                    ) lesson_counts ON lesson_counts.course_id = e.course_id
                    WHERE c.teacher_id = ?
                    GROUP BY e.user_id
                ) lessons ON lessons.user_id = u.id
                LEFT JOIN (
                    SELECT
                        lp.user_id,
                        COUNT(CASE WHEN lp.concluida = 1 THEN 1 END) AS completed_lessons,
                        MAX(lp.data_conclusao) AS last_lesson_at
                    FROM lesson_progress lp
                    JOIN lessons l ON l.id = lp.lesson_id
                    JOIN courses c ON c.id = l.course_id
                    WHERE c.teacher_id = ?
                    GROUP BY lp.user_id
                ) progress_stats ON progress_stats.user_id = u.id
                LEFT JOIN (
                    SELECT
                        qa.user_id,
                        COUNT(DISTINCT qa.quiz_id) AS total_quizzes,
                        COUNT(DISTINCT CASE WHEN qa.aprovado = 1 THEN qa.quiz_id END) AS approved_quizzes,
                        COUNT(*) AS total_attempts,
                        ROUND(AVG(qa.percentual), 1) AS average_score,
                        MAX(qa.data_realizacao) AS last_attempt_at
                    FROM quiz_attempts qa
                    JOIN (
                        SELECT q.id AS quiz_id, COALESCE(q.course_id, l.course_id) AS course_id
                        FROM quizzes q
                        LEFT JOIN lessons l ON l.id = q.lesson_id
                    ) course_map ON course_map.quiz_id = qa.quiz_id
                    JOIN courses c ON c.id = course_map.course_id
                    WHERE c.teacher_id = ?
                    GROUP BY qa.user_id
                ) quiz_stats ON quiz_stats.user_id = u.id
                LEFT JOIN (
                    SELECT
                        cert.user_id,
                        COUNT(*) AS total_certificates,
                        MAX(COALESCE(cert.issued_at, cert.data_emissao)) AS last_certificate_at
                    FROM certificates cert
                    JOIN courses c ON c.id = cert.course_id
                    WHERE c.teacher_id = ?
                    GROUP BY cert.user_id
                ) certificate_stats ON certificate_stats.user_id = u.id
                WHERE 1=1';

        $params = [$teacher_id, $teacher_id, $teacher_id, $teacher_id, $teacher_id];
        if (!empty($search)) {
            $sql .= ' AND (u.nome LIKE ? OR u.email LIKE ?)';
            $like = '%' . $search . '%';
            $params[] = $like;
            $params[] = $like;
        }

        $sql .= ' ORDER BY student_courses.progress DESC, student_courses.data_inscricao DESC, u.nome ASC LIMIT ? OFFSET ?';
        $params[] = (int)$limit;
        $params[] = (int)$offset;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Contar alunos para todos os cursos de um professor (com filtro opcional)
     */
    public function contarAlunosPorProfessor($teacher_id, $search = '') {
        $sql = 'SELECT COUNT(DISTINCT e.user_id) as total
                FROM enrollments e
                JOIN users u ON e.user_id = u.id
                JOIN courses c ON e.course_id = c.id
                WHERE c.teacher_id = ?';
        $params = [$teacher_id];
        if (!empty($search)) {
            $sql .= ' AND (u.nome LIKE ? OR u.email LIKE ?)';
            $like = '%' . $search . '%';
            $params[] = $like;
            $params[] = $like;
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $r = $stmt->fetch();
        return $r['total'] ?? 0;
    }
}
?>
