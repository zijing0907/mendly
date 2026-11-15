<?php
require_once __DIR__ . '/../includes/helpers.php';   // starts the session, url(), e(), requireRole()

    require_once __DIR__ . '/../includes/db.php';


    if (! isset($_GET['id']) || ! is_numeric($_GET['id'])) {
    header('Location: ' . url("pages/browse-services.php"));
    exit();
    }

    $serviceID = (int) $_GET['id'];

    $currentUserId = (($_SESSION['role'] ?? '') === 'customer')
    ? (int) ($_SESSION['user_id'] ?? 0)
    : 0;

    $isFavorite = false;

    if ($currentUserId > 0) {
    $favoriteSQL = "
        SELECT favorite_id
        FROM favorites
        WHERE customer_id = ?
        AND service_id = ?
        LIMIT 1
    ";

    $favoriteStmt = mysqli_prepare($conn, $favoriteSQL);
    mysqli_stmt_bind_param(
        $favoriteStmt,
        "ii",
        $currentUserId,
        $serviceID
    );
    mysqli_stmt_execute($favoriteStmt);

    $favoriteResult = mysqli_stmt_get_result($favoriteStmt);

    $isFavorite = mysqli_num_rows($favoriteResult) > 0;
    }

    $sql = "
SELECT s.*,
    u.full_name,
    u.avatar,
    u.avatar_mime,
    u.location,
    u.bio,
    u.years_experience,
    u.completed_repairs,
    u.response_rate,
    u.average_response_time,
    u.verified,
    c.category_name,
    c.image_path AS category_image,
    review_summary.average_rating,
    COALESCE(review_summary.total_reviews, 0) AS total_reviews
FROM services s

INNER JOIN experts u
ON s.expert_id = u.expert_id
INNER JOIN categories c
ON s.category_id = c.category_id

LEFT JOIN (
    SELECT
        rq.service_id,
        ROUND(AVG(r.rating), 1) AS average_rating,
        COUNT(r.review_id) AS total_reviews
    FROM repair_requests rq
    INNER JOIN reviews r
    ON r.request_id = rq.request_id
    GROUP BY rq.service_id
) review_summary
ON review_summary.service_id = s.service_id

WHERE s.service_id = ? AND s.status = 'active'
";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $serviceID);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) === 0) {
    header('Location: ' . url("pages/browse-services.php"));
    exit();
    }

    $service = mysqli_fetch_assoc($result);

    $imageSQL = "
SELECT *
FROM service_images
WHERE service_id = ?
ORDER BY display_order
";

    $stmtImage = mysqli_prepare($conn, $imageSQL);
    mysqli_stmt_bind_param($stmtImage, "i", $serviceID);
    mysqli_stmt_execute($stmtImage);
    $images = mysqli_stmt_get_result($stmtImage);

    $reviewSQL = "
SELECT r.rating, r.comment, r.review_date, u.full_name, u.avatar
FROM reviews r
INNER JOIN repair_requests rq
ON r.request_id = rq.request_id
INNER JOIN customers u
ON rq.customer_id = u.customer_id
WHERE rq.service_id = ?
ORDER BY r.review_date DESC
";

    $stmtReview = mysqli_prepare($conn, $reviewSQL);
    mysqli_stmt_bind_param($stmtReview, "i", $serviceID);
    mysqli_stmt_execute($stmtReview);
    $reviews = mysqli_stmt_get_result($stmtReview);

    $imageList     = mysqli_fetch_all($images, MYSQLI_ASSOC);

    // New listings have no photos yet — show the category's cover photo
    if (empty($imageList) && ! empty($service['category_image'])) {
    $imageList[] = ['image_path' => $service['category_image'], 'caption' => $service['category_name']];
    }

    // Expert avatars are stored as image bytes in the database; no photo
    // means we show their initials instead
    $expertAvatar = imageDataUri($service['avatar'], $service['avatar_mime']);
    $viewerRole   = currentRole();
    $averageRating = (float) ($service['average_rating'] ?? 0);
    $filledStars   = max(0, min(5, (int) round($averageRating)));
    $emptyStars    = 5 - $filledStars;

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <!-- All relative links on this page resolve from the project root -->
    <base href="<?php echo e(url()); ?>">
    <meta name="csrf-token" content="<?php echo e(csrfToken()); ?>">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($service['title']); ?></title>
    <link rel="stylesheet" href="assets/css/global.css">
    <link rel="stylesheet" href="assets/css/service-details.css">
