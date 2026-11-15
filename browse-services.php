<?php

    // 初始化 Session、页面标题和数据库连接
    // Initialize session, page title, and database connection
    session_start();
    $pageTitle = 'Browse Services';
    include 'includes/db.php';

    // 获取并处理分页、搜索、筛选和排序参数
    // Get and process pagination, search, filter, and sorting parameters
    $perPage     = 12;
    $page        = max(1, (int) ($_GET['page'] ?? 1));
    $search      = trim($_GET['search'] ?? '');
    $categoryIds = [];
    if (isset($_GET['category'])) {
    $categoryIds = array_map('intval', (array) $_GET['category']);
    $categoryIds = array_values(array_filter($categoryIds, function ($id) {
        return $id > 0;
    }));
    }
    $location  = trim($_GET['location'] ?? '');
    $minPrice  = max(0, (float) ($_GET['min_price'] ?? 0));
    $maxPrice  = max(0, (float) ($_GET['max_price'] ?? 0));
    $minRating = max(0, min(5, (float) ($_GET['rating'] ?? 0)));
    $sort      = $_GET['sort'] ?? 'newest';
    // 定义服务列表的排序方式
    // Define sorting options for the service list
    $sortSql = [
    'rating'     => 'average_rating DESC, s.created_at DESC',
    'price_low'  => 's.starting_price ASC, s.created_at DESC',
    'price_high' => 's.starting_price DESC, s.created_at DESC',
    'newest'     => 's.created_at DESC',
    ];
    // 防止无效的排序参数
    // Prevent invalid sorting parameters
    if (! isset($sortSql[$sort])) {
    $sort = 'newest';
    }

    // 建立动态 SQL 查询条件及参数
    // Build dynamic SQL conditions and parameters
    $where  = ["s.status = 'active'"];
    $types  = '';
    $params = [];

    if ($search !== '') {
    $where[]   = '(s.title LIKE ? OR s.description LIKE ?)';
    $types    .= 'ss';
    $params[]  = "%$search%";
    $params[]  = "%$search%";
    }
    if (! empty($categoryIds)) {
    $placeholders  = implode(',', array_fill(0, count($categoryIds), '?'));
    $where[]       = "s.category_id IN ($placeholders)";
    $types        .= str_repeat('i', count($categoryIds));
    foreach ($categoryIds as $categoryId) {
        $params[] = $categoryId;
    }
    }
    if ($location !== '') {
    $where[]   = 'u.location = ?';
    $types    .= 's';
    $params[]  = $location;
    }
    if ($minPrice > 0) {
    $where[]   = 's.starting_price >= ?';
    $types    .= 'd';
    $params[]  = $minPrice;
    }
    if ($maxPrice > 0) {
    $where[]   = 's.starting_price <= ?';
    $types    .= 'd';
    $params[]  = $maxPrice;
    }
    if ($minRating > 0) {
    $where[]   = 'COALESCE(review_summary.average_rating, 0) >= ?';
    $types    .= 'd';
    $params[]  = $minRating;
    }
    $whereSql = implode(' AND ', $where);

    // 汇总每个服务的平均评分和评论数量
    // Calculate average rating and total reviews for each service
    $reviewJoin = "LEFT JOIN (
    SELECT rq.service_id, ROUND(AVG(r.rating), 1) AS average_rating, COUNT(r.review_id) AS total_reviews
    FROM repair_requests rq INNER JOIN reviews r ON r.request_id = rq.request_id GROUP BY rq.service_id
) review_summary ON review_summary.service_id = s.service_id";

    // 查询符合筛选条件的服务总数，用于计算分页
    // Count filtered services to calculate pagination
    $countSql   = "SELECT COUNT(*) AS total FROM services s INNER JOIN experts u ON s.expert_id = u.expert_id $reviewJoin WHERE $whereSql";
    $countStmt  = mysqli_prepare($conn, $countSql);
    if ($types !== '') {
    mysqli_stmt_bind_param($countStmt, $types, ...$params);
    }

    mysqli_stmt_execute($countStmt);
    // 计算总页数、当前页和 SQL 查询偏移量
    // Calculate total pages, current page, and SQL offset
    $totalServices = (int) mysqli_fetch_assoc(mysqli_stmt_get_result($countStmt))['total'];
    $totalPages    = max(1, (int) ceil($totalServices / $perPage));
    $page          = min($page, $totalPages);
    $offset        = ($page - 1) * $perPage;

    // 只有 Customer 可以收藏服务，避免 Expert ID 被错误匹配到 favorites.customer_id
    // Only customers have favorites — an expert browsing services shouldn't
    // have their expert_id accidentally matched against favorites.customer_id
    $currentUserId  = (($_SESSION['role'] ?? '') === 'customer') ? (int) ($_SESSION['user_id'] ?? 0) : 0;
    $favoriteJoin   = $currentUserId > 0 ? 'LEFT JOIN favorites f ON f.service_id = s.service_id AND f.customer_id = ?' : '';
    $favoriteColumn = $currentUserId > 0 ? ', f.favorite_id IS NOT NULL AS is_favorite' : ', 0 AS is_favorite';
    $sql            = "SELECT s.service_id, s.title, s.starting_price, s.estimated_duration, u.full_name, u.location,
    c.category_name,
    (SELECT image_path FROM service_images WHERE service_id = s.service_id ORDER BY display_order LIMIT 1) AS thumbnail,
    review_summary.average_rating, COALESCE(review_summary.total_reviews, 0) AS total_reviews $favoriteColumn
    FROM services s INNER JOIN experts u ON s.expert_id = u.expert_id
    INNER JOIN categories c ON s.category_id = c.category_id $reviewJoin $favoriteJoin
    WHERE $whereSql ORDER BY {$sortSql[$sort]} LIMIT ? OFFSET ?";
    $stmt          = mysqli_prepare($conn, $sql);
    $serviceTypes  = ($currentUserId > 0 ? 'i' : '') . $types . 'ii';
    $serviceParams = $currentUserId > 0 ? array_merge([$currentUserId], $params, [$perPage, $offset]) : array_merge($params, [$perPage, $offset]);
    mysqli_stmt_bind_param($stmt, $serviceTypes, ...$serviceParams);
    mysqli_stmt_execute($stmt);
    $result         = mysqli_stmt_get_result($stmt);
    $categoryResult = mysqli_query($conn, 'SELECT category_id, category_name FROM categories ORDER BY category_id');
    $locationResult = mysqli_query($conn, "SELECT location_name FROM locations ORDER BY location_name");

    function browseUrl($overrides = [])
    {
    return '?' . http_build_query(array_merge($_GET, $overrides));
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Browse Services | Mendly</title>

    <link rel="stylesheet" href="assets/css/global.css">
    <link rel="stylesheet" href="assets/css/browse-services.css">
</head>

<body>

<?php include "includes/header.php"; ?>

<main class="browse-container">

    <!-- Breadcrumb navigation -->
    <nav class="breadcrumb" aria-label="Breadcrumb">
        <a href="index.php">Home</a>
        <span>&rsaquo;</span>
        <span class="breadcrumb-current">Browse Services</span>
    </nav>

    <!-- Page title and description -->
    <div class="browse-heading">
        <h2>Browse Repair Services <span class="heading-leaf">🔍</span></h2>
        <p>Find a skilled local expert for the repair your item needs.</p>
    </div>

    <!-- Search -->
    <div class="search-area">

        <form method="get" class="search-form">
            <label class="search-label" for="searchInput">Search services</label>
            <input id="searchInput" type="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search repair services...">

            <!-- Keep current filters when searching -->
            <?php foreach ($categoryIds as $selectedCategoryId) {?>
                <input type="hidden" name="category[]" accept="" value="<?php echo $selectedCategoryId; ?>">
            <?php }?>
            <input type="hidden" name="location" value="<?php echo htmlspecialchars($location); ?>">
            <input type="hidden" name="min_price" value="<?php echo $minPrice ?: ''; ?>">
            <input type="hidden" name="max_price" value="<?php echo $maxPrice ?: ''; ?>">
            <input type="hidden" name="rating" value="<?php echo $minRating ?: ''; ?>">
            <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort); ?>">

            <button class="search-button" type="submit">Search</button>

            <?php if ($search !== '') {?>
                <a class="clear-search" href="<?php echo browseUrl(['search' => '', 'page' => 1]); ?>">
                    Clear
                </a>
            <?php }?>
        </form>

    </div>

    <div class="browse-layout">

        <!-- Left-side filter panel -->
        <aside class="filter-panel">

            <div class="filter-heading">
                <span aria-hidden="true">⌕</span>
                <h3>Filter</h3>
                <button class="filter-toggle" type="button" aria-expanded="false" aria-controls="filterForm">
                    <span aria-hidden="true">⌄</span>
                </button>
            </div>

            <!-- Service filter form -->
            <form method="get" class="filters" id="filterForm">

                <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">

                <!-- Category filter -->
                <fieldset>
                    <legend>Category</legend>

                    <?php while ($category = mysqli_fetch_assoc($categoryResult)) {?>
                        <label class="check-option">
                            <input
                                type="checkbox"
                                name="category[]"
                                value="<?php echo $category['category_id']; ?>"
                                <?php echo in_array((int) $category['category_id'], $categoryIds, true) ? 'checked' : ''; ?>
                            >
                            <span><?php echo htmlspecialchars($category['category_name']); ?></span>
                        </label>
                    <?php }?>

                    <label class="check-option">
                        <input
                            type="checkbox"
                            name="category_all"
                            value="1"
                            <?php echo empty($categoryIds) ? 'checked' : ''; ?>
                        >
                        <span>All categories</span>
                    </label>
                </fieldset>

                <!-- Location filter -->
                <label for="location">Location</label>
                <select id="location" name="location">
                    <option value="">All locations</option>
                    <?php while ($place = mysqli_fetch_assoc($locationResult)) {?>
                        <option
                            value="<?php echo htmlspecialchars($place['location_name']); ?>"
                            <?php echo $location === $place['location_name'] ? 'selected' : ''; ?>
                        >
                            <?php echo htmlspecialchars($place['location_name']); ?>
                        </option>
                    <?php }?>
                </select>

                <!-- Price filter -->
                <fieldset>
                    <legend>Price range (RM)</legend>
                    <div class="price-inputs">
                        <input type="number" min="0" name="min_price" value="<?php echo $minPrice ?: ''; ?>" placeholder="Min">
                        <span>&ndash;</span>
                        <input type="number" min="0" name="max_price" value="<?php echo $maxPrice ?: ''; ?>" placeholder="Max">
                    </div>
                </fieldset>

                <!-- Minimum rating filter -->
                <fieldset>
                    <legend>Minimum rating</legend>

                    <?php for ($rating = 5; $rating >= 1; $rating--) {?>
                        <label class="check-option rating-option">
                            <input
                                type="radio"
                                name="rating"
                                value="<?php echo $rating; ?>"
                                <?php echo $minRating == $rating ? 'checked' : ''; ?>
                            >

                            <span class="stars">
                                <?php echo str_repeat('★', $rating); ?>
                                <span class="empty-stars">
                                    <?php echo str_repeat('☆', 5 - $rating); ?>
                                </span>
                            </span>
                        </label>
                    <?php }?>

                    <label class="check-option">
                        <input
                            type="radio"
                            name="rating"
                            value=""
                            <?php echo $minRating == 0 ? 'checked' : ''; ?>
                        >
                        <span>Any rating</span>
                    </label>
                </fieldset>

                <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort); ?>">

                <button class="btn-solid apply-filter" type="submit">Apply filters</button>
                <a class="btn-outline reset-filter" href="browse-services.php">Reset filters</a>

            </form>
        </aside>

        <!-- Service Card area -->
        <div class="services-area">

            <!-- Service count and sorting toolbar -->
            <div class="toolbar">
                <p>Showing <strong><?php echo $totalServices; ?></strong> services</p>

                <form method="get">
                    <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                    <?php foreach ($categoryIds as $selectedCategoryId) {?>
                        <input type="hidden" name="category[]" value="<?php echo $selectedCategoryId; ?>">
                    <?php }?>
                    <input type="hidden" name="location" value="<?php echo htmlspecialchars($location); ?>">
                    <input type="hidden" name="min_price" value="<?php echo $minPrice ?: ''; ?>">
                    <input type="hidden" name="max_price" value="<?php echo $maxPrice ?: ''; ?>">
                    <input type="hidden" name="rating" value="<?php echo $minRating ?: ''; ?>">

                    <label for="sort">Sort by</label>
                    <select id="sort" name="sort" onchange="this.form.submit()">
                        <?php foreach (['newest' => 'Newest', 'rating' => 'Highest rated', 'price_low' => 'Price: low to high', 'price_high' => 'Price: high to low'] as $value => $label) {?>
                            <option value="<?php echo $value; ?>" <?php echo $sort === $value ? 'selected' : ''; ?>>
                                <?php echo $label; ?>
                            </option>
                        <?php }?>
                    </select>
                </form>
            </div>

            <!-- 如果没有符合条件的服务，显示空结果提示 -->
            <!-- Display an empty-state message when no services match the filters -->
            <?php if ($totalServices === 0) {?>

                <div class="browse-empty">
                    <h3>No services found</h3>
                    <p>Try changing or <a href="browse-services.php">clearing your filters</a>.</p>
                </div>

            <?php } else {?>

                <!-- Service card grid -->
                <div class="card-grid service-grid">

                    <!-- Display each service as a card -->
                    <?php while ($row = mysqli_fetch_assoc($result)) {?>

                        <!-- Individual service card -->
                        <article class="service-card">

                            <!-- Service image and favorite button -->
                            <div class="card-image">
                                <img
                                    src="<?php echo htmlspecialchars($row['thumbnail'] ?: 'assets/images/placeholder.jpg'); ?>"
                                    alt="<?php echo htmlspecialchars($row['title']); ?>"
                                >

                                <button
                                    class="wishlist-heart <?php echo $row['is_favorite'] ? 'active' : ''; ?>"
                                    type="button"
                                    aria-label="<?php echo $row['is_favorite'] ? 'Remove from favorites' : 'Save to favorites'; ?>"
                                    aria-pressed="<?php echo $row['is_favorite'] ? 'true' : 'false'; ?>"
                                    data-service-id="<?php echo $row['service_id']; ?>"
                                >
                                    <?php echo $row['is_favorite'] ? '♥' : '♡'; ?>
                                </button>
                            </div>

                            <!-- Service category -->
                            <span class="service-tag"><?php echo htmlspecialchars($row['category_name']); ?></span>

                            <h4 class="service-name"><?php echo htmlspecialchars($row['title']); ?></h4>

                            <p class="card-meta">◎ <?php echo htmlspecialchars($row['full_name']); ?></p>

                            <!-- Expert and rating information -->
                            <div class="meta-row">
                                <?php if ($row['average_rating'] === null) {?>
                                    <span class="stars new-rating">—</span>
                                    <span>New</span>
                                <?php } else {?>
                                    <?php $displayRating = (int) round($row['average_rating']); ?>
                                    <span class="stars"><?php echo str_repeat('★', $displayRating); ?><span class="empty-stars"><?php echo str_repeat('☆', 5 - $displayRating); ?></span></span>
                                    <span><?php echo number_format($row['average_rating'], 1) . ' (' . $row['total_reviews'] . ')'; ?></span>
                                <?php }?>
                            </div>

                            <div class="meta-row">
                                <span>◷ <?php echo htmlspecialchars($row['estimated_duration']); ?></span>
                                <span>⌖ <?php echo htmlspecialchars($row['location']); ?></span>
                            </div>

                            <div class="service-price-row">
                                <span class="service-price">from RM<?php echo number_format($row['starting_price'], 0); ?>+</span>
                            </div>

                            <!-- Starting service price -->
                            <div class="card-actions">
                                <a href="service-details.php?id=<?php echo $row['service_id']; ?>" class="btn-solid">View details</a>
                            </div>
                        </article>

                    <?php }?>
                </div>

                <!-- Service pagination navigation -->
                <nav class="pagination" aria-label="Services pages">

                    <!-- Previous page -->
                    <?php if ($page > 1) {?>
                        <a href="<?php echo browseUrl(['page' => $page - 1]); ?>" aria-label="Previous page">&lsaquo;</a>
                    <?php }?>

                    <!-- Page number list -->
                    <?php for ($number = 1; $number <= $totalPages; $number++) {?>
                        <a
                            class="<?php echo $number === $page ? 'current' : ''; ?>"
                            href="<?php echo browseUrl(['page' => $number]); ?>"
                            aria-current="<?php echo $number === $page ? 'page' : 'false'; ?>"
                        >
                            <?php echo $number; ?>
                        </a>
                    <?php }?>

                    <!-- Next page -->
                    <?php if ($page < $totalPages) {?>
                        <a href="<?php echo browseUrl(['page' => $page + 1]); ?>" aria-label="Next page">&rsaquo;</a>
                    <?php }?>

                </nav>

            <?php }?>

        </div>
    </div>
</main>

<?php include "includes/footer.php"; ?>

<script src="assets/js/browse-services.js"></script>
<script src="assets/js/favorites.js"></script>

</body>
</html>
