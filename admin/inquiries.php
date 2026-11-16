<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

// Admins only. This page lists every contact-form message with the
// sender's name and email, so it must never be reachable anonymously.
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$allowedStatuses = ['Pending', 'In Progress', 'Resolved'];

// Status update from the per-row form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $inquiryId = (int) ($_POST['inquiry_id'] ?? 0);
    $newStatus = $_POST['status'] ?? '';

    if (!in_array($newStatus, $allowedStatuses, true)) {
        header("Location: inquiries.php");
        exit;
    }

    $updateStmt = mysqli_prepare($conn, "UPDATE inquiries SET status = ? WHERE inquiry_id = ?");
    mysqli_stmt_bind_param($updateStmt, "si", $newStatus, $inquiryId);
    mysqli_stmt_execute($updateStmt);
    mysqli_stmt_close($updateStmt);

    header("Location: inquiries.php?updated=1");
    exit;
}

// Optional filter by subject (Suggestion / Complaint / Question)
$filterType = $_GET['type'] ?? 'All';
if ($filterType !== 'All' && in_array($filterType, ['Suggestion', 'Complaint', 'Question'])) {
    $stmt = mysqli_prepare($conn, "SELECT * FROM inquiries WHERE subject_type = ? ORDER BY created_at DESC");
    mysqli_stmt_bind_param($stmt, "s", $filterType);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
} else {
    $result = mysqli_query($conn, "SELECT * FROM inquiries ORDER BY created_at DESC");
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Inquiry Management</title>
    <link rel="stylesheet" href="../<?php echo asset('assets/css/admin.css'); ?>">
</head>

<body>

    <div class="container">
        <?php $adminPage = 'inquiries'; include __DIR__ . '/../includes/admin-nav.php'; ?>

        <h2>Customer &amp; Expert Inquiries</h2>

        <?php if (isset($_GET['updated'])): ?>
            <p class="notice">Status updated.</p>
        <?php endif; ?>

        <div class="filters">
            <strong>Filter Subject: </strong>
            <a href="inquiries.php?type=All" class="filter-btn <?= $filterType === 'All' ? 'active' : '' ?>">All</a>
            <a href="inquiries.php?type=Suggestion" class="filter-btn <?= $filterType === 'Suggestion' ? 'active' : '' ?>">Suggestions</a>
            <a href="inquiries.php?type=Complaint" class="filter-btn <?= $filterType === 'Complaint' ? 'active' : '' ?>">Complaints</a>
            <a href="inquiries.php?type=Question" class="filter-btn <?= $filterType === 'Question' ? 'active' : '' ?>">Questions</a>
        </div>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>User Details</th>
                    <th>User Identity</th>
                    <th>Subject</th>
                    <th>Message</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($result) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td>#<?= $row['inquiry_id'] ?></td>
                            <td>
                                <strong><?= htmlspecialchars($row['full_name']) ?></strong><br>
                                <small style="color: #777;"><?= htmlspecialchars($row['email']) ?></small>
                            </td>
                            <td>
                                <span class="badge role-<?= strtolower($row['user_type']) ?>">
                                    <?= htmlspecialchars($row['user_type']) ?>
                                </span>
                            </td>
                            <td><strong><?= htmlspecialchars($row['subject_type']) ?></strong></td>
                            <td style="max-width: 300px;"><?= nl2br(htmlspecialchars($row['message'])) ?></td>
                            <td>
                                <span class="badge status-<?= str_replace(' ', '-', $row['status']) ?>">
                                    <?= htmlspecialchars($row['status']) ?>
                                </span>
                            </td>
                            <td>
                                <form method="POST" action="inquiries.php" style="display: flex; gap: 5px;">
                                    <?php echo csrfField(); ?>
                                    <input type="hidden" name="inquiry_id" value="<?= $row['inquiry_id'] ?>">
                                    <select name="status">
                                        <option value="Pending" <?= $row['status'] === 'Pending' ? 'selected' : '' ?>>Pending</option>
                                        <option value="In Progress" <?= $row['status'] === 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                                        <option value="Resolved" <?= $row['status'] === 'Resolved' ? 'selected' : '' ?>>Resolved</option>
                                    </select>
                                    <button type="submit" name="update_status" class="btn-update">Save</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align: center;">No inquiries found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</body>

</html>