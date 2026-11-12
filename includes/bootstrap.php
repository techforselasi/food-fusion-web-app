<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/db_config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * @param string|null $s
 */
function h(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

/**
 * @return array{user_id:int, email:string, first_name:string}|null
 */
function ff_user(): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }

    return [
        'user_id' => (int) $_SESSION['user_id'],
        'email' => (string) ($_SESSION['email'] ?? ''),
        'first_name' => (string) ($_SESSION['first_name'] ?? ''),
    ];
}

function ff_table_exists(PDO $conn, string $table): bool
{
    static $cache = [];

    if (!isset($cache[$table])) {
        $stmt = $conn->prepare('SHOW TABLES LIKE :t');
        $stmt->execute([':t' => $table]);
        $cache[$table] = (bool) $stmt->fetchColumn();
    }

    return $cache[$table];
}

/**
 * @return list<string>
 */
function ff_table_columns(PDO $conn, string $table): array
{
    static $cache = [];

    if (!isset($cache[$table])) {
        if (!ff_table_exists($conn, $table)) {
            return $cache[$table] = [];
        }
        $stmt = $conn->query('SHOW COLUMNS FROM `' . str_replace('`', '``', $table) . '`');
        $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        $cache[$table] = array_map(static function ($r) {
            return $r['Field'];
        }, $rows);
    }

    return $cache[$table];
}

/**
 * First matching column name for diet-style filters.
 */
function ff_recipe_diet_column(array $recipeCols): ?string
{
    foreach (['diet_type', 'diet', 'dietary_type', 'dietary_restriction'] as $c) {
        if (in_array($c, $recipeCols, true)) {
            return $c;
        }
    }

    return null;
}

function ff_current_path(): string
{
    return basename($_SERVER['SCRIPT_NAME'] ?? 'index.php');
}

function ff_nav_active(string $file): string
{
    return ff_current_path() === $file ? 'aria-current="page"' : '';
}

/**
 * Classify a resource URL for UI labels (PDF download vs. embedded video hosts).
 */
function ff_resource_kind_from_url(string $url): string
{
    if (preg_match('/\.pdf(\?|#|$)/i', $url) === 1) {
        return 'pdf';
    }
    $lower = strtolower($url);
    if (strpos($lower, 'youtube.com') !== false || strpos($lower, 'youtu.be') !== false) {
        return 'video';
    }

    return 'link';
}

function ff_resource_action_label(string $kind): string
{
    if ($kind === 'pdf') {
        return 'Download PDF';
    }
    if ($kind === 'video') {
        return 'Watch video';
    }

    return 'Open resource';
}

function ff_youtube_video_id(string $url): ?string
{
    if (preg_match('~(?:youtube\.com/watch\?v=|youtu\.be/)([A-Za-z0-9_-]{11})~', $url, $m)) {
        return $m[1];
    }
    if (preg_match('~youtube\.com/embed/([A-Za-z0-9_-]{11})~', $url, $m)) {
        return $m[1];
    }

    return null;
}

/**
 * Thumbnail src and CSS helpers for resource cards (YouTube stills + title-themed PDF art).
 *
 * @param array<string, mixed> $row
 * @return array{src: string, thumb_class: string, is_video: bool}|null
 */
function ff_resource_thumbnail(array $row): ?array
{
    $custom = isset($row['thumbnail_url']) ? trim((string) $row['thumbnail_url']) : '';
    if ($custom !== '') {
        $customVideo = ff_youtube_video_id($custom) !== null
            || stripos($custom, 'youtube.com') !== false
            || stripos($custom, 'youtu.be') !== false;

        return [
            'src' => $custom,
            'thumb_class' => 'resource-card__thumb--custom'
                . ($customVideo ? ' resource-card__thumb--video' : ''),
            'is_video' => $customVideo,
        ];
    }

    $url = isset($row['resource_url']) ? trim((string) $row['resource_url']) : '';
    $title = isset($row['title']) ? (string) $row['title'] : '';
    $kind = $url !== '' ? ff_resource_kind_from_url($url) : 'link';

    if ($kind === 'video') {
        $id = ff_youtube_video_id($url);
        if ($id !== null) {
            return [
                'src' => 'https://img.youtube.com/vi/' . $id . '/mqdefault.jpg',
                'thumb_class' => 'resource-card__thumb--video',
                'is_video' => true,
            ];
        }
    }

    if ($kind === 'pdf') {
        $t = strtolower($title);
        if (strpos($t, 'fda') !== false) {
            return [
                'src' => 'assets/resource-thumbs/fda-reference.svg',
                'thumb_class' => 'resource-card__thumb--pdf resource-card__thumb--pdf-fda',
                'is_video' => false,
            ];
        }
        if (strpos($t, 'usda') !== false || strpos($t, 'kitchen companion') !== false) {
            return [
                'src' => 'assets/resource-thumbs/usda-kitchen.svg',
                'thumb_class' => 'resource-card__thumb--pdf resource-card__thumb--pdf-usda',
                'is_video' => false,
            ];
        }

        return [
            'src' => 'assets/resource-thumbs/pdf-document.svg',
            'thumb_class' => 'resource-card__thumb--pdf resource-card__thumb--pdf-generic',
            'is_video' => false,
        ];
    }

    return null;
}

/**
 * @param array<string, mixed> $row
 */
function ff_render_resource_thumbnail(?string $url, array $row, string $action, bool $download = false): void
{
    $thumb = ff_resource_thumbnail($row);
    if ($thumb === null) {
        return;
    }

    $title = (string) ($row['title'] ?? 'Resource');
    $href = is_string($url) && trim($url) !== '' ? trim($url) : $thumb['src'];
    $label = $action . ': ' . $title;
    ?>
    <a class="resource-card__thumb <?php echo h($thumb['thumb_class']); ?>"
       href="<?php echo h($href); ?>"
       <?php if ($download): ?>download<?php else: ?>target="_blank" rel="noopener noreferrer"<?php endif; ?>
       aria-label="<?php echo h($label); ?>">
      <img src="<?php echo h($thumb['src']); ?>" alt="" width="320" height="180" loading="lazy" decoding="async" />
    </a>
    <?php
}

/**
 * @param list<string> $cols
 * @param list<string> $candidates
 */
function ff_pick_column(array $cols, array $candidates): ?string
{
    foreach ($candidates as $c) {
        if (in_array($c, $cols, true)) {
            return $c;
        }
    }

    return null;
}

require_once __DIR__ . '/media.php';
require_once __DIR__ . '/cloudinary.php';
