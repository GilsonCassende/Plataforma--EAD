<?php
/**
 * View: Alunos matriculados em um curso
 */

$students = is_array($alunos ?? null) ? $alunos : [];
$course = is_array($curso ?? null) ? $curso : [];

$formatDate = static function (?string $value, bool $withTime = false): string {
    if (!$value) {
        return '-';
    }

    $timestamp = strtotime($value);
    if ($timestamp === false) {
        return '-';
    }

    return date($withTime ? 'd/m/Y H:i' : 'd/m/Y', $timestamp);
};

$totalStudents = count($students);
$completedStudents = 0;
$studentsWithCertificates = 0;
$averageProgressBase = 0;
$averageScoreValues = [];

foreach ($students as $student) {
    $progress = (int)($student['progress'] ?? 0);
    $averageProgressBase += $progress;

    if ($progress >= 100 || !empty($student['data_conclusao'])) {
        $completedStudents++;
    }

    if ((int)($student['total_certificates'] ?? 0) > 0) {
        $studentsWithCertificates++;
    }

    if (isset($student['average_score']) && $student['average_score'] !== null) {
        $averageScoreValues[] = (float)$student['average_score'];
    }
}

$averageProgress = $totalStudents > 0 ? (int)round($averageProgressBase / $totalStudents) : 0;
$averageScore = !empty($averageScoreValues) ? round(array_sum($averageScoreValues) / count($averageScoreValues), 1) : 0;
$completionRate = $totalStudents > 0 ? (int)round(($completedStudents / $totalStudents) * 100) : 0;
?>

