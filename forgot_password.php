<?php
declare(strict_types=1);

require_once __DIR__ . '/db_config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$errorMessage = '';
$successMessage = '';
$emailField = '';
$resetLink = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emailField = trim((string) ($_POST['email'] ?? ''));
    if ($emailField === '') {
        $errorMessage = 'Please enter your account email.';
    } elseif (!filter_var($emailField, FILTER_VALIDATE_EMAIL)) {
        $errorMessage = 'Please enter a valid email address.';
    } elseif (!ff_table_exists_local($conn, 'password_resets')) {
        $errorMessage = 'Password reset is not available yet. Run the latest SQL schema first.';
    } else {
        $rawToken = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $rawToken);
        $expiresAt = (new DateTimeImmutable('+30 minutes'))->format('Y-m-d H:i:s');

        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = (string) ($_SERVER['HTTP_HOST'] ?? '');
        $scriptDir = dirname((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
        $scriptDir = str_replace('\\', '/', $scriptDir);
        $basePath = ($scriptDir === '/' || $scriptDir === '.') ? '' : rtrim($scriptDir, '/');
        $resetLink = $scheme . '://' . $host . $basePath . '/reset_password.php?token=' . rawurlencode($rawToken);

        try {
            $userId = null;
            if (ff_table_exists_local($conn, 'users')) {
                $stmt = $conn->prepare('SELECT user_id FROM users WHERE email = :email LIMIT 1');
                $stmt->execute([':email' => $emailField]);
                $u = $stmt->fetch(PDO::FETCH_ASSOC);
                if (is_array($u) && isset($u['user_id'])) {
                    $userId = (int) $u['user_id'];
                }
            }

            if ($userId !== null && $userId > 0) {
                $conn->beginTransaction();
                $expireOld = $conn->prepare(
                    'UPDATE password_resets
                     SET used_at = NOW()
                     WHERE user_id = :uid AND used_at IS NULL'
                );
                $expireOld->execute([':uid' => $userId]);

                $ins = $conn->prepare(
                    'INSERT INTO password_resets (user_id, token_hash, expires_at)
                     VALUES (:uid, :hash, :exp)'
                );
                $ins->execute([
                    ':uid' => $userId,
                    ':hash' => $tokenHash,
                    ':exp' => $expiresAt,
                ]);
                $conn->commit();
            }

            // Keep response generic to avoid exposing whether email exists.
            $successMessage = 'If this email is registered, a reset link has been generated.';
        } catch (PDOException $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            error_log('FoodFusion forgot_password: ' . $e->getMessage());
            $errorMessage = 'Could not process password reset right now. Please try again shortly.';
            $resetLink = '';
        }
    }
}

function ff_table_exists_local(PDO $conn, string $table): bool
{
    static $cache = [];
    if (!isset($cache[$table])) {
        $stmt = $conn->prepare('SHOW TABLES LIKE :t');
        $stmt->execute([':t' => $table]);
        $cache[$table] = (bool) $stmt->fetchColumn();
    }

    return $cache[$table];
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>FoodFusion | Forgot password</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link rel="stylesheet" href="style.css" />
  </head>
  <body>
    <main class="login-page">
      <section class="image-section" aria-label="FoodFusion showcase image">
        <div class="image-branding">
          <div class="image-branding-content">
            <h1>Reset your password.</h1>
            <p>Enter your email and we will generate a one-time reset link for your account.</p>
          </div>
        </div>
      </section>

      <section class="form-section" aria-label="Forgot password form">
        <div class="form-container">
          <a class="site-logo site-logo--form" href="index.php" aria-label="FoodFusion home">
            <img class="site-logo__img" src="assets/food_fusion_logo1.svg" alt="" width="156" height="122" decoding="async" />
            <span class="site-logo__wordmark"><span class="site-wordmark-food">Food</span><span class="site-logo-accent">Fusion</span></span>
          </a>

          <div class="registration-card" role="region" aria-label="Forgot password card">
            <h2 class="card-title">Forgot password</h2>
            <p class="card-subtitle">Use your registered email address.</p>

            <?php if ($errorMessage !== ''): ?>
              <div class="form-error" role="alert"><?php echo htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>

            <?php if ($successMessage !== ''): ?>
              <div class="banner banner--success" role="status"><?php echo htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8'); ?></div>
              <?php if ($resetLink !== ''): ?>
                <p class="helper-text auth-helper-link">
                  Demo link: <a href="<?php echo htmlspecialchars($resetLink, ENT_QUOTES, 'UTF-8'); ?>">Reset password now</a>
                </p>
              <?php endif; ?>
            <?php endif; ?>

            <form method="post" action="forgot_password.php" autocomplete="on" novalidate>
              <div class="form-grid">
                <div class="form-field full">
                  <label for="email">Email</label>
                  <input
                    class="input-box"
                    type="email"
                    id="email"
                    name="email"
                    placeholder="you@example.com"
                    required
                    value="<?php echo htmlspecialchars($emailField, ENT_QUOTES, 'UTF-8'); ?>"
                  />
                </div>
              </div>
              <button class="submit-btn" type="submit">Generate reset link</button>
              <p class="helper-text">Remembered it? <a href="login.php">Back to login</a></p>
            </form>
          </div>
        </div>
      </section>
    </main>
    <?php require __DIR__ . '/includes/cookie_bar.php'; ?>
  </body>
</html>

