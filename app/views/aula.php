<?php

/**
 * View: Página de Aula
 */
?>

<section class="aula-view">
    <div class="container">
        <?php if (isset($aula) && $aula && isset($curso) && $curso): ?>
            <?php
            $lessonCssVersion = @filemtime(__DIR__ . '/../../public/css/pages/aula.css') ?: time();
            $lessonJsVersion = @filemtime(__DIR__ . '/../../public/js/pages/aula.js') ?: time();
            $lessonModeCssVersion = @filemtime(__DIR__ . '/../../public/css/pages/aula-modos.css') ?: time();
            $lessonModeJsVersion = @filemtime(__DIR__ . '/../../public/js/pages/aula-modos.js') ?: time();
            $aiChatCssVersion = @filemtime(__DIR__ . '/../../public/css/pages/ai-chat.css') ?: time();
            $aiChatJsVersion = @filemtime(__DIR__ . '/../../public/js/pages/ai-chat.js') ?: time();
            $usuario = usuario_atual();
            $isOwner = is_course_owner($curso);
            $totalAulasCurso = count($aulas_curso ?? []);
            $progressValue = (int)($progresso_curso ?? 0);
            $trailProgressValue = $totalAulasCurso > 0
                ? (int)floor((((int)($aulas_concluidas_total ?? 0)) / $totalAulasCurso) * 100)
                : 0;
            $currentLessonIndex = 1;
            $modulosCurso = is_array($modulos_curso ?? null) ? $modulos_curso : [];
            $currentModule = $current_module ?? null;
            $allModulesCompleted = !empty($modulosCurso);
            foreach ($modulosCurso as $moduleItemState) {
                if (empty($moduleItemState['completed'])) {
                    $allModulesCompleted = false;
                    break;
                }
            }

            foreach (($aulas_curso ?? []) as $lessonCurso) {
                if ((int)($lessonCurso['id'] ?? 0) === (int)$aula['id']) {
                    $currentLessonIndex = (int)($lessonCurso['position'] ?? $currentLessonIndex);
                    break;
                }
            }

            $showLessonModes = ($aula['tipo'] ?? '') === 'video';
            $audioSourceUrl = lesson_audio_url($aula);
            $summaryMarkdown = trim((string)($aula['resumo'] ?? ''));
            $summaryFallback = 'Resumo não disponível para esta aula';
            $readingIntro = trim((string)($aula['descricao'] ?? ''));
            $lessonTranscript = trim((string)($aula['lesson_transcript'] ?? ''));
            $hasTranscript = $lessonTranscript !== '';
            $hasEconomicAudioFile = $showLessonModes && $audioSourceUrl !== '';
            $hasEconomicAudio = $showLessonModes && ($hasEconomicAudioFile || $hasTranscript);
            $lessonAiContent = trim((string)($lesson_ai_content ?? ''));
            $hasLessonAiContent = $lessonAiContent !== '';
            $lessonAiError = trim((string)($lesson_ai_error ?? ''));
            $lessonAiGeneratedAt = trim((string)($lesson_ai_generated_at ?? ''));
            $formattedLessonAiGeneratedAt = '';
            if ($lessonAiGeneratedAt !== '') {
                $generatedTimestamp = strtotime($lessonAiGeneratedAt);
                if ($generatedTimestamp !== false) {
                    $formattedLessonAiGeneratedAt = date('d/m/Y H:i', $generatedTimestamp);
                }
            }
            $initialLessonMode = trim((string)($initial_lesson_mode ?? ''));
            $teacherName = trim((string)($curso['professor_nome'] ?? ''));
            $assistantDisplayName = $teacherName !== '' ? ('Assistente do Prof. ' . $teacherName) : 'Assistente IA';
            $assistantGreeting = $teacherName !== ''
                ? ('Sou o assistente do Prof. ' . $teacherName . '. Posso ajudar com esta aula ou com estratégias para estudar melhor.')
                : 'Posso ajudar com esta aula ou com estratégias para estudar melhor. O que você quer saber?';
            $assistantAvatar = 'AI';
            if ($teacherName !== '') {
                $teacherParts = preg_split('/\s+/u', $teacherName, -1, PREG_SPLIT_NO_EMPTY) ?: [];
                $initials = '';
                foreach (array_slice($teacherParts, 0, 2) as $part) {
                    $initials .= mb_strtoupper(mb_substr((string)$part, 0, 1, 'UTF-8'), 'UTF-8');
                }
                if ($initials !== '') {
                    $assistantAvatar = $initials;
                }
            }
            $assistantThemeSeed = $teacherName !== '' ? $teacherName : ((string)($curso['titulo'] ?? 'assistente'));
            $assistantThemeHash = abs(crc32($assistantThemeSeed . '|' . (string)($curso['id'] ?? 0)));
            $assistantHue = $assistantThemeHash % 360;
            $assistantAccent = 'hsl(' . $assistantHue . ' 78% 50%)';
            $assistantAccentDark = 'hsl(' . $assistantHue . ' 78% 38%)';
            $assistantAccentSoft = 'hsl(' . $assistantHue . ' 85% 92%)';
            $assistantAccentSoftAlt = 'hsl(' . $assistantHue . ' 82% 86%)';
            $aiEndpoint = BASE_URL . '/perguntar-ia';
            $chatAssistantName = $teacherName !== '' ? $teacherName : 'Tutor IA';
            $chatAssistantAvatar = $teacherName !== '' ? $assistantAvatar : 'IA';
            $chatAssistantGreeting = $teacherName !== ''
                ? ("Olá 👋\nSou o assistente do Prof. " . $teacherName . ".\nPosso explicar conteúdos, resumir conceitos e tirar dúvidas sobre esta aula.")
                : "Olá 👋\nSou seu Tutor IA desta aula.\nPosso explicar conteúdos, resumir conceitos e tirar dúvidas sobre esta aula.";
            $lessonActionEndpoint = BASE_URL . '/index.php?page=aula&lesson_id=' . (int)$aula['id'] . '&course_id=' . (int)$curso['id'];
            $renderLessonMarkdown = static function (string $markdown): string {
                $markdown = trim(str_replace(["\r\n", "\r"], "\n", $markdown));
                if ($markdown === '') {
                    return '';
                }

                $markdown = preg_replace_callback('/```(?:[^\n`]*)\n([\s\S]*?)```/u', static function (array $matches): string {
                    $code = rtrim((string)($matches[1] ?? ''), "\n");
                    return "\n\n<pre><code>" . htmlspecialchars($code, ENT_QUOTES, 'UTF-8') . "</code></pre>\n\n";
                }, $markdown);

                $blocks = preg_split("/\n{2,}/", $markdown) ?: [];
                $html = [];

                foreach ($blocks as $block) {
                    $block = trim($block);
                    if ($block === '') {
                        continue;
                    }

                    if (preg_match('/^<pre><code>[\s\S]*<\/code><\/pre>$/u', $block) === 1) {
                        $html[] = $block;
                        continue;
                    }

                    $lines = array_values(array_filter(array_map('trim', explode("\n", trim($block))), static function ($line) {
                        return $line !== '';
                    }));

                    if ($lines === []) {
                        continue;
                    }

                    $isList = true;
                    $isOrderedList = true;
                    foreach ($lines as $line) {
                        if (!preg_match('/^[-*]\s+/', $line)) {
                            $isList = false;
                        }
                        if (!preg_match('/^\d+\.\s+/', $line)) {
                            $isOrderedList = false;
                        }
                    }

                    if ($isList) {
                        $items = [];
                        foreach ($lines as $line) {
                            $items[] = '<li>' . htmlspecialchars(preg_replace('/^[-*]\s+/', '', $line), ENT_QUOTES, 'UTF-8') . '</li>';
                        }
                        $html[] = '<ul>' . implode('', $items) . '</ul>';
                        continue;
                    }

                    if ($isOrderedList) {
                        $items = [];
                        foreach ($lines as $line) {
                            $items[] = '<li>' . htmlspecialchars(preg_replace('/^\d+\.\s+/', '', $line), ENT_QUOTES, 'UTF-8') . '</li>';
                        }
                        $html[] = '<ol>' . implode('', $items) . '</ol>';
                        continue;
                    }

                    $headingLine = $lines[0];
                    if (preg_match('/^(#{1,3})\s+(.+)$/', $headingLine, $matches)) {
                        $level = min(strlen($matches[1]) + 2, 4);
                        $html[] = '<h' . $level . '>' . htmlspecialchars($matches[2], ENT_QUOTES, 'UTF-8') . '</h' . $level . '>';

                        $remaining = array_slice($lines, 1);
                        if ($remaining !== []) {
                            $html[] = '<p>' . nl2br(htmlspecialchars(implode("\n", $remaining), ENT_QUOTES, 'UTF-8')) . '</p>';
                        }
                        continue;
                    }

                    if (preg_match('/^\s*`{1,3}.+`{1,3}\s*$/u', $headingLine)) {
                        $html[] = '<p><code>' . htmlspecialchars(trim($headingLine, "` \t"), ENT_QUOTES, 'UTF-8') . '</code></p>';
                        continue;
                    }

                    $html[] = '<p>' . nl2br(htmlspecialchars(implode("\n", $lines), ENT_QUOTES, 'UTF-8')) . '</p>';
                }

                return implode("\n", $html);
            };
            ?>
            <link rel="stylesheet" href="<?php echo htmlspecialchars(BASE_URL . '/css/pages/aula-modos.css?v=' . rawurlencode((string)$lessonModeCssVersion), ENT_QUOTES, 'UTF-8'); ?>">
            <link rel="stylesheet" href="<?php echo htmlspecialchars(BASE_URL . '/css/pages/ai-chat.css?v=' . rawurlencode((string)$aiChatCssVersion), ENT_QUOTES, 'UTF-8'); ?>">
            <link rel="stylesheet" href="<?php echo htmlspecialchars(BASE_URL . '/css/pages/aula.css?v=' . rawurlencode((string)$lessonCssVersion), ENT_QUOTES, 'UTF-8'); ?>">
            <div class="lesson-shell">
                <main class="lesson-main">
                    <header class="aula-header">
                        <div class="aula-header-top">
                            <span class="lesson-type-badge"><?php echo htmlspecialchars(strtoupper($aula['tipo'] ?? 'AULA'), ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>

                        <div class="aula-header-copy">
                            <p class="curso-nome">Curso: <?php echo htmlspecialchars($curso['titulo']); ?></p>
                            <h1><?php echo htmlspecialchars($aula['titulo']); ?></h1>
                            <p class="lesson-intro"><?php echo htmlspecialchars($aula['descricao'] ?? 'Explore esta aula com foco total no conteúdo e avance com clareza pela trilha do curso.'); ?></p>
                        </div>

                        <div class="lesson-overview">
                            <div class="lesson-overview-item lesson-overview-item--trail">
                                <span class="lesson-overview-label">Trilha</span>
                                <strong><?php echo htmlspecialchars((string)($currentModule['titulo'] ?? 'Módulo principal'), ENT_QUOTES, 'UTF-8'); ?> · Aula <?php echo htmlspecialchars($currentLessonIndex, ENT_QUOTES, 'UTF-8'); ?> de <?php echo htmlspecialchars($totalAulasCurso, ENT_QUOTES, 'UTF-8'); ?></strong>
                            </div>
                            <div class="lesson-overview-item lesson-overview-item--status">
                                <span class="lesson-overview-label">Status atual</span>
                                <strong class="lesson-current-status"><?php echo !empty($concluida) ? 'Assistida' : 'Em andamento'; ?></strong>
                            </div>
                        </div>

                        <?php if ($isOwner): ?>
                            <div class="aula-header-actions">
                                <a href="?page=editar-aula&lesson_id=<?php echo $aula['id']; ?>&course_id=<?php echo $curso['id']; ?>" class="btn btn-outline btn-sm" data-fragment="?page=editar-aula&partial=1&lesson_id=<?php echo (int)$aula['id']; ?>&course_id=<?php echo (int)$curso['id']; ?>" data-fragment-title="Editar Aula">Editar Aula</a>
                                <form method="POST" class="inline-form" data-confirm="Deletar esta aula?">
                                    <?php echo csrf_input(); ?>
                                    <input type="hidden" name="acao" value="deletar_aula">
                                    <input type="hidden" name="lesson_id" value="<?php echo $aula['id']; ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">Deletar Aula</button>
                                </form>
                                <button
                                    type="button"
                                    class="btn btn-outline btn-sm"
                                    data-generate-transcript
                                    data-lesson-id="<?php echo (int)$aula['id']; ?>"
                                    data-endpoint="<?php echo htmlspecialchars($lessonActionEndpoint, ENT_QUOTES, 'UTF-8'); ?>">
                                    Gerar Transcrição
                                </button>
                                <a href="?page=criar-quiz&lesson_id=<?php echo (int)($aula['id'] ?? 0); ?>&course_id=<?php echo (int)($curso['id'] ?? 0); ?>" class="btn btn-outline btn-sm" data-fragment="?page=criar-quiz&partial=1&lesson_id=<?php echo (int)($aula['id'] ?? 0); ?>&course_id=<?php echo (int)($curso['id'] ?? 0); ?>" data-fragment-title="Criar Quiz da Aula">Quiz da Aula</a>
                                <?php if (($curso['course_structure'] ?? 'single_module') === 'multi_module' && !empty($currentModule['id'])): ?>
                                    <a href="?page=criar-quiz&module_id=<?php echo (int)($currentModule['id'] ?? 0); ?>&course_id=<?php echo (int)($curso['id'] ?? 0); ?>" class="btn btn-outline btn-sm" data-fragment="?page=criar-quiz&partial=1&module_id=<?php echo (int)$currentModule['id']; ?>&course_id=<?php echo $curso['id']; ?>" data-fragment-title="Criar Quiz de Módulo">Quiz do Módulo</a>
                                <?php endif; ?>
                                <a href="?page=criar-quiz&course_id=<?php echo (int)($curso['id'] ?? 0); ?>" class="btn btn-outline btn-sm" data-fragment="?page=criar-quiz&partial=1&course_id=<?php echo $curso['id']; ?>" data-fragment-title="Criar Quiz Final">Quiz Final</a>
                            </div>
                        <?php endif; ?>
                    </header>

                    <section class="lesson-stage">
                        <article class="lesson-player-card">
                            <?php if ($showLessonModes): ?>
                                <div class="lesson-mode-switch" data-lesson-mode-switch data-initial-mode="<?php echo htmlspecialchars($initialLessonMode, ENT_QUOTES, 'UTF-8'); ?>">
                                    <button type="button" class="mode-btn is-active" data-mode-target="video" aria-pressed="true">
                                        <span class="lesson-mode-icon" aria-hidden="true">🎥</span>
                                        <span>Vídeo</span>
                                    </button>
                                    <button
                                        type="button"
                                        class="mode-btn<?php echo $hasEconomicAudio ? '' : ' is-disabled'; ?>"
                                        data-mode-target="economico"
                                        aria-pressed="false"
                                        <?php if (!$hasEconomicAudio): ?>disabled aria-disabled="true" title="Áudio não disponível nesta aula"<?php endif; ?>>
                                        <span class="lesson-mode-icon" aria-hidden="true">🎧</span>
                                        <span>Econômico</span>
                                    </button>
                                    <button type="button" class="mode-btn" data-mode-target="leitura" aria-pressed="false">
                                        <span class="lesson-mode-icon" aria-hidden="true">📄</span>
                                        <span>Leitura</span>
                                    </button>
                                </div>
                                <?php if (!$hasEconomicAudio): ?>
                                    <p class="lesson-mode-helper" role="status">
                                        O modo econômico está visível, mas o áudio ainda não foi cadastrado para esta aula.
                                    </p>
                                <?php endif; ?>
                            <?php endif; ?>
                            <div class="aula-player">
                                <?php if ($aula['tipo'] === 'video'): ?>
                                    <div class="lesson-mode-panel lesson-mode-panel--video" data-mode-panel="video">
                                        <div class="video-player">
                                        <?php if (!empty($aula['video_id'])):
                                            $yid = htmlspecialchars($aula['video_id']);
                                            $thumb = "https://i.ytimg.com/vi/{$yid}/hqdefault.jpg";
                                            $embed = "https://www.youtube-nocookie.com/embed/{$yid}?autoplay=1&rel=0&modestbranding=1&playsinline=1&iv_load_policy=3&fs=1&enablejsapi=1";
                                        ?>
                                            <div class="video-wrapper" data-embed="<?php echo $embed; ?>" data-title="<?php echo htmlspecialchars((string)($aula['titulo'] ?? 'Reprodutor de vídeo da aula'), ENT_QUOTES, 'UTF-8'); ?>">
                                                <button type="button" class="placeholder" aria-label="Reproduzir vídeo">
                                                    <img src="<?php echo $thumb; ?>" alt="Thumbnail da aula">
                                                    <span class="play-btn" aria-hidden="true">►</span>
                                                </button>
                                            </div>
                                        <?php elseif (!empty($aula['url_arquivo'])): ?>
                                            <?php if (strpos($aula['url_arquivo'], 'youtube') !== false): ?>
                                                <?php
                                                $videoUrl = (string)$aula['url_arquivo'];
                                                $youtubeId = '';
                                                if (preg_match('~(?:v=|youtu\.be/|embed/)([A-Za-z0-9_-]{6,})~', $videoUrl, $matches)) {
                                                    $youtubeId = $matches[1];
                                                }
                                                ?>
                                                <?php if ($youtubeId !== ''): ?>
                                                    <div class="video-wrapper" data-embed="https://www.youtube-nocookie.com/embed/<?php echo htmlspecialchars($youtubeId, ENT_QUOTES, 'UTF-8'); ?>?autoplay=1&rel=0&modestbranding=1&playsinline=1&iv_load_policy=3&fs=1&enablejsapi=1" data-title="<?php echo htmlspecialchars((string)($aula['titulo'] ?? 'Reprodutor de vídeo da aula'), ENT_QUOTES, 'UTF-8'); ?>">
                                                        <button type="button" class="placeholder" aria-label="Reproduzir vídeo">
                                                            <img src="https://i.ytimg.com/vi/<?php echo htmlspecialchars($youtubeId, ENT_QUOTES, 'UTF-8'); ?>/hqdefault.jpg" alt="Thumbnail da aula">
                                                            <span class="play-btn" aria-hidden="true">►</span>
                                                        </button>
                                                    </div>
                                                <?php else: ?>
                                                    <iframe class="media-frame" src="<?php echo htmlspecialchars($aula['url_arquivo']); ?>" title="<?php echo htmlspecialchars($aula['titulo']); ?>" frameborder="0" allowfullscreen loading="lazy"></iframe>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <video controls class="media-video" preload="metadata">
                                                    <source src="<?php echo htmlspecialchars($aula['url_arquivo']); ?>" type="video/mp4">
                                                    Seu navegador não suporta vídeo HTML5.
                                                </video>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <div class="media-unavailable">Vídeo não disponível.</div>
                                        <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="lesson-mode-panel lesson-mode-panel--economico is-hidden" data-mode-panel="economico" aria-hidden="true">
                                        <div class="lesson-mode-economic">
                                            <div class="lesson-mode-banner" data-mode-banner="economico" hidden>
                                                Modo econômico ativado — até 80% menos consumo de dados
                                            </div>
                                            <div class="lesson-mode-audio-card">
                                                <div class="lesson-mode-section-head">
                                                    <span class="lesson-mode-kicker">Baixo consumo</span>
                                                    <h2>Ouça a aula com menos dados</h2>
                                                </div>
                                                <?php if ($hasEconomicAudioFile): ?>
                                                    <audio controls preload="none" class="lesson-mode-audio">
                                                        <source src="<?php echo htmlspecialchars($audioSourceUrl, ENT_QUOTES, 'UTF-8'); ?>" type="audio/mpeg">
                                                        Seu navegador não suporta reprodução de áudio.
                                                    </audio>
                                                <?php elseif ($hasTranscript): ?>
                                                    <div class="lesson-mode-tts" data-browser-tts>
                                                        <p class="lesson-mode-tts__intro">Este áudio econômico será narrado a partir da transcrição da aula.</p>
                                                        <div class="lesson-mode-tts__controls">
                                                            <button type="button" class="btn btn-primary btn-sm" data-tts-play>Ouvir agora</button>
                                                            <button type="button" class="btn btn-outline btn-sm" data-tts-pause disabled aria-disabled="true">Pausar</button>
                                                            <button type="button" class="btn btn-outline btn-sm" data-tts-resume disabled aria-disabled="true">Continuar</button>
                                                            <button type="button" class="btn btn-outline btn-sm" data-tts-stop disabled aria-disabled="true">Parar</button>
                                                        </div>
                                                        <p class="lesson-mode-tts__status" data-tts-status role="status">Pronto para reproduzir a aula em áudio.</p>
                                                        <textarea hidden data-tts-text><?php echo htmlspecialchars($lessonTranscript, ENT_QUOTES, 'UTF-8'); ?></textarea>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="lesson-mode-empty-state">Áudio não disponível nesta aula.</div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="lesson-mode-summary-card">
                                                <div class="lesson-mode-section-head">
                                                    <span class="lesson-mode-kicker">Resumo rápido</span>
                                                    <h2>Pontos principais da aula</h2>
                                                </div>
                                                <div class="lesson-mode-summary lesson-rich-text">
                                                    <?php if ($summaryMarkdown !== ''): ?>
                                                        <?php echo $renderLessonMarkdown($summaryMarkdown); ?>
                                                    <?php else: ?>
                                                        <p><?php echo htmlspecialchars($summaryFallback, ENT_QUOTES, 'UTF-8'); ?></p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="lesson-mode-panel lesson-mode-panel--leitura is-hidden" data-mode-panel="leitura" aria-hidden="true">
                                        <div class="lesson-mode-reading">
                                            <div class="lesson-intelligent-reading lesson-reading-premium" data-reading-premium>
                                                <div class="lesson-intelligent-reading__header lesson-reading-premium__header">
                                                    <div class="lesson-reading-premium__intro">
                                                        <span class="lesson-mode-kicker lesson-reading-premium__eyebrow">Leitura inteligente com IA</span>
                                                        <h3>Material de estudo estruturado</h3>
                                                        <p>Uma experiência de leitura organizada para estudar com mais conforto, foco e clareza.</p>
                                                    </div>
                                                    <?php if ($formattedLessonAiGeneratedAt !== ''): ?>
                                                        <span class="lesson-intelligent-reading__stamp lesson-reading-premium__stamp">Atualizado em <?php echo htmlspecialchars($formattedLessonAiGeneratedAt, ENT_QUOTES, 'UTF-8'); ?></span>
                                                    <?php endif; ?>
                                                </div>

                                                <?php if ($hasLessonAiContent): ?>
                                                    <div class="lesson-intelligent-reading__content lesson-reading-premium__body lesson-rich-text" data-reading-prose>
                                                        <?php echo $renderLessonMarkdown($lessonAiContent); ?>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="lesson-intelligent-reading__empty lesson-reading-premium__empty">
                                                        <p>Gere uma versão didática desta aula para estudar com mais clareza, organização e foco.</p>
                                                        <?php if (!$hasTranscript): ?>
                                                            <p class="lesson-intelligent-reading__hint">Esta aula ainda não possui transcrição disponível, por isso o conteúdo inteligente não pode ser gerado agora.</p>
                                                        <?php endif; ?>
                                                        <?php if ($lessonAiError !== ''): ?>
                                                            <p class="lesson-intelligent-reading__error"><?php echo htmlspecialchars($lessonAiError, ENT_QUOTES, 'UTF-8'); ?></p>
                                                        <?php endif; ?>
                                                        <form method="POST" class="lesson-ai-generate-form" data-lesson-ai-generate-form>
                                                            <?php echo csrf_input(); ?>
                                                            <input type="hidden" name="acao" value="gerar_conteudo_inteligente_aula">
                                                            <input type="hidden" name="lesson_id" value="<?php echo (int)$aula['id']; ?>">
                                                            <input type="hidden" name="course_id" value="<?php echo (int)$curso['id']; ?>">
                                                            <button type="submit" class="btn btn-primary" <?php echo $hasTranscript ? '' : 'disabled aria-disabled="true"'; ?> data-lesson-ai-generate-button>
                                                                Gerar conteúdo inteligente
                                                            </button>
                                                            <p class="lesson-ai-generate-form__loading is-hidden" data-lesson-ai-loading>
                                                                Gerando conteúdo inteligente da aula...
                                                            </p>
                                                        </form>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php elseif ($aula['tipo'] === 'pdf'): ?>
                                    <div class="pdf-viewer">
                                        <iframe class="pdf-frame" src="<?php echo htmlspecialchars($aula['url_arquivo']); ?>" title="<?php echo htmlspecialchars($aula['titulo']); ?>"></iframe>
                                    </div>
                                <?php else: ?>
                                    <div class="aula-texto">
                                        <?php echo sanitize_html($aula['conteudo'] ?? ''); ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <?php if (!$isOwner): ?>
                                <div
                                    class="aula-actions"
                                    data-lesson-id="<?php echo (int)$aula['id']; ?>"
                                    data-course-id="<?php echo (int)$curso['id']; ?>">
                                    <div class="aula-actions-copy">
                                        <h2>Progresso da aula</h2>
                                        <p>Finalize esta etapa para atualizar o progresso do curso e avançar automaticamente na trilha.</p>
                                    </div>
                                    <div class="aula-actions-control">
                                        <?php if (!empty($concluida)): ?>
                                            <button id="btn-marcar-concluida" class="btn btn-success btn-lg completed" data-completed="1">✓ Concluída</button>
                                        <?php else: ?>
                                            <form id="form-marcar-concluida" method="POST" class="inline-form">
                                                <input type="hidden" name="acao" value="marcar_concluida">
                                                <?php echo csrf_input(); ?>
                                                <input type="hidden" name="lesson_id" value="<?php echo $aula['id']; ?>">
                                                <button type="submit" class="btn btn-secondary btn-lg" id="btn-marcar-concluida">✓ Marcar como Concluída</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </article>

                        <article class="lesson-reading-card">
                            <div class="lesson-section-heading">
                                <span class="lesson-section-eyebrow">Conteúdo da aula</span>
                                <h2>Resumo e material de apoio</h2>
                            </div>
                            <div class="lesson-reading-overview">
                                <article class="lesson-reading-pill">
                                    <span>Formato</span>
                                    <strong><?php echo htmlspecialchars(strtoupper((string)($aula['tipo'] ?? 'AULA')), ENT_QUOTES, 'UTF-8'); ?></strong>
                                </article>
                                <article class="lesson-reading-pill">
                                    <span>Transcrição</span>
                                    <strong><?php echo $hasTranscript ? 'Disponível' : 'Parcial'; ?></strong>
                                </article>
                                <article class="lesson-reading-pill">
                                    <span>Apoio extra</span>
                                    <strong><?php echo $hasEconomicAudio ? 'Áudio ativo' : 'Leitura guiada'; ?></strong>
                                </article>
                                <article class="lesson-reading-pill">
                                    <span>Avaliação</span>
                                    <strong><?php echo !empty($quizzes) ? count($quizzes) . ' quiz(es)' : 'Sem quiz'; ?></strong>
                                </article>
                            </div>
                            <div class="aula-descricao">
                                <p><?php echo htmlspecialchars($aula['descricao'] ?? ''); ?></p>
                            </div>
                            <?php if ($aula['tipo'] !== 'texto' && !empty($aula['conteudo'])): ?>
                                <div class="aula-texto lesson-rich-text">
                                    <?php echo sanitize_html($aula['conteudo']); ?>
                                </div>
                            <?php endif; ?>
                        </article>

                        <?php if (isset($quizzes) && count($quizzes) > 0): ?>
                            <section class="quizzes-section">
                                <div class="lesson-section-heading">
                                    <span class="lesson-section-eyebrow">Avaliação</span>
                                    <h2>Quizzes disponíveis</h2>
                                </div>

                                <?php if (!empty($avaliacao['nota'])): ?>
                                    <div class="quiz-course-overview">
                                        <div class="quiz-course-overview__item">
                                            <span>Nota final do curso</span>
                                            <strong><?php echo htmlspecialchars(number_format((float)($avaliacao['nota']['nota_final'] ?? 0), 1, ',', '.'), ENT_QUOTES, 'UTF-8'); ?>/20</strong>
                                        </div>
                                        <div class="quiz-course-overview__item">
                                            <span>Progresso de avaliação</span>
                                            <strong><?php echo htmlspecialchars($avaliacao['progresso_avaliacao'] ?? 0, ENT_QUOTES, 'UTF-8'); ?>%</strong>
                                        </div>
                                        <div class="quiz-course-overview__item">
                                            <span>Status</span>
                                            <strong><?php echo !empty($avaliacao['nota']['aprovado']) ? 'Aprovado' : 'Pendente'; ?></strong>
                                        </div>
                                    </div>

                                    <?php if (!empty($avaliacao['nota']['grupos']) && is_array($avaliacao['nota']['grupos'])): ?>
                                        <div class="quiz-course-groups">
                                            <?php foreach ($avaliacao['nota']['grupos'] as $grupo): ?>
                                                <?php if (($grupo['count'] ?? 0) <= 0) { continue; } ?>
                                                <article class="quiz-course-groups__item">
                                                    <span><?php echo htmlspecialchars($grupo['label'] ?? 'Grupo', ENT_QUOTES, 'UTF-8'); ?></span>
                                                    <strong><?php echo htmlspecialchars(number_format((float)($grupo['media'] ?? 0), 1, ',', '.'), ENT_QUOTES, 'UTF-8'); ?>/20</strong>
                                                    <small><?php echo htmlspecialchars((string)($grupo['count'] ?? 0), ENT_QUOTES, 'UTF-8'); ?> quiz(es) · peso <?php echo htmlspecialchars(number_format((float)($grupo['peso_normalizado'] ?? 0), 1, ',', '.'), ENT_QUOTES, 'UTF-8'); ?>%</small>
                                                </article>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <div class="quizzes-list">
                                    <?php foreach ($quizzes as $quiz): ?>
                                        <div class="quiz-card">
                                            <div class="quiz-header">
                                                <h3><?php echo htmlspecialchars($quiz['titulo']); ?></h3>
                                                <span class="quiz-pontos"><?php echo htmlspecialchars(number_format((float)($quiz['pontos_totais'] ?? 20), 1, ',', '.'), ENT_QUOTES, 'UTF-8'); ?>/20</span>
                                            </div>
                                            <p><?php echo htmlspecialchars($quiz['descricao'] ?? ''); ?></p>
                                            <p class="tentativas">Tentativas: <?php echo htmlspecialchars($quiz['tentativas_usadas'] ?? 0, ENT_QUOTES, 'UTF-8'); ?>/<?php echo htmlspecialchars($quiz['tentativas_maximas'], ENT_QUOTES, 'UTF-8'); ?></p>
                                            <p class="tentativas">Tipo: <?php echo htmlspecialchars(strtoupper($quiz['tipo'] ?? 'final'), ENT_QUOTES, 'UTF-8'); ?> · <?php echo htmlspecialchars($quiz['dificuldade_label'] ?? 'Normal', ENT_QUOTES, 'UTF-8'); ?> · Peso <?php echo htmlspecialchars((string)($quiz['peso_percentual'] ?? $quiz['peso'] ?? 20), ENT_QUOTES, 'UTF-8'); ?>%</p>
                                            <?php if (!empty($quiz['melhor_resultado'])): ?>
                                                <p class="tentativas">Melhor nota: <?php echo htmlspecialchars(number_format((float)($quiz['melhor_resultado']['pontuacao'] ?? 0), 1, ',', '.'), ENT_QUOTES, 'UTF-8'); ?>/20</p>
                                            <?php endif; ?>
                                            <?php if ($isOwner): ?>
                                                <a href="?page=quiz&quiz_id=<?php echo $quiz['id']; ?>&course_id=<?php echo $curso['id']; ?>" class="btn btn-outline">Gerenciar Quiz</a>
                                            <?php else: ?>
                                                <a href="?page=quiz&quiz_id=<?php echo $quiz['id']; ?>&course_id=<?php echo $curso['id']; ?>" class="btn btn-primary">Fazer Quiz</a>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </section>
                        <?php endif; ?>
                    </section>
                </main>

                <aside class="aula-sidebar">
                    <div class="aulas-related">
                        <div class="lesson-sidebar-head">
                            <span class="lesson-section-eyebrow">Trilha do curso</span>
                            <h2>Módulos e etapas</h2>
                            <p><?php echo htmlspecialchars($aulas_concluidas_total ?? 0, ENT_QUOTES, 'UTF-8'); ?> de <?php echo htmlspecialchars($totalAulasCurso, ENT_QUOTES, 'UTF-8'); ?> concluídas</p>
                        </div>

                        <div class="lesson-progress-summary">
                            <div class="progresso-bar" role="progressbar" aria-label="Progresso da trilha" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?php echo htmlspecialchars($trailProgressValue, ENT_QUOTES, 'UTF-8'); ?>">
                                <div class="progresso-fill" data-course-id="<?php echo htmlspecialchars((int)$curso['id'], ENT_QUOTES, 'UTF-8'); ?>" data-progress="<?php echo htmlspecialchars($trailProgressValue, ENT_QUOTES, 'UTF-8'); ?>"></div>
                            </div>
                            <p class="progresso-text" data-course-id="<?php echo htmlspecialchars((int)$curso['id'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($trailProgressValue, ENT_QUOTES, 'UTF-8'); ?>% completo</p>
                        </div>

                        <div class="aulas-grid-sidebar">
                            <?php foreach ($modulosCurso as $module): ?>
                                <section class="lesson-module-sidebar <?php echo !empty($module['unlocked']) ? '' : 'is-locked'; ?>">
                                    <header class="lesson-module-sidebar__head">
                                        <div>
                                            <span class="lesson-section-eyebrow"><?php echo !empty($module['unlocked']) ? 'Disponível' : 'Bloqueado'; ?></span>
                                            <h3><?php echo htmlspecialchars((string)($module['titulo'] ?? 'Módulo'), ENT_QUOTES, 'UTF-8'); ?></h3>
                                        </div>
                                        <strong><?php echo htmlspecialchars((string)($module['progress_percent'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>%</strong>
                                    </header>

                                    <?php foreach (($module['lessons'] ?? []) as $a): ?>
                                        <?php
                                        $isCurrent = (int)($a['id'] ?? 0) === (int)($aula['id'] ?? 0);
                                        $isCompleted = !empty($a['is_completed']);
                                        ?>
                                        <?php if (!empty($module['unlocked']) || $isOwner): ?>
                                            <a href="?page=aula&lesson_id=<?php echo $a['id']; ?>&course_id=<?php echo $curso['id']; ?>" class="aula-card-sidebar <?php echo $isCurrent ? 'active' : ''; ?>">
                                                <div class="aula-card-index"><?php echo htmlspecialchars($a['position'] ?? 1, ENT_QUOTES, 'UTF-8'); ?></div>
                                                <div class="aula-card-copy">
                                                    <div class="aula-card-status-row">
                                                        <span class="aula-card-status <?php echo $isCurrent ? 'is-current' : ($isCompleted ? 'is-completed' : 'is-pending'); ?>">
                                                            <?php echo $isCurrent ? 'Atual' : ($isCompleted ? 'Assistida' : 'Pendente'); ?>
                                                        </span>
                                                        <span class="aula-card-type"><?php echo htmlspecialchars(strtoupper($a['tipo'] ?? 'AULA'), ENT_QUOTES, 'UTF-8'); ?></span>
                                                    </div>
                                                    <div class="aula-card-title"><?php echo htmlspecialchars($a['titulo']); ?></div>
                                                </div>
                                            </a>
                                        <?php else: ?>
                                            <div class="aula-card-sidebar is-disabled">
                                                <div class="aula-card-index"><?php echo htmlspecialchars($a['position'] ?? 1, ENT_QUOTES, 'UTF-8'); ?></div>
                                                <div class="aula-card-copy">
                                                    <div class="aula-card-status-row">
                                                        <span class="aula-card-status is-pending">Bloqueada</span>
                                                        <span class="aula-card-type"><?php echo htmlspecialchars(strtoupper($a['tipo'] ?? 'AULA'), ENT_QUOTES, 'UTF-8'); ?></span>
                                                    </div>
                                                    <div class="aula-card-title"><?php echo htmlspecialchars($a['titulo']); ?></div>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>

                                    <?php foreach (($module['module_quizzes'] ?? []) as $moduleQuiz): ?>
                                        <?php if (!empty($module['quiz_unlocked']) || $isOwner): ?>
                                            <a href="?page=quiz&quiz_id=<?php echo htmlspecialchars((string)($moduleQuiz['id'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>" class="aula-card-sidebar aula-card-sidebar--quiz <?php echo (int)($moduleQuiz['module_id'] ?? 0) === (int)($currentModule['id'] ?? 0) ? 'active' : ''; ?>">
                                                <div class="aula-card-index">Q</div>
                                                <div class="aula-card-copy">
                                                    <div class="aula-card-status-row">
                                                        <span class="aula-card-status <?php echo !empty($module['quiz_unlocked']) ? 'is-current' : 'is-pending'; ?>">
                                                            Quiz do módulo
                                                        </span>
                                                        <span class="aula-card-type"><?php echo htmlspecialchars(strtoupper((string)($moduleQuiz['dificuldade_label'] ?? 'Normal')), ENT_QUOTES, 'UTF-8'); ?></span>
                                                    </div>
                                                    <div class="aula-card-title"><?php echo htmlspecialchars((string)($moduleQuiz['titulo'] ?? 'Quiz'), ENT_QUOTES, 'UTF-8'); ?></div>
                                                </div>
                                            </a>
                                        <?php else: ?>
                                            <div class="aula-card-sidebar aula-card-sidebar--quiz is-disabled">
                                                <div class="aula-card-index">Q</div>
                                                <div class="aula-card-copy">
                                                    <div class="aula-card-status-row">
                                                        <span class="aula-card-status is-pending">Conclua as aulas</span>
                                                        <span class="aula-card-type"><?php echo htmlspecialchars(strtoupper((string)($moduleQuiz['dificuldade_label'] ?? 'Normal')), ENT_QUOTES, 'UTF-8'); ?></span>
                                                    </div>
                                                    <div class="aula-card-title"><?php echo htmlspecialchars((string)($moduleQuiz['titulo'] ?? 'Quiz'), ENT_QUOTES, 'UTF-8'); ?></div>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </section>
                            <?php endforeach; ?>

                            <?php foreach (($curso['quizzes_finais'] ?? []) as $finalQuiz): ?>
                                <?php if ($allModulesCompleted || $isOwner): ?>
                                    <a href="?page=quiz&quiz_id=<?php echo htmlspecialchars((string)($finalQuiz['id'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>" class="aula-card-sidebar aula-card-sidebar--quiz">
                                        <div class="aula-card-index">F</div>
                                        <div class="aula-card-copy">
                                            <div class="aula-card-status-row">
                                                <span class="aula-card-status is-current">Final</span>
                                                <span class="aula-card-type">CURSO</span>
                                            </div>
                                            <div class="aula-card-title"><?php echo htmlspecialchars((string)($finalQuiz['titulo'] ?? 'Quiz final'), ENT_QUOTES, 'UTF-8'); ?></div>
                                        </div>
                                    </a>
                                <?php else: ?>
                                    <div class="aula-card-sidebar aula-card-sidebar--quiz is-disabled">
                                        <div class="aula-card-index">F</div>
                                        <div class="aula-card-copy">
                                            <div class="aula-card-status-row">
                                                <span class="aula-card-status is-pending">Bloqueado</span>
                                                <span class="aula-card-type">CURSO</span>
                                            </div>
                                            <div class="aula-card-title"><?php echo htmlspecialchars((string)($finalQuiz['titulo'] ?? 'Quiz final'), ENT_QUOTES, 'UTF-8'); ?></div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </aside>
            </div>
        <?php else: ?>
            <div class="alert alert-error">Aula não encontrada.</div>
        <?php endif; ?>
    </div>
</section>
<?php if (isset($aula) && $aula): ?>
    <div
        class="ai-chat-widget"
        data-ai-chat-widget
        data-lesson-id="<?php echo (int)($aula['id'] ?? 0); ?>"
        data-endpoint="<?php echo htmlspecialchars(BASE_URL . '/index.php?page=perguntar-ia', ENT_QUOTES, 'UTF-8'); ?>"
        data-assistant-name="<?php echo htmlspecialchars($chatAssistantName, ENT_QUOTES, 'UTF-8'); ?>"
        data-assistant-avatar="<?php echo htmlspecialchars($chatAssistantAvatar, ENT_QUOTES, 'UTF-8'); ?>"
        data-assistant-greeting="<?php echo htmlspecialchars($chatAssistantGreeting, ENT_QUOTES, 'UTF-8'); ?>"
        style="--ai-chat-accent: <?php echo htmlspecialchars($assistantAccent, ENT_QUOTES, 'UTF-8'); ?>; --ai-chat-accent-dark: <?php echo htmlspecialchars($assistantAccentDark, ENT_QUOTES, 'UTF-8'); ?>; --ai-chat-accent-soft: <?php echo htmlspecialchars($assistantAccentSoft, ENT_QUOTES, 'UTF-8'); ?>; --ai-chat-accent-soft-alt: <?php echo htmlspecialchars($assistantAccentSoftAlt, ENT_QUOTES, 'UTF-8'); ?>;">
        <button type="button" class="ai-chat-toggle" data-ai-chat-toggle aria-label="Abrir <?php echo htmlspecialchars($chatAssistantName, ENT_QUOTES, 'UTF-8'); ?>">
            <span class="ai-chat-toggle-icon" aria-hidden="true">✦</span>
            <span class="ai-chat-toggle-ping" aria-hidden="true"></span>
        </button>

        <div class="ai-chat-box" aria-live="polite">
            <div class="ai-chat-header">
                <div class="ai-chat-header-main">
                    <span class="ai-chat-header-avatar" aria-hidden="true"><?php echo htmlspecialchars($chatAssistantAvatar, ENT_QUOTES, 'UTF-8'); ?></span>
                    <div class="ai-chat-header-copy">
                        <strong><?php echo htmlspecialchars($chatAssistantName, ENT_QUOTES, 'UTF-8'); ?></strong>
                        <small>Especialista nesta aula</small>
                        <span class="ai-chat-header-status">Online agora</span>
                    </div>
                </div>
                <div class="ai-chat-header-actions">
                    <button type="button" class="ai-chat-clear" data-ai-chat-clear aria-label="Limpar conversa">Limpar</button>
                    <button type="button" class="ai-chat-close" data-ai-chat-close aria-label="Fechar chat">×</button>
                </div>
            </div>

            <div class="ai-chat-messages" data-ai-chat-messages>
                <div class="ai-chat-message ai-chat-assistant">
                    <span class="ai-chat-avatar" aria-hidden="true"><?php echo htmlspecialchars($chatAssistantAvatar, ENT_QUOTES, 'UTF-8'); ?></span>
                    <div class="ai-chat-bubble"><?php echo htmlspecialchars($chatAssistantGreeting, ENT_QUOTES, 'UTF-8'); ?></div>
                </div>
            </div>

            <form class="ai-chat-input" data-ai-chat-form>
                <div class="ai-chat-input-main">
                    <textarea
                        class="ai-chat-input-field"
                        data-ai-chat-input
                        placeholder="Pergunte qualquer dúvida sobre esta aula…"
                        rows="1"
                        maxlength="1000"></textarea>
                    <p class="ai-chat-input-hint" data-ai-chat-input-hint hidden>Escreva uma pergunta com pelo menos 3 caracteres.</p>
                </div>
                <button type="submit" class="ai-chat-send" data-ai-chat-submit>Enviar</button>
            </form>
        </div>
    </div>
<?php endif; ?>
<?php if (isset($aula) && $aula && ($aula['tipo'] ?? '') === 'video'): ?>
    <script src="<?php echo htmlspecialchars(BASE_URL . '/js/pages/aula-modos.js?v=' . rawurlencode((string)$lessonModeJsVersion), ENT_QUOTES, 'UTF-8'); ?>"></script>
<?php endif; ?>
<?php if (isset($aula) && $aula): ?>
    <script src="<?php echo htmlspecialchars(BASE_URL . '/js/pages/ai-chat.js?v=' . rawurlencode((string)$aiChatJsVersion), ENT_QUOTES, 'UTF-8'); ?>"></script>
    <script src="<?php echo htmlspecialchars(BASE_URL . '/js/pages/aula.js?v=' . rawurlencode((string)$lessonJsVersion), ENT_QUOTES, 'UTF-8'); ?>"></script>
<?php endif; ?>
