<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$pageTitle = 'FoodFusion | Cookie policy';
$bodyClass = 'page-legal';

require __DIR__ . '/includes/header.php';
?>

<section class="page-hero page-hero--compact">
  <div class="wrap">
    <h1 class="page-title">Cookie policy</h1>
    <p class="page-lead">What cookies and similar storage FoodFusion uses on your device.</p>
  </div>
</section>

<section class="section">
  <div class="wrap prose">
    <h2>What are cookies?</h2>
    <p>Cookies are small text files stored in your browser. Local storage is a similar browser feature that keeps small amounts of data on your device until you clear it.</p>

    <h2>How FoodFusion uses storage</h2>
    <ul>
      <li>
        <strong>Session cookie (essential):</strong> When you log in, PHP sets a session cookie so the server can remember that you are authenticated. This is required for account features.
      </li>
      <li>
        <strong>Cookie consent preference:</strong> When you use the banner, we store your choice under the key <code>foodfusion_cookie_consent</code> in <code>localStorage</code>. Values are <code>accepted</code> (you chose &ldquo;Accept all&rdquo;) or <code>essential</code> (you chose &ldquo;Essential only&rdquo;). This preference is not sent to the server automatically.
      </li>
    </ul>

    <h2>Managing preferences</h2>
    <p>
      On main site pages, use <strong>Cookie preferences</strong> in the footer legal column to clear your saved choice and open the banner again. You can also clear site data from your browser settings at any time.
    </p>
    <p>
      Choosing &ldquo;Essential only&rdquo; still allows the PHP session cookie when you log in. The <code>data-ff-consent</code> attribute on the <code>&lt;html&gt;</code> element is updated by our scripts so you (or future analytics code) can read the current mode: <code>all</code> or <code>essential</code>.
    </p>
    <p>
      Scripts may listen for a <code>foodfusion:cookie-consent</code> custom event on <code>document</code> with <code>detail.choice</code> set to <code>all</code> or <code>essential</code> whenever the user confirms a choice.
    </p>

    <h2>Third parties</h2>
    <p>This project loads fonts from Google Fonts when stylesheets are applied. Those requests may be logged by Google according to their policies. You can self-host fonts later if you need to avoid that.</p>

    <p><a href="privacy.php">Back to privacy policy</a></p>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
