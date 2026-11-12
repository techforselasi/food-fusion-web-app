<?php

require_once __DIR__ . '/db_config.php'; // Provides $conn (PDO)

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$errorMessage = '';

// Sticky form values (so the user doesn't lose everything on validation errors)
$firstName = '';
$lastName = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName  = trim($_POST['last_name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $password  = (string)($_POST['password'] ?? '');
    $confirm   = (string)($_POST['confirm_password'] ?? '');

    // Basic validation (clear, presentation-friendly rules)
    if ($firstName === '' || $lastName === '' || $email === '' || $password === '' || $confirm === '') {
        $errorMessage = 'Please fill in all required fields.';
    } elseif (!preg_match('/^[A-Za-z ]+$/', $firstName) || !preg_match('/^[A-Za-z ]+$/', $lastName)) {
        $errorMessage = 'Please enter a valid name using only letters.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMessage = 'Please enter a valid email address.';
    } elseif (strlen($password) < 8) {
        $errorMessage = 'Password must be at least 8 characters long.';
    } elseif ($password !== $confirm) {
        $errorMessage = 'Passwords do not match. Please try again.';
    } else {
        try {
            // Check if email already exists
            $checkStmt = $conn->prepare('SELECT 1 FROM users WHERE email = :email LIMIT 1');
            $checkStmt->execute([':email' => $email]);

            if ($checkStmt->fetchColumn()) {
                $errorMessage = 'An account with this email already exists. Please use a different email.';
            } else {
                // Hash password securely (Argon2i)
                $passwordHash = password_hash($password, PASSWORD_ARGON2I);

                if ($passwordHash === false) {
                    $errorMessage = 'We could not securely process your password. Please try again.';
                } else {
                    // Insert new user
                    $insertStmt = $conn->prepare(
                        'INSERT INTO users (first_name, last_name, email, user_password)
                         VALUES (:first_name, :last_name, :email, :user_password)'
                    );

                    $insertStmt->execute([
                        ':first_name'    => $firstName,
                        ':last_name'     => $lastName,
                        ':email'         => $email,
                        ':user_password' => $passwordHash,
                    ]);

                    header('Location: login.php');
                    exit;
                }
            }
        } catch (PDOException $e) {
            // Avoid leaking sensitive database details to end users
            $errorMessage = 'Registration failed due to a system error. Please try again shortly.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $errorMessage !== '' && !empty($_POST['signup_modal'])) {
    $_SESSION['register_flash'] = [
        'error' => $errorMessage,
        'first_name' => $firstName,
        'last_name' => $lastName,
        'email' => $email,
    ];
    header('Location: index.php#signup-modal');
    exit;
}
?>

<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>FoodFusion | Create Account</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link rel="stylesheet" href="style.css" />
  </head>

  <body>
    <main class="register-page register-page--form-left">
      <section class="image-section" aria-label="FoodFusion showcase image">
        <div class="image-branding">
          <div class="image-branding-content">
            <h1>Cook boldly.<br />Share beautifully.</h1>
            <p>
              Join FoodFusion to save recipes, explore cuisines, and build your own delicious collection.
            </p>
          </div>
        </div>
      </section>

      <section class="form-section" aria-label="Create your FoodFusion account">
        <div class="form-container">
          <a class="site-logo site-logo--form" href="index.php" aria-label="FoodFusion home">
            <img class="site-logo__img" src="assets/food_fusion_logo1.svg" alt="" width="156" height="122" decoding="async" />
            <span class="site-logo__wordmark"><span class="site-wordmark-food">Food</span><span class="site-logo-accent">Fusion</span></span>
          </a>

          <div class="registration-card" role="region" aria-label="Registration form card">
            <h2 class="card-title">Create your account</h2>
            <p class="card-subtitle">It only takes a minute to get started.</p>

            <?php if ($errorMessage !== ''): ?>
              <div class="form-error" role="alert">
                <?php echo htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?>
              </div>
            <?php endif; ?>

            <form method="post" action="register.php" autocomplete="on" novalidate>
              <div class="form-grid">
                <div class="form-field">
                  <label for="first_name">First name</label>
                  <input
                    class="input-box"
                    type="text"
                    id="first_name"
                    name="first_name"
                    placeholder=""
                    value="<?php echo htmlspecialchars($firstName, ENT_QUOTES, 'UTF-8'); ?>"
                    required
                  />
                </div>

                <div class="form-field">
                  <label for="last_name">Last name</label>
                  <input
                    class="input-box"
                    type="text"
                    id="last_name"
                    name="last_name"
                    placeholder=""
                    value="<?php echo htmlspecialchars($lastName, ENT_QUOTES, 'UTF-8'); ?>"
                    required
                  />
                </div>

                <div class="form-field full">
                  <label for="email">Email</label>
                  <input
                    class="input-box"
                    type="email"
                    id="email"
                    name="email"
                    placeholder="you@example.com"
                    value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>"
                    required
                  />
                </div>

                <div class="form-field full">
                  <label for="password">Password</label>
                  <div class="password-field-wrap">
                    <input
                      class="input-box"
                      type="password"
                      id="password"
                      name="password"
                      placeholder="At least 8 characters"
                      required
                    />
                    <button type="button" class="password-toggle" id="password-toggle" aria-controls="password" aria-pressed="false">Show</button>
                  </div>
                </div>

                <div class="form-field full">
                  <label for="confirm_password">Confirm password</label>
                  <div class="password-field-wrap">
                    <input
                      class="input-box"
                      type="password"
                      id="confirm_password"
                      name="confirm_password"
                      placeholder="Re-enter your password"
                      required
                    />
                    <button type="button" class="password-toggle" id="confirm-password-toggle" aria-controls="confirm_password" aria-pressed="false">Show</button>
                  </div>
                </div>
              </div>

              <button class="submit-btn" type="submit">Create account</button>

              <p class="helper-text">
                Already have an account? <a href="login.php">Log in</a>
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
