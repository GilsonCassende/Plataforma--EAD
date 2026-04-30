<?php
/**
 * View: Certificados e verificação pública
 */
$mode = $mode ?? 'owner';
$certificado = $certificado ?? null;
$snapshot = $snapshot ?? [];
$requestedType = ($requested_type ?? 'course') === 'module' ? 'module' : 'course';
$requestedModuleId = isset($requested_module_id) ? (int)$requested_module_id : null;
$verification = $verification ?? null;
$pdfExport = !empty($pdf_export);
$downloadPdfUrl = (string)($download_pdf_url ?? ('?page=certificado&course_id=' . urlencode((string)($course_id ?? 0)) . (($requestedType === 'module' && $requestedModuleId) ? '&type=module&module_id=' . urlencode((string)$requestedModuleId) : '') . '&download=pdf'));

$formatNota = static function ($value) {
    return number_format((float)$value, 1, ',', '.') . '/20';
};

$formatDate = static function ($value) {
    return !empty($value) ? date('d/m/Y', strtotime((string)$value)) : '-';
};

$targetState = null;
if ($mode === 'owner') {
    if ($requestedType === 'module') {
        foreach (($snapshot['modules'] ?? []) as $moduleState) {
            if ((int)($moduleState['module_id'] ?? 0) === $requestedModuleId) {
                $targetState = $moduleState;
                break;
            }
        }
    } else {
        $targetState = $snapshot['course'] ?? null;
    }
}

$certificateEntity = $certificado ?: [];
$certificateType = ($certificateEntity['type'] ?? $requestedType) === 'module' ? 'module' : 'course';
$studentName = (string)($certificateEntity['student_name'] ?? 'Aluno');
$courseTitle = (string)($certificateEntity['course_title'] ?? ($snapshot['course_title'] ?? 'Curso'));
$moduleTitle = (string)($certificateEntity['module_title'] ?? '');
$certificateTitle = $certificateType === 'module' ? 'Certificado de Conclusão de Módulo' : 'Certificado de Conclusão';
$heroTitle = $certificateType === 'module' ? 'Parabéns pela conquista deste módulo' : 'Parabéns pela conquista';
$scopeDescription = $certificateType === 'module' ? 'concluiu com sucesso o módulo' : 'concluiu com sucesso o curso';
$scopeTitle = $certificateType === 'module' ? ($moduleTitle !== '' ? $moduleTitle : '-') : $courseTitle;
$verifiedLoad = '-';

if ($certificateType === 'module' && !empty($targetState)) {
    $verifiedLoad = (string)($targetState['total_lessons'] ?? 0) . ' etapa(s)';
} elseif (!empty($snapshot['modules']) && is_array($snapshot['modules'])) {
    $lessonTotal = 0;
    foreach ($snapshot['modules'] as $moduleSnapshot) {
        $lessonTotal += (int)($moduleSnapshot['total_lessons'] ?? 0);
    }
    $verifiedLoad = $lessonTotal > 0 ? $lessonTotal . ' etapa(s)' : 'Trilha completa';
}
?>

