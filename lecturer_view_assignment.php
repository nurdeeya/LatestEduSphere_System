<?php
session_start();
require_once 'config/security.php';
require_once 'config/database.php';

if (
    !isset($_SESSION['user_id'], $_SESSION['role'], $_SESSION['auth_complete']) ||
    $_SESSION['role'] !== 'lecturer' ||
    $_SESSION['auth_complete'] !== true
) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$assignment_id = $_GET['id'] ?? 0;

if ($assignment_id === 0) {
    die("Error: No assignment selected.");
}

try {
    // Ambil SEMUA maklumat assignment dari database
    $stmt = $pdo->prepare("SELECT * FROM assignments WHERE id = :id AND created_by = :user_id");
    $stmt->execute([':id' => $assignment_id, ':user_id' => $user_id]);
    $assignment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$assignment) {
        die("Error: Assignment not found or you do not have permission to view it.");
    }
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Assignment - EduSphere</title>
    <link rel="stylesheet" href="css/lecturer_dashboard_style.css">
    <style>
        .detail-box { background: #f8fafc; padding: 20px; border-radius: 8px; border: 1px solid #e2e8f0; margin-bottom: 20px; }
        .detail-label { font-size: 13px; color: #64748b; font-weight: bold; text-transform: uppercase; margin-bottom: 5px; }
        .detail-value { font-size: 16px; color: #1e293b; margin-bottom: 15px; }
        .btn-download { display: inline-block; padding: 10px 16px; background-color: #243b64; color: white; text-decoration: none; border-radius: 6px; font-weight: bold; font-size: 14px; }
    </style>
</head>
<body>

    <header class="navbar">
        <div class="brand">EduSphere</div>
        <div class="navbar-right">
            <span class="role-label">Lecturer Account</span>
            <a class="logout-btn" href="logout.php">Log Out</a>
        </div>
    </header>

    <main class="container">
        <section class="welcome-section">
            <h1>Assignment Details</h1>
            <p>View the full details and files for this assignment.</p>
        </section>

        <section class="information" style="max-width: 700px;">
            
            <div class="detail-box">
                <div class="detail-label">Assignment Title</div>
                <div class="detail-value"><strong><?= escape($assignment['title']) ?></strong></div>

                <div class="detail-label">Description</div>
                <div class="detail-value">
                    <?= !empty($assignment['description']) ? nl2br(escape($assignment['description'])) : '<em style="color: #94a3b8;">No description provided.</em>' ?>
                </div>

                <div class="detail-label">Deadline</div>
                <div class="detail-value"><?= date('d M Y, h:i A', strtotime($assignment['deadline'])) ?></div>

                <div class="detail-label">Attached File</div>
                <div class="detail-value">
                    <?php if (!empty($assignment['file_path'])): ?>
                        <a href="uploads/<?= escape($assignment['file_path']) ?>" target="_blank" class="btn-download">Download File</a>
                    <?php else: ?>
                        <em style="color: #94a3b8;">No file attached.</em>
                    <?php endif; ?>
                </div>
            </div>

            <a href="lecturer_create_assignment.php" style="color: #64748b; text-decoration: none;">&larr; Back to Manage Assignments</a>
        </section>
    </main>

</body>
</html>