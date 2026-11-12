<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$pageTitle = 'FoodFusion | Recipes';
$bodyClass = 'page-recipes';

$recipeCols = ff_table_columns($conn, 'recipes');
$dietCol = ff_recipe_diet_column($recipeCols);

$fCuisine = trim((string) ($_GET['cuisine'] ?? ''));
$fDiet = trim((string) ($_GET['diet'] ?? ''));
$fDifficulty = trim((string) ($_GET['difficulty'] ?? ''));
$fSearch = trim((string) ($_GET['q'] ?? ''));
if (strcasecmp($fCuisine, 'fusion') === 0 || strcasecmp($fCuisine, 'other') === 0) {
    $fCuisine = '__other__';
}
if (function_exists('mb_strlen') && mb_strlen($fSearch) > 200) {
    $fSearch = mb_substr($fSearch, 0, 200);
} elseif (strlen($fSearch) > 200) {
    $fSearch = substr($fSearch, 0, 200);
}

$allowedDiff = ['Easy', 'Intermediate', 'Advanced'];
$standardCuisines = ['Asian', 'European', 'African', 'Americas'];

$distinctCuisines = [];
$customCuisines = [];
$distinctDiets = [];
if (ff_table_exists($conn, 'recipes')) {
    try {
        $stmt = $conn->query('SELECT DISTINCT cuisine_type FROM recipes WHERE cuisine_type IS NOT NULL AND cuisine_type <> "" ORDER BY cuisine_type');
        $distinctCuisines = $stmt ? $stmt->fetchAll(PDO::FETCH_COLUMN) : [];
    } catch (PDOException $e) {
        $distinctCuisines = [];
    }

    foreach ($distinctCuisines as $cRaw) {
        $c = trim((string) $cRaw);
        if ($c === '') {
            continue;
        }
        $isStandard = false;
        foreach ($standardCuisines as $std) {
            if (strcasecmp($c, $std) === 0) {
                $isStandard = true;
                break;
            }
        }
        if ($isStandard || strcasecmp($c, 'fusion') === 0 || strcasecmp($c, 'other') === 0) {
            continue;
        }
        $customCuisines[] = $c;
    }
    $customCuisines = array_values(array_unique($customCuisines));
    natcasesort($customCuisines);
    $customCuisines = array_values($customCuisines);

    if ($dietCol !== null) {
        try {
            $stmt = $conn->query(
                'SELECT DISTINCT `' . str_replace('`', '``', $dietCol) . '` FROM recipes WHERE `' . str_replace('`', '``', $dietCol) . '` IS NOT NULL AND `' . str_replace('`', '``', $dietCol) . "` <> '' ORDER BY 1"
            );
            $distinctDiets = $stmt ? $stmt->fetchAll(PDO::FETCH_COLUMN) : [];
        } catch (PDOException $e) {
            $distinctDiets = [];
        }
    }
}

$where = ['1=1'];
$params = [];

if ($fCuisine !== '') {
    if ($fCuisine === '__other__') {
        $where[] = "cuisine_type IS NOT NULL AND TRIM(cuisine_type) <> ''";
        $where[] = "LOWER(TRIM(cuisine_type)) NOT IN ('asian','european','african','americas','fusion','other')";
    } else {
        $where[] = 'cuisine_type = :cuisine';
        $params[':cuisine'] = $fCuisine;
    }
}

if ($fDiet !== '' && $dietCol !== null) {
    $where[] = '`' . str_replace('`', '``', $dietCol) . '` = :diet';
    $params[':diet'] = $fDiet;
}

if ($fDifficulty !== '' && in_array($fDifficulty, $allowedDiff, true)) {
    $where[] = 'difficulty = :difficulty';
    $params[':difficulty'] = $fDifficulty;
}

