<?php
declare(strict_types=1);

require_once __DIR__ . '/db_config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$token = trim((string) ($_GET['token'] ?? $_POST['token'] ?? ''));
$errorMessage = '';
$successMessage = '';
$isTokenValid = false;
$resetRow = null;

if ($token === '') {
    $errorMessage = 'Missing or invalid reset token.';
} elseif (!ff_table_exists_local($conn, 'password_resets')) {
    $errorMessage = 'Password reset is not available yet. Run the latest SQL schema first.';
} else {
    $tokenHash = hash('sha256', $token);
    try {
        $stmt = $conn->prepare(
            'SELECT pr.reset_id, pr.user_id, pr.expires_at, pr.used_at, u.email
             FROM password_resets pr
             INNER JOIN users u ON u.user_id = pr.user_id
             WHERE pr.token_hash = :hash
             LIMIT 1'
        );
        $stmt->execute([':hash' => $tokenHash]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row !== false) {
            $isUnused = empty($row['used_at']);
            $isNotExpired = !empty($row['expires_at']) && strtotime((string) $row['expires_at']) > time();
            if ($isUnused && $isNotExpired) {
                $isTokenValid = true;
                $resetRow = $row;
            } else {
                $errorMessage = 'This reset link has expired or was already used.';
            }
        } else {
            $errorMessage = 'This reset link is invalid.';
        }
    } catch (PDOException $e) {
        error_log('FoodFusion reset_password lookup: ' . $e->getMessage());
        $errorMessage = 'Could not validate reset link right now. Please try again shortly.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isTokenValid && $resetRow !== null) {
    $password = (string) ($_POST['password'] ?? '');
    $confirm = (string) ($_POST['confirm_password'] ?? '');

    if ($password === '' || $confirm === '') {
        $errorMessage = 'Please fill in both password fields.';
    } elseif (strlen($password) < 8) {
        $errorMessage = 'Password must be at least 8 characters long.';
    } elseif ($password !== $confirm) {
        $errorMessage = 'Passwords do not match.';
    } else {
        $hash = password_hash($password, PASSWORD_ARGON2I);
        if ($hash === false) {
            $errorMessage = 'Could not process your new password. Please try again.';
        } else {
            try {
                $conn->beginTransaction();

                $updUser = $conn->prepare(
                    'UPDATE users
                     SET user_password = :pwd, failed_attempts = 0, locked_until = NULL
                     WHERE user_id = :uid'
                );
                $updUser->execute([
                    ':pwd' => $hash,
                    ':uid' => (int) $resetRow['user_id'],
                ]);

                $markUsed = $conn->prepare(
                    'UPDATE password_resets
                     SET used_at = NOW()
                     WHERE reset_id = :rid'
                );
                $markUsed->execute([':rid' => (int) $resetRow['reset_id']]);

                $invalidateOthers = $conn->prepare(
                    'UPDATE password_resets
                     SET used_at = NOW()
                     WHERE user_id = :uid AND used_at IS NULL AND reset_id <> :rid'
                );
                $invalidateOthers->execute([
                    ':uid' => (int) $resetRow['user_id'],
                    ':rid' => (int) $resetRow['reset_id'],
                ]);

                $conn->commit();
                $successMessage = 'Your password has been reset successfully. You can now log in.';
                $isTokenValid = false;
            } catch (PDOException $e) {
                if ($conn->inTransaction()) {
                    $conn->rollBack();
                }
                error_log('FoodFusion reset_password update: ' . $e->getMessage());
                $errorMessage = 'Could not reset password right now. Please try again shortly.';
            }
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
    <title>FoodFusion | Reset password</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link rel="stylesheet" href="style.css" />
  </head>
  <body>
    <main class="login-page">
      <section class="image-section" aria-label="FoodFusion showcase image">
        <div class="image-branding">
          <div class="image-branding-content">
            <h1>Choose a new password.</h1>
            <p>For account security, this link works once and expires in 30 minutes.</p>
          </div>
        </div>
      </section>

      <section class="form-section" aria-label="Reset password form">
        <div class="form-container">
          <a class="site-logo site-logo--form" href="index.php" aria-label="FoodFusion home">
            <img class="site-logo__img" src="assets/food_fusion_logo1.svg" alt="" width="156" height="122" decoding="async" />
            <span class="site-logo__wordmark"><span class="site-wordmark-food">Food</span><span class="site-logo-accent">Fusion</span></span>
          </a>

          <div class="registration-card" role="region" aria-label="Reset password card">
            <h2 class="card-title">Reset password</h2>
            <p class="card-subtitle">Create a new password for your account.</p>

            <?php if ($errorMessage !== ''): ?>
              <div class="form-error" role="alert"><?php echo htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>

            <?php if ($successMessage !== ''): ?>
              <div class="banner banner--success" role="status"><?php echo htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8'); ?></div>
              <p class="helper-text"><a href="login.php">Continue to login</a></p>
            <?php endif; ?>

            <?php if ($isTokenValid): ?>
              <form method="post" action="reset_password.php" autocomplete="on" novalidate>
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token, ENT_QUOTES, 'UTF-8'); ?>" />
                <div class="form-grid">
                  <div class="form-field full">
                    <label for="password">New password</label>
                    <div class="password-field-wrap">
                      <input class="input-box" type="password" id="password" name="password" placeholder="At least 8 characters" required minlength="8" />
                      <button type="button" class="password-toggle" id="password-toggle" aria-controls="password" aria-pressed="false">Show</button>
                    </div>
                  </div>
                  <div class="form-field full">
                    <label for="confirm_password">Confirm password</label>
                    <div class="password-field-wrap">
                      <input class="input-box" type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter password" required minlength="8" />
                      <button type="button" class="password-toggle" id="confirm-password-toggle" aria-controls="confirm_password" aria-pressed="false">Show</button>
                    </div>
                  </div>
                </div>
                <button class="submit-btn" type="submit">Save new password</button>
                <p class="helper-text"><a href="login.php">Back to login</a></p>
              </form>
            <?php endif; ?>
          </div>
        </div>
      </section>
    </main>

    <?php require __DIR__ . '/includes/cookie_bar.php'; ?>
    <script src="script.js" defer></script>
  </body>
</html>

