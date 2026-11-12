<?php

declare(strict_types=1);

/**
 * Global contact sidebar (drawer). Included from footer on every page except contact.php.
 * Form POSTs to contact.php; optional prefills via $ff_sidebar_contact_* when set.
 */

$ff_sidebar_contact_name = $ff_sidebar_contact_name ?? '';
$ff_sidebar_contact_email = $ff_sidebar_contact_email ?? '';
$ff_sidebar_contact_message = $ff_sidebar_contact_message ?? '';

?>
<div
  id="contact-drawer-overlay"
  class="contact-drawer-overlay"
  aria-hidden="true"
></div>

<aside
  id="contact-sidebar"
  class="contact-drawer"
  role="dialog"
  aria-modal="true"
  aria-labelledby="contact-drawer-title"
  aria-hidden="true"
  tabindex="-1"
>
  <div class="contact-drawer__chrome">
    <button
      type="button"
      class="contact-drawer__close"
      id="contact-drawer-close"
      aria-label="Close contact panel"
    >
      <span class="contact-drawer__close-x" aria-hidden="true">&times;</span>
    </button>
    <h2 id="contact-drawer-title" class="contact-drawer__heading">Get in Touch with Us</h2>
  </div>

  <div class="contact-drawer__body">
    <div class="contact-drawer__thumb-wrap">
      <img
        class="contact-drawer__thumb"
        src="assets/food_fusion_logo1.svg"
        alt="FoodFusion"
        width="160"
        height="125"
        loading="lazy"
        decoding="async"
      />
    </div>

    <div class="contact-drawer__mid">
      <div class="contact-drawer__col contact-drawer__col--form">
        <form method="post" action="contact.php" class="contact-drawer__form stack-form">
          <div class="form-field full">
            <label for="sidebar_contact_name">Name</label>
            <input
              class="input-box"
              type="text"
              id="sidebar_contact_name"
              name="name"
              required
              value="<?php echo h($ff_sidebar_contact_name); ?>"
            />
          </div>
          <div class="form-field full">
            <label for="sidebar_contact_email">Email</label>
            <input
              class="input-box"
              type="email"
              id="sidebar_contact_email"
              name="email"
              required
              value="<?php echo h($ff_sidebar_contact_email); ?>"
            />
          </div>
          <div class="form-field full">
            <label for="sidebar_contact_message">Message</label>
            <textarea
              class="input-box input-box--textarea"
              id="sidebar_contact_message"
              name="message"
              rows="5"
              required
            ><?php echo h($ff_sidebar_contact_message); ?></textarea>
          </div>
          <button class="btn btn--primary" type="submit">Send message</button>
        </form>
      </div>

      <div class="contact-drawer__col contact-drawer__col--details">
        <h3 class="contact-drawer__details-title">Contact details</h3>
        <dl class="contact-drawer__details">
          <div class="contact-drawer__detail">
            <dt class="contact-drawer__detail-label">Email</dt>
            <dd class="contact-drawer__detail-value">
              <a href="mailto:hello@foodfusion.example">hello@foodfusion.example</a>
            </dd>
          </div>
          <div class="contact-drawer__detail">
            <dt class="contact-drawer__detail-label">Phone</dt>
            <dd class="contact-drawer__detail-value">
              <a href="tel:+442012345678">(+44) 20 1234 5678</a>
            </dd>
          </div>
          <div class="contact-drawer__detail">
            <dt class="contact-drawer__detail-label">Address</dt>
            <dd class="contact-drawer__detail-value">
              123 Fusion Lane, Culinary Quarter<br />
              London, EC1A 1BB, United Kingdom
            </dd>
          </div>
        </dl>
      </div>
    </div>

    <div class="contact-drawer__map-block">
      <h3 class="visually-hidden">Location map</h3>
      <div class="contact-drawer__map">
        <iframe
          title="Map of London near FoodFusion"
          src="https://www.openstreetmap.org/export/embed.html?bbox=-0.145%2C51.495%2C-0.065%2C51.535&amp;layer=mapnik"
          loading="lazy"
        ></iframe>
      </div>
      <p class="contact-drawer__map-note">
        Map © <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener noreferrer">OpenStreetMap</a>
        (placeholder — replace with Google Maps embed if you add an API key).
      </p>
    </div>
  </div>
</aside>
