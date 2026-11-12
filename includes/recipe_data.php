<?php

declare(strict_types=1);

/**
 * Recipe display helpers + structured recipe rows (ingredients, steps, tags, nutrition).
 */

/**
 * @return array{head: string, accent: string}
 */
function ff_recipe_split_display_title(string $title): array
{
    $title = trim($title);
    if ($title === '') {
        return ['head' => '', 'accent' => ''];
    }
    $words = preg_split('/\s+/u', $title, -1, PREG_SPLIT_NO_EMPTY);
    if ($words === false || count($words) <= 2) {
        return ['head' => $title, 'accent' => ''];
    }
    $tail = array_slice($words, -2);
    $head = array_slice($words, 0, -2);

    return [
        'head' => implode(' ', $head),
        'accent' => implode(' ', $tail),
    ];
}

/** @return list<string> */
function ff_recipe_ingredient_lines(string $ingredients): array
{
    $ingredients = str_replace("\r\n", "\n", $ingredients);
    $lines = explode("\n", $ingredients);
    $out = [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line !== '') {
            $out[] = $line;
        }
    }

    return $out;
}

/** @return list<string> */
function ff_recipe_instruction_steps(string $instructions): array
{
    $instructions = trim(str_replace("\r\n", "\n", $instructions));
    if ($instructions === '') {
        return [];
    }
    if (preg_match_all('/^\s*\d+[\.\)]\s*(.+)$/m', $instructions, $m) && isset($m[1]) && count($m[1]) >= 1) {
        return array_values(array_filter(array_map('trim', $m[1])));
    }
    $parts = preg_split('/\n{2,}/', $instructions);
    $parts = array_values(array_filter(array_map('trim', $parts)));
    if ($parts !== []) {
        return $parts;
    }

    return [$instructions];
}

function ff_recipe_author_initials(string $first, string $last): string
{
    $a = strtoupper(substr(trim($first), 0, 1));
    $b = strtoupper(substr(trim($last), 0, 1));
    $pair = $a . $b;

    return $pair !== '' ? $pair : '?';
}

/** @return list<string> */
function ff_recipe_ingredient_lines_db(PDO $conn, int $recipeId, string $fallbackText): array
{
    if ($recipeId < 1 || !ff_table_exists($conn, 'recipe_ingredients')) {
        return ff_recipe_ingredient_lines($fallbackText);
    }
    try {
        $stmt = $conn->prepare(
            'SELECT line_text FROM recipe_ingredients WHERE recipe_id = :id ORDER BY sort_order ASC, ingredient_id ASC'
        );
        $stmt->execute([':id' => $recipeId]);
        $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);
        if (is_array($rows) && $rows !== []) {
            return array_values(array_filter(array_map('strval', $rows)));
        }
    } catch (PDOException $e) {
    }

    return ff_recipe_ingredient_lines($fallbackText);
}

/**
 * @return list<array{body: string, media_url: ?string, media_type: ?string}>
 */
