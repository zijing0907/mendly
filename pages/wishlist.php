<?php
require_once __DIR__ . '/../includes/helpers.php';   // starts the session, url(), e(), requireRole()

    require_once __DIR__ . '/../includes/db.php';

    // Wishlist is a customer-only feature
    $customerId = requireRole('customer');

    $sql = "SELECT f.favorite_id, s.service_id, s.title, s.starting_price, s.estimated_duration,
    e.full_name AS expert_name, e.location, c.category_id, c.category_name,
    COALESCE((SELECT image_path FROM service_images WHERE service_id = s.service_id ORDER BY display_order LIMIT 1), c.image_path) AS thumbnail,
    review_summary.average_rating, COALESCE(review_summary.total_reviews, 0) AS total_reviews
    FROM favorites f
    INNER JOIN services s ON f.service_id = s.service_id
    INNER JOIN experts e ON s.expert_id = e.expert_id
    INNER JOIN categories c ON s.category_id = c.category_id
    LEFT JOIN (
        SELECT rq.service_id, ROUND(AVG(r.rating), 1) AS average_rating, COUNT(r.review_id) AS total_reviews
        FROM repair_requests rq INNER JOIN reviews r ON r.request_id = rq.request_id GROUP BY rq.service_id
    ) review_summary ON review_summary.service_id = s.service_id
    WHERE f.customer_id = ? AND s.status = 'active' AND e.approval_status = 'approved'
    ORDER BY f.created_at DESC";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $customerId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $categoryResult = mysqli_query($conn, 'SELECT category_id, category_name FROM categories ORDER BY category_name');
    $savedCount     = mysqli_num_rows($result);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <!-- All relative links on this page resolve from the project root -->
    <base href="<?php echo e(url()); ?>">
    <meta name="csrf-token" content="<?php echo e(csrfToken()); ?>">
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>My Wishlist | Mendly</title>

    <link rel="stylesheet" href="<?php echo asset('assets/css/global.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset('assets/css/wishlist.css'); ?>">
</head>

<body>

<?php include __DIR__ . '/../includes/header.php'; ?>

<main class="wishlist-container">

    <nav class="breadcrumb" aria-label="Breadcrumb">
        <a href="index.php">Home</a>
        <span>&rsaquo;</span>
        <a href="pages/profile.php">My Account</a>
        <span>&rsaquo;</span>
        <span class="breadcrumb-current">My Wishlist</span>
    </nav>

    <div class="wishlist-heading">
        <h2>My Wishlist <span class="heading-leaf">🌿</span></h2>
        <p>Save repair services you love for future reference.</p>
    </div>

    <div class="wishlist-controls">
        <p class="wishlist-count"><strong id="savedCount"><?php echo $savedCount; ?></strong> saved services</p>

        <div class="category-filter">
            <select id="categoryFilter" aria-label="Filter by category">
                <option value="all">All Categories</option>

                <?php while ($category = mysqli_fetch_assoc($categoryResult)): ?>
                    <option value="<?php echo $category['category_id']; ?>">
                        <?php echo htmlspecialchars($category['category_name']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
    </div>

    <div class="card-grid service-grid" id="serviceGrid">

        <?php while ($row = mysqli_fetch_assoc($result)): ?>

            <article
                class="service-card"
                data-card
                data-category="<?php echo $row['category_id']; ?>"
                data-service-id="<?php echo $row['service_id']; ?>"
            >
                <div class="card-image">
                    <img
                        src="<?php echo htmlspecialchars($row['thumbnail'] ?: 'assets/images/logo.png'); ?>"
                        alt="<?php echo htmlspecialchars($row['title']); ?>"
                    >

                    <button
                        type="button"
                        class="wishlist-heart active"
                        aria-label="Remove from wishlist"
                        data-service-id="<?php echo $row['service_id']; ?>"
                    >♥</button>
                </div>

                <div class="card-content">
                    <span class="category-badge"><?php echo htmlspecialchars($row['category_name']); ?></span>

                    <h3 class="service-name"><?php echo htmlspecialchars($row['title']); ?></h3>
                    <p class="expert-name"><?php echo icon('user'); ?> <?php echo htmlspecialchars($row['expert_name']); ?></p>

                    <div class="meta-row">
                        <?php $stars = (int) round($row['average_rating'] ?? 0); ?>
                        <span class="stars"><?php echo str_repeat('★', $stars) . str_repeat('☆', 5 - $stars); ?></span>
                        <span>
                            <?php echo $row['average_rating'] === null ? 'New' : number_format($row['average_rating'], 1) . ' (' . $row['total_reviews'] . ')'; ?>
                        </span>
                    </div>

                    <div class="meta-row">
                        <span><?php echo icon('clock'); ?> <?php echo htmlspecialchars($row['estimated_duration']); ?></span>
                        <span><?php echo icon('map-pin'); ?> <?php echo htmlspecialchars($row['location']); ?></span>
                    </div>

                    <div class="card-footer">
                        <span>from <strong>RM<?php echo number_format($row['starting_price'], 0); ?>+</strong></span>
                        <a href="pages/service-details.php?id=<?php echo $row['service_id']; ?>">View details</a>
                    </div>
                </div>
            </article>

        <?php endwhile; ?>

    </div>

    <p class="wishlist-empty <?php echo $savedCount > 0 ? 'wl-hidden' : ''; ?>" id="wishlistEmpty">
        No saved services yet. <a href="pages/browse-services.php">Browse Mendly</a> to start saving repair services.
    </p>

</main>

<div class="wishlist-toast" id="wishlistToast">Removed from wishlist</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script src="<?php echo asset('assets/js/main.js'); ?>"></script>
<script src="<?php echo asset('assets/js/wishlist.js'); ?>"></script>

</body>
</html>
