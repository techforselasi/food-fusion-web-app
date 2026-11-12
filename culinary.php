<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$pageTitle = 'FoodFusion | Culinary resources';
$bodyClass = 'page-resources';

$items = [];
if (ff_table_exists($conn, 'culinary_resources')) {
    try {
        $stmt = $conn->query('SELECT * FROM culinary_resources ORDER BY title ASC');
        $items = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    } catch (PDOException $e) {
        $items = [];
    }
}

require __DIR__ . '/includes/header.php';
?>

<section class="page-hero page-hero--compact">
  <div class="wrap">
    <h1 class="page-title">Culinary resources</h1>
    <p class="page-lead">Downloadable references, technique videos, and trusted links—including ice cream demos and cooking fundamentals.</p>
  </div>
</section>

<section class="section">
  <div class="wrap">
    <?php if ($items === []): ?>
      <p class="empty-state">No resources yet. Populate the <strong>culinary_resources</strong> table to list items here.</p>
    <?php else: ?>
      <ul class="resource-grid">
        <?php foreach ($items as $row): ?>
          <?php
            $title = $row['title'] ?? 'Resource';
            $desc = $row['description'] ?? $row['summary'] ?? '';
            $url = $row['resource_url'] ?? $row['url'] ?? $row['link'] ?? '';
            $url = is_string($url) ? trim($url) : '';
            $titleText = trim((string) $title);
            $knifeTitle = 'Knife Skills: How to Chop — Serious Eats';
            $knifeDownloadUrl = 'https://res.cloudinary.com/dkk1jdfop/video/upload/fl_attachment/v1776185683/Knife_Skills_How_to_Chop_Serious_Eats_ouao97.mp3';
            if ($titleText === $knifeTitle) {
                $url = $knifeDownloadUrl;
            }
            $kind = $url !== '' ? ff_resource_kind_from_url($url) : 'link';
            $action = ff_resource_action_label($kind);
            $downloadVideo = $titleText === $knifeTitle;
            if ($downloadVideo) {
                $action = 'Download video';
            }
            ?>
          <li class="resource-card">
            <?php ff_render_resource_thumbnail($url !== '' ? $url : null, $row, $action, $downloadVideo); ?>
            <div class="resource-card__inner">
              <?php if ($kind === 'pdf' || $kind === 'video'): ?>
                <span class="resource-card__kind resource-card__kind--<?php echo h($kind); ?>"><?php echo $kind === 'pdf' ? 'PDF' : 'Video'; ?></span>
              <?php endif; ?>
              <h2 class="resource-card__title"><?php echo h((string) $title); ?></h2>
              <?php if (is_string($desc) && trim($desc) !== ''): ?>
                <p class="resource-card__desc"><?php echo h(trim(strip_tags($desc))); ?></p>
              <?php endif; ?>
              <?php if ($url !== ''): ?>
                <a class="resource-card__link"
                   href="<?php echo h($url); ?>"
                   <?php if ($downloadVideo): ?>download<?php else: ?>target="_blank" rel="noopener noreferrer"<?php endif; ?>><?php echo h($action); ?></a>
              <?php endif; ?>
            </div>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
