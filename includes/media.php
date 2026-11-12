<?php

declare(strict_types=1);

/**
 * Media helpers: recipe images stored as Cloudinary URLs in `media` table.
 * Configuration and uploads live in includes/cloudinary.php.
 */

/**
 * Primary image for a recipe (first by is_primary, sort_order, media_id).
 *
 * @return array{media_id:int, secure_url:string, alt_text:?string, width:?int, height:?int}|null
 */
function ff_recipe_primary_media(PDO $conn, int $recipeId): ?array
{
    if ($recipeId < 1 || !ff_table_exists($conn, 'media')) {
        return null;
    }

    try {
        $stmt = $conn->prepare(
            'SELECT media_id, secure_url, alt_text, width, height
             FROM media
             WHERE recipe_id = :rid
             ORDER BY is_primary DESC, sort_order ASC, media_id ASC
             LIMIT 1'
        );
        $stmt->execute([':rid' => $recipeId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row === false) {
            return null;
        }

        return [
            'media_id' => (int) $row['media_id'],
            'secure_url' => (string) $row['secure_url'],
            'alt_text' => isset($row['alt_text']) && $row['alt_text'] !== '' ? (string) $row['alt_text'] : null,
            'width' => isset($row['width']) ? (int) $row['width'] : null,
            'height' => isset($row['height']) ? (int) $row['height'] : null,
        ];
    } catch (PDOException $e) {
        return null;
    }
}

/**
 * @param list<int> $recipeIds
 * @return array<int, array{secure_url:string, alt_text:?string}>
 */
function ff_media_map_for_recipes(PDO $conn, array $recipeIds): array
{
    $recipeIds = array_values(array_unique(array_filter(array_map('intval', $recipeIds), static function ($id) {
        return $id > 0;
    })));

    if ($recipeIds === [] || !ff_table_exists($conn, 'media')) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($recipeIds), '?'));

    try {
        $sql = 'SELECT recipe_id, secure_url, alt_text, is_primary, sort_order, media_id
                FROM media
                WHERE recipe_id IN (' . $placeholders . ')
                ORDER BY recipe_id, is_primary DESC, sort_order ASC, media_id ASC';
        $stmt = $conn->prepare($sql);
        $stmt->execute($recipeIds);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }

    $map = [];
    foreach ($rows as $row) {
        $rid = (int) ($row['recipe_id'] ?? 0);
        if ($rid < 1 || isset($map[$rid])) {
            continue;
        }
        $map[$rid] = [
            'secure_url' => (string) $row['secure_url'],
            'alt_text' => isset($row['alt_text']) && $row['alt_text'] !== '' ? (string) $row['alt_text'] : null,
        ];
    }

    return $map;
}