</head>

<body>

<?php include __DIR__ . '/../includes/header.php'; ?>

<main class="service-details-page">

    <section class="breadcrumb">
        <div class="container">
            <a href="index.php">Home</a>
            /
            <a href="pages/browse-services.php">Browse Services</a>
            /
            <span><?php echo htmlspecialchars($service['title']); ?></span>
        </div>
    </section>

    <section class="service-overview">

        <div class="container">
            <div class="overview-grid">

                <div class="gallery">

                    <?php if (! empty($imageList)): ?>

                    <div class="gallery-stage">
                        <img class="main-image" src="<?php echo htmlspecialchars($imageList[0]['image_path']); ?>"
                            alt="<?php echo htmlspecialchars($imageList[0]['caption'] ?? 'Service Image'); ?>">
                        <button class="gallery-control previous" type="button" aria-label="Previous image">&#8249;</button>
                        <button class="gallery-control next" type="button" aria-label="Next image">&#8250;</button>
                    </div>

                    <div class="thumbnail-list">
                        <?php foreach ($imageList as $image): ?>
                            <img class="thumbnail" src="<?php echo htmlspecialchars($image['image_path']); ?>" alt="<?php echo htmlspecialchars($image['caption'] ?? 'Thumbnail'); ?>">
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                </div>

                <div class="service-info">

                    <span class="category"><?php echo htmlspecialchars($service['category_name']); ?></span>
                    <h1><?php echo htmlspecialchars($service['title']); ?></h1>
                    <p class="service-tagline">Careful repair by a trusted local specialist.</p>
                    <div class="rating">
                        <?php echo icon('star', 'icon-fill'); ?>
                        <?php if ((int) $service['total_reviews'] === 0): ?>
                            New: no reviews yet
                        <?php else: ?>
                            <?php echo number_format($service['average_rating'], 1); ?>
                            (<?php echo (int) $service['total_reviews']; ?> <?php echo (int) $service['total_reviews'] === 1 ? 'Review' : 'Reviews'; ?>)
                        <?php endif; ?>
                    </div>
                    <p><?php echo nl2br(htmlspecialchars($service['description'])); ?></p>

                    <div class="info-list">
                        <p><?php echo icon('map-pin'); ?> <?php echo htmlspecialchars($service['location']); ?></p>
                        <p><?php echo icon('clock'); ?> <?php echo htmlspecialchars($service['estimated_duration']); ?></p>
                        <p><?php echo icon('user'); ?> <?php echo htmlspecialchars($service['full_name']); ?></p>
                    </div>

                </div>

                <div class="price-card">

                    <h2>Starting Price</h2>

                    <div class="price">
                        <small>From</small>
                        <strong>RM <?php echo number_format($service['starting_price'], 2); ?>+</strong>
                        <span>Final price may vary after inspection.</span>
                    </div>

                    <div class="price-rating">
                        <?php if ((int) $service['total_reviews'] === 0): ?>
                            <strong>New service</strong>
                            <span>Be the first to leave a review</span>
                        <?php else: ?>
                            <strong><?php echo number_format($averageRating, 1); ?> / 5</strong>
                            <span>Customer rating from <?php echo (int) $service['total_reviews']; ?> <?php echo (int) $service['total_reviews'] === 1 ? 'review' : 'reviews'; ?></span>
                        <?php endif; ?>
                    </div>

                    <?php if ($viewerRole === 'expert'): ?>
                        <p class="expert-note">You're signed in as an expert. Log in with a customer account to request this repair.</p>
                    <?php else: ?>
                    <a href="pages/submit-request.php?service=<?php echo $serviceID; ?>" class="request-btn">Submit Repair Request</a>
                    <button
                        class="favorite-btn <?php echo $isFavorite ? 'active' : ''; ?>"
                        type="button"
                        data-service-id="<?php echo $serviceID; ?>"
                        aria-label="<?php echo $isFavorite ? 'Remove from favorites' : 'Save to favorites'; ?>"
                        aria-pressed="<?php echo $isFavorite ? 'true' : 'false'; ?>"
                    >
                        <?php echo $isFavorite ? '&#9829; Saved to Favorites' : '&#9825; Save to Favorites'; ?>
                    </button>
                    <?php endif; ?>

                    <div class="expert-summary">

                        <?php
                            if ($service['verified']) {
                            ?>
                            <p class="verified-line"><?php echo icon('check-circle'); ?> Verified Expert</p>
                        <?php
                            }
                        ?>

                        <p><?php echo icon('award'); ?> <?php echo (int) $service['years_experience']; ?> years experience</p>
                        <p><?php echo icon('tool'); ?> <?php echo (int) $service['completed_repairs']; ?> repairs completed</p>
                        <p><?php echo icon('message'); ?> <?php echo e($service['response_rate']); ?>% response rate</p>

                        <div class="trust-box">
                            <p><strong>Secure &amp; trusted</strong></p>
                            <p>Your item is handled with care by experienced specialists.</p>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="expert-section">

        <div class="container">

            <h2>Meet Your Repair Expert</h2>

            <div class="expert-card">
                <div class="expert-avatar">
                    <?php if ($expertAvatar): ?>
                        <img src="<?php echo e($expertAvatar); ?>" alt="<?php echo e($service['full_name']); ?>">
                    <?php else: ?>
                        <span class="initials-avatar expert-avatar-initials" aria-hidden="true"><?php echo e(nameInitials($service['full_name'])); ?></span>
                    <?php endif; ?>
                </div>

                <div class="expert-details">
                    <h3><?php echo htmlspecialchars($service['full_name']); ?></h3>

                    <p><?php echo nl2br(e($service['bio'])); ?></p>

                    <div class="expert-stats">

                        <div>
                            <strong><?php echo $service['years_experience']; ?></strong>
                            <span>Years Experience</span>
                        </div>

                        <div>
                            <strong><?php echo $service['completed_repairs']; ?></strong>
                            <span>Repairs</span>
                        </div>

                        <div>
                            <strong><?php echo $service['response_rate']; ?>%</strong>
                            <span>Response Rate</span>
                        </div>

                    </div>
                </div>

                <a class="profile-btn" href="pages/browse-services.php?expert=<?php echo (int) $service['expert_id']; ?>">All Services by <?php echo e(strtok($service['full_name'], ' ')); ?></a>

            </div>
        </div>
    </section>

    <section class="about-section">
        <div class="container">

            <h2>About This Service</h2>

            <div class="about-card">

                <div class="about-copy">

                    <h3>What is included</h3>

                    <p><?php echo nl2br(htmlspecialchars($service['description'])); ?></p>

                    <ul>
                        <li>Initial assessment of your item</li>
                        <li>Repair and restoration by a specialist</li>
                        <li>Care advice after collection</li>
                    </ul>

                </div>

                <aside class="service-note">

                    <h3>Please note</h3>

                    <ul>
                        <li>Final price may vary after inspection.</li>
                        <li>Turnaround time depends on the item's condition.</li>
                        <li>Collection or shipping is arranged with the expert.</li>
                    </ul>

                </aside>
            </div>
        </div>
    </section>

    <section class="review-section">
        <div class="container">
            <h2>Customer Reviews</h2>
            <p class="all-reviews-link">
                <a href="pages/expert-review.php?expert_id=<?php echo (int) $service['expert_id']; ?>">See all reviews for <?php echo e($service['full_name']); ?> &rarr;</a>
            </p>

            <?php
                if (mysqli_num_rows($reviews) == 0) {
                ?>
                <p>No reviews yet.</p>
                <?php
                    } else {
                        while ($review = mysqli_fetch_assoc($reviews)) {
                        ?>
                    <div class="review-card">

                        <h4><?php echo htmlspecialchars($review['full_name']); ?></h4>

                        <div>
                            <?php echo icon('star', 'icon-fill'); ?>
                            <?php echo $review['rating']; ?>/5
                        </div>

                        <p><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>

                        <small><?php echo date("d M Y", strtotime($review['review_date'])); ?></small>

                    </div>
                <?php
                    }
                    }
                ?>
        </div>
    </section>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script src="assets/js/gallery.js"></script>
<script src="assets/js/favorites.js"></script>

</body>
</html>