$searchTerm = str_replace(['%', '_'], '', $fSearch);
$searchTerm = trim($searchTerm);
if ($searchTerm !== '') {
    $like = '%' . $searchTerm . '%';
    $searchCols = ['title', 'cuisine_type', 'ingredients', 'instructions'];
    if (in_array('description', $recipeCols, true)) {
        $searchCols[] = 'description';
    }
    $searchParts = [];
    foreach ($searchCols as $i => $col) {
        $ph = ':sq' . $i;
        $searchParts[] = '`' . str_replace('`', '``', $col) . '` LIKE ' . $ph;
        $params[$ph] = $like;
    }
    $where[] = '(' . implode(' OR ', $searchParts) . ')';
}

$sql = 'SELECT * FROM recipes WHERE ' . implode(' AND ', $where) . ' ORDER BY title ASC';
$recipes = [];

if (ff_table_exists($conn, 'recipes')) {
    try {
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $recipes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $recipes = [];
    }
}

$recipeMediaMap = [];
if ($recipes !== [] && ff_table_exists($conn, 'media')) {
    $recipeMediaMap = ff_media_map_for_recipes($conn, array_column($recipes, 'recipe_id'));
}

require __DIR__ . '/includes/header.php';
?>

<section class="page-hero page-hero--compact">
  <div class="wrap">
    <h1 class="page-title">Recipe collection</h1>
    <p class="page-lead">Search by keyword, then narrow down with cuisine, diet, and difficulty.</p>
  </div>
</section>

<section class="section">
  <div class="wrap">
    <form class="filters" method="get" action="recipes.php" aria-label="Search and filter recipes">
      <div class="filters__search-row">
        <div class="form-field filters__search">
          <label for="filter-q">Search</label>
          <input
            class="input-box"
            type="search"
            id="filter-q"
            name="q"
            value="<?php echo h($fSearch); ?>"
            placeholder="Title, ingredients, description, cuisine…"
            autocomplete="off"
            maxlength="200"
          />
        </div>
      </div>
      <div class="filters__row">
        <div class="form-field">
          <label for="filter-cuisine">Cuisine</label>
          <select class="input-box input-box--select filters__native-select" id="filter-cuisine" name="cuisine">
            <option value="">All cuisines</option>
            <?php foreach ($standardCuisines as $c): ?>
              <option value="<?php echo h($c); ?>"<?php echo strcasecmp($fCuisine, $c) === 0 ? ' selected' : ''; ?>><?php echo h($c); ?></option>
            <?php endforeach; ?>
            <option value="__other__"<?php echo $fCuisine === '__other__' ? ' selected' : ''; ?>>Other</option>
            <?php if ($customCuisines !== []): ?>
              <optgroup label="Other — custom cuisines">
                <?php foreach ($customCuisines as $c): ?>
                  <option value="<?php echo h($c); ?>"<?php echo $fCuisine === $c ? ' selected' : ''; ?>><?php echo h($c); ?></option>
                <?php endforeach; ?>
              </optgroup>
            <?php endif; ?>
          </select>
          <div class="ff-select" id="filter-cuisine-custom" data-target-select="filter-cuisine">
            <button
              type="button"
              class="ff-select__trigger"
              id="filter-cuisine-trigger"
              aria-haspopup="listbox"
              aria-expanded="false"
            >
              <span class="ff-select__label">All cuisines</span>
              <span class="ff-select__chevron" aria-hidden="true">▾</span>
            </button>
            <ul class="ff-select__menu" role="listbox" aria-labelledby="filter-cuisine-trigger">
              <li class="ff-select__option" role="option" data-value="">All cuisines</li>
              <?php foreach ($standardCuisines as $c): ?>
                <li class="ff-select__option" role="option" data-value="<?php echo h($c); ?>"><?php echo h($c); ?></li>
              <?php endforeach; ?>
              <li class="ff-select__option" role="option" data-value="__other__">Other</li>
              <?php if ($customCuisines !== []): ?>
                <li class="ff-select__group" aria-hidden="true">Other — custom cuisines</li>
                <?php foreach ($customCuisines as $c): ?>
                  <li class="ff-select__option ff-select__option--custom" role="option" data-value="<?php echo h($c); ?>"><?php echo h($c); ?></li>
                <?php endforeach; ?>
              <?php endif; ?>
            </ul>
          </div>
        </div>
        <?php if ($dietCol !== null): ?>
          <div class="form-field">
            <label for="filter-diet">Diet</label>
            <select class="input-box input-box--select" id="filter-diet" name="diet">
              <option value="">All diets</option>
              <?php foreach ($distinctDiets as $d): ?>
                <option value="<?php echo h((string) $d); ?>"<?php echo $fDiet === (string) $d ? ' selected' : ''; ?>><?php echo h((string) $d); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        <?php endif; ?>
        <div class="form-field">
          <label for="filter-difficulty">Difficulty</label>
          <select class="input-box input-box--select" id="filter-difficulty" name="difficulty">
            <option value="">Any level</option>
            <?php foreach ($allowedDiff as $d): ?>
              <option value="<?php echo h($d); ?>"<?php echo $fDifficulty === $d ? ' selected' : ''; ?>><?php echo h($d); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="filters__actions">
          <button class="btn btn--primary" type="submit">Apply</button>
          <a class="btn btn--ghost" href="recipes.php">Reset</a>
        </div>
      </div>
    </form>

    <?php if ($dietCol === null): ?>
      <p class="filters-note">Add a diet column to your recipes table (for example <code>diet_type</code>) to enable diet filtering.</p>
    <?php endif; ?>

    <?php if ($recipes === []): ?>
      <p class="empty-state">
        <?php if ($searchTerm !== ''): ?>
          No recipes matched “<?php echo h($searchTerm); ?>”. Try different words or clear the search box.
        <?php else: ?>
          No recipes match your filters yet. Adjust the filters or add recipes to the database.
        <?php endif; ?>
      </p>
    <?php else: ?>
      <ul class="recipe-grid">
        <?php foreach ($recipes as $r): ?>
          <?php
            $rid = (int) ($r['recipe_id'] ?? 0);
            $title = $r['title'] ?? 'Recipe';
            $cuisine = $r['cuisine_type'] ?? '';
            $diff = $r['difficulty'] ?? '';
            $dietVal = ($dietCol !== null && isset($r[$dietCol])) ? (string) $r[$dietCol] : '';
            $detailUrl = 'recipe.php?recipe_id=' . $rid;
            $thumb = $recipeMediaMap[$rid] ?? null;
            ?>
          <li class="recipe-card">
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

