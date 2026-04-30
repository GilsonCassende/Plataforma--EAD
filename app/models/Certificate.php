<?php

/**
 * Model: Certificate
 * Gerencia emissão, validação, consulta pública e download de certificados.
 */
class Certificate
{
    private $pdo;
    private $columnCache = [];

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
        $this->ensureSchema();
    }

    public function syncCourseCertificates($userId, $courseId)
    {
        $userId = (int)$userId;
        $courseId = (int)$courseId;
        if ($userId <= 0 || $courseId <= 0) {
            return ['issued' => [], 'revoked' => 0, 'state' => $this->buildEligibilitySnapshot($userId, $courseId)];
        }

        $state = $this->buildEligibilitySnapshot($userId, $courseId);
        $issued = [];
        $revoked = 0;

        foreach (($state['modules'] ?? []) as $moduleState) {
            $moduleId = (int)($moduleState['module_id'] ?? 0);
            if ($moduleId <= 0) {
                continue;
            }

            if (!empty($moduleState['eligible'])) {
                $certificate = $this->generateCertificate($userId, $courseId, 'module', $moduleId);
                if (!empty($certificate['issued_now'])) {
                    $issued[] = $certificate;
                }
            } else {
                $revoked += $this->revokeCertificateIfExists($userId, $courseId, 'module', $moduleId);
            }
        }

        if (!empty($state['course']['eligible'])) {
            $certificate = $this->generateCertificate($userId, $courseId, 'course', null);
            if (!empty($certificate['issued_now'])) {
                $issued[] = $certificate;
            }
        } else {
            $revoked += $this->revokeCertificateIfExists($userId, $courseId, 'course', null);
        }

        return [
            'issued' => $issued,
            'revoked' => $revoked,
            'state' => $this->buildEligibilitySnapshot($userId, $courseId),
        ];
    }

    public function generateCertificate($userId, $courseId, $type = 'course', $moduleId = null)
    {
        $userId = (int)$userId;
        $courseId = (int)$courseId;
        $moduleId = $moduleId !== null ? (int)$moduleId : null;
        $type = $type === 'module' ? 'module' : 'course';

        $eligibility = $this->validateEligibility($userId, $courseId, $type, $moduleId);
        if (empty($eligibility['eligible'])) {
            return ['success' => false, 'eligible' => false, 'message' => $eligibility['message'] ?? 'Certificado indisponível'];
        }

        $existing = $this->getOwnedCertificate($userId, $courseId, $type, $moduleId);
        if ($existing) {
            $this->updateCertificateGrade($existing['id'], (float)($eligibility['grade'] ?? 0));
            $updated = $this->getCertificateById((int)$existing['id']);
            return ['success' => true, 'eligible' => true, 'issued_now' => false, 'certificate' => $updated ?: $existing];
        }

        $code = $this->generateUniqueCode();
        $stmt = $this->pdo->prepare(
            'INSERT INTO certificates (
                user_id, course_id, module_id, type, certificate_code, grade, issued_at,
                codigo_certificado, nota_final, data_emissao
            ) VALUES (?, ?, ?, ?, ?, ?, NOW(), ?, ?, NOW())'
        );
        $stmt->execute([
            $userId,
            $courseId,
            $moduleId,
            $type,
            $code,
            $eligibility['grade'],
            $code,
            $eligibility['grade'],
        ]);

        $certificate = $this->getCertificateById((int)$this->pdo->lastInsertId());
        return ['success' => true, 'eligible' => true, 'issued_now' => true, 'certificate' => $certificate];
    }

    public function buildEligibilitySnapshot($userId, $courseId)
    {
        $userId = (int)$userId;
        $courseId = (int)$courseId;

        $course = $this->fetchCourse($courseId);
        $user = $this->fetchUser($userId);
        $modules = $this->fetchModules($courseId);
        $moduleStates = [];

        foreach ($modules as $module) {
            $moduleStates[] = $this->buildModuleState($userId, $courseId, $course, $module);
        }

        $allModulesEligible = !empty($moduleStates);
        foreach ($moduleStates as $moduleState) {
            if (empty($moduleState['eligible'])) {
                $allModulesEligible = false;
                break;
            }
        }

        $finalQuizzes = $this->fetchFinalQuizzes($courseId);
        $approvedFinalQuizzes = 0;
        $finalQuizGrade = 0.0;
        foreach ($finalQuizzes as $quiz) {
            $best = $this->fetchBestAttempt($userId, (int)$quiz['id']);
            $passed = $best && (float)($best['pontuacao'] ?? 0) >= (float)($quiz['nota_minima'] ?? 10);
            if ($passed) {
                $approvedFinalQuizzes++;
                $finalQuizGrade = max($finalQuizGrade, (float)($best['pontuacao'] ?? 0));
            }
        }

        $courseGrade = $this->calculateCourseGrade($userId, $courseId);
        $courseEligible = $allModulesEligible
            && !empty($finalQuizzes)
            && $approvedFinalQuizzes === count($finalQuizzes);

        return [
            'course_id' => $courseId,
            'course_title' => $course['titulo'] ?? 'Curso',
            'user_id' => $userId,
            'student_name' => $user['nome'] ?? 'Aluno',
            'modules' => $moduleStates,
            'course' => [
                'eligible' => $courseEligible,
                'type' => 'course',
                'grade' => $courseGrade,
                'completed_modules' => array_sum(array_map(static function ($moduleState) {
                    return !empty($moduleState['eligible']) ? 1 : 0;
                }, $moduleStates)),
                'total_modules' => count($moduleStates),
                'approved_final_quizzes' => $approvedFinalQuizzes,
                'total_final_quizzes' => count($finalQuizzes),
                'final_quiz_grade' => $finalQuizGrade,
                'message' => $courseEligible
                    ? 'Todos os módulos e avaliações finais foram concluídos com aprovação.'
                    : 'Conclua todos os módulos e seja aprovado no quiz final para liberar o certificado do curso.',
                'certificate' => $this->hydrateCertificate($this->getOwnedCertificate($userId, $courseId, 'course', null)),
            ],
        ];
    }

    public function validateEligibility($userId, $courseId, $type = 'course', $moduleId = null)
    {
        $snapshot = $this->buildEligibilitySnapshot($userId, $courseId);
        if ($type === 'module') {
            foreach (($snapshot['modules'] ?? []) as $moduleState) {
                if ((int)($moduleState['module_id'] ?? 0) === (int)$moduleId) {
                    return $moduleState;
                }
            }
            return ['eligible' => false, 'message' => 'Módulo não encontrado para emissão do certificado.'];
        }

        return $snapshot['course'] ?? ['eligible' => false, 'message' => 'Curso não encontrado para emissão do certificado.'];
    }

    public function listUserCertificatesForCourse($userId, $courseId)
    {
        $stmt = $this->pdo->prepare(
            'SELECT cert.*, c.titulo AS course_title, m.titulo AS module_title, u.nome AS student_name
             FROM certificates cert
             JOIN courses c ON c.id = cert.course_id
             JOIN users u ON u.id = cert.user_id
             LEFT JOIN course_modules m ON m.id = cert.module_id
             WHERE cert.user_id = ? AND cert.course_id = ?
             ORDER BY cert.type DESC, cert.issued_at DESC, cert.id DESC'
        );
        $stmt->execute([(int)$userId, (int)$courseId]);
        $rows = $stmt->fetchAll();

        $byType = [
            'course' => null,
            'modules' => [],
        ];

        foreach ($rows as $row) {
            $certificate = $this->hydrateCertificate($row);
            if (($row['type'] ?? 'course') === 'module') {
                $byType['modules'][(int)($row['module_id'] ?? 0)] = $certificate;
                continue;
            }

            $byType['course'] = $certificate;
        }

        return $byType;
    }

    public function findByCode($code)
    {
        $code = trim((string)$code);
        if ($code === '') {
            return null;
        }

        $stmt = $this->pdo->prepare(
            'SELECT cert.*, c.titulo AS course_title, m.titulo AS module_title, u.nome AS student_name
             FROM certificates cert
             JOIN courses c ON c.id = cert.course_id
             JOIN users u ON u.id = cert.user_id
             LEFT JOIN course_modules m ON m.id = cert.module_id
             WHERE cert.certificate_code = ? OR cert.codigo_certificado = ?
             LIMIT 1'
        );
        $stmt->execute([$code, $code]);
        $row = $stmt->fetch();
        return $this->hydrateCertificate($row ?: null);
    }

    public function getOwnedCertificate($userId, $courseId, $type = 'course', $moduleId = null)
    {
        $type = $type === 'module' ? 'module' : 'course';
        $sql = 'SELECT cert.*, c.titulo AS course_title, m.titulo AS module_title, u.nome AS student_name
                FROM certificates cert
                JOIN courses c ON c.id = cert.course_id
                JOIN users u ON u.id = cert.user_id
                LEFT JOIN course_modules m ON m.id = cert.module_id
                WHERE cert.user_id = ? AND cert.course_id = ? AND cert.type = ?';
        $params = [(int)$userId, (int)$courseId, $type];

        if ($type === 'module') {
            $sql .= ' AND cert.module_id = ?';
            $params[] = (int)$moduleId;
        } else {
            $sql .= ' AND (cert.module_id IS NULL OR cert.module_id = 0)';
        }

        $sql .= ' ORDER BY cert.id DESC LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch() ?: null;
    }

    public function buildPublicVerificationData($code)
    {
        $certificate = $this->findByCode($code);
        if (!$certificate) {
            return [
                'valid' => false,
                'code' => trim((string)$code),
                'message' => 'Nenhum certificado foi encontrado com esse código.',
            ];
        }

        return [
            'valid' => true,
            'code' => $certificate['certificate_code'],
            'certificate' => $certificate,
            'student_masked' => $this->maskStudentName((string)($certificate['student_name'] ?? 'Aluno')),
            'issued_at_formatted' => !empty($certificate['issued_at']) ? date('d/m/Y', strtotime((string)$certificate['issued_at'])) : '-',
        ];
    }

    public function buildPdfPayload(array $certificate)
    {
        $isModule = ($certificate['type'] ?? 'course') === 'module';
        $title = $isModule ? 'Certificado de Conclusao de Modulo' : 'Certificado de Conclusao';
        $scopeLabel = $isModule ? ($certificate['module_title'] ?? '-') : ($certificate['course_title'] ?? '-');
        $issuedDate = !empty($certificate['issued_at']) ? date('d/m/Y', strtotime((string)$certificate['issued_at'])) : date('d/m/Y');
        $grade = number_format((float)($certificate['grade'] ?? 0), 1, ',', '.');
        $student = (string)($certificate['student_name'] ?? 'Aluno');
        $code = (string)($certificate['certificate_code'] ?? '-');
        $course = (string)($certificate['course_title'] ?? '-');

        return [
            'title' => $title,
            'platform' => 'Plataforma EAD',
            'subtitle' => 'Certificacao academica oficial',
            'student' => $student,
            'achievement_line' => $isModule ? 'concluiu com sucesso o modulo' : 'concluiu com sucesso o curso',
            'scope' => $scopeLabel,
            'course' => $course,
            'issued_date' => $issuedDate,
            'grade' => $grade . '/20',
            'verification_code' => $code,
            'verification_text' => 'Verifique em /certificado/' . $code,
            'signature_left' => 'Plataforma EAD',
            'signature_right' => 'Diretoria Academica',
            'filename' => $this->buildPdfFilename($certificate),
        ];
    }

    private function buildModuleState($userId, $courseId, array $course, array $module)
    {
        $moduleId = (int)($module['id'] ?? 0);
        $courseStructure = trim((string)($course['course_structure'] ?? 'single_module'));
        $lessonIds = $this->fetchModuleLessonIds($courseId, $moduleId);
        $totalLessons = count($lessonIds);
        $completedLessons = $totalLessons > 0 ? $this->countCompletedLessons($userId, $lessonIds) : 0;

        $moduleQuizzes = $this->fetchModuleQuizzes($courseId, $moduleId);
        $approvedModuleQuizzes = 0;
        $quizGrades = [];
        foreach ($moduleQuizzes as $quiz) {
            $best = $this->fetchBestAttempt($userId, (int)$quiz['id']);
            $passed = $best && (float)($best['pontuacao'] ?? 0) >= (float)($quiz['nota_minima'] ?? 10);
            if ($passed) {
                $approvedModuleQuizzes++;
                $quizGrades[] = (float)($best['pontuacao'] ?? 0);
            }
        }

        $lessonsComplete = $totalLessons > 0 && $completedLessons === $totalLessons;
        $moduleEligible = $lessonsComplete
            && (
                (!empty($moduleQuizzes) && $approvedModuleQuizzes === count($moduleQuizzes))
                || ($courseStructure === 'single_module' && empty($moduleQuizzes))
            );
        $grade = !empty($quizGrades) ? round(array_sum($quizGrades) / count($quizGrades), 2) : 0.0;

        return [
            'module_id' => $moduleId,
            'type' => 'module',
            'module_title' => $module['titulo'] ?? 'Módulo',
            'eligible' => $moduleEligible,
            'grade' => $grade,
            'completed_lessons' => $completedLessons,
            'total_lessons' => $totalLessons,
            'approved_quizzes' => $approvedModuleQuizzes,
            'total_quizzes' => count($moduleQuizzes),
            'message' => $moduleEligible
                ? 'Módulo concluído com 100% das aulas e critérios de avaliação atendidos.'
                : (
                    $courseStructure === 'single_module' && empty($moduleQuizzes)
                        ? 'Conclua todas as aulas deste módulo para validar a etapa antes do certificado final.'
                        : 'Conclua todas as aulas do módulo e seja aprovado no quiz do módulo.'
                ),
            'certificate' => $this->hydrateCertificate($this->getOwnedCertificate($userId, $courseId, 'module', $moduleId)),
        ];
    }

    private function revokeCertificateIfExists($userId, $courseId, $type, $moduleId)
    {
        $existing = $this->getOwnedCertificate($userId, $courseId, $type, $moduleId);
        if (!$existing) {
            return 0;
        }

        $stmt = $this->pdo->prepare('DELETE FROM certificates WHERE id = ?');
        $stmt->execute([(int)$existing['id']]);
        return $stmt->rowCount() > 0 ? 1 : 0;
    }

    private function updateCertificateGrade($certificateId, $grade)
    {
        $stmt = $this->pdo->prepare(
            'UPDATE certificates
             SET grade = ?, nota_final = ?
             WHERE id = ?'
        );
        $stmt->execute([(float)$grade, (float)$grade, (int)$certificateId]);
    }

    private function calculateCourseGrade($userId, $courseId)
    {
        require_once __DIR__ . '/Quiz.php';
        $quizModel = new Quiz($this->pdo);
        $nota = $quizModel->calcularNotaFinalCurso((int)$courseId, (int)$userId);
        return (float)($nota['nota_final'] ?? 0);
    }

    private function fetchBestAttempt($userId, $quizId)
    {
        $stmt = $this->pdo->prepare(
            'SELECT *
             FROM quiz_attempts
             WHERE user_id = ? AND quiz_id = ?
             ORDER BY pontuacao DESC, percentual DESC, id DESC
             LIMIT 1'
        );
        $stmt->execute([(int)$userId, (int)$quizId]);
        return $stmt->fetch() ?: null;
    }

    private function fetchCourse($courseId)
    {
        $stmt = $this->pdo->prepare('SELECT * FROM courses WHERE id = ?');
        $stmt->execute([(int)$courseId]);
        return $stmt->fetch() ?: [];
    }

    private function fetchUser($userId)
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([(int)$userId]);
        return $stmt->fetch() ?: [];
    }

    private function fetchModules($courseId)
    {
        $stmt = $this->pdo->prepare('SELECT * FROM course_modules WHERE course_id = ? ORDER BY ordem ASC, id ASC');
        $stmt->execute([(int)$courseId]);
        return $stmt->fetchAll();
    }

    private function fetchModuleLessonIds($courseId, $moduleId)
    {
        $stmt = $this->pdo->prepare('SELECT id FROM lessons WHERE course_id = ? AND module_id = ? ORDER BY ordem ASC, id ASC');
        $stmt->execute([(int)$courseId, (int)$moduleId]);
        return array_map('intval', array_column($stmt->fetchAll(), 'id'));
    }

    private function countCompletedLessons($userId, array $lessonIds)
    {
        if (empty($lessonIds)) {
            return 0;
        }

        $placeholders = implode(',', array_fill(0, count($lessonIds), '?'));
        $params = array_merge([(int)$userId], $lessonIds);
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM lesson_progress
             WHERE user_id = ? AND concluida = 1 AND lesson_id IN ($placeholders)"
        );
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    private function fetchModuleQuizzes($courseId, $moduleId)
    {
        $stmt = $this->pdo->prepare(
            "SELECT *
             FROM quizzes
             WHERE course_id = ? AND module_id = ? AND tipo = 'modulo'
             ORDER BY id ASC"
        );
        $stmt->execute([(int)$courseId, (int)$moduleId]);
        return $stmt->fetchAll();
    }

    private function fetchFinalQuizzes($courseId)
    {
        $stmt = $this->pdo->prepare(
            "SELECT *
             FROM quizzes
             WHERE course_id = ? AND tipo = 'final'
             ORDER BY id ASC"
        );
        $stmt->execute([(int)$courseId]);
        return $stmt->fetchAll();
    }

    private function getCertificateById($certificateId)
    {
        $stmt = $this->pdo->prepare(
            'SELECT cert.*, c.titulo AS course_title, m.titulo AS module_title, u.nome AS student_name
             FROM certificates cert
             JOIN courses c ON c.id = cert.course_id
             JOIN users u ON u.id = cert.user_id
             LEFT JOIN course_modules m ON m.id = cert.module_id
             WHERE cert.id = ?
             LIMIT 1'
        );
        $stmt->execute([(int)$certificateId]);
        return $this->hydrateCertificate($stmt->fetch() ?: null);
    }

    private function hydrateCertificate($row)
    {
        if (!$row) {
            return null;
        }

        return [
            'id' => (int)($row['id'] ?? 0),
            'user_id' => (int)($row['user_id'] ?? 0),
            'course_id' => (int)($row['course_id'] ?? 0),
            'module_id' => isset($row['module_id']) ? (int)$row['module_id'] : null,
            'type' => $row['type'] ?? 'course',
            'certificate_code' => $row['certificate_code'] ?? $row['codigo_certificado'] ?? '',
            'grade' => (float)($row['grade'] ?? $row['nota_final'] ?? 0),
            'issued_at' => $row['issued_at'] ?? $row['data_emissao'] ?? null,
            'student_name' => $row['student_name'] ?? $row['aluno_nome'] ?? '',
            'course_title' => $row['course_title'] ?? $row['curso_titulo'] ?? '',
            'module_title' => $row['module_title'] ?? '',
            'verification_url' => BASE_URL . '/certificado/' . rawurlencode((string)($row['certificate_code'] ?? $row['codigo_certificado'] ?? '')),
        ];
    }

    private function maskStudentName($name)
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $parts = array_values(array_filter($parts, static function ($part) {
            return $part !== '';
        }));

        if (empty($parts)) {
            return 'Aluno';
        }

        $first = mb_substr($parts[0], 0, 1, 'UTF-8') . str_repeat('*', max(2, mb_strlen($parts[0], 'UTF-8') - 1));
        if (count($parts) === 1) {
            return $first;
        }

        $last = mb_substr($parts[count($parts) - 1], 0, 1, 'UTF-8') . '***';
        return $first . ' ' . $last;
    }

    private function buildPdfFilename(array $certificate)
    {
        $suffix = ($certificate['type'] ?? 'course') === 'module'
            ? 'modulo-' . (int)($certificate['module_id'] ?? 0)
            : 'curso-' . (int)($certificate['course_id'] ?? 0);

        return 'certificado-' . $suffix . '-' . strtolower((string)($certificate['certificate_code'] ?? 'cert')) . '.pdf';
    }

    private function generateUniqueCode()
    {
        do {
            $code = strtoupper(bin2hex(random_bytes(8)));
            $stmt = $this->pdo->prepare('SELECT id FROM certificates WHERE certificate_code = ? OR codigo_certificado = ? LIMIT 1');
            $stmt->execute([$code, $code]);
            $exists = $stmt->fetchColumn();
        } while ($exists);

        return $code;
    }

    private function ensureSchema()
    {
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS certificates (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            course_id INT NOT NULL,
            module_id INT NULL,
            type VARCHAR(20) NOT NULL DEFAULT 'course',
            certificate_code VARCHAR(100) NULL,
            grade DECIMAL(5,2) NULL,
            issued_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            codigo_certificado VARCHAR(100) NULL,
            nota_final DECIMAL(5,2) NULL,
            data_emissao TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX (user_id),
            INDEX (course_id),
            INDEX (module_id),
            UNIQUE KEY unique_certificate_code (certificate_code),
            UNIQUE KEY unique_codigo_certificado (codigo_certificado)
        )");

        $this->addColumnIfMissing('certificates', 'module_id', 'INT NULL AFTER course_id');
        $this->addColumnIfMissing('certificates', 'type', "VARCHAR(20) NOT NULL DEFAULT 'course' AFTER module_id");
        $this->addColumnIfMissing('certificates', 'certificate_code', 'VARCHAR(100) NULL AFTER type');
        $this->addColumnIfMissing('certificates', 'grade', 'DECIMAL(5,2) NULL AFTER certificate_code');
        $this->addColumnIfMissing('certificates', 'issued_at', 'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP AFTER grade');
        $this->addColumnIfMissing('certificates', 'nota_final', 'DECIMAL(5,2) NULL AFTER codigo_certificado');
        $this->dropIndexIfExists('certificates', 'unique_cert');
        $this->addUniqueIndexIfMissing('certificates', 'unique_certificate_code', 'certificate_code');
        $this->addUniqueIndexIfMissing('certificates', 'unique_codigo_certificado', 'codigo_certificado');

        $this->pdo->exec("UPDATE certificates
            SET type = CASE
                WHEN type IS NULL OR type = '' THEN 'course'
                ELSE type
            END");

        $this->pdo->exec("UPDATE certificates
            SET certificate_code = COALESCE(NULLIF(certificate_code, ''), codigo_certificado)");
        $this->pdo->exec("UPDATE certificates
            SET codigo_certificado = COALESCE(NULLIF(codigo_certificado, ''), certificate_code)");
        $this->pdo->exec("UPDATE certificates
            SET grade = COALESCE(grade, nota_final)");
        $this->pdo->exec("UPDATE certificates
            SET nota_final = COALESCE(nota_final, grade)");
        $this->pdo->exec("UPDATE certificates
            SET issued_at = COALESCE(issued_at, data_emissao, NOW())");
        $this->pdo->exec("UPDATE certificates
            SET data_emissao = COALESCE(data_emissao, issued_at, NOW())");
    }

    private function addColumnIfMissing($table, $column, $definition)
    {
        if (!$this->hasColumn($table, $column)) {
            $this->pdo->exec("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
        }
    }

    private function hasColumn($table, $column)
    {
        $key = $table . '.' . $column;
        if (array_key_exists($key, $this->columnCache)) {
            return $this->columnCache[$key];
        }

        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
        );
        $stmt->execute([$table, $column]);
        $this->columnCache[$key] = (int)$stmt->fetchColumn() > 0;
        return $this->columnCache[$key];
    }

    private function addUniqueIndexIfMissing($table, $indexName, $column)
    {
        if (!$this->hasIndex($table, $indexName)) {
            $this->pdo->exec("ALTER TABLE `$table` ADD UNIQUE KEY `$indexName` (`$column`)");
        }
    }

    private function dropIndexIfExists($table, $indexName)
    {
        if ($this->hasIndex($table, $indexName)) {
            $this->pdo->exec("ALTER TABLE `$table` DROP INDEX `$indexName`");
        }
    }

    private function hasIndex($table, $indexName)
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?'
        );
        $stmt->execute([$table, $indexName]);
        return (int)$stmt->fetchColumn() > 0;
    }
}
