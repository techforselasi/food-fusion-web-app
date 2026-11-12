<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/recipe_data.php';

$recipeId = filter_var($_GET['recipe_id'] ?? '', FILTER_VALIDATE_INT);
$recipe = null;
$notFound = true;

if ($recipeId !== false && $recipeId > 0 && ff_table_exists($conn, 'recipes')) {
    try {
        $authorImgExpr = ff_recipe_column_exists($conn, 'users', 'profile_image_url')
            ? 'u.profile_image_url AS author_image'
            : 'NULL AS author_image';
        $stmt = $conn->prepare(
            "SELECT r.*, u.first_name AS author_first, u.last_name AS author_last, {$authorImgExpr}
             FROM recipes r
             LEFT JOIN users u ON r.user_id = u.user_id
             WHERE r.recipe_id = :id
             LIMIT 1"
        );
        $stmt->execute([':id' => $recipeId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row !== false) {
            $recipe = $row;
            $notFound = false;
        }
    } catch (PDOException $e) {
        $recipe = null;
        $notFound = true;
    }
}

if ($notFound) {
    http_response_code(404);
    $pageTitle = 'FoodFusion | Recipe not found';
    $bodyClass = 'page-recipe-detail page-recipe-detail--missing';
} else {
    $title = (string) ($recipe['title'] ?? 'Recipe');
    $pageTitle = 'FoodFusion | ' . $title;
    $bodyClass = 'page-recipe-detail';
}

$recipeCols = $notFound ? [] : ff_table_columns($conn, 'recipes');
$dietCol = $notFound ? null : ff_recipe_diet_column($recipeCols);

$ff_assistant_context = null;
if (!$notFound) {
    $ff_assistant_context = [
        'page' => 'recipe',
        'recipeTitle' => (string) ($recipe['title'] ?? 'Recipe'),
        'cuisine' => (string) ($recipe['cuisine_type'] ?? ''),
    ];
}

require __DIR__ . '/includes/header.php';

if ($notFound):
?>

<section class="page-hero page-hero--compact">
  <div class="wrap">
    <h1 class="page-title">Recipe not found</h1>
    <p class="page-lead">This recipe may have been removed or the link is incorrect.</p>
    <p class="recipe-detail__back-wrap">
      <a class="btn btn--ghost" href="recipes.php">Back to recipe collection</a>
      <a class="btn btn--primary" href="community.php">Community cookbook</a>
    </p>
  </div>
</section>

<?php
else:
    $cuisine = (string) ($recipe['cuisine_type'] ?? '');
    $diff = (string) ($recipe['difficulty'] ?? '');
    $dietVal = ($dietCol !== null && isset($recipe[$dietCol])) ? (string) $recipe[$dietCol] : '';
    $ingredients = (string) ($recipe['ingredients'] ?? '');
    $instructions = (string) ($recipe['instructions'] ?? '');
    $description = trim((string) ($recipe['description'] ?? ''));
    $servings = trim((string) ($recipe['servings'] ?? ''));
    $prepM = isset($recipe['prep_time_minutes']) && $recipe['prep_time_minutes'] !== null && $recipe['prep_time_minutes'] !== ''
        ? (int) $recipe['prep_time_minutes']
        : null;
    $cookM = isset($recipe['cook_time_minutes']) && $recipe['cook_time_minutes'] !== null && $recipe['cook_time_minutes'] !== ''
        ? (int) $recipe['cook_time_minutes']
        : null;
    $isFeatured = !empty($recipe['is_featured'] ?? false);
    $isCommunity = !empty($recipe['is_community']);
    $backHref = $isCommunity ? 'community.php' : 'recipes.php';
    $backLabel = $isCommunity ? 'Community cookbook' : 'Recipe collection';
    $authorFirst = trim((string) ($recipe['author_first'] ?? ''));
    $authorLast = trim((string) ($recipe['author_last'] ?? ''));
    $authorName = trim($authorFirst . ' ' . $authorLast);
    $authorImage = trim((string) ($recipe['author_image'] ?? ''));
    $created = $recipe['created_at'] ?? null;
    $recipeMedia = ff_recipe_primary_media($conn, (int) ($recipe['recipe_id'] ?? 0));

    $recipeImageWarning = '';
    if (!empty($_SESSION['ff_recipe_image_warning'])) {
        $recipeImageWarning = (string) $_SESSION['ff_recipe_image_warning'];
        unset($_SESSION['ff_recipe_image_warning']);
    }

    $titleParts = ff_recipe_split_display_title($title);
    $rid = (int) ($recipe['recipe_id'] ?? 0);
    $ingredientLines = ff_recipe_ingredient_lines_db($conn, $rid, $ingredients);
    $recipeSteps = ff_recipe_steps_db($conn, $rid, $instructions);
    $nutrition = ff_recipe_nutrition_row($conn, $rid);
    $dbTagNames = ff_recipe_tag_names($conn, $rid);
    $recipeReviews = ff_recipe_reviews_for_recipe($conn, $rid, 8);

    $relatedRecipes = [];
    if ($rid > 0 && ff_table_exists($conn, 'recipes')) {
        try {
            $stmt = $conn->prepare(
                'SELECT recipe_id, title, cuisine_type, difficulty, is_community, created_at
                 FROM recipes WHERE recipe_id != :id ORDER BY created_at DESC LIMIT 16'
            );
            $stmt->execute([':id' => $rid]);
            $candidates = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            if ($cuisine !== '') {
                usort($candidates, static function (array $a, array $b) use ($cuisine): int {
                    $ma = (($a['cuisine_type'] ?? '') === $cuisine) ? 0 : 1;
                    $mb = (($b['cuisine_type'] ?? '') === $cuisine) ? 0 : 1;

                    return $ma <=> $mb;
                });
            }
            $relatedRecipes = array_slice($candidates, 0, 3);
        } catch (PDOException $e) {
            $relatedRecipes = [];
        }
    }

    $relatedIds = array_column($relatedRecipes, 'recipe_id');
    $relatedMediaMap = ($relatedIds !== [] && ff_table_exists($conn, 'media'))
        ? ff_media_map_for_recipes($conn, array_map('intval', $relatedIds))
        : [];
    $relatedLikeMap = ff_recipe_like_counts($conn, $relatedIds);

    $displayTags = $dbTagNames;
    if ($displayTags === []) {
        $displayTags = array_values(array_filter([$cuisine, $dietVal, $diff]));
    }
    if ($isFeatured) {
        array_unshift($displayTags, 'Featured');
    }
    $displayTags = array_values(array_unique($displayTags));

    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = (string) ($_SERVER['HTTP_HOST'] ?? '');
    $scriptDir = dirname((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    $scriptDir = str_replace('\\', '/', $scriptDir);
    $basePath = ($scriptDir === '/' || $scriptDir === '.') ? '' : rtrim($scriptDir, '/');
    $shareUrl = $host !== '' ? $scheme . '://' . $host . $basePath . '/recipe.php?recipe_id=' . $rid : '';
?>

<article class="recipe-detail recipe-detail--magazine">
  <?php if ($recipeImageWarning !== ''): ?>
    <div class="recipe-detail__banner-wrap wrap">
      <div class="banner banner--warning" role="alert"><?php echo h($recipeImageWarning); ?></div>
    </div>
  <?php endif; ?>

  <header class="recipe-detail__hero">
    <div class="wrap recipe-detail__hero-grid">
      <div class="recipe-detail__hero-copy">
        <p class="recipe-detail__back-wrap">
          <a class="recipe-detail__back" href="<?php echo h($backHref); ?>"><?php echo h($backLabel); ?></a>
        </p>
        <?php if ($isCommunity): ?>
          <p class="recipe-detail__badge"><span class="recipe-detail__badge-pill">Community recipe</span></p>
        <?php endif; ?>
        <h1 class="recipe-detail__title recipe-detail__title--split">
          <?php if ($titleParts['accent'] !== ''): ?>
            <span class="recipe-detail__title-line"><?php echo h($titleParts['head']); ?></span>
            <span class="recipe-detail__title-accent"><?php echo h($titleParts['accent']); ?></span>
          <?php else: ?>
            <span class="recipe-detail__title-line"><?php echo h($titleParts['head']); ?></span>
          <?php endif; ?>
        </h1>

        <ul class="recipe-detail__meta-bar" aria-label="Recipe summary">
          <?php if ($cuisine !== ''): ?>
            <li class="recipe-detail__meta-chip">
              <span class="recipe-detail__meta-icon" aria-hidden="true">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2C8 6 4 9 4 14a8 8 0 1016 0c0-5-4-8-8-12z"/></svg>
              </span>
              <span class="recipe-detail__meta-chip-label">Cuisine</span>
              <span class="recipe-detail__meta-chip-value"><?php echo h($cuisine); ?></span>
            </li>
          <?php endif; ?>
          <?php if ($servings !== ''): ?>
            <li class="recipe-detail__meta-chip">
              <span class="recipe-detail__meta-icon" aria-hidden="true">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>
              </span>
              <span class="recipe-detail__meta-chip-label">Servings</span>
              <span class="recipe-detail__meta-chip-value"><?php echo h($servings); ?></span>
            </li>
          <?php endif; ?>
          <?php if ($prepM !== null): ?>
            <li class="recipe-detail__meta-chip">
              <span class="recipe-detail__meta-icon" aria-hidden="true">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
              </span>
              <span class="recipe-detail__meta-chip-label">Prep</span>
              <span class="recipe-detail__meta-chip-value"><?php echo h((string) $prepM); ?> min</span>
            </li>
          <?php endif; ?>
          <?php if ($cookM !== null): ?>
            <li class="recipe-detail__meta-chip">
              <span class="recipe-detail__meta-icon" aria-hidden="true">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8.5 14.5L7 13 3 17l1.5 1.5"/><path d="M12 2a10 10 0 00-10 9h20a10 10 0 00-10-9z"/></svg>
              </span>
              <span class="recipe-detail__meta-chip-label">Cook</span>
              <span class="recipe-detail__meta-chip-value"><?php echo h((string) $cookM); ?> min</span>
            </li>
          <?php endif; ?>
          <?php if ($diff !== ''): ?>
            <li class="recipe-detail__meta-chip">
              <span class="recipe-detail__meta-icon" aria-hidden="true">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/><circle cx="12" cy="12" r="3"/></svg>
              </span>
              <span class="recipe-detail__meta-chip-label">Difficulty</span>
              <span class="recipe-detail__meta-chip-value"><?php echo h($diff); ?></span>
            </li>
          <?php endif; ?>
          <?php if ($dietVal !== ''): ?>
            <li class="recipe-detail__meta-chip">
              <span class="recipe-detail__meta-icon" aria-hidden="true">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
              </span>
              <span class="recipe-detail__meta-chip-label">Diet</span>
              <span class="recipe-detail__meta-chip-value"><?php echo h($dietVal); ?></span>
            </li>
          <?php endif; ?>
          <?php if ($authorName !== ''): ?>
            <li class="recipe-detail__meta-chip">
              <span class="recipe-detail__meta-icon" aria-hidden="true">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
              </span>
              <span class="recipe-detail__meta-chip-label"><?php echo $isCommunity ? 'Shared by' : 'By'; ?></span>
              <span class="recipe-detail__meta-chip-value"><?php echo h($authorName); ?></span>
            </li>
          <?php endif; ?>
          <?php if ($created !== null && $created !== ''): ?>
            <li class="recipe-detail__meta-chip">
              <span class="recipe-detail__meta-icon" aria-hidden="true">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
              </span>
              <span class="recipe-detail__meta-chip-label">Added</span>
              <span class="recipe-detail__meta-chip-value"><time datetime="<?php echo h((string) $created); ?>"><?php echo h((string) $created); ?></time></span>
            </li>
          <?php endif; ?>
        </ul>

        <?php if ($displayTags !== []): ?>
          <ul class="recipe-detail__tags" aria-label="Recipe tags">
            <?php foreach ($displayTags as $tagLabel): ?>
              <li>
                <span class="recipe-detail__tag<?php echo $tagLabel === 'Featured' ? ' recipe-detail__tag--featured' : ''; ?>"><?php echo h($tagLabel); ?></span>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>

        <?php if ($description !== ''): ?>
          <p class="recipe-detail__description"><?php echo h($description); ?></p>
        <?php endif; ?>

        <div class="recipe-detail__actions no-print">
          <?php if ($shareUrl !== ''): ?>
            <button type="button" class="btn recipe-detail__action-btn recipe-detail__action-btn--dark" id="recipe-copy-link" data-url="<?php echo h($shareUrl); ?>">
              Copy link
            </button>
          <?php endif; ?>
          <button type="button" class="btn btn--ghost recipe-detail__action-btn" onclick="window.print()">Print recipe</button>
        </div>
      </div>

      <div class="recipe-detail__hero-media">
        <?php if ($recipeMedia !== null): ?>
          <figure class="recipe-detail__figure">
            <img
              class="recipe-detail__image"
              src="<?php echo h($recipeMedia['secure_url']); ?>"
              alt="<?php echo h($recipeMedia['alt_text'] ?? $title); ?>"
              width="<?php echo $recipeMedia['width'] !== null ? (int) $recipeMedia['width'] : 1200; ?>"
              height="<?php echo $recipeMedia['height'] !== null ? (int) $recipeMedia['height'] : 800; ?>"
              loading="eager"
              decoding="async"
            />
          </figure>
        <?php else: ?>
          <div class="recipe-detail__figure recipe-detail__figure--placeholder" aria-hidden="true">
            <span class="recipe-detail__placeholder-icon">
              <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.25"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
            </span>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </header>

  <div class="wrap recipe-detail__shell">
    <div class="recipe-detail__layout">
      <div class="recipe-detail__main">

        <section class="recipe-detail__panel recipe-detail__panel--ingredients" aria-labelledby="recipe-ingredients-heading">
          <h2 class="recipe-detail__panel-title" id="recipe-ingredients-heading">Ingredients</h2>
          <?php if ($ingredientLines !== []): ?>
            <ul class="recipe-detail__ing-list">
              <?php foreach ($ingredientLines as $line): ?>
                <li><?php echo h($line); ?></li>
              <?php endforeach; ?>
            </ul>
          <?php else: ?>
            <p class="recipe-detail__empty-note">No ingredients listed.</p>
          <?php endif; ?>
        </section>

        <section class="recipe-detail__panel recipe-detail__panel--instructions" aria-labelledby="recipe-instructions-heading">
          <h2 class="recipe-detail__panel-title" id="recipe-instructions-heading">Instructions</h2>
          <?php if ($recipeSteps !== []): ?>
            <div class="recipe-detail__steps">
              <?php $stepNum = 1; ?>
              <?php foreach ($recipeSteps as $step): ?>
                <?php
                  $stepBody = (string) ($step['body'] ?? '');
                  $mediaUrl = isset($step['media_url']) && $step['media_url'] !== '' ? (string) $step['media_url'] : '';
                  $mediaType = isset($step['media_type']) ? (string) $step['media_type'] : '';
                  $hasMedia = $mediaUrl !== '';
                  $isVideo = $mediaType === 'video';
                ?>
                <div class="recipe-detail__step<?php echo $hasMedia ? ' recipe-detail__step--with-media' : ''; ?>">
                  <span class="recipe-detail__step-num" aria-hidden="true"><?php echo str_pad((string) $stepNum, 2, '0', STR_PAD_LEFT); ?></span>
                  <div class="recipe-detail__step-main">
                    <div class="recipe-detail__step-body">
                      <p class="recipe-detail__step-text"><?php echo nl2br(h($stepBody)); ?></p>
                    </div>
                    <?php if ($hasMedia): ?>
                      <div class="recipe-detail__step-media">
                        <?php if ($isVideo): ?>
                          <video class="recipe-detail__step-video" controls preload="metadata" playsinline>
                            <source src="<?php echo h($mediaUrl); ?>" />
                          </video>
                        <?php else: ?>
                          <img class="recipe-detail__step-img" src="<?php echo h($mediaUrl); ?>" alt="" loading="lazy" decoding="async" width="640" height="400" />
                        <?php endif; ?>
                      </div>
                    <?php endif; ?>
                  </div>
                </div>
                <?php ++$stepNum; ?>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <p class="recipe-detail__empty-note">No instructions listed.</p>
          <?php endif; ?>
        </section>

        <?php if ($recipeReviews !== []): ?>
          <section class="recipe-detail__reviews" aria-labelledby="recipe-reviews-heading">
            <h2 class="recipe-detail__panel-title" id="recipe-reviews-heading">Cooking notes</h2>
            <ul class="recipe-detail__review-list">
              <?php foreach ($recipeReviews as $rev): ?>
                <?php
                  $rf = trim((string) ($rev['first_name'] ?? ''));
                  $rl = trim((string) ($rev['last_name'] ?? ''));
                  $rname = trim($rf . ' ' . $rl);
                  $rcomment = trim((string) ($rev['comment'] ?? ''));
                  $rrating = isset($rev['rating']) && $rev['rating'] !== null && $rev['rating'] !== '' ? (int) $rev['rating'] : null;
                  $rdate = (string) ($rev['interacted_at'] ?? '');
                  $rimg = trim((string) ($rev['profile_image_url'] ?? ''));
                ?>
                <li class="recipe-detail__review-card">
                  <div class="recipe-detail__review-top">
                    <?php if ($rimg !== ''): ?>
                      <img class="recipe-detail__review-avatar" src="<?php echo h($rimg); ?>" alt="" width="48" height="48" loading="lazy" decoding="async" />
                    <?php else: ?>
                      <span class="recipe-detail__review-avatar recipe-detail__review-avatar--initials" aria-hidden="true"><?php echo h(ff_recipe_author_initials($rf, $rl)); ?></span>
                    <?php endif; ?>
                    <div class="recipe-detail__review-meta">
                      <p class="recipe-detail__review-name"><?php echo h($rname !== '' ? $rname : 'Member'); ?></p>
                      <?php if ($rdate !== ''): ?>
                        <p class="recipe-detail__review-date"><time datetime="<?php echo h($rdate); ?>"><?php echo h($rdate); ?></time></p>
                      <?php endif; ?>
                    </div>
                    <?php if ($rrating !== null && $rrating >= 1 && $rrating <= 5): ?>
                      <p class="recipe-detail__review-stars" aria-label="<?php echo h('Rating ' . $rrating . ' out of 5'); ?>"><?php echo str_repeat('★', $rrating) . str_repeat('☆', 5 - $rrating); ?></p>
                    <?php endif; ?>
                  </div>
                  <?php if ($rcomment !== ''): ?>
                    <blockquote class="recipe-detail__review-quote"><?php echo h($rcomment); ?></blockquote>
                  <?php endif; ?>
                </li>
              <?php endforeach; ?>
            </ul>
          </section>
        <?php endif; ?>

        <?php if ($relatedRecipes !== []): ?>
          <section class="recipe-detail__related" aria-labelledby="recipe-related-heading">
            <div class="recipe-detail__related-head">
              <h2 class="recipe-detail__panel-title" id="recipe-related-heading">More recipes</h2>
              <a class="recipe-detail__related-all" href="recipes.php">See all</a>
            </div>
            <ul class="recipe-detail__related-grid">
              <?php foreach ($relatedRecipes as $rr): ?>
                <?php
                  $rrid = (int) ($rr['recipe_id'] ?? 0);
                  $rTitle = (string) ($rr['title'] ?? 'Recipe');
                  $rUrl = 'recipe.php?recipe_id=' . $rrid;
                  $rThumb = $relatedMediaMap[$rrid] ?? null;
                  $rLikes = $relatedLikeMap[$rrid] ?? 0;
                ?>
                <li class="recipe-detail__related-card">
                  <?php if ($rThumb !== null): ?>
                    <a class="recipe-detail__related-thumb-link" href="<?php echo h($rUrl); ?>" tabindex="-1" aria-hidden="true">
                      <img class="recipe-detail__related-thumb" src="<?php echo h($rThumb['secure_url']); ?>" alt="" width="320" height="200" loading="lazy" decoding="async" />
                    </a>
                  <?php else: ?>
                    <div class="recipe-detail__related-thumb recipe-detail__related-thumb--empty" aria-hidden="true"></div>
                  <?php endif; ?>
                  <h3 class="recipe-detail__related-title">
                    <a href="<?php echo h($rUrl); ?>"><?php echo h($rTitle); ?></a>
                  </h3>
                  <?php if ($rLikes > 0): ?>
                    <p class="recipe-detail__related-likes" aria-label="<?php echo h((string) $rLikes . ' likes'); ?>">
                      <span class="recipe-detail__related-likes-icon" aria-hidden="true">♥</span> <?php echo h((string) $rLikes); ?>
                    </p>
                  <?php endif; ?>
                  <a class="btn btn--ghost recipe-detail__related-cta" href="<?php echo h($rUrl); ?>">View recipe</a>
                </li>
              <?php endforeach; ?>
            </ul>
          </section>
        <?php endif; ?>

        <p class="recipe-detail__footer-nav no-print">
          <a class="btn btn--primary" href="<?php echo h($backHref); ?>">Back to <?php echo h(strtolower($backLabel)); ?></a>
        </p>
      </div>

      <aside class="recipe-detail__aside no-print" aria-label="Recipe sidebar">
        <?php if ($authorName !== ''): ?>
          <div class="recipe-detail__author-card">
            <?php if ($authorImage !== ''): ?>
              <img class="recipe-detail__author-photo" src="<?php echo h($authorImage); ?>" alt="" width="72" height="72" loading="lazy" decoding="async" />
            <?php else: ?>
              <div class="recipe-detail__author-avatar" aria-hidden="true"><?php echo h(ff_recipe_author_initials($authorFirst, $authorLast)); ?></div>
            <?php endif; ?>
            <div class="recipe-detail__author-body">
              <p class="recipe-detail__author-label"><?php echo $isCommunity ? 'Shared by' : 'Recipe author'; ?></p>
              <p class="recipe-detail__author-name"><?php echo h($authorName); ?></p>
              <?php if ($created !== null && $created !== ''): ?>
                <p class="recipe-detail__author-meta"><time datetime="<?php echo h((string) $created); ?>"><?php echo h((string) $created); ?></time></p>
              <?php endif; ?>
            </div>
          </div>
        <?php endif; ?>

        <?php if ($nutrition !== null): ?>
          <div class="recipe-detail__nutrition-card" aria-labelledby="recipe-nutrition-heading">
            <h3 class="recipe-detail__nutrition-title" id="recipe-nutrition-heading">Nutrition <span class="recipe-detail__nutrition-note">(per serving)</span></h3>
            <dl class="recipe-detail__nutrition-dl">
              <?php if ($nutrition['calories'] !== null): ?>
                <div class="recipe-detail__nutrition-row"><dt>Calories</dt><dd><?php echo h((string) $nutrition['calories']); ?></dd></div>
              <?php endif; ?>
              <?php if ($nutrition['protein_g'] !== null): ?>
                <div class="recipe-detail__nutrition-row"><dt>Protein</dt><dd><?php echo h((string) $nutrition['protein_g']); ?>g</dd></div>
              <?php endif; ?>
              <?php if ($nutrition['fat_g'] !== null): ?>
                <div class="recipe-detail__nutrition-row"><dt>Fat</dt><dd><?php echo h((string) $nutrition['fat_g']); ?>g</dd></div>
              <?php endif; ?>
              <?php if ($nutrition['fiber_g'] !== null): ?>
                <div class="recipe-detail__nutrition-row"><dt>Fiber</dt><dd><?php echo h((string) $nutrition['fiber_g']); ?>g</dd></div>
              <?php endif; ?>
              <?php if ($nutrition['sugars_g'] !== null): ?>
                <div class="recipe-detail__nutrition-row"><dt>Sugars</dt><dd><?php echo h((string) $nutrition['sugars_g']); ?>g</dd></div>
              <?php endif; ?>
              <?php if ($nutrition['carbs_g'] !== null): ?>
                <div class="recipe-detail__nutrition-row"><dt>Carbohydrates</dt><dd><?php echo h((string) $nutrition['carbs_g']); ?>g</dd></div>
              <?php endif; ?>
            </dl>
          </div>
        <?php endif; ?>

        <div class="recipe-detail__promo-card">
          <p class="recipe-detail__promo-title">Hungry for more?</p>
          <p class="recipe-detail__promo-text">Browse the full collection and filter by cuisine or diet.</p>
          <a class="btn recipe-detail__promo-btn" href="recipes.php">Browse recipes</a>
        </div>
      </aside>
    </div>
  </div>
</article>

<?php if ($shareUrl !== ''): ?>
  <script>
    (function () {
      var btn = document.getElementById("recipe-copy-link");
      if (!btn) return;
      btn.addEventListener("click", function () {
        var url = btn.getAttribute("data-url") || "";
        if (!url) return;
        if (navigator.clipboard && navigator.clipboard.writeText) {
          navigator.clipboard.writeText(url).then(function () {
            var t = btn.textContent;
            btn.textContent = "Copied!";
            setTimeout(function () { btn.textContent = t; }, 2000);
          }).catch(function () {
            window.prompt("Copy this link:", url);
          });
        } else {
          window.prompt("Copy this link:", url);
        }
      });
    })();
  </script>
<?php endif; ?>

<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
