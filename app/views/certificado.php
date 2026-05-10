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
$officialValidation = !empty($official_validation);
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
$teacherName = (string)($certificateEntity['teacher_name'] ?? 'Coordenação Acadêmica');
$verificationCode = (string)($certificateEntity['certificate_code'] ?? '-');
$verificationUrl = (string)($certificateEntity['verification_url'] ?? '#');
$qrCodeUrl = (string)($certificateEntity['qr_code_url'] ?? '');
$workloadLabel = (string)($certificateEntity['workload_label'] ?? 'Não informada');
$verificationCount = (int)($certificateEntity['verification_count'] ?? 0);
$lastVerifiedAt = !empty($certificateEntity['last_verified_at']) ? date('d/m/Y H:i', strtotime((string)$certificateEntity['last_verified_at'])) : '-';
$gradeLabel = $certificateType === 'module' ? 'Nota final' : 'Média final';
$formattedGrade = $formatNota($certificateEntity['grade'] ?? 0);
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
            <div class="<?php echo !empty($verification['valid']) ? 'certificate-shell certificate-shell--verification' : 'certificate-pending certificate-pending--verification'; ?>">
                <header class="certificate-shell__header">
                    <span class="certificate-shell__eyebrow"><?php echo !empty($verification['valid']) ? 'Certificado autêntico' : 'Validação não concluída'; ?></span>
                    <h1><?php echo !empty($verification['valid']) ? 'Verificação oficial concluída.' : 'Não foi possível validar este certificado.'; ?></h1>
                    <p>
                        <?php if (!empty($verification['valid'])): ?>
                            O código foi confirmado na plataforma e o certificado é autêntico.
                        <?php else: ?>
                            <?php echo htmlspecialchars($verification['message'] ?? 'Código inválido.', ENT_QUOTES, 'UTF-8'); ?>
                        <?php endif; ?>
                    </p>
                </header>

                <?php if (!empty($verification['valid']) && !empty($certificado)): ?>
                <article class="certificate-card certificate-card--public certificate-card--verification">
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
                                <strong><?php echo $officialValidation ? 'Verificador oficial de certificados' : 'Validação oficial de certificado'; ?></strong>
                            </div>
                        </div>

                        <div class="certificate-card__hero">
                            <h2>Certificado Autêntico</h2>
                            <p class="certificate-card__lead">Emitido em nome de</p>
                            <div class="certificate-card__student"><?php echo htmlspecialchars($studentName, ENT_QUOTES, 'UTF-8'); ?></div>
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
                                <span>Professor</span>
                                <strong><?php echo htmlspecialchars($teacherName, ENT_QUOTES, 'UTF-8'); ?></strong>
                            </div>
                            <div>
                                <span>Carga horária</span>
                                <strong><?php echo htmlspecialchars($workloadLabel, ENT_QUOTES, 'UTF-8'); ?></strong>
                            </div>
                            <div>
                                <span><?php echo htmlspecialchars($gradeLabel, ENT_QUOTES, 'UTF-8'); ?></span>
                                <strong><?php echo htmlspecialchars($formattedGrade, ENT_QUOTES, 'UTF-8'); ?></strong>
                            </div>
                            <div>
                                <span>Status</span>
                                <strong>Verificado</strong>
                            </div>
                        </div>

                        <div class="certificate-card__verification certificate-card__verification--with-qr">
                            <div class="certificate-card__verification-copy">
                                <span>Selo de autenticidade</span>
                                <strong><?php echo htmlspecialchars($verificationCode, ENT_QUOTES, 'UTF-8'); ?></strong>
                                <p class="certificate-card__verification-text">Documento confirmado nos registros oficiais da Plataforma EAD.</p>
                                <div class="certificate-card__verification-grid">
                                    <div>
                                        <small>Validações</small>
                                        <strong><?php echo htmlspecialchars((string)$verificationCount, ENT_QUOTES, 'UTF-8'); ?></strong>
                                    </div>
                                    <div>
                                        <small>Última verificação</small>
                                        <strong><?php echo htmlspecialchars((string)($verification['last_verified_at_formatted'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></strong>
                                    </div>
                                </div>
                                <a href="<?php echo htmlspecialchars($verificationUrl, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">
                                    <?php echo htmlspecialchars($verificationUrl, ENT_QUOTES, 'UTF-8'); ?>
                                </a>
                            </div>
                            <div class="certificate-card__qr">
                                <?php if ($qrCodeUrl !== ''): ?>
                                    <img src="<?php echo htmlspecialchars($qrCodeUrl, ENT_QUOTES, 'UTF-8'); ?>" alt="QR Code do certificado">
                                <?php endif; ?>
                                <span>Escaneie para validar online</span>
                            </div>
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
                            <span>Professor</span>
                            <strong><?php echo htmlspecialchars($teacherName, ENT_QUOTES, 'UTF-8'); ?></strong>
                        </div>
                        <div>
                            <span>Carga horária</span>
                            <strong><?php echo htmlspecialchars($workloadLabel !== 'Não informada' ? $workloadLabel : $verifiedLoad, ENT_QUOTES, 'UTF-8'); ?></strong>
                        </div>
                        <div>
                            <span><?php echo htmlspecialchars($gradeLabel, ENT_QUOTES, 'UTF-8'); ?></span>
                            <strong><?php echo htmlspecialchars($formattedGrade, ENT_QUOTES, 'UTF-8'); ?></strong>
                        </div>
                        <div>
                            <span>Código</span>
                            <strong><?php echo htmlspecialchars($verificationCode, ENT_QUOTES, 'UTF-8'); ?></strong>
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

                    <div class="certificate-card__verification certificate-card__verification--with-qr">
                        <div class="certificate-card__verification-copy">
                            <span>Código de verificação</span>
                            <strong><?php echo htmlspecialchars($verificationCode, ENT_QUOTES, 'UTF-8'); ?></strong>
                            <p class="certificate-card__verification-text">Verifique autenticidade online e compartilhe este certificado com segurança.</p>
                            <a href="<?php echo htmlspecialchars($verificationUrl, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $pdfExport ? '' : ' target="_blank" rel="noopener noreferrer"'; ?>>
                                <?php echo htmlspecialchars($verificationUrl, ENT_QUOTES, 'UTF-8'); ?>
                            </a>
                        </div>
                        <div class="certificate-card__qr">
                            <?php if ($qrCodeUrl !== ''): ?>
                                <img src="<?php echo htmlspecialchars($qrCodeUrl, ENT_QUOTES, 'UTF-8'); ?>" alt="QR Code do certificado">
                            <?php endif; ?>
                            <span>Verifique autenticidade online</span>
                        </div>
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
