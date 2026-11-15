<?php
session_start();

$pageTitle = "My Repair Requests";

include 'includes/db.php';

// 登录校验：优先读取 Session，未登录则跳转（兼顾开发环境调试）
if (!isset($_SESSION['user_id'])) {
    // 如果你在本地开发测试，可临时解除下一句注释并设定固定 ID
    // $_SESSION['user_id'] = 1; 
    header("Location: login.php");
    exit();
}

$customerID = (int)$_SESSION['user_id'];

// 查询当前 Customer 的所有 Repair Requests
$sql = "
    SELECT 
        rq.request_id,
        rq.item_name,
        rq.repair_description,
        rq.quoted_price,
        rq.status,
        rq.request_date,
        s.service_id,
        s.title AS service_title,
        s.starting_price,
        e.full_name AS expert_name,
        e.business_name,
        (
            SELECT image_path 
            FROM service_images 
            WHERE service_id = s.service_id 
            ORDER BY display_order ASC 
            LIMIT 1
        ) AS service_thumbnail
    FROM repair_requests rq
    INNER JOIN services s ON rq.service_id = s.service_id
    INNER JOIN experts e ON s.expert_id = e.expert_id
    WHERE rq.customer_id = ?
    ORDER BY rq.request_id DESC
";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $customerID);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$requests = [];
while ($row = mysqli_fetch_assoc($result)) {
    $requests[] = $row;
}

// 辅助函数：格式化 Status 显示名称和 CSS 类
function getStatusBadge($status = 'Pending')
{
    $currentStatus = trim($status ?? 'Pending');
    switch ($currentStatus) {
        case 'Accepted':
            return ['label' => 'Accepted', 'class' => 'badge-accepted'];
        case 'In Progress':
            return ['label' => 'In Progress', 'class' => 'badge-progress'];
        case 'Ready for Collection':
            return ['label' => 'Ready for Collection', 'class' => 'badge-ready'];
        case 'Completed':
            return ['label' => 'Completed', 'class' => 'badge-completed'];
        case 'Cancelled':
            return ['label' => 'Cancelled', 'class' => 'badge-cancelled'];
        case 'Pending':
        default:
            return ['label' => 'Pending', 'class' => 'badge-pending'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="assets/css/global.css">
    <link rel="stylesheet" href="assets/css/my-requests.css">
</head>

<body>

    <?php
    if (file_exists("includes/header.php")) {
        include "includes/header.php";
    }
    ?>

    <main class="my-requests-container">

        <div class="page-header">
            <h1>My Repair Requests</h1>
            <p>Track and manage your submitted repair service requests.</p>
        </div>

        <!-- 刚提交请求成功时显示的绿色 Alert 提示框 -->
        <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
            <div class="alert-success">
                🎉 <strong>Success!</strong> Your repair request has been submitted. The expert will review it shortly.
            </div>
        <?php endif; ?>

        <!-- 列表展示 -->
        <?php if (!empty($requests)): ?>
            <?php foreach ($requests as $req): ?>
                <?php
                $badge = getStatusBadge($req['status']);
                $thumbnail = !empty($req['service_thumbnail']) ? $req['service_thumbnail'] : 'assets/images/default-service.jpg';
                $createdDate = date('M d, Y', strtotime($req['request_date']));
                ?>
                <div class="request-card">
                    <!-- 卡片顶部：物品信息与状态 -->
                    <div class="card-header">
                        <div class="request-info">
                            <strong><?php echo htmlspecialchars($req['item_name']); ?></strong>
                            <?php if (!empty($req['brand'])): ?>
                                <span>(<?php echo htmlspecialchars($req['brand']); ?>)</span>
                            <?php endif; ?>
                            &bull; <small>Submitted on <?php echo $createdDate; ?></small>
                        </div>
                        <span class="status-badge <?php echo $badge['class']; ?>">
                            <?php echo $badge['label']; ?>
                        </span>
                    </div>

                    <!-- 卡片主体：服务细节 -->
                    <div class="card-body">
                        <img
                            src="<?php echo htmlspecialchars($thumbnail); ?>"
                            alt="<?php echo htmlspecialchars($req['service_title']); ?>"
                            class="service-img"
                            onerror="this.src='assets/images/default-service.jpg';">
                        <div class="details-wrapper">
                            <h3 class="service-title"><?php echo htmlspecialchars($req['service_title']); ?></h3>
                            <div class="expert-meta">
                                Handled by <strong><?php echo htmlspecialchars($req['expert_name']); ?></strong> &bull;
                                Starting Price: <strong>RM <?php echo number_format($req['starting_price'], 2); ?></strong>
                            </div>

                            <div class="desc-box">
                                <strong>Problem Description:</strong><br>
                                <?php echo nl2br(htmlspecialchars($req['repair_description'])); ?>

                                <?php if (!empty($req['additional_notes'])): ?>
                                    <br><br><strong>Additional Notes:</strong><br>
                                    <?php echo nl2br(htmlspecialchars($req['additional_notes'])); ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <!-- 没有数据时的空状态 -->
            <div class="empty-state">
                <h3>No Repair Requests Found</h3>
                <p>You haven't submitted any repair requests yet.</p>
                <a href="browse-services.php" class="btn-browse">Browse Repair Services</a>
            </div>
        <?php endif; ?>

    </main>

</body>

</html>