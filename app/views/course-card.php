<?php
$courseCard = $courseCard ?? [];
$courseData = $courseCard['course'] ?? [];
$cardClass = trim('course-card' . (!empty($courseCard['class']) ? ' ' . $courseCard['class'] : ''));
$thumbnail = (string)($courseCard['thumbnail'] ?? ($courseData['thumbnail'] ?? ''));
$thumbnailUrl = $thumbnail !== '' ? thumbnail_url($thumbnail) : '';
$title = (string)($courseCard['title'] ?? ($courseData['titulo'] ?? 'Curso sem título'));
$titleHref = (string)($courseCard['title_href'] ?? '');
$eyebrow = trim((string)($courseCard['eyebrow'] ?? ''));
$instructor = trim((string)($courseCard['instructor'] ?? ''));
$description = trim((string)($courseCard['description'] ?? ''));
$metaItems = array_values(array_filter($courseCard['meta'] ?? [], static fn($item) => trim((string)$item) !== ''));
$progress = array_key_exists('progress', $courseCard) && $courseCard['progress'] !== null
    ? max(0, min(100, (int)$courseCard['progress']))
    : null;
$progressLabel = trim((string)($courseCard['progress_label'] ?? ($progress !== null ? $progress . '% concluído' : '')));
$progressCaption = trim((string)($courseCard['progress_caption'] ?? 'Progresso'));
$status = $courseCard['status'] ?? null;
$primaryAction = $courseCard['primary_action'] ?? null;
$secondaryActions = $courseCard['secondary_actions'] ?? [];
?>

<article class="<?php echo htmlspecialchars($cardClass, ENT_QUOTES, 'UTF-8'); ?>">
    <div class="course-card__media">
        <?php if ($thumbnailUrl !== ''): ?>
            <img
                src="<?php echo htmlspecialchars($thumbnailUrl, ENT_QUOTES, 'UTF-8'); ?>"
                alt="<?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?>"
                class="course-card__image"
                loading="lazy"
                width="640"
                height="360">
        <?php else: ?>
            <div class="course-card__placeholder" aria-hidden="true">
                <span>📘</span>
            </div>
        <?php endif; ?>
    </div>

    <div class="course-card__body">
        <div class="course-card__header">
            <?php if ($eyebrow !== ''): ?>
                <span class="course-card__eyebrow"><?php echo htmlspecialchars($eyebrow, ENT_QUOTES, 'UTF-8'); ?></span>
            <?php endif; ?>

            <?php if ($titleHref !== ''): ?>
                <h3 class="course-card__title">
                    <a href="<?php echo htmlspecialchars($titleHref, ENT_QUOTES, 'UTF-8'); ?>" class="course-card__title-link">
                        <?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                </h3>
            <?php else: ?>
                <h3 class="course-card__title"><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></h3>
            <?php endif; ?>

            <?php if ($instructor !== ''): ?>
                <p class="course-card__instructor"><?php echo htmlspecialchars($instructor, ENT_QUOTES, 'UTF-8'); ?></p>
            <?php endif; ?>
        </div>

        <?php if ($description !== ''): ?>
            <p class="course-card__description"><?php echo htmlspecialchars($description, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>

        <?php if (!empty($metaItems)): ?>
            <ul class="course-card__meta" aria-label="Detalhes do curso">
                <?php foreach ($metaItems as $metaItem): ?>
                    <li class="course-card__meta-item"><?php echo htmlspecialchars((string)$metaItem, ENT_QUOTES, 'UTF-8'); ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <?php if ($progress !== null): ?>
            <div class="course-card__progress">
                <div class="course-card__progress-head">
                    <span><?php echo htmlspecialchars($progressCaption, ENT_QUOTES, 'UTF-8'); ?></span>
                    <strong><?php echo htmlspecialchars($progressLabel, ENT_QUOTES, 'UTF-8'); ?></strong>
                </div>
                <div class="progresso-bar" role="progressbar" aria-label="<?php echo htmlspecialchars($progressCaption, ENT_QUOTES, 'UTF-8'); ?>" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?php echo htmlspecialchars((string)$progress, ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="progresso-fill" style="width: <?php echo htmlspecialchars((string)$progress, ENT_QUOTES, 'UTF-8'); ?>%;"></div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($status) && !empty($status['label'])): ?>
            <div class="course-card__status">
                <span class="badge <?php echo htmlspecialchars((string)($status['class'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                    <?php echo htmlspecialchars((string)$status['label'], ENT_QUOTES, 'UTF-8'); ?>
                </span>
            </div>
        <?php endif; ?>

        <div class="course-card__actions">
            <?php if (!empty($primaryAction['label'])): ?>
                <?php $primaryClass = trim('btn btn-block ui-btn ui-btn--primary ' . (string)($primaryAction['class'] ?? '')); ?>
                <?php if (!empty($primaryAction['href'])): ?>
                    <a href="<?php echo htmlspecialchars((string)$primaryAction['href'], ENT_QUOTES, 'UTF-8'); ?>" class="<?php echo htmlspecialchars($primaryClass, ENT_QUOTES, 'UTF-8'); ?>">
                        <?php echo htmlspecialchars((string)$primaryAction['label'], ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                <?php else: ?>
                    <button type="<?php echo htmlspecialchars((string)($primaryAction['type'] ?? 'button'), ENT_QUOTES, 'UTF-8'); ?>" class="<?php echo htmlspecialchars($primaryClass, ENT_QUOTES, 'UTF-8'); ?>">
                        <?php echo htmlspecialchars((string)$primaryAction['label'], ENT_QUOTES, 'UTF-8'); ?>
                    </button>
                <?php endif; ?>
            <?php endif; ?>

            <?php if (!empty($secondaryActions)): ?>
                <div class="course-card__secondary-actions">
                    <?php foreach ($secondaryActions as $action): ?>
                        <?php if (empty($action['label'])) continue; ?>
                        <?php $secondaryClass = trim('btn ui-btn course-card__secondary-action ' . (string)($action['class'] ?? 'btn-outline')); ?>
                        <?php if (!empty($action['href'])): ?>
                            <a href="<?php echo htmlspecialchars((string)$action['href'], ENT_QUOTES, 'UTF-8'); ?>" class="<?php echo htmlspecialchars($secondaryClass, ENT_QUOTES, 'UTF-8'); ?>">
                                <?php echo htmlspecialchars((string)$action['label'], ENT_QUOTES, 'UTF-8'); ?>
                            </a>
                        <?php else: ?>
                            <button
                                type="<?php echo htmlspecialchars((string)($action['type'] ?? 'button'), ENT_QUOTES, 'UTF-8'); ?>"
                                class="<?php echo htmlspecialchars($secondaryClass, ENT_QUOTES, 'UTF-8'); ?>"
                                <?php if (!empty($action['attributes']) && is_array($action['attributes'])): ?>
                                    <?php foreach ($action['attributes'] as $attrName => $attrValue): ?>
                                        <?php echo htmlspecialchars((string)$attrName, ENT_QUOTES, 'UTF-8'); ?>="<?php echo htmlspecialchars((string)$attrValue, ENT_QUOTES, 'UTF-8'); ?>"
                                    <?php endforeach; ?>
                                <?php endif; ?>>
                                <?php echo htmlspecialchars((string)$action['label'], ENT_QUOTES, 'UTF-8'); ?>
                            </button>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</article>
