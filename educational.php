<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$pageTitle = 'FoodFusion | Educational resources';
$bodyClass = 'page-resources';

$items = [];
if (ff_table_exists($conn, 'culinary_resources')) {
    try {
        $stmt = $conn->query(
            'SELECT * FROM culinary_resources WHERE is_educational = 1 ORDER BY title ASC'
        );
        $items = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    } catch (PDOException $e) {
        $items = [];
    }
}

require __DIR__ . '/includes/header.php';
?>

<section class="page-hero page-hero--compact">
  <div class="wrap">
    <h1 class="page-title">Educational resources</h1>
    <p class="page-lead">Focused learning materials for technique, theory, and food literacy.</p>
  </div>
</section>

<section class="section">
  <div class="wrap">
    <?php if ($items === []): ?>
      <p class="empty-state">
        No educational resources flagged yet. Set <strong>is_educational = 1</strong> on rows in <strong>culinary_resources</strong>, or add new rows.
      </p>
    <?php else: ?>
      <ul class="resource-grid">
        <?php foreach ($items as $row): ?>
          <?php
            $title = $row['title'] ?? 'Resource';
            $desc = $row['description'] ?? $row['summary'] ?? '';
            $url = $row['resource_url'] ?? $row['url'] ?? $row['link'] ?? '';
            $url = is_string($url) ? trim($url) : '';
            $kind = $url !== '' ? ff_resource_kind_from_url($url) : 'link';
            $action = ff_resource_action_label($kind);
            ?>
          <li class="resource-card resource-card--edu">
            <?php ff_render_resource_thumbnail($url !== '' ? $url : null, $row, $action); ?>
            <div class="resource-card__inner">
              <div class="resource-card__badges">
                <span class="resource-card__badge">Learning</span>
                <?php if ($kind === 'pdf'): ?>
                  <span class="resource-card__badge resource-card__badge--pdf">PDF</span>
                <?php elseif ($kind === 'video'): ?>
                  <span class="resource-card__badge resource-card__badge--video">Video</span>
                <?php endif; ?>
              </div>
              <h2 class="resource-card__title"><?php echo h((string) $title); ?></h2>
              <?php if (is_string($desc) && trim($desc) !== ''): ?>
                <p class="resource-card__desc"><?php echo h(trim(strip_tags($desc))); ?></p>
              <?php endif; ?>
              <?php if ($url !== ''): ?>
                <a class="resource-card__link" href="<?php echo h($url); ?>" target="_blank" rel="noopener noreferrer"><?php echo h($action); ?></a>
              <?php endif; ?>
            </div>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