function ff_recipe_steps_db(PDO $conn, int $recipeId, string $fallbackText): array
{
    $mapPlain = static function (array $plain): array {
        $out = [];
        foreach ($plain as $b) {
            $out[] = ['body' => $b, 'media_url' => null, 'media_type' => null];
        }

        return $out;
    };

    if ($recipeId < 1 || !ff_table_exists($conn, 'recipe_steps')) {
        return $mapPlain(ff_recipe_instruction_steps($fallbackText));
    }
    try {
        $stmt = $conn->prepare(
            'SELECT body, media_secure_url, media_type FROM recipe_steps WHERE recipe_id = :id ORDER BY step_number ASC, step_id ASC'
        );
        $stmt->execute([':id' => $recipeId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (is_array($rows) && $rows !== []) {
            $out = [];
            foreach ($rows as $row) {
                $body = trim((string) ($row['body'] ?? ''));
                if ($body === '') {
                    continue;
                }
                $url = isset($row['media_secure_url']) && $row['media_secure_url'] !== ''
                    ? trim((string) $row['media_secure_url'])
                    : null;
                $mt = isset($row['media_type']) && $row['media_type'] !== ''
                    ? trim((string) $row['media_type'])
                    : null;
                $out[] = ['body' => $body, 'media_url' => $url, 'media_type' => $mt];
            }
            if ($out !== []) {
                return $out;
            }
        }
    } catch (PDOException $e) {
    }

    return $mapPlain(ff_recipe_instruction_steps($fallbackText));
}

/** @return array{calories: ?int, protein_g: ?float, fat_g: ?float, fiber_g: ?float, sugars_g: ?float, carbs_g: ?float}|null */
function ff_recipe_nutrition_row(PDO $conn, int $recipeId): ?array
{
    if ($recipeId < 1 || !ff_table_exists($conn, 'recipe_nutrition')) {
        return null;
    }
    try {
        $stmt = $conn->prepare('SELECT * FROM recipe_nutrition WHERE recipe_id = :id LIMIT 1');
        $stmt->execute([':id' => $recipeId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row === false) {
            return null;
        }
        $cal = isset($row['calories']) && $row['calories'] !== '' ? (int) $row['calories'] : null;
        $num = static function ($k) use ($row): ?float {
            if (!isset($row[$k]) || $row[$k] === '' || $row[$k] === null) {
                return null;
            }

            return (float) $row[$k];
        };

        $n = [
            'calories' => $cal,
            'protein_g' => $num('protein_g'),
            'fat_g' => $num('fat_g'),
            'fiber_g' => $num('fiber_g'),
            'sugars_g' => $num('sugars_g'),
            'carbs_g' => $num('carbs_g'),
        ];
        $any = false;
        foreach ($n as $v) {
            if ($v !== null) {
                $any = true;
                break;
            }
        }

        return $any ? $n : null;
    } catch (PDOException $e) {
        return null;
    }
}

/** @return list<string> */
function ff_recipe_tag_names(PDO $conn, int $recipeId): array
{
    if ($recipeId < 1 || !ff_table_exists($conn, 'recipe_tags') || !ff_table_exists($conn, 'tags')) {
        return [];
    }
    try {
        $stmt = $conn->prepare(
            'SELECT t.name FROM recipe_tags rt
             INNER JOIN tags t ON t.tag_id = rt.tag_id
             WHERE rt.recipe_id = :id
             ORDER BY t.name ASC'
        );
        $stmt->execute([':id' => $recipeId]);
        $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);

        return is_array($rows) ? array_values(array_filter(array_map('strval', $rows))) : [];
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * @return list<array{rating: ?int, comment: ?string, interacted_at: string, first_name: string, last_name: string, profile_image_url: ?string}>
 */
function ff_recipe_reviews_for_recipe(PDO $conn, int $recipeId, int $limit = 8): array
{
    if ($recipeId < 1 || !ff_table_exists($conn, 'interactions')) {
        return [];
    }
    $limit = max(1, min(20, $limit));
    try {
        $sql = 'SELECT i.rating, i.comment, i.interacted_at, u.first_name, u.last_name';
        if (ff_table_exists($conn, 'users') && ff_recipe_column_exists($conn, 'users', 'profile_image_url')) {
            $sql .= ', u.profile_image_url';
        } else {
            $sql .= ', NULL AS profile_image_url';
        }
        $sql .= ' FROM interactions i
             INNER JOIN users u ON u.user_id = i.user_id
             WHERE i.recipe_id = :rid
               AND (
                 (i.comment IS NOT NULL AND i.comment <> "")
                 OR i.rating IS NOT NULL
               )
             ORDER BY i.interacted_at DESC
             LIMIT ' . (int) $limit;
        $stmt = $conn->prepare($sql);
        $stmt->execute([':rid' => $recipeId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return is_array($rows) ? $rows : [];
    } catch (PDOException $e) {
        return [];
    }
}

function ff_recipe_column_exists(PDO $conn, string $table, string $column): bool
{
    static $cache = [];

    $key = $table . '.' . $column;
    if (isset($cache[$key])) {
        return $cache[$key];
    }
    $cols = ff_table_columns($conn, $table);

    return $cache[$key] = in_array($column, $cols, true);
}

/** @return array<int, int> recipe_id => count */
function ff_recipe_like_counts(PDO $conn, array $recipeIds): array
{
    $recipeIds = array_values(array_unique(array_filter(array_map('intval', $recipeIds), static function ($id) {
        return $id > 0;
    })));
    if ($recipeIds === [] || !ff_table_exists($conn, 'recipe_likes')) {
        return [];
    }
    $placeholders = implode(',', array_fill(0, count($recipeIds), '?'));
    try {
        $stmt = $conn->prepare(
            "SELECT recipe_id, COUNT(*) AS c FROM recipe_likes WHERE recipe_id IN ($placeholders) GROUP BY recipe_id"
        );
        $stmt->execute($recipeIds);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row['recipe_id']] = (int) $row['c'];
        }

        return $map;
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Persist structured rows after a recipe is inserted (or updated).
 *
 * @param array{calories: ?int, protein_g: ?float, fat_g: ?float, fiber_g: ?float, sugars_g: ?float, carbs_g: ?float}|null $nutrition
 */
function ff_recipe_save_structured(
    PDO $conn,
    int $recipeId,
    string $ingredientsText,
    string $instructionsText,
    ?string $tagsCommaSeparated,
    ?array $nutrition,
    string $stepMediaUrlsRaw = ''
): void {
    if ($recipeId < 1) {
        return;
    }

    $ingredientLines = ff_recipe_ingredient_lines($ingredientsText);
    $steps = ff_recipe_instruction_steps($instructionsText);
    $mediaLines = preg_split('/\r\n|\r|\n/', $stepMediaUrlsRaw) ?: [];

    try {
        $conn->beginTransaction();

        if (ff_table_exists($conn, 'recipe_ingredients')) {
            $del = $conn->prepare('DELETE FROM recipe_ingredients WHERE recipe_id = :id');
            $del->execute([':id' => $recipeId]);
            $ins = $conn->prepare(
                'INSERT INTO recipe_ingredients (recipe_id, sort_order, line_text) VALUES (:rid, :ord, :line)'
            );
            foreach ($ingredientLines as $ord => $line) {
                $ins->execute([':rid' => $recipeId, ':ord' => $ord, ':line' => $line]);
            }
        }

        if (ff_table_exists($conn, 'recipe_steps')) {
            $del = $conn->prepare('DELETE FROM recipe_steps WHERE recipe_id = :id');
            $del->execute([':id' => $recipeId]);
            $ins = $conn->prepare(
                'INSERT INTO recipe_steps (recipe_id, step_number, body, media_secure_url, media_type)
                 VALUES (:rid, :num, :body, :url, :mt)'
            );
            foreach ($steps as $i => $body) {
                $url = isset($mediaLines[$i]) ? trim((string) $mediaLines[$i]) : '';
                $url = $url !== '' ? $url : null;
                $mt = null;
                if ($url !== null) {
                    $mt = (preg_match('/\.(mp4|webm|ogg)(\?|#|$)/i', $url) === 1 || stripos($url, 'video') !== false)
                        ? 'video'
                        : 'image';
                }
                $ins->execute([
                    ':rid' => $recipeId,
                    ':num' => $i + 1,
                    ':body' => $body,
                    ':url' => $url,
                    ':mt' => $mt,
                ]);
            }
        }

        if (ff_table_exists($conn, 'recipe_nutrition') && is_array($nutrition)) {
            $del = $conn->prepare('DELETE FROM recipe_nutrition WHERE recipe_id = :id');
            $del->execute([':id' => $recipeId]);
            $has = false;
            foreach ($nutrition as $v) {
                if ($v !== null) {
                    $has = true;
                    break;
                }
            }
            if ($has) {
                $ins = $conn->prepare(
                    'INSERT INTO recipe_nutrition (recipe_id, calories, protein_g, fat_g, fiber_g, sugars_g, carbs_g)
                     VALUES (:rid, :cal, :p, :f, :fib, :sug, :carb)'
                );
                $ins->execute([
                    ':rid' => $recipeId,
                    ':cal' => $nutrition['calories'],
                    ':p' => $nutrition['protein_g'],
                    ':f' => $nutrition['fat_g'],
                    ':fib' => $nutrition['fiber_g'],
                    ':sug' => $nutrition['sugars_g'],
                    ':carb' => $nutrition['carbs_g'],
                ]);
            }
        }

        if (ff_table_exists($conn, 'recipe_tags') && ff_table_exists($conn, 'tags') && $tagsCommaSeparated !== null) {
            $del = $conn->prepare('DELETE FROM recipe_tags WHERE recipe_id = :id');
            $del->execute([':id' => $recipeId]);
            $parts = preg_split('/[,;]+/', $tagsCommaSeparated) ?: [];
            $tagIns = $conn->prepare('INSERT IGNORE INTO tags (name) VALUES (:name)');
            $selTag = $conn->prepare('SELECT tag_id FROM tags WHERE name = :name LIMIT 1');
            $link = $conn->prepare('INSERT IGNORE INTO recipe_tags (recipe_id, tag_id) VALUES (:rid, :tid)');
            foreach ($parts as $p) {
                $name = trim($p);
                if ($name === '') {
                    continue;
                }
                $tagIns->execute([':name' => $name]);
                $selTag->execute([':name' => $name]);
                $tid = (int) $selTag->fetchColumn();
                if ($tid > 0) {
                    $link->execute([':rid' => $recipeId, ':tid' => $tid]);
                }
            }
        }

        $conn->commit();
    } catch (PDOException $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
    }
}
