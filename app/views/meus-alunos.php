<?php
/**
 * Partial: Meus Alunos (professor)
 */

$studentRows = is_array($students ?? null) ? $students : [];

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

$totalStudents = count($studentRows);
$completedStudents = 0;
$studentsWithCertificates = 0;
$averageProgressBase = 0;
$averageScoreValues = [];

foreach ($studentRows as $student) {
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

<div class="page container page-meus-alunos-view">
    <section class="meus-alunos-hero card">
        <div class="meus-alunos-hero__copy">
            <span class="meus-alunos-hero__eyebrow">Base ativa de estudantes</span>
            <h1>Meus Alunos</h1>
            <p>Acompanhe matrícula, progresso, atividade recente e desempenho dos alunos em todos os seus cursos.</p>
        </div>

        <div class="meus-alunos-hero__stats">
            <article class="meus-alunos-stat">
                <strong><?php echo htmlspecialchars((string)$totalStudents, ENT_QUOTES, 'UTF-8'); ?></strong>
                <span>alunos nesta página</span>
            </article>
            <article class="meus-alunos-stat">
                <strong><?php echo htmlspecialchars((string)$averageProgress, ENT_QUOTES, 'UTF-8'); ?>%</strong>
                <span>progresso médio</span>
            </article>
            <article class="meus-alunos-stat">
                <strong><?php echo htmlspecialchars((string)$completionRate, ENT_QUOTES, 'UTF-8'); ?>%</strong>
                <span>taxa de conclusão</span>
            </article>
            <article class="meus-alunos-stat">
                <strong><?php echo htmlspecialchars(number_format($averageScore, 1, ',', '.'), ENT_QUOTES, 'UTF-8'); ?>%</strong>
                <span>média de quizzes</span>
            </article>
            <article class="meus-alunos-stat">
                <strong><?php echo htmlspecialchars((string)$studentsWithCertificates, ENT_QUOTES, 'UTF-8'); ?></strong>
                <span>com certificado</span>
            </article>
        </div>

        <div class="meus-alunos-hero__toolbar">
            <input type="text" id="student-search" class="search-input" placeholder="Buscar por nome ou email..." value="<?php echo htmlspecialchars((string)($q ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
        </div>
    </section>

    <?php if (!empty($studentRows)): ?>
        <section class="meus-alunos-grid">
            <?php foreach ($studentRows as $aluno): ?>
                <?php
                $progress = max(0, min(100, (int)($aluno['progress'] ?? 0)));
                $completedLessons = (int)($aluno['completed_lessons'] ?? 0);
                $totalLessons = (int)($aluno['total_lessons'] ?? 0);
                $approvedQuizzes = (int)($aluno['approved_quizzes'] ?? 0);
                $totalQuizzes = (int)($aluno['total_quizzes'] ?? 0);
                $totalAttempts = (int)($aluno['total_attempts'] ?? 0);
                $totalCertificates = (int)($aluno['total_certificates'] ?? 0);
                $coursesCount = (int)($aluno['courses_count'] ?? 0);
                $averageStudentScore = isset($aluno['average_score']) && $aluno['average_score'] !== null
                    ? number_format((float)$aluno['average_score'], 1, ',', '.')
                    : '-';
                $lastActivity = $aluno['last_attempt_at'] ?? $aluno['last_lesson_at'] ?? null;
                $statusLabel = 'Em andamento';
                $statusClass = 'is-active';

                if ($progress >= 100 || !empty($aluno['data_conclusao'])) {
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
                                <?php if (!empty($aluno['fotografia'])): ?>
                                    <img src="<?php echo htmlspecialchars(BASE_URL . '/uploads/' . ltrim((string)$aluno['fotografia'], '/'), ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars((string)$aluno['nome'], ENT_QUOTES, 'UTF-8'); ?>">
                                <?php else: ?>
                                    <span><?php echo htmlspecialchars(strtoupper(substr((string)($aluno['nome'] ?? 'A'), 0, 1)), ENT_QUOTES, 'UTF-8'); ?></span>
                                <?php endif; ?>
                            </div>
                            <div>
                                <h3 class="student-name"><?php echo htmlspecialchars((string)($aluno['nome'] ?? 'Aluno'), ENT_QUOTES, 'UTF-8'); ?></h3>
                                <p class="student-email"><?php echo htmlspecialchars((string)($aluno['email'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></p>
                                <p class="student-course"><?php echo htmlspecialchars((string)$coursesCount, ENT_QUOTES, 'UTF-8'); ?> curso(s) com você</p>
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
                                <small>Cursos matriculados</small>
                                <strong><?php echo htmlspecialchars((string)$coursesCount, ENT_QUOTES, 'UTF-8'); ?></strong>
                            </div>
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
                                <small>Concluído em</small>
                                <strong><?php echo htmlspecialchars($formatDate($aluno['data_conclusao'] ?? null), ENT_QUOTES, 'UTF-8'); ?></strong>
                            </div>
                            <div>
                                <small>Certificados</small>
                                <strong><?php echo htmlspecialchars((string)$totalCertificates, ENT_QUOTES, 'UTF-8'); ?></strong>
                            </div>
                        </div>

                        <dl class="student-card__details">
                        <div>
                            <dt>Primeira matrícula</dt>
                            <dd><?php echo htmlspecialchars($formatDate($aluno['data_inscricao'] ?? null), ENT_QUOTES, 'UTF-8'); ?></dd>
                        </div>
                        <div>
                            <dt>Última atividade</dt>
                            <dd><?php echo htmlspecialchars($formatDate($lastActivity, true), ENT_QUOTES, 'UTF-8'); ?></dd>
                        </div>
                        <div>
                            <dt>Último certificado</dt>
                            <dd><?php echo htmlspecialchars($formatDate($aluno['last_certificate_at'] ?? null), ENT_QUOTES, 'UTF-8'); ?></dd>
                        </div>
                        </dl>
                </article>
            <?php endforeach; ?>
        </section>

        <?php if (isset($total_paginas) && $total_paginas > 1): ?>
            <div class="pagination panel-spacing-top">
                <?php for ($p = 1; $p <= $total_paginas; $p++): ?>
                    <?php $active = ($p == ($pagina ?? 1)); ?>
                    <a href="?page=meus-alunos&p=<?php echo htmlspecialchars((string)$p, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-sm<?php echo $active ? ' is-active-page' : ''; ?>"><?php echo htmlspecialchars((string)$p, ENT_QUOTES, 'UTF-8'); ?></a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <div class="empty-state">
            <p>Nenhum aluno encontrado.</p>
        </div>
    <?php endif; ?>
</div>