<section class="container editor-shell page-alunos-curso-view">
    <div class="alunos-curso-hero card">
        <div class="alunos-curso-hero__copy">
            <span class="alunos-curso-hero__eyebrow">Gestão de estudantes</span>
            <h1>Alunos do Curso: <?php echo htmlspecialchars((string)($course['titulo'] ?? 'Curso'), ENT_QUOTES, 'UTF-8'); ?></h1>
            <p>
                Acompanhe matrícula, progresso, desempenho e sinais de conclusão de cada aluno em um único painel.
            </p>
        </div>

        <div class="alunos-curso-hero__stats">
            <article class="alunos-curso-stat">
                <strong><?php echo htmlspecialchars((string)$totalStudents, ENT_QUOTES, 'UTF-8'); ?></strong>
                <span>matriculados</span>
            </article>
            <article class="alunos-curso-stat">
                <strong><?php echo htmlspecialchars((string)$averageProgress, ENT_QUOTES, 'UTF-8'); ?>%</strong>
                <span>progresso médio</span>
            </article>
            <article class="alunos-curso-stat">
                <strong><?php echo htmlspecialchars((string)$completionRate, ENT_QUOTES, 'UTF-8'); ?>%</strong>
                <span>conclusão</span>
            </article>
            <article class="alunos-curso-stat">
                <strong><?php echo htmlspecialchars(number_format($averageScore, 1, ',', '.'), ENT_QUOTES, 'UTF-8'); ?>%</strong>
                <span>média dos quizzes</span>
            </article>
            <article class="alunos-curso-stat">
                <strong><?php echo htmlspecialchars((string)$studentsWithCertificates, ENT_QUOTES, 'UTF-8'); ?></strong>
                <span>com certificado</span>
            </article>
        </div>
    </div>

    <div class="data-table-card alunos-curso-card">
        <header class="editor-card__header alunos-curso-card__header">
            <div>
                <h2 class="editor-card__title">Panorama completo da turma</h2>
                <p class="editor-card__hint">Veja rapidamente quem avançou, quem concluiu e quem precisa de acompanhamento.</p>
            </div>
        </header>

        <?php if (!empty($students)): ?>
            <div class="alunos-curso-grid">
                <?php foreach ($students as $student): ?>
                    <?php
                    $progress = max(0, min(100, (int)($student['progress'] ?? 0)));
                    $completedLessons = (int)($student['completed_lessons'] ?? 0);
                    $totalLessons = (int)($student['total_lessons'] ?? 0);
                    $approvedQuizzes = (int)($student['approved_quizzes'] ?? 0);
                    $totalQuizzes = (int)($student['total_quizzes'] ?? 0);
                    $totalAttempts = (int)($student['total_attempts'] ?? 0);
                    $totalCertificates = (int)($student['total_certificates'] ?? 0);
                    $averageStudentScore = isset($student['average_score']) && $student['average_score'] !== null
                        ? number_format((float)$student['average_score'], 1, ',', '.')
                        : '-';
                    $lastActivity = $student['last_attempt_at'] ?? $student['last_lesson_at'] ?? null;
                    $statusLabel = 'Em andamento';
                    $statusClass = 'is-active';

                    if ($progress >= 100 || !empty($student['data_conclusao'])) {
                        $statusLabel = 'Concluído';
                        $statusClass = 'is-complete';
                    } elseif ($progress <= 0 && $completedLessons === 0 && $totalAttempts === 0) {
                        $statusLabel = 'Sem atividade';
                        $statusClass = 'is-idle';
                    }
                    ?>
                    <article class="student-card">
                        <header class="student-card__header">
                            <div class="student-card__identity">
                                <div class="student-card__avatar">
                                    <?php if (!empty($student['fotografia'])): ?>
                                        <img src="<?php echo htmlspecialchars(upload_image_url((string)$student['fotografia'], ['w' => 120, 'h' => 120, 'fit' => 'cover', 'q' => 80]), ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars((string)$student['nome'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php else: ?>
                                        <span><?php echo htmlspecialchars(strtoupper(substr((string)($student['nome'] ?? 'A'), 0, 1)), ENT_QUOTES, 'UTF-8'); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <h3 class="student-name"><?php echo htmlspecialchars((string)($student['nome'] ?? 'Aluno'), ENT_QUOTES, 'UTF-8'); ?></h3>
                                    <p class="student-email"><?php echo htmlspecialchars((string)($student['email'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></p>
                                </div>
                            </div>
                            <span class="student-card__status <?php echo $statusClass; ?>">
                                <?php echo htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8'); ?>
                            </span>
                        </header>

                        <div class="student-card__progress">
                            <div class="student-card__progress-top">
                                <strong>Progresso geral</strong>
                                <span class="student-progress-text"><?php echo htmlspecialchars((string)$progress, ENT_QUOTES, 'UTF-8'); ?>% completo</span>
                            </div>
                            <div class="student-card__progress-bar" aria-hidden="true">
                                <span style="width: <?php echo $progress; ?>%;"></span>
                            </div>
                        </div>

                        <div class="student-card__metrics">
                            <div>
                                <small>Aulas concluídas</small>
                                <strong><?php echo htmlspecialchars((string)$completedLessons, ENT_QUOTES, 'UTF-8'); ?>/<?php echo htmlspecialchars((string)$totalLessons, ENT_QUOTES, 'UTF-8'); ?></strong>
                            </div>
                            <div>
                                <small>Quizzes aprovados</small>
                                <strong><?php echo htmlspecialchars((string)$approvedQuizzes, ENT_QUOTES, 'UTF-8'); ?>/<?php echo htmlspecialchars((string)$totalQuizzes, ENT_QUOTES, 'UTF-8'); ?></strong>
                            </div>
                            <div>
                                <small>Tentativas</small>
                                <strong><?php echo htmlspecialchars((string)$totalAttempts, ENT_QUOTES, 'UTF-8'); ?></strong>
                            </div>
                            <div>
                                <small>Média nos quizzes</small>
                                <strong><?php echo htmlspecialchars((string)$averageStudentScore, ENT_QUOTES, 'UTF-8'); ?><?php echo $averageStudentScore !== '-' ? '%' : ''; ?></strong>
                            </div>
                            <div>
                                <small>Certificados</small>
                                <strong><?php echo htmlspecialchars((string)$totalCertificates, ENT_QUOTES, 'UTF-8'); ?></strong>
                            </div>
                            <div>
                                <small>Concluído em</small>
                                <strong><?php echo htmlspecialchars($formatDate($student['data_conclusao'] ?? null), ENT_QUOTES, 'UTF-8'); ?></strong>
                            </div>
                        </div>

                        <dl class="student-card__details">
                            <div>
                                <dt>Matriculado em</dt>
                                <dd><?php echo htmlspecialchars($formatDate($student['data_inscricao'] ?? null), ENT_QUOTES, 'UTF-8'); ?></dd>
                            </div>
                            <div>
                                <dt>Última atividade</dt>
                                <dd><?php echo htmlspecialchars($formatDate($lastActivity, true), ENT_QUOTES, 'UTF-8'); ?></dd>
                            </div>
                            <div>
                                <dt>Último certificado</dt>
                                <dd><?php echo htmlspecialchars($formatDate($student['last_certificate_at'] ?? null), ENT_QUOTES, 'UTF-8'); ?></dd>
                            </div>
                        </dl>

                        <div class="student-card__actions">
                            <button
                                type="button"
                                class="btn btn-outline btn-sm ui-btn ui-btn--small btn-edit-progress"
                                data-course-id="<?php echo htmlspecialchars((string)($course['id'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>"
                                data-user-id="<?php echo htmlspecialchars((string)($student['id'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>">
                                Editar progresso
                            </button>
                            <button
                                type="button"
                                class="btn btn-danger btn-sm ui-btn ui-btn--small btn-remove-enrollment"
                                data-course-id="<?php echo htmlspecialchars((string)($course['id'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>"
                                data-user-id="<?php echo htmlspecialchars((string)($student['id'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>">
                                Remover matrícula
                            </button>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="editor-card__hint">Nenhum aluno matriculado ainda.</p>
        <?php endif; ?>
    </div>
</section>
