<?php

require_once __DIR__ . '/db_config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$errorMain = '';
$isLocked = false;
$lockRemainingSeconds = 0;
$emailField = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = (string) ($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $errorMain = 'Please fill in all fields.';
        $emailField = $email;
    } else {
        try {
            $stmt = $conn->prepare(
                'SELECT user_id, email, first_name, user_password, failed_attempts, locked_until
                 FROM users
                 WHERE email = :email
                 LIMIT 1'
            );
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user === false) {
                $errorMain = 'Invalid email or password.';
                $emailField = $email;
            } else {
                $now = new DateTimeImmutable('now');
                $lockedUntil = null;

                if (!empty($user['locked_until'])) {
                    try {
                        $lockedUntil = new DateTimeImmutable($user['locked_until']);
                    } catch (Exception $e) {
                        $lockedUntil = null;
                    }
                }

                $isLocked = ($lockedUntil instanceof DateTimeImmutable && $lockedUntil > $now);

                if (!$isLocked && isset($_SESSION['login_lock_until_ts'], $_SESSION['login_lock_email'])
                    && $_SESSION['login_lock_email'] === $email) {
                    unset($_SESSION['login_lock_until_ts'], $_SESSION['login_lock_email']);
                }

                if ($isLocked) {
                    $_SESSION['login_lock_until_ts'] = $lockedUntil->getTimestamp();
                    $_SESSION['login_lock_email'] = $email;
                    $lockRemainingSeconds = max(0, $lockedUntil->getTimestamp() - time());
                    $emailField = $email;
                    $errorMain = 'The session is temporarily locked due to multiple failed login attempts.';
                } elseif ($errorMain === '') {
                    if (password_verify($password, $user['user_password'])) {
                        unset($_SESSION['login_lock_until_ts'], $_SESSION['login_lock_email']);

                        $reset = $conn->prepare(
                            'UPDATE users
                             SET failed_attempts = 0, locked_until = NULL
                             WHERE user_id = :user_id'
                        );
                        $reset->execute([':user_id' => $user['user_id']]);

                        session_regenerate_id(true);
                        $_SESSION['user_id']   = (int) $user['user_id'];
                        $_SESSION['email']     = $user['email'];
                        $_SESSION['first_name'] = $user['first_name'];

                        header('Location: index.php');
                        exit;
                    }

                    $fail = $conn->prepare(
                        'UPDATE users
                         SET failed_attempts = failed_attempts + 1,
                             locked_until = IF(failed_attempts + 1 >= 3, DATE_ADD(NOW(), INTERVAL 3 MINUTE), locked_until)
                         WHERE user_id = :user_id'
                    );
                    $fail->execute([':user_id' => $user['user_id']]);

                    $errorMain = 'Invalid email or password.';
                    $emailField = $email;
                }
            }
        } catch (PDOException $e) {
            $errorMain = 'Login failed due to a system error. Please try again shortly.';
            $emailField = $email;
        }
    }
} else {
    // GET: restore lock UI so countdown works after refresh without re-posting
    if (isset($_SESSION['login_lock_until_ts'], $_SESSION['login_lock_email'])) {
        $ts = (int) $_SESSION['login_lock_until_ts'];
        if ($ts > time()) {
            $isLocked = true;
            $lockRemainingSeconds = $ts - time();
            $emailField = $_SESSION['login_lock_email'];
            $errorMain = 'The session is temporarily locked due to multiple failed login attempts.';
        } else {
            unset($_SESSION['login_lock_until_ts'], $_SESSION['login_lock_email']);
        }
    }
}
?>

<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>FoodFusion | Login</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link rel="stylesheet" href="style.css" />
  </head>

  <body>
    <main class="login-page">
      <section class="image-section" aria-label="FoodFusion showcase image">
        <div class="image-branding">
          <div class="image-branding-content">
            <h1>Good to see you again.</h1>
            <p>Continue your journey of creating, sharing, and savoring world-class delicacies.</p>
          </div>
        </div>
      </section>

      <section class="form-section" aria-label="Login to your FoodFusion account">
        <div class="form-container">
          <a class="site-logo site-logo--form" href="index.php" aria-label="FoodFusion home">
            <img class="site-logo__img" src="assets/food_fusion_logo1.svg" alt="" width="156" height="122" decoding="async" />
            <span class="site-logo__wordmark"><span class="site-wordmark-food">Food</span><span class="site-logo-accent">Fusion</span></span>
          </a>

          <div class="registration-card" role="region" aria-label="Login form card">
            <h2 class="card-title">Welcome Back</h2>
            <p class="card-subtitle">Log in to continue.</p>

            <?php if ($errorMain !== ''): ?>
              <div class="form-error" role="alert">
                <?php echo htmlspecialchars($errorMain, ENT_QUOTES, 'UTF-8'); ?>
                <?php if ($isLocked && $lockRemainingSeconds > 0): ?>
                  <p class="lock-countdown" id="lock-countdown" data-seconds="<?php echo (int) $lockRemainingSeconds; ?>" aria-live="polite"></p>
                <?php endif; ?>
              </div>
            <?php endif; ?>

            <form method="post" action="login.php" autocomplete="on" novalidate>
              <div class="form-grid">
                <div class="form-field full">
                  <label for="email">Email</label>
                  <input class="input-box" type="email" id="email" name="email" placeholder="you@example.com" required value="<?php echo htmlspecialchars($emailField, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $isLocked ? ' readonly' : ''; ?> />
                </div>

                <div class="form-field full">
                  <label for="password">Password</label>
                  <div class="password-field-wrap">
                    <input class="input-box" type="password" id="password" name="password" placeholder="Your password" required<?php echo $isLocked ? ' readonly' : ''; ?> />
                    <button type="button" class="password-toggle" id="password-toggle" aria-controls="password" aria-pressed="false">Show</button>
                  </div>
                </div>
              </div>

              <button class="submit-btn" type="submit" id="login-submit"<?php echo $isLocked ? ' disabled' : ''; ?>>Log in</button>

              <p class="helper-text auth-helper-link">
                <a href="forgot_password.php">Forgot password?</a>
              </p>

              <p class="helper-text">
                New here? <a href="register.php">Create an account</a>
              </p>
            </form>
          </div>
        </div>
      </section>
    </main>

    <?php require __DIR__ . '/includes/cookie_bar.php'; ?>

    <script src="script.js" defer></script>
    <script src="site.js" defer></script>
  </body>
</html>
