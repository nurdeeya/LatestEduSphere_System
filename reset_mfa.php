<?php
session_start();

require_once __DIR__ . '/config/database.php';

// Allow this function only in the local development environment.
// Change this condition only if you deliberately configure a separate,
// protected test environment.
if (
    !in_array(
        $_SERVER['SERVER_NAME'] ?? '',
        ['localhost', '127.0.0.1'],
        true
    )
) {
    http_response_code(403);
    exit('This function is only available in the local development environment.');
}

// Development-only access control.
// This page is available only on localhost.
if (
    !in_array(
        $_SERVER['SERVER_NAME'] ?? '',
        ['localhost', '127.0.0.1'],
        true
    )
) {
    http_response_code(403);
    exit('Access denied. This function is for local testing only.');
}

// Generate a CSRF token.
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    $userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);

    if (
        !is_string($csrf) ||
        !hash_equals($_SESSION['csrf_token'], $csrf)
    ) {
        $error = 'Invalid request. Please refresh the page and try again.';
    } elseif (!$userId || $userId <= 0) {
        $error = 'Please select a valid test account.';
    } else {
        try {
            // Reset MFA only for the selected student or lecturer.
            $stmt = $pdo->prepare(
                'UPDATE users
                 SET mfa_enabled = 0,
                     mfa_secret = NULL
                 WHERE user_id = :user_id
                   AND role IN (:student_role, :lecturer_role)
                   AND is_active = 1'
            );

            $stmt->execute([
                'user_id' => $userId,
                'student_role' => 'student',
                'lecturer_role' => 'lecturer'
            ]);

            if ($stmt->rowCount() > 0) {
                $message = 'MFA has been reset for the selected test account.';
            } else {
                $error = 'No eligible account was updated. Check the account ID and its current MFA status.';
            }
        } catch (Throwable $e) {
            error_log((string) $e);
            $error = 'A system error occurred. Please try again.';
        }
    }
}

// Retrieve eligible test-account candidates.
// This page is restricted to local development and an authenticated admin.
$stmt = $pdo->query(
    "SELECT user_id, login_identifier, full_name, role, mfa_enabled
     FROM users
     WHERE role IN ('student', 'lecturer')
       AND is_active = 1
     ORDER BY role, login_identifier"
);

$accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset MFA - EduSphere</title>

    <link rel="stylesheet" href="css/reset_mfa_style.css">

</head>
<body>
    <main class="card">
        <h1>Reset MFA</h1>

        <p class="description">
            Use this development-only page to reset MFA for a student
            or lecturer test account. The selected account will need to
            configure MFA again during its next login.
        </p>

        <?php if ($message !== ''): ?>
            <div class="message" role="status">
                <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
            <div class="error" role="alert">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="reset_mfa.php"
              onsubmit="return confirm('Reset MFA for the selected test account? The account will need to set up MFA again.');">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars(
                    $_SESSION['csrf_token'],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
            >

            <label for="user_id">Select test account</label>

            <select name="user_id" id="user_id" required>
                <option value="">-- Choose an account --</option>

                <?php foreach ($accounts as $account): ?>
                    <option value="<?= (int) $account['user_id'] ?>">
                        <?= htmlspecialchars(
                            $account['login_identifier'] . ' — ' .
                            $account['full_name'] . ' (' .
                            $account['role'] . ') — MFA: ' .
                            ((int) $account['mfa_enabled'] === 1
                                ? 'Enabled'
                                : 'Disabled'),
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <button type="submit">Reset Selected Account</button>
        </form>

        <div class="warning">
            <strong>Warning:</strong> This page is intended for local
            development only. Do not deploy it as an unrestricted reset
            function on a production server.
        </div>
    </main>
</body>
</html>