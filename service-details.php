<?php

    include 'includes/db.php';

    if (session_status() === PHP_SESSION_NONE) {
    session_start();
    }

    if (! isset($_GET['id']) || ! is_numeric($_GET['id'])) {
    header("Location: browse-services.php");
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
    u.location,
    u.bio,
    u.years_experience,
    u.completed_repairs,
    u.response_rate,
    u.average_response_time,
    u.verified,
    c.category_name,
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

WHERE s.service_id = ?
";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $serviceID);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) === 0) {
    header("Location: browse-services.php");
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
    $averageRating = (float) ($service['average_rating'] ?? 0);
    $filledStars   = max(0, min(5, (int) round($averageRating)));
    $emptyStars    = 5 - $filledStars;

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($service['title']); ?></title>
    <link rel="stylesheet" href="assets/css/global.css">
    <link rel="stylesheet" href="assets/css/service-details.css">
</head>

<body>

<?php include "includes/header.php"; ?>

<main class="service-details-page">

    <section class="breadcrumb">
        <div class="container">
            <a href="index.php">Home</a>
            /
            <a href="browse-services.php">Browse Services</a>
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
                        ⭐
                        <?php echo number_format($service['average_rating'] ?? 0, 1); ?>
                        (<?php echo $service['total_reviews']; ?>Reviews)
                    </div>
                    <p><?php echo nl2br(htmlspecialchars($service['description'])); ?></p>

                    <div class="info-list">
                        <p>📍<?php echo htmlspecialchars($service['location']); ?></p>
                        <p>⏱<?php echo htmlspecialchars($service['estimated_duration']); ?></p>
                        <p>👤<?php echo htmlspecialchars($service['full_name']); ?></p>
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
                        <strong><?php echo number_format($averageRating, 1); ?> / 5</strong>
                        <span>Customer rating from <?php echo $service['total_reviews']; ?> reviews</span>
                    </div>

                    <a href="submit-request.php?service=<?php echo $serviceID; ?>" class="request-btn">Submit Repair Request</a>
                    <button
                        class="favorite-btn <?php echo $isFavorite ? 'active' : ''; ?>"
                        type="button"
                        data-service-id="<?php echo $serviceID; ?>"
                        aria-label="<?php echo $isFavorite ? 'Remove from favorites' : 'Save to favorites'; ?>"
                        aria-pressed="<?php echo $isFavorite ? 'true' : 'false'; ?>"
                    >
                        <?php echo $isFavorite ? '&#9829; Saved to Favorites' : '&#9825; Save to Favorites'; ?>
                    </button>

                    <div class="expert-summary">

                        <?php
                            if ($service['verified']) {
                            ?>
                            <p>✓ Verified Expert</p>
                        <?php
                            }
                        ?>

                        <p>Experience <?php echo $service['years_experience']; ?> Years</p>
                        <p>Completed Repairs <?php echo $service['completed_repairs']; ?></p>
                        <p>Response Rate <?php echo $service['response_rate']; ?>%</p>

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
                    <img src="<?php echo htmlspecialchars($service['avatar']); ?>" alt="<?php echo htmlspecialchars($service['full_name']); ?>">
                </div>

                <div class="expert-details">
                    <h3><?php echo htmlspecialchars($service['full_name']); ?></h3>

                    <p><?php echo nl2br(htmlspecialchars($service['bio'])); ?></p>

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

                <button class="profile-btn" type="button">View Full Profile</button>

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
                            ⭐
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

<?php include "includes/footer.php"; ?>

<script src="assets/js/gallery.js"></script>
<script src="assets/js/favorites.js"></script>

</body>
</html>