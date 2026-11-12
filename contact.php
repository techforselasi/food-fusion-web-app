<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

/** Full-page contact layout; sidebar drawer is omitted on this page (see footer.php). */
$foodfusion_contact_page = true;

$pageTitle = 'FoodFusion | Contact';
$bodyClass = 'page-contact';

$contactMessage = isset($_GET['sent']) ? 'Thanks—your message has been received. We will get back to you soon.' : '';
$contactError = '';
$name = '';
$email = '';
$messageField = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim((string) ($_POST['name'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $messageField = trim((string) ($_POST['message'] ?? ''));

    if ($name === '' || $email === '' || $messageField === '') {
        $contactError = 'Please complete your name, email, and message.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $contactError = 'Please enter a valid email address.';
    } else {
        try {
            $stmt = $conn->prepare(
                'INSERT INTO feedback (name, email, message) VALUES (:name, :email, :message)'
            );
            $stmt->execute([
                ':name' => $name,
                ':email' => $email,
                ':message' => $messageField,
            ]);
            header('Location: contact.php?sent=1');
            exit;
        } catch (PDOException $e) {
            $contactError = 'Something went wrong while sending your message. Please try again later.';
        }
    }
}

require __DIR__ . '/includes/header.php';
?>

<div class="contact-page wrap">
  <header class="contact-page__intro">
    <h1 class="contact-page__title">Get in Touch with Us</h1>
    <p class="contact-page__lead">
      Questions, recipe ideas, or partnership enquiries? Send a message—we read every note.
    </p>
  </header>

  <?php if ($contactMessage !== ''): ?>
    <div class="banner banner--success contact-page__flash" role="status"><?php echo h($contactMessage); ?></div>
  <?php endif; ?>
  <?php if ($contactError !== ''): ?>
    <div class="form-error contact-page__flash" role="alert"><?php echo h($contactError); ?></div>
  <?php endif; ?>

  <div class="contact-page__thumb-wrap">
    <img
      class="contact-page__thumb"
      src="assets/food_fusion_logo1.svg"
      alt="FoodFusion"
      width="200"
      height="156"
      loading="lazy"
      decoding="async"
    />
  </div>

  <div class="contact-page__mid">
    <section class="contact-page__panel contact-page__panel--form" aria-labelledby="contact-form-heading">
      <h2 id="contact-form-heading" class="visually-hidden">Contact form</h2>
      <form method="post" action="contact.php" class="stack-form contact-page__form">
        <div class="form-field full">
          <label for="contact_name">Name</label>
          <input
            class="input-box"
            type="text"
            id="contact_name"
            name="name"
            required
            value="<?php echo h($name); ?>"
          />
        </div>
        <div class="form-field full">
          <label for="contact_email">Email</label>
          <input
            class="input-box"
            type="email"
            id="contact_email"
            name="email"
            required
            value="<?php echo h($email); ?>"
          />
        </div>
        <div class="form-field full">
          <label for="contact_message">Message</label>
          <textarea
            class="input-box input-box--textarea"
            id="contact_message"
            name="message"
            rows="6"
            required
          ><?php echo h($messageField); ?></textarea>
        </div>
        <button class="btn btn--primary" type="submit">Send message</button>
      </form>
    </section>

    <section class="contact-page__panel contact-page__panel--details" aria-labelledby="contact-details-heading">
      <h2 id="contact-details-heading" class="contact-page__details-title">Contact details</h2>
      <dl class="contact-page__details">
        <div class="contact-page__detail">
          <dt class="contact-page__detail-label">Email</dt>
          <dd class="contact-page__detail-value">
            <a href="mailto:hello@foodfusion.example">hello@foodfusion.example</a>
          </dd>
        </div>
        <div class="contact-page__detail">
          <dt class="contact-page__detail-label">Phone</dt>
          <dd class="contact-page__detail-value">
            <a href="tel:+442012345678">(+44) 20 1234 5678</a>
          </dd>
        </div>
        <div class="contact-page__detail">
          <dt class="contact-page__detail-label">Address</dt>
          <dd class="contact-page__detail-value">
            123 Fusion Lane, Culinary Quarter<br />
            London, EC1A 1BB, United Kingdom
          </dd>
        </div>
      </dl>
    </section>
  </div>

  <section class="contact-page__map-section" aria-labelledby="contact-map-heading">
    <h2 id="contact-map-heading" class="contact-page__map-heading">Find us</h2>
    <div class="contact-page__map">
      <iframe
        title="Map of London near FoodFusion"
        src="https://www.openstreetmap.org/export/embed.html?bbox=-0.145%2C51.495%2C-0.065%2C51.535&amp;layer=mapnik"
        loading="lazy"
      ></iframe>
    </div>
    <p class="contact-page__map-note">
      Map © <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener noreferrer">OpenStreetMap</a>
      (placeholder — replace with Google Maps embed if you add an API key).
    </p>
  </section>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
