<?php
session_start();
$pageTitle = "Meet Our Experts";

include 'includes/db.php';

// 获取当前选中的分类 (从 URL 参数获取，默认为 'all')
$selectedCategory = $_GET['category'] ?? 'all';

// 定义三大固定分类，便于导航渲染
$categories = [
    'all' => 'ALL',
    'textiles' => 'TEXTILES & APPAREL',
    'toys' => 'TOYS & MEMORABILIA',
    'leather' => 'LEATHER & ACCESSORIES'
];

// 定义大分类与数据库中散乱 specialisation 关键词的映射关系
$categoryMap = [
    'textiles' => ['clothing', 'textile', 'apparel', 'fabric', 'dress', 'garment', 'tailor', 'sewing'],
    'toys'     => ['doll', 'toy', 'book', 'figurine', 'memorabilia', 'collectible', 'plush', 'game'],
    'leather'  => ['leather', 'shoe', 'bag', 'belt', 'accessory', 'accessories', 'wallet', 'footwear']
];

// 构建 SQL 查询：提取所有专家信息
$sql = "SELECT 
            expert_id, 
            full_name, 
            business_name, 
            specialisation, 
            years_experience, 
            completed_repairs, 
            bio, 
            photo_path, 
            avatar, 
            verified 
        FROM experts 
        ORDER BY expert_id DESC";

$result = mysqli_query($conn, $sql);
$experts = [];

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $experts[] = $row;
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
    <link rel="stylesheet" href="assets/css/experts.css">
</head>

<body>

    <?php
    if (file_exists("includes/header.php")) {
        include "includes/header.php";
    }
    ?>

    <main class="experts-page-container">

        <!-- 顶部标题区域 -->
        <div class="page-header">
            <h1>Meet Our Experts</h1>
            <p>Connect with master craftspeople dedicated to restoring your cherished possessions.</p>
        </div>

        <!-- 专属 Category Nav (Horizontal) -->
        <div class="expert-category-nav">
            <?php foreach ($categories as $key => $label): ?>
                <a
                    href="experts.php?category=<?php echo $key; ?>"
                    class="cat-btn <?php echo ($selectedCategory === $key) ? 'active' : ''; ?>">
                    <?php echo htmlspecialchars($label); ?>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- 专家卡片列表 -->
        <div class="experts-list">
            <?php
            $hasVisibleExperts = false;

            foreach ($experts as $expert):
                $specLower = strtolower(trim($expert['specialisation'] ?? ''));

                $isMatch = false;

                if ($selectedCategory === 'all') {
                    $isMatch = true;
                } elseif (isset($categoryMap[$selectedCategory])) {
                    // 检查专家的 specialisation 是否包含该大类下的任意一个子关键词
                    foreach ($categoryMap[$selectedCategory] as $keyword) {
                        if (strpos($specLower, $keyword) !== false) {
                            $isMatch = true;
                            break; // 匹配到一个立刻通过
                        }
                    }
                }

                if (!$isMatch) continue;

                $hasVisibleExperts = true;
                $photo = !empty($expert['photo_path']) ? $expert['photo_path'] : (!empty($expert['avatar']) ? $expert['avatar'] : 'assets/images/default-expert.jpg');
            ?>
                <div class="expert-card">
                    <div class="expert-img-wrapper">
                        <img
                            src="<?php echo htmlspecialchars($photo); ?>"
                            alt="<?php echo htmlspecialchars($expert['business_name']); ?>"
                            class="expert-img"
                            onerror="this.src='assets/images/default-expert.jpg';">
                        <?php if (!empty($expert['verified'])): ?>
                            <span class="verified-badge">✓ Verified</span>
                        <?php endif; ?>
                    </div>

                    <div class="expert-content">
                        <div>
                            <div class="expert-header">
                                <div class="expert-title-area">
                                    <h2><?php echo htmlspecialchars($expert['business_name'] ?: $expert['full_name']); ?></h2>
                                    <p class="expert-name">By <?php echo htmlspecialchars($expert['full_name']); ?></p>
                                </div>
                                <span class="specialisation-tag">
                                    <?php echo htmlspecialchars($expert['specialisation']); ?>
                                </span>
                            </div>

                            <div class="expert-stats">
                                <span class="stat-item">
                                    🏋️‍♂️ <strong><?php echo (int)($expert['years_experience'] ?? 0); ?></strong> Years Experience
                                </span>
                                <span class="stat-item">
                                    🛠️ <strong><?php echo (int)($expert['completed_repairs'] ?? 0); ?></strong> Repairs Completed
                                </span>
                            </div>

                            <p class="expert-bio">
                                <?php
                                $bioText = $expert['bio'] ?? $expert['description'] ?? 'No bio provided yet.';
                                // 超过 120 个字符就自动截断并补上 ...
                                echo htmlspecialchars(mb_strimwidth($bioText, 0, 120, '...'));
                                ?>
                            </p>
                        </div>

                        <div class="card-footer">
                            <!-- 点击跳转至该专家的专属服务列表 -->
                            <a href="expert-services.php?expert_id=<?php echo $expert['expert_id']; ?>" class="btn-view-services">
                                View Expert's Services &rarr;
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if (!$hasVisibleExperts): ?>
                <div class="empty-experts">
                    <h3>No experts found in this category.</h3>
                    <p>Try switching to another category to view our artisans.</p>
                </div>
            <?php endif; ?>
        </div>

    </main>

</body>

</html>