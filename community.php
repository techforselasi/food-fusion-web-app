<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/recipe_data.php';

$pageTitle = 'FoodFusion | Community cookbook';
$bodyClass = 'page-community';

$user = ff_user();
$recipeCols = ff_table_columns($conn, 'recipes');
$dietCol = ff_recipe_diet_column($recipeCols);

$communityMessage = isset($_GET['posted']) ? 'Thank you! Your recipe has been added to the community cookbook.' : '';
$communityError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['community_recipe'])) {
    if (!$user) {
        $communityError = 'Please log in to submit a community recipe.';
    } else {
        $title = trim((string) ($_POST['title'] ?? ''));
        $cuisine = trim((string) ($_POST['cuisine_type'] ?? ''));
        $difficulty = (string) ($_POST['difficulty'] ?? '');
        $dietVal = trim((string) ($_POST['diet_value'] ?? ''));
        $ingredients = trim((string) ($_POST['ingredients'] ?? ''));
        $instructions = trim((string) ($_POST['instructions'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));
        $servings = trim((string) ($_POST['servings'] ?? ''));
        $tagsRaw = trim((string) ($_POST['tags'] ?? ''));
        $stepMediaRaw = (string) ($_POST['step_media_urls'] ?? '');

        $prepRaw = trim((string) ($_POST['prep_time_minutes'] ?? ''));
        $cookRaw = trim((string) ($_POST['cook_time_minutes'] ?? ''));
        $prepM = $prepRaw === '' ? null : filter_var($prepRaw, FILTER_VALIDATE_INT);
        $cookM = $cookRaw === '' ? null : filter_var($cookRaw, FILTER_VALIDATE_INT);
        if ($prepM !== null && $prepM !== false && $prepM < 0) {
            $prepM = null;
        }
        if ($cookM !== null && $cookM !== false && $cookM < 0) {
            $cookM = null;
        }
        if ($prepM === false) {
            $prepM = null;
        }
        if ($cookM === false) {
            $cookM = null;
        }

        $parseOptInt = static function (string $v): ?int {
            $v = trim($v);
            if ($v === '') {
                return null;
            }
            $i = filter_var($v, FILTER_VALIDATE_INT);

            return ($i !== false && $i >= 0) ? $i : null;
        };
        $parseOptFloat = static function (string $v): ?float {
            $v = trim($v);
            if ($v === '') {
                return null;
            }
            $f = filter_var($v, FILTER_VALIDATE_FLOAT);

            return $f !== false ? (float) $f : null;
        };

        $cal = $parseOptInt((string) ($_POST['nutrition_calories'] ?? ''));
        $protein = $parseOptFloat((string) ($_POST['nutrition_protein'] ?? ''));
        $fat = $parseOptFloat((string) ($_POST['nutrition_fat'] ?? ''));
        $fiber = $parseOptFloat((string) ($_POST['nutrition_fiber'] ?? ''));
        $sugars = $parseOptFloat((string) ($_POST['nutrition_sugars'] ?? ''));
        $carbs = $parseOptFloat((string) ($_POST['nutrition_carbs'] ?? ''));

        $allowedDiff = ['Easy', 'Intermediate', 'Advanced'];

        if ($title === '' || $ingredients === '' || $instructions === '') {
            $communityError = 'Title, ingredients, and instructions are required.';
        } elseif ($difficulty !== '' && !in_array($difficulty, $allowedDiff, true)) {
            $communityError = 'Please choose a valid difficulty level.';
        } else {
            if ($difficulty === '') {
                $difficulty = 'Easy';
            }

            $sessionUserId = (int) ($user['user_id'] ?? 0);
            $userRowExists = false;
            if ($sessionUserId > 0 && ff_table_exists($conn, 'users')) {
                try {
                    $cu = $conn->prepare('SELECT 1 FROM users WHERE user_id = :uid LIMIT 1');
                    $cu->execute([':uid' => $sessionUserId]);
                    $userRowExists = (bool) $cu->fetchColumn();
                } catch (PDOException $e) {
                    $userRowExists = false;
                }
            }

            if (!$userRowExists) {
                $communityError = 'Your browser is still signed in with an old account id that is not in the database anymore. This happens after recreating tables. Use Log out, then sign in again, and publish your recipe.';
            } else {
            try {
                $fields = ['title', 'cuisine_type', 'difficulty', 'ingredients', 'instructions', 'user_id', 'is_community'];
                $placeholders = [':title', ':cuisine_type', ':difficulty', ':ingredients', ':instructions', ':user_id', '1'];
                $exec = [
                    ':title' => $title,
                    ':cuisine_type' => $cuisine !== '' ? $cuisine : null,
                    ':difficulty' => $difficulty,
                    ':ingredients' => $ingredients,
                    ':instructions' => $instructions,
                    ':user_id' => $sessionUserId,
                ];

                $optionalCols = [
                    'description' => $description !== '' ? $description : null,
                    'servings' => $servings !== '' ? $servings : null,
                    'prep_time_minutes' => $prepM,
                    'cook_time_minutes' => $cookM,
                ];
                foreach ($optionalCols as $col => $val) {
                    if (ff_recipe_column_exists($conn, 'recipes', $col)) {
                        $fields[] = '`' . str_replace('`', '``', $col) . '`';
                        $placeholders[] = ':' . $col;
                        $exec[':' . $col] = $val;
                    }
                }

                if ($dietCol !== null && $dietVal !== '') {
                    $fields[] = '`' . str_replace('`', '``', $dietCol) . '`';
                    $placeholders[] = ':diet_val';
                    $exec[':diet_val'] = $dietVal;
                }

                $sql = 'INSERT INTO recipes (' . implode(', ', $fields) . ') VALUES (' . implode(', ', $placeholders) . ')';
                $stmt = $conn->prepare($sql);
                $stmt->execute($exec);
                $newId = (int) $conn->lastInsertId();

                if ($newId > 0) {
                    $nutritionPayload = [
                        'calories' => $cal,
                        'protein_g' => $protein,
                        'fat_g' => $fat,
                        'fiber_g' => $fiber,
                        'sugars_g' => $sugars,
                        'carbs_g' => $carbs,
                    ];
                    ff_recipe_save_structured(
                        $conn,
                        $newId,
                        $ingredients,
                        $instructions,
                        $tagsRaw !== '' ? $tagsRaw : null,
                        $nutritionPayload,
                        $stepMediaRaw
                    );
                }

                if ($newId > 0 && !empty($_FILES['recipe_image'])) {
                    $img = $_FILES['recipe_image'];
                    $triedImage = (($img['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK)
                        && ((int) ($img['size'] ?? 0) > 0);
                    $mediaId = ff_cloudinary_upload_recipe_image($conn, $newId, $sessionUserId, $img);
                    if ($triedImage && $mediaId === null) {
                        $_SESSION['ff_recipe_image_warning'] = 'Your recipe was saved. We could not add your photo—try another image (JPEG, PNG, WebP, or GIF).';
                    }
                }
                if ($newId > 0) {
                    header('Location: recipe.php?recipe_id=' . $newId);
                } else {
                    header('Location: community.php?posted=1');
                }
                exit;
            } catch (PDOException $e) {
                error_log('FoodFusion community recipe save: ' . $e->getMessage());
                $sqlState = (string) $e->getCode();
                $msg = $e->getMessage();
                if ($sqlState === '23000' || str_contains($msg, '1452') || stripos($msg, 'foreign key') !== false) {
                    $communityError = 'The database rejected this recipe because your account id does not match a user row (often after recreating the database). Log out, sign in again, then try publishing.';
                } else {
                    $communityError = 'We could not save your recipe. Please try again shortly.';
                }
            }
            }
        }
    }
}

$communityRecipes = [];
if (ff_table_exists($conn, 'recipes')) {
    $rc = ff_table_columns($conn, 'recipes');
    $orderCol = ff_pick_column($rc, ['created_at', 'recipe_id']);
    try {
        if ($orderCol !== null) {
            $stmt = $conn->query(
                'SELECT * FROM recipes WHERE is_community = 1 ORDER BY `' . str_replace('`', '``', $orderCol) . '` DESC'
            );
        } else {
            $stmt = $conn->query('SELECT * FROM recipes WHERE is_community = 1');
        }
        $communityRecipes = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    } catch (PDOException $e) {
        $communityRecipes = [];
    }
}

$communityMediaMap = [];
if ($communityRecipes !== [] && ff_table_exists($conn, 'media')) {
    $communityMediaMap = ff_media_map_for_recipes($conn, array_column($communityRecipes, 'recipe_id'));
}

require __DIR__ . '/includes/header.php';
?>

<section class="page-hero page-hero--compact">
  <div class="wrap">
    <h1 class="page-title">Community cookbook</h1>
    <p class="page-lead">Recipes shared by FoodFusion members—submit yours when you are signed in.</p>
  </div>
</section>

<section class="section">
  <div class="wrap">
    <?php if ($communityMessage !== ''): ?>
      <div class="banner banner--success" role="status"><?php echo h($communityMessage); ?></div>
    <?php endif; ?>
    <?php if ($communityError !== ''): ?>
      <div class="form-error" role="alert"><?php echo h($communityError); ?></div>
    <?php endif; ?>

    <?php if ($user): ?>
      <section class="panel" aria-labelledby="submit-community-heading">
        <h2 class="panel__title" id="submit-community-heading">Share a recipe</h2>
        <form method="post" action="community.php" class="stack-form" enctype="multipart/form-data">
          <input type="hidden" name="community_recipe" value="1" />
          <div class="form-field full">
            <label for="com_title">Recipe title</label>
            <input class="input-box" type="text" id="com_title" name="title" required />
          </div>
          <div class="form-grid">
            <div class="form-field">
              <label for="com_cuisine">Cuisine</label>
              <input class="input-box" type="text" id="com_cuisine" name="cuisine_type" list="cuisine_suggestions" placeholder="Pick a suggestion or type your own" autocomplete="off" />
              <datalist id="cuisine_suggestions">
                <option value="Asian"></option>
                <option value="European"></option>
                <option value="African"></option>
                <option value="Americas"></option>
                <option value="Other"></option>
              </datalist>
            </div>
            <div class="form-field">
              <label for="com_difficulty">Difficulty</label>
              <select class="input-box input-box--select" id="com_difficulty" name="difficulty">
                <option value="Easy">Easy</option>
                <option value="Intermediate">Intermediate</option>
                <option value="Advanced">Advanced</option>
              </select>
            </div>
            <?php if ($dietCol !== null): ?>
              <div class="form-field full">
                <label for="com_diet">Diet</label>
                <input class="input-box" type="text" id="com_diet" name="diet_value" placeholder="e.g. Vegetarian" />
              </div>
            <?php endif; ?>
            <div class="form-field">
              <label for="com_servings">Servings <span class="form-field__optional">(optional)</span></label>
              <input class="input-box" type="text" id="com_servings" name="servings" placeholder="e.g. 4 or 4 people" />
            </div>
            <div class="form-field">
              <label for="com_prep">Prep time (minutes) <span class="form-field__optional">(optional)</span></label>
              <input class="input-box" type="number" id="com_prep" name="prep_time_minutes" min="0" step="1" placeholder="" />
            </div>
            <div class="form-field">
              <label for="com_cook">Cook time (minutes) <span class="form-field__optional">(optional)</span></label>
              <input class="input-box" type="number" id="com_cook" name="cook_time_minutes" min="0" step="1" placeholder="" />
            </div>
          </div>
          <div class="form-field full">
            <label for="com_description">Short description <span class="form-field__optional">(optional)</span></label>
            <textarea class="input-box input-box--textarea" id="com_description" name="description" rows="3" placeholder="What makes this dish special?"></textarea>
          </div>
          <div class="form-field full">
            <label for="com_tags">Tags <span class="form-field__optional">(optional)</span></label>
            <input class="input-box" type="text" id="com_tags" name="tags" placeholder="Comma-separated, e.g. Spicy, Weeknight, Seafood" />
          </div>
          <div class="form-field full">
            <label for="com_ingredients">Ingredients</label>
            <textarea class="input-box input-box--textarea" id="com_ingredients" name="ingredients" rows="5" required placeholder="One ingredient per line, e.g. 1 lb ground beef"></textarea>
          </div>
          <div class="form-field full">
            <label for="com_instructions">Instructions</label>
            <textarea class="input-box input-box--textarea" id="com_instructions" name="instructions" rows="6" required placeholder="Numbered steps (1. …) or separate paragraphs per step"></textarea>
          </div>
          <div class="form-field full">
            <label for="com_step_media">Step photos or videos (URLs) <span class="form-field__optional">(optional)</span></label>
            <textarea class="input-box input-box--textarea" id="com_step_media" name="step_media_urls" rows="3" placeholder="One URL per line, in the same order as your steps (image or .mp4/.webm link)"></textarea>
          </div>
          <fieldset class="community-nutrition-fieldset">
            <legend class="community-nutrition-legend">Nutrition per serving <span class="form-field__optional">(optional)</span></legend>
            <div class="form-grid community-nutrition-grid">
              <div class="form-field">
                <label for="com_n_cal">Calories</label>
                <input class="input-box" type="number" id="com_n_cal" name="nutrition_calories" min="0" step="1" placeholder="" />
              </div>
              <div class="form-field">
                <label for="com_n_protein">Protein (g)</label>
                <input class="input-box" type="number" id="com_n_protein" name="nutrition_protein" min="0" step="0.1" placeholder="" />
              </div>
              <div class="form-field">
                <label for="com_n_fat">Fat (g)</label>
                <input class="input-box" type="number" id="com_n_fat" name="nutrition_fat" min="0" step="0.1" placeholder="" />
              </div>
              <div class="form-field">
                <label for="com_n_fiber">Fiber (g)</label>
                <input class="input-box" type="number" id="com_n_fiber" name="nutrition_fiber" min="0" step="0.1" placeholder="" />
              </div>
              <div class="form-field">
                <label for="com_n_sugars">Sugars (g)</label>
                <input class="input-box" type="number" id="com_n_sugars" name="nutrition_sugars" min="0" step="0.1" placeholder="" />
              </div>
              <div class="form-field">
                <label for="com_n_carbs">Carbohydrates (g)</label>
                <input class="input-box" type="number" id="com_n_carbs" name="nutrition_carbs" min="0" step="0.1" placeholder="" />
              </div>
            </div>
          </fieldset>
          <div class="form-field full">
            <label for="com_recipe_image">Recipe photo <span class="form-field__optional">(optional)</span></label>
            <input
              class="input-box input-box--file"
              type="file"
              id="com_recipe_image"
              name="recipe_image"
              accept="image/jpeg,image/png,image/webp,image/gif"
            />
            <p class="form-field__hint">JPEG, PNG, WebP, or GIF. Shown on your recipe page when added.</p>
            <div class="recipe-image-preview" id="com_image_preview" hidden>
              <img class="recipe-image-preview__img" src="" alt="" width="320" height="200" decoding="async" />
            </div>
          </div>
          <button class="btn btn--primary" type="submit">Publish to community</button>
        </form>
      </section>
    <?php else: ?>
      <p class="empty-state">
        <a href="login.php">Log in</a> or <a href="register.php">create an account</a> to submit your own community recipe.
      </p>
    <?php endif; ?>
  </div>
</section>

<section class="section section--tint">
  <div class="wrap">
    <h2 class="section__title">Community recipes</h2>
    <?php if ($communityRecipes === []): ?>
      <p class="empty-state">No community recipes yet. Be the first to share one.</p>
    <?php else: ?>
      <ul class="recipe-grid">
        <?php foreach ($communityRecipes as $r): ?>
          <?php
            $rid = (int) ($r['recipe_id'] ?? 0);
            $title = $r['title'] ?? 'Recipe';
            $cuisine = $r['cuisine_type'] ?? '';
            $diff = $r['difficulty'] ?? '';
            $dietVal = ($dietCol !== null && isset($r[$dietCol])) ? (string) $r[$dietCol] : '';
            $detailUrl = 'recipe.php?recipe_id=' . $rid;
            $thumb = $communityMediaMap[$rid] ?? null;
            ?>
          <li class="recipe-card recipe-card--community">
            <?php if ($thumb !== null): ?>
              <a class="recipe-card__thumb-link" href="<?php echo h($detailUrl); ?>" tabindex="-1" aria-hidden="true">
                <img
                  class="recipe-card__thumb"
                  src="<?php echo h($thumb['secure_url']); ?>"
                  alt="<?php echo h((string) $title); ?>"
                  width="400"
                  height="240"
                  loading="lazy"
                  decoding="async"
                />
              </a>
            <?php endif; ?>
            <h2 class="recipe-card__title">
              <?php if ($rid > 0): ?>
                <a href="<?php echo h($detailUrl); ?>"><?php echo h((string) $title); ?></a>
              <?php else: ?>
                <?php echo h((string) $title); ?>
              <?php endif; ?>
            </h2>
            <ul class="recipe-card__meta">
              <?php if ($cuisine !== ''): ?>
                <li><span class="recipe-card__label">Cuisine</span> <?php echo h((string) $cuisine); ?></li>
              <?php endif; ?>
              <?php if ($dietVal !== ''): ?>
                <li><span class="recipe-card__label">Diet</span> <?php echo h($dietVal); ?></li>
              <?php endif; ?>
              <?php if ($diff !== ''): ?>
                <li><span class="recipe-card__label">Level</span> <?php echo h((string) $diff); ?></li>
              <?php endif; ?>
            </ul>
            <?php if ($rid > 0): ?>
              <p class="recipe-card__cta">
                <a class="recipe-card__link" href="<?php echo h($detailUrl); ?>">View full recipe</a>
              </p>
            <?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