<script>
  (function () {
    var select = document.getElementById("filter-cuisine");
    var root = document.getElementById("filter-cuisine-custom");
    if (!select || !root) {
      return;
    }

    var trigger = root.querySelector(".ff-select__trigger");
    var label = root.querySelector(".ff-select__label");
    var menu = root.querySelector(".ff-select__menu");
    var options = root.querySelectorAll(".ff-select__option");
    if (!trigger || !label || !menu || !options.length) {
      return;
    }

    function optionTextFor(value) {
      for (var i = 0; i < options.length; i++) {
        if ((options[i].getAttribute("data-value") || "") === value) {
          return options[i].textContent || "";
        }
      }
      return "All cuisines";
    }

    function syncFromSelect() {
      var value = select.value || "";
      label.textContent = optionTextFor(value);
      for (var i = 0; i < options.length; i++) {
        var isSelected = (options[i].getAttribute("data-value") || "") === value;
        options[i].setAttribute("aria-selected", isSelected ? "true" : "false");
        options[i].classList.toggle("is-selected", isSelected);
      }
    }

    function setOpen(open) {
      root.classList.toggle("is-open", open);
      trigger.setAttribute("aria-expanded", open ? "true" : "false");
    }

    trigger.addEventListener("click", function () {
      setOpen(!root.classList.contains("is-open"));
    });

    options.forEach(function (opt) {
      opt.addEventListener("click", function () {
        var value = opt.getAttribute("data-value") || "";
        select.value = value;
        syncFromSelect();
        setOpen(false);
      });
    });

    document.addEventListener("click", function (e) {
      if (!root.contains(e.target)) {
        setOpen(false);
      }
    });

    document.addEventListener("keydown", function (e) {
      if (e.key === "Escape") {
        setOpen(false);
      }
    });

    syncFromSelect();
  })();
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
