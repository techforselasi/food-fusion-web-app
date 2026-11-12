<?php

declare(strict_types=1);

if (!isset($pageTitle)) {
    $pageTitle = 'FoodFusion';
}

$bodyClass = $bodyClass ?? '';
$user = ff_user();
$ff_assistant_context = $ff_assistant_context ?? null;
$ff_assistant_context_attr = '';
if (is_array($ff_assistant_context) && $ff_assistant_context !== []) {
    $ff_assistant_context_attr = ' data-assistant-context="' . h((string) json_encode($ff_assistant_context, JSON_UNESCAPED_UNICODE)) . '"';
}

?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?php echo h($pageTitle); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link rel="stylesheet" href="style.css" />
  </head>
  <body class="site-body<?php echo $bodyClass !== '' ? ' ' . h($bodyClass) : ''; ?>"<?php echo $ff_assistant_context_attr; ?>>
    <a class="skip-link" href="#main-content">Skip to main content</a>

    <header class="site-header">
      <div class="site-header__inner">
        <a class="site-header__brand" href="index.php" aria-label="FoodFusion home">
          <img class="site-header__logo" src="assets/food_fusion_logo1.svg" alt="" width="128" height="100" decoding="async" />
          <span class="site-header__wordmark"><span class="site-wordmark-food">Food</span><span class="site-logo-accent">Fusion</span></span>
        </a>

        <button type="button" class="nav-toggle" id="nav-toggle" aria-expanded="false" aria-controls="site-nav">
          <span class="nav-toggle__bar" aria-hidden="true"></span>
          <span class="nav-toggle__bar" aria-hidden="true"></span>
          <span class="nav-toggle__bar" aria-hidden="true"></span>
          <span class="visually-hidden">Menu</span>
        </button>

        <nav class="site-nav" id="site-nav" aria-label="Primary">
          <ul class="site-nav__list">
            <li><a href="index.php" <?php echo ff_nav_active('index.php'); ?>>Home</a></li>
            <li><a href="recipes.php" <?php echo ff_nav_active('recipes.php'); ?>>Recipes</a></li>
            <li><a href="community.php" <?php echo ff_nav_active('community.php'); ?>>Community</a></li>
            <li><a href="culinary.php" <?php echo ff_nav_active('culinary.php'); ?>>Culinary</a></li>
            <li><a href="about.php" <?php echo ff_nav_active('about.php'); ?>>About</a></li>
            <li><a href="contact.php" <?php echo ff_nav_active('contact.php'); ?>>Contact</a></li>
            <?php if ($user): ?>
              <li class="site-nav__account site-nav__account--menu">
                <button
                  type="button"
                  class="site-account-btn"
                  id="site-account-btn"
                  aria-expanded="false"
                  aria-controls="site-account-menu"
                  aria-label="Account menu"
                >
                  <span class="site-account-btn__avatar" aria-hidden="true"><?php echo h(strtoupper(substr((string) ($user['first_name'] ?? 'U'), 0, 1))); ?></span>
                </button>
                <div class="site-account-menu" id="site-account-menu" role="menu" hidden>
                  <div class="site-account-menu__meta" role="none">
                    <span class="site-account-menu__label">Signed in as</span>
                    <span class="site-account-menu__email"><?php echo h((string) ($user['email'] ?? '')); ?></span>
                  </div>
                  <a href="logout.php" role="menuitem">Log out</a>
                </div>
              </li>
            <?php else: ?>
              <li class="site-nav__account"><a href="login.php">Log in</a></li>
              <li class="site-nav__account"><a class="site-nav__cta" href="register.php">Sign up</a></li>
            <?php endif; ?>
          </ul>
        </nav>
      </div>
    </header>

    <main id="main-content" class="site-main">
