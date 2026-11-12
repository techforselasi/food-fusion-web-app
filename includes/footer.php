    </main>

    <?php
    // Prefer SCRIPT_NAME so behaviour does not depend on a page-level flag being set correctly.
    $ff_script_basename = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    $ff_is_contact_page =
        strcasecmp($ff_script_basename, 'contact.php') === 0 || !empty($foodfusion_contact_page);
    if (!$ff_is_contact_page) {
        require __DIR__ . '/sidebar-contact.php';
    }
    ?>

    <footer class="site-footer">
      <div class="site-footer__inner">
        <div class="site-footer__brand">
          <span class="site-footer__wordmark"><span class="site-wordmark-food">Food</span><span class="site-logo-accent">Fusion</span></span>
          <p class="site-footer__tagline">Celebrate global flavours, one recipe at a time.</p>
          <?php if (!$ff_is_contact_page): ?>
            <button
              type="button"
              class="site-footer__contact-btn"
              id="footer-contact-open"
              aria-haspopup="dialog"
              aria-controls="contact-sidebar"
            >
              Get in Touch
            </button>
          <?php endif; ?>
        </div>

        <div class="site-footer__cols">
          <div class="site-footer__col">
            <h2 class="site-footer__heading">Explore</h2>
            <ul class="site-footer__links">
              <li><a href="recipes.php">Recipes</a></li>
              <li><a href="community.php">Community cookbook</a></li>
              <li><a href="culinary.php">Culinary resources</a></li>
              <li><a href="educational.php">Educational resources</a></li>
            </ul>
          </div>
          <div class="site-footer__col">
            <h2 class="site-footer__heading">Legal</h2>
            <ul class="site-footer__links">
              <li><a href="privacy.php">Privacy policy</a></li>
              <li><a href="cookies.php">Cookie policy</a></li>
              <li>
                <button type="button" class="site-footer__text-btn" id="cookie-preferences-btn">Cookie preferences</button>
              </li>
            </ul>
          </div>
          <div class="site-footer__col">
            <h2 class="site-footer__heading">Follow</h2>
            <ul class="site-footer__social" aria-label="Social media">
              <li>
                <a class="site-footer__social-link" href="https://www.instagram.com/" target="_blank" rel="noopener noreferrer" aria-label="Instagram">
                  <svg class="site-footer__social-svg" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                    <path
                      fill="currentColor"
                      d="M7 2h10a5 5 0 0 1 5 5v10a5 5 0 0 1-5 5H7a5 5 0 0 1-5-5V7a5 5 0 0 1 5-5zm0 2a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V7a3 3 0 0 0-3-3H7zm5 3.5a4.5 4.5 0 1 1 0 9.001 4.5 4.5 0 0 1 0-9zm0 2a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5zm5.25-.75a1 1 0 1 1-2 0 1 1 0 0 1 2 0z"
                    />
                  </svg>
                </a>
              </li>
              <li>
                <a class="site-footer__social-link" href="https://www.facebook.com/" target="_blank" rel="noopener noreferrer" aria-label="Facebook">
                  <svg class="site-footer__social-svg" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                    <path
                      fill="currentColor"
                      d="M13.5 22v-8.5h2.9l.55-3.5H13.5V7.9c0-.96.47-1.9 2-1.9h1.5V2.8c-.8-.08-1.6-.12-2.4-.12-2.7 0-4.6 1.65-4.6 4.7v2.62H7v3.5h2.9V22h3.6z"
                    />
                  </svg>
                </a>
              </li>
              <li>
                <a class="site-footer__social-link" href="https://www.youtube.com/" target="_blank" rel="noopener noreferrer" aria-label="YouTube">
                  <svg class="site-footer__social-svg" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                    <path
                      fill="currentColor"
                      d="M21.8 8.2s-.2-1.5-.8-2.2c-.8-.8-1.6-.8-2-.9C16 5 12 5 12 5s-4 0-6.9.1c-.5.1-1.3.1-2.1.9-.6.7-.8 2.2-.8 2.2S2 10 2 12s.2 3.8.2 3.8.2 1.5.8 2.2c.9.9 2 .9 2.5 1 1.8.2 6.5.2 6.5.2s4 0 6.9-.1c.4 0 1.2 0 2-.9.6-.7.8-2.2.8-2.2s.2-1.9.2-3.8-.2-3.8-.2-3.8zM10 15V9l5 3-5 3z"
                    />
                  </svg>
                </a>
              </li>
              <li>
                <a class="site-footer__social-link" href="https://www.pinterest.com/" target="_blank" rel="noopener noreferrer" aria-label="Pinterest">
                  <svg class="site-footer__social-svg" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                    <path
                      fill="currentColor"
                      d="M12 2C6.5 2 2 6.5 2 12c0 4.1 2.4 7.6 5.8 9.2-.1-.8-.1-2 0-2.9.2-1.2 1.3-7.9 1.3-7.9s-.3-.7-.3-1.7c0-1.6.9-2.8 2.1-2.8 1 0 1.5.7 1.5 1.6 0 1-.6 2.5-1 3.9-.3 1.2.6 2.1 1.8 2.1 2.1 0 3.8-2.2 3.8-5.4 0-2.8-2-4.8-4.9-4.8-3.3 0-5.3 2.5-5.3 5.1 0 1 .4 2.1 1 2.7.1.1.1.2.1.3l-.4 1.6c0 .2-.1.2-.3.1-1.2-.6-2-2.4-2-3.9 0-3.2 2.3-6.1 6.6-6.1 3.5 0 6.2 2.5 6.2 5.8 0 3.5-2.2 6.3-5.2 6.3-1 0-2-.5-2.3-1.2l-.6 2.4c-.2.9-.7 2-1.2 2.6 1 .3 2.1.5 3.2.5 5.5 0 10-4.5 10-10S17.5 2 12 2z"
                    />
                  </svg>
                </a>
              </li>
            </ul>
          </div>
        </div>
      </div>
      <p class="site-footer__copy">&copy; <?php echo date('Y'); ?> FoodFusion. University project.</p>
    </footer>

    <?php require __DIR__ . '/assistant_fab.php'; ?>

    <?php require __DIR__ . '/cookie_bar.php'; ?>

    <?php
    $ff_site_js = dirname(__DIR__) . '/site.js';
    $ff_site_js_v = is_readable($ff_site_js) ? (string) filemtime($ff_site_js) : '1';
    ?>
    <script src="site.js?v=<?php echo h($ff_site_js_v); ?>" defer></script>
  </body>
</html>
