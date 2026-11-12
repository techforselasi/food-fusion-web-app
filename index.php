<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/news_feed.php';

$pageTitle = 'FoodFusion | Home';

$registerFlash = $_SESSION['register_flash'] ?? null;
unset($_SESSION['register_flash']);
$signupModalOpen = is_array($registerFlash);

$newsItems = ff_news_feed_items(6);

if ($newsItems === [] && ff_table_exists($conn, 'news_feed')) {
    $cols = ff_table_columns($conn, 'news_feed');
    $orderCol = ff_pick_column($cols, ['created_at', 'posted_at', 'published_at', 'news_id']);
    if ($orderCol !== null) {
        $stmt = $conn->query(
            'SELECT * FROM news_feed ORDER BY `' . str_replace('`', '``', $orderCol) . '` DESC LIMIT 6'
        );
        $newsItems = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }
}

$eventItems = [];
if (ff_table_exists($conn, 'events')) {
    $cols = ff_table_columns($conn, 'events');
    $orderCol = ff_pick_column($cols, ['event_date', 'start_date', 'event_datetime', 'created_at', 'event_id']);
    if ($orderCol !== null) {
        $stmt = $conn->query(
            'SELECT * FROM events ORDER BY `' . str_replace('`', '``', $orderCol) . '` ASC LIMIT 12'
        );
        $eventItems = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }
}

$currentUser = ff_user();
$isLoggedIn = $currentUser !== null;
$heroFirstName = '';
if ($isLoggedIn) {
    $fn = trim((string) ($currentUser[''] ?? ''));
    $heroFirstName = $fn !== '' ? $fn : '';
}
$bodyClass = 'page-home' . ($isLoggedIn ? ' page-home--logged-in' : '');

$heroSlides = [
    [
        'src' => 'assets/event1.png',
        'alt' => 'Cooking event',
        'caption' => 'Cooking event',
        'blurb' => 'Workshops, demos, and tastings—learn techniques you can use at home the same week.',
    ],
    [
        'src' => 'assets/event2.png',
        'alt' => 'Get together cookout',
        'caption' => 'Get together cookout',
        'blurb' => 'Relaxed outdoor feasts where friends share grills, sides, and stories from every kitchen.',
    ],
    [
        'src' => 'assets/event3.png',
        'alt' => 'Market event',
        'caption' => 'Market event',
        'blurb' => 'Meet growers and makers, taste what is in season, and pick up ideas for your next shop.',
    ],
];

$heroCarouselAuto = count($heroSlides) >= 2;

require __DIR__ . '/includes/header.php';
?>

<?php
$heroSectionClass = 'hero hero--carousel';
if ($heroCarouselAuto) {
    $heroSectionClass .= ' hero--intro-wait';
}
?>
<section class="<?php echo h($heroSectionClass); ?>" aria-label="Featured culinary moments">
  <div class="hero__bg-carousel" data-hero-carousel>
    <?php if ($heroSlides !== []): ?>
      <?php foreach ($heroSlides as $i => $slide): ?>
        <?php
        $slideActive = $heroCarouselAuto ? false : $i === 0;
        $slideAriaHidden = $heroCarouselAuto ? 'true' : ($slideActive ? 'false' : 'true');
        ?>
        <div
          class="hero__bg-slide<?php echo $slideActive ? ' is-active' : ''; ?>"
          data-hero-slide
          data-hero-caption="<?php echo h($slide['caption']); ?>"
          data-hero-blurb="<?php echo h($slide['blurb'] ?? ''); ?>"
          aria-hidden="<?php echo $slideAriaHidden; ?>"
        >
          <img
            class="hero__bg-image"
            src="<?php echo h($slide['src']); ?>"
            alt="<?php echo h($slide['alt']); ?>"
            loading="<?php echo $i === 0 ? 'eager' : 'lazy'; ?>"
            decoding="async"
          />
        </div>
      <?php endforeach; ?>
      <?php if ($heroCarouselAuto): ?>
        <div class="hero__intro-plate" aria-hidden="true"></div>
      <?php endif; ?>
    <?php else: ?>
      <div class="hero__bg-empty" aria-live="polite">No events atm</div>
    <?php endif; ?>
    <div class="hero__bg-overlay" aria-hidden="true"></div>
  </div>

  <div class="hero__inner wrap">
    <div class="hero__message-stage">
      <div class="hero__message-slot hero__message-slot--welcome" id="hero-welcome-slot">
        <div class="hero__copy">
          <p class="hero__eyebrow">Culinary discovery</p>
          <h1 class="hero__title">Welcome back to your kitchen, <span class="hero__accent"><?php echo h($heroFirstName); ?></span>!</h1>
          <p class="hero__lead">
            FoodFusion is your space to explore recipes, learn from curated resources, and cook alongside a community that celebrates
            diversity on every plate.
          </p>
          <div class="hero__actions">
            <?php if (!$isLoggedIn): ?>
              <button type="button" class="btn btn--primary" id="open-signup-modal" data-modal-open="signup-modal">Sign up now</button>
            <?php endif; ?>
            <a class="btn <?php echo $isLoggedIn ? 'btn--primary' : 'btn--ghost'; ?>" href="recipes.php">Browse recipes</a>
          </div>
        </div>
      </div>
      <?php if ($heroSlides !== []): ?>
        <div
          class="hero__message-slot hero__message-slot--events"
          id="hero-events-slot"
          aria-hidden="<?php echo $heroCarouselAuto ? 'true' : 'false'; ?>"
        >
          <?php
          $firstHero = $heroSlides[0];
          $firstBlurb = isset($firstHero['blurb']) ? (string) $firstHero['blurb'] : '';
          $eventCopyVisible = !$heroCarouselAuto;
          ?>
          <div
            class="hero__event-copy<?php echo $eventCopyVisible ? ' is-visible' : ''; ?>"
            id="hero-event-copy"
            aria-live="polite"
            aria-atomic="true"
          >
            <p class="hero__event-title" id="hero-event-title"><?php echo h($firstHero['caption']); ?></p>
            <p class="hero__event-blurb" id="hero-event-blurb"><?php echo h($firstBlurb); ?></p>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>