<section class="certificate-page<?php echo $pdfExport ? ' certificate-page--pdf' : ''; ?>">
    <div class="container">
        <?php if ($mode === 'public'): ?>
            <div class="<?php echo !empty($verification['valid']) ? 'certificate-shell' : 'certificate-pending'; ?>">
                <header class="certificate-shell__header">
                    <span class="certificate-shell__eyebrow"><?php echo !empty($verification['valid']) ? 'Certificado válido' : 'Certificado não encontrado'; ?></span>
                    <h1><?php echo !empty($verification['valid']) ? 'Verificação pública concluída.' : 'Não foi possível validar este certificado.'; ?></h1>
                    <p>
                        <?php if (!empty($verification['valid'])): ?>
                            O código foi confirmado e o certificado pertence a <?php echo htmlspecialchars($verification['student_masked'] ?? 'Aluno', ENT_QUOTES, 'UTF-8'); ?>.
                        <?php else: ?>
                            <?php echo htmlspecialchars($verification['message'] ?? 'Código inválido.', ENT_QUOTES, 'UTF-8'); ?>
                        <?php endif; ?>
                    </p>
                </header>

                <?php if (!empty($verification['valid']) && !empty($certificado)): ?>
                <article class="certificate-card certificate-card--public">
                        <div class="certificate-card__watermark" aria-hidden="true">EAD</div>
                        <div class="certificate-card__seal" aria-hidden="true">
                            <div class="certificate-card__seal-core">
                                <span>Verificado</span>
                            </div>
                        </div>
                        <div class="certificate-card__brand">
                            <div class="certificate-card__brand-mark">E</div>
                            <div class="certificate-card__brand-copy">
                                <span>Plataforma EAD</span>
                                <strong>Validação oficial de certificado</strong>
                            </div>
                        </div>

                        <div class="certificate-card__hero">
                            <h2><?php echo htmlspecialchars($certificateTitle, ENT_QUOTES, 'UTF-8'); ?></h2>
                            <p class="certificate-card__lead">Emitido em nome de</p>
                            <div class="certificate-card__student"><?php echo htmlspecialchars($verification['student_masked'] ?? 'Aluno', ENT_QUOTES, 'UTF-8'); ?></div>
                            <p class="certificate-card__copy">
                                Certificamos oficialmente a conclusão de
                                <strong><?php echo htmlspecialchars($scopeTitle, ENT_QUOTES, 'UTF-8'); ?></strong>
                                <?php if ($certificateType === 'module'): ?>
                                    no curso <strong><?php echo htmlspecialchars($courseTitle, ENT_QUOTES, 'UTF-8'); ?></strong>,
                                <?php else: ?>
                                    com aprovação nas avaliações exigidas,
                                <?php endif; ?>
                                com autenticidade confirmada pela plataforma.
                            </p>
                        </div>

                        <div class="certificate-card__meta">
                            <div>
                                <span>Data</span>
                                <strong><?php echo htmlspecialchars($verification['issued_at_formatted'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></strong>
                            </div>
                            <div>
                                <span>Nota</span>
                                <strong><?php echo htmlspecialchars($formatNota($certificado['grade'] ?? 0), ENT_QUOTES, 'UTF-8'); ?></strong>
                            </div>
                            <div>
                                <span>Código</span>
                                <strong><?php echo htmlspecialchars($certificado['certificate_code'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></strong>
                            </div>
                            <div>
                                <span>Status</span>
                                <strong>Válido</strong>
                            </div>
                        </div>

                        <div class="certificate-card__signature-row">
                            <div class="certificate-card__signature">
                                <div class="certificate-card__signature-line" aria-hidden="true"></div>
                                <strong>Plataforma EAD</strong>
                                <span>Secretaria acadêmica digital</span>
                            </div>
                            <div class="certificate-card__signature certificate-card__signature--right">
                                <div class="certificate-card__signature-line" aria-hidden="true"></div>
                                <strong>Documento autenticado</strong>
                                <span>Validação institucional concluída</span>
                            </div>
                        </div>

                        <div class="certificate-card__verification">
                            <span>Código de verificação</span>
                            <strong><?php echo htmlspecialchars($certificado['certificate_code'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></strong>
                            <a href="<?php echo htmlspecialchars($certificado['verification_url'] ?? '#', ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">
                                Verifique em /certificado/<?php echo htmlspecialchars($certificado['certificate_code'] ?? '-', ENT_QUOTES, 'UTF-8'); ?>
                            </a>
                        </div>
                    </article>
                <?php endif; ?>
            </div>
        <?php elseif (!empty($certificado) && !empty($targetState['eligible'])): ?>
            <div class="<?php echo $pdfExport ? 'certificate-shell certificate-shell--pdf' : 'certificate-shell'; ?>">
                <?php if (!$pdfExport): ?>
                <header class="certificate-shell__header">
                    <span class="certificate-shell__eyebrow">Certificado liberado</span>
                    <h1><?php echo htmlspecialchars($heroTitle, ENT_QUOTES, 'UTF-8'); ?></h1>
                    <p>Seu desempenho foi validado com sucesso. O certificado abaixo já está pronto para impressão e download em PDF.</p>
                </header>
                <?php endif; ?>

                <article class="certificate-card<?php echo $pdfExport ? ' certificate-card--pdf' : ''; ?>">
                    <div class="certificate-card__watermark" aria-hidden="true">EAD</div>
                    <div class="certificate-card__seal" aria-hidden="true">
                        <div class="certificate-card__seal-core">
                            <span>Excelência</span>
                        </div>
                    </div>
                    <div class="certificate-card__brand">
                        <div class="certificate-card__brand-mark">E</div>
                        <div class="certificate-card__brand-copy">
                            <span>Plataforma EAD</span>
                            <strong>Certificação acadêmica oficial</strong>
                        </div>
                    </div>

                    <div class="certificate-card__hero">
                        <h2><?php echo htmlspecialchars($certificateTitle, ENT_QUOTES, 'UTF-8'); ?></h2>
                        <p class="certificate-card__lead">Certificamos que</p>
                        <div class="certificate-card__student"><?php echo htmlspecialchars($studentName, ENT_QUOTES, 'UTF-8'); ?></div>
                        <p class="certificate-card__copy">
                            concluiu com sucesso
                            <strong><?php echo htmlspecialchars($scopeTitle, ENT_QUOTES, 'UTF-8'); ?></strong>
                            <?php if ($certificateType === 'module'): ?>
                                no curso <strong><?php echo htmlspecialchars($courseTitle, ENT_QUOTES, 'UTF-8'); ?></strong>
                            <?php endif; ?>
                            com aproveitamento validado oficialmente pela plataforma.
                        </p>
                    </div>

                    <div class="certificate-card__meta">
                        <div>
                            <span>Data</span>
                            <strong><?php echo htmlspecialchars($formatDate($certificado['issued_at'] ?? null), ENT_QUOTES, 'UTF-8'); ?></strong>
                        </div>
                        <div>
                            <span>Nota final</span>
                            <strong><?php echo htmlspecialchars($formatNota($certificado['grade'] ?? 0), ENT_QUOTES, 'UTF-8'); ?></strong>
                        </div>
                        <div>
                            <span>Carga validada</span>
                            <strong><?php echo htmlspecialchars($verifiedLoad, ENT_QUOTES, 'UTF-8'); ?></strong>
                        </div>
                        <div>
                            <span>Código</span>
                            <strong><?php echo htmlspecialchars($certificado['certificate_code'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></strong>
                        </div>
                    </div>

                    <div class="certificate-card__signature-row">
                        <div class="certificate-card__signature">
                            <div class="certificate-card__signature-line" aria-hidden="true"></div>
                            <strong>Plataforma EAD</strong>
                            <span>Diretoria acadêmica</span>
                        </div>
                        <div class="certificate-card__signature certificate-card__signature--right">
                            <div class="certificate-card__signature-line" aria-hidden="true"></div>
                            <strong>Documento emitido digitalmente</strong>
                            <span>Autenticação institucional validada</span>
                        </div>
                    </div>

                    <div class="certificate-card__verification">
                        <span>Código de verificação</span>
                        <strong><?php echo htmlspecialchars($certificado['certificate_code'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></strong>
                        <a href="<?php echo htmlspecialchars($certificado['verification_url'] ?? '#', ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">
                            Verifique em /certificado/<?php echo htmlspecialchars($certificado['certificate_code'] ?? '-', ENT_QUOTES, 'UTF-8'); ?>
                        </a>
                    </div>
                </article>

                <?php if (!$pdfExport): ?>
                <div class="certificate-actions">
                    <a href="?page=curso&id=<?php echo htmlspecialchars((string)$course_id, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-secondary">Voltar ao curso</a>
                    <a href="<?php echo htmlspecialchars($downloadPdfUrl, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary">Baixar PDF</a>
                    <button type="button" class="btn btn-outline-secondary" onclick="window.print()">Imprimir</button>
                    <button type="button" class="btn btn-outline-secondary" disabled>Compartilhar em breve</button>
                </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="certificate-pending">
                <span class="certificate-shell__eyebrow">Certificado pendente</span>
                <h1>O certificado ainda não foi liberado.</h1>
                <p><?php echo htmlspecialchars($targetState['message'] ?? 'Finalize as etapas obrigatórias para liberar este certificado.', ENT_QUOTES, 'UTF-8'); ?></p>

                <?php if ($requestedType === 'module' && !empty($targetState)): ?>
                    <div class="certificate-pending__grid">
                        <article class="certificate-pending__item">
                            <span>Aulas concluídas</span>
                            <strong><?php echo htmlspecialchars((string)(($targetState['completed_lessons'] ?? 0) . '/' . ($targetState['total_lessons'] ?? 0)), ENT_QUOTES, 'UTF-8'); ?></strong>
                        </article>
                        <article class="certificate-pending__item">
                            <span>Quizzes aprovados</span>
                            <strong><?php echo htmlspecialchars((string)(($targetState['approved_quizzes'] ?? 0) . '/' . ($targetState['total_quizzes'] ?? 0)), ENT_QUOTES, 'UTF-8'); ?></strong>
                        </article>
                        <article class="certificate-pending__item">
                            <span>Status</span>
                            <strong><?php echo !empty($targetState['eligible']) ? 'Elegível' : 'Em andamento'; ?></strong>
                        </article>
                    </div>
                <?php else: ?>
                    <div class="certificate-pending__grid">
                        <article class="certificate-pending__item">
                            <span>Módulos concluídos</span>
                            <strong><?php echo htmlspecialchars((string)(($targetState['completed_modules'] ?? 0) . '/' . ($targetState['total_modules'] ?? 0)), ENT_QUOTES, 'UTF-8'); ?></strong>
                        </article>
                        <article class="certificate-pending__item">
                            <span>Quizzes finais aprovados</span>
                            <strong><?php echo htmlspecialchars((string)(($targetState['approved_final_quizzes'] ?? 0) . '/' . ($targetState['total_final_quizzes'] ?? 0)), ENT_QUOTES, 'UTF-8'); ?></strong>
                        </article>
                        <article class="certificate-pending__item">
                            <span>Nota atual</span>
                            <strong><?php echo htmlspecialchars($formatNota($targetState['grade'] ?? 0), ENT_QUOTES, 'UTF-8'); ?></strong>
                        </article>
                    </div>
                <?php endif; ?>

                <div class="certificate-actions">
                    <a href="?page=curso&id=<?php echo htmlspecialchars((string)$course_id, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary">Voltar ao curso</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>
