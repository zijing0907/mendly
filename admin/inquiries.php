<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

// 处理状态更新
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $inquiryId = (int)$_POST['inquiry_id'];
    $newStatus = $_POST['status'];

    $updateStmt = mysqli_prepare($conn, "UPDATE inquiries SET status = ? WHERE inquiry_id = ?");
    mysqli_stmt_bind_param($updateStmt, "si", $newStatus, $inquiryId);
    mysqli_stmt_execute($updateStmt);
    mysqli_stmt_close($updateStmt);

    header("Location: inquiries.php?updated=1");
    exit;
}

// 处理类型筛选 (Suggestion / Complaint / Question)
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
    <title>Admin - Inquiry Management</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 30px;
            background: #f4f6f9;
            color: #333;
        }

        .container {
            background: #fff;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        h2 {
            margin-top: 0;
            color: #2c3e50;
        }

        .filters {
            margin-bottom: 20px;
        }

        .filter-btn {
            padding: 8px 16px;
            text-decoration: none;
            background: #e9ecef;
            color: #495057;
            border-radius: 4px;
            margin-right: 8px;
            font-weight: 500;
        }

        .filter-btn.active {
            background: #4a69bd;
            color: #fff;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        th,
        td {
            padding: 12px 15px;
            border-bottom: 1px solid #e9ecef;
            text-align: left;
        }

        th {
            background: #f8f9fa;
            color: #6c757d;
            font-size: 14px;
            text-transform: uppercase;
        }

        .badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: bold;
        }

        .role-customer {
            background: #e3f2fd;
            color: #1976d2;
        }

        .role-expert {
            background: #f3e5f5;
            color: #7b1fa2;
        }

        .role-guest {
            background: #eee;
            color: #616161;
        }

        .status-Pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-In-Progress {
            background: #cce5ff;
            color: #004085;
        }

        .status-Resolved {
            background: #d4edda;
            color: #155724;
        }

        select {
            padding: 4px 8px;
            border-radius: 4px;
            border: 1px solid #ccc;
        }

        .btn-update {
            padding: 4px 10px;
            background: #2ed573;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
    </style>
</head>

<body>

    <div class="container">
        <h2>Admin Center: Customer & Expert Inquiries</h2>

        <!-- 筛选工具栏 -->
        <div class="filters">
            <strong>Filter Subject: </strong>
            <a href="inquiries.php?type=All" class="filter-btn <?= $filterType === 'All' ? 'active' : '' ?>">All</a>
            <a href="inquiries.php?type=Suggestion" class="filter-btn <?= $filterType === 'Suggestion' ? 'active' : '' ?>">Suggestions</a>
            <a href="inquiries.php?type=Complaint" class="filter-btn <?= $filterType === 'Complaint' ? 'active' : '' ?>">Complaints</a>
            <a href="inquiries.php?type=Question" class="filter-btn <?= $filterType === 'Question' ? 'active' : '' ?>">Questions</a>
        </div>

        <!-- 数据列表 -->
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
                                    <?= $row['user_type'] ?>
                                </span>
                            </td>
                            <td><strong><?= htmlspecialchars($row['subject_type']) ?></strong></td>
                            <td style="max-width: 300px;"><?= nl2br(htmlspecialchars($row['message'])) ?></td>
                            <td>
                                <span class="badge status-<?= str_replace(' ', '-', $row['status']) ?>">
                                    <?= $row['status'] ?>
                                </span>
                            </td>
                            <td>
                                <form method="POST" action="inquiries.php" style="display: flex; gap: 5px;">
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