<section class="section section--tint" id="news">
  <div class="wrap">
    <header class="section__head">
      <h2 class="section__title">News feed</h2>
      <p class="section__intro">Stories and updates from the FoodFusion kitchen desk.</p>
    </header>
    <?php if ($newsItems === []): ?>
      <p class="empty-state">No food updates available right now.</p>
    <?php else: ?>
      <ul class="news-grid">
        <?php foreach ($newsItems as $row): ?>
          <?php
            $title = $row['title'] ?? $row['headline'] ?? 'Update';
            $body = $row['summary'] ?? $row['content'] ?? $row['body'] ?? $row['article_text'] ?? '';
            $body = is_string($body) ? trim(strip_tags($body)) : '';
            $excerpt = $body !== '' ? (strlen($body) > 160 ? substr($body, 0, 157) . '...' : $body) : '';
            $img = isset($row['image_url']) && $row['image_url'] !== '' ? (string) $row['image_url'] : '';
            $url = isset($row['source_url']) && $row['source_url'] !== '' ? (string) $row['source_url'] : '';
            ?>
          <li class="news-card">
            <?php if ($img !== ''): ?>
              <div class="news-card__media">
                <img class="news-card__img" src="<?php echo h($img); ?>" alt="" loading="lazy" decoding="async" width="480" height="280" />
              </div>
            <?php endif; ?>
            <h3 class="news-card__title"><?php echo h($title); ?></h3>
            <?php if ($excerpt !== ''): ?>
              <p class="news-card__excerpt"><?php echo h($excerpt); ?></p>
            <?php endif; ?>
            <?php if ($url !== ''): ?>
              <p class="news-card__cta"><a href="<?php echo h($url); ?>" target="_blank" rel="noopener noreferrer">Read more</a></p>
            <?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>
</section>

<div
  class="modal"
  id="signup-modal"
  role="dialog"
  aria-modal="true"
  aria-labelledby="signup-modal-title"
  <?php echo $signupModalOpen ? 'data-open-on-load="true"' : ''; ?>
  hidden
>
  <div class="modal__backdrop" data-modal-close tabindex="-1"></div>
  <div class="modal__dialog" role="document">
    <button type="button" class="modal__close" data-modal-close aria-label="Close sign up dialog">&times;</button>
    <h2 class="modal__title" id="signup-modal-title">Create your account</h2>
    <p class="modal__lede">Join FoodFusion to save favourites and share with the community.</p>

    <?php if ($signupModalOpen && !empty($registerFlash['error'])): ?>
      <div class="form-error" role="alert"><?php echo h($registerFlash['error']); ?></div>
    <?php endif; ?>

    <form class="modal__form" method="post" action="register.php" autocomplete="on">
      <input type="hidden" name="signup_modal" value="1" />
      <div class="form-field full">
        <label for="modal_first_name">First name</label>
        <input
          class="input-box"
          type="text"
          id="modal_first_name"
          name="first_name"
          required
          value="<?php echo h($registerFlash['first_name'] ?? ''); ?>"
          pattern="[A-Za-z ]+"
          title="Letters and spaces only"
        />
      </div>
      <div class="form-field full">
        <label for="modal_last_name">Last name</label>
        <input
          class="input-box"
          type="text"
          id="modal_last_name"
          name="last_name"
          required
          value="<?php echo h($registerFlash['last_name'] ?? ''); ?>"
          pattern="[A-Za-z ]+"
          title="Letters and spaces only"
        />
      </div>
      <div class="form-field full">
        <label for="modal_email">Email</label>
        <input
          class="input-box"
          type="email"
          id="modal_email"
          name="email"
          required
          placeholder="you@example.com"
          value="<?php echo h($registerFlash['email'] ?? ''); ?>"
        />
      </div>
      <div class="form-field full">
        <label for="modal_password">Password</label>
        <input class="input-box" type="password" id="modal_password" name="password" required minlength="8" placeholder="At least 8 characters" autocomplete="new-password" />
      </div>
      <div class="form-field full">
        <label for="modal_confirm_password">Confirm password</label>
        <input class="input-box" type="password" id="modal_confirm_password" name="confirm_password" required minlength="8" autocomplete="new-password" />
      </div>
      <button class="btn btn--primary btn--block" type="submit">Create account</button>
      <p class="modal__footnote">
        Already registered? <a href="login.php">Log in</a>. By signing up you agree to our <a href="privacy.php">privacy policy</a>.
      </p>
    </form>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
