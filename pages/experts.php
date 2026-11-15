<?php
require_once __DIR__ . '/../includes/helpers.php';   // starts the session, url(), e(), requireRole()
$pageTitle   = "Meet Our Experts";
$currentPage = 'experts';

include __DIR__ . '/../includes/db.php';

// Category tab currently selected (?category=...), defaulting to 'all'
$selectedCategory = $_GET['category'] ?? 'all';

// The three groups shown on the home page; categories.category_group
// stores which group each repair category belongs to.
$categories = [
    'all'      => 'ALL',
    'textiles' => 'TEXTILES & APPAREL',
    'toys'     => 'TOYS & MEMORABILIA',
    'leather'  => 'LEATHER & ACCESSORIES'
];

if (!isset($categories[$selectedCategory])) {
    $selectedCategory = 'all';
}

// An expert's specialisation is whatever categories their active services
// are listed under, so it can never drift out of sync with what they offer.
$sql = "SELECT
            e.expert_id,
            e.full_name,
            e.business_name,
            e.experience,
            e.years_experience,
            e.completed_repairs,
            e.bio,
            e.description,
            e.photo_path,
            e.photo_path_mime,
            e.avatar,
            e.avatar_mime,
            e.verified,
            GROUP_CONCAT(DISTINCT c.category_name ORDER BY c.category_name SEPARATOR ', ') AS specialisation,
            GROUP_CONCAT(DISTINCT c.category_group) AS category_groups
        FROM experts e
        LEFT JOIN services s   ON s.expert_id = e.expert_id AND s.status = 'active'
        LEFT JOIN categories c ON c.category_id = s.category_id
        GROUP BY e.expert_id
        ORDER BY e.expert_id DESC";

$result  = mysqli_query($conn, $sql);
$experts = [];

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $groups = $row['category_groups'] ? explode(',', $row['category_groups']) : [];

        if ($selectedCategory === 'all' || in_array($selectedCategory, $groups, true)) {
            $experts[] = $row;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <!-- All relative links on this page resolve from the project root -->
    <base href="<?php echo e(url()); ?>">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> | Mendly</title>
    <link rel="stylesheet" href="assets/css/global.css">
    <link rel="stylesheet" href="assets/css/experts.css">
</head>

<body>

    <?php include __DIR__ . '/../includes/header.php'; ?>

    <main class="experts-page-container">

        <!-- Page title -->
        <div class="page-header">
            <h1>Meet Our Experts</h1>
            <p>Connect with master craftspeople dedicated to restoring your cherished possessions.</p>
        </div>

        <!-- Category tabs -->
        <div class="expert-category-nav">
            <?php foreach ($categories as $key => $label): ?>
                <a
                    href="pages/experts.php?category=<?php echo $key; ?>"
                    class="cat-btn <?php echo ($selectedCategory === $key) ? 'active' : ''; ?>">
                    <?php echo htmlspecialchars($label); ?>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Expert cards -->
        <div class="experts-list">
            <?php foreach ($experts as $expert):
                // Business photo first, then personal avatar, then the stock image
                $photo = imageDataUri($expert['photo_path'], $expert['photo_path_mime'])
                    ?? imageDataUri($expert['avatar'], $expert['avatar_mime'], 'assets/images/default-expert.jpg');
                $years = (int) ($expert['years_experience'] ?? 0);
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
                                <?php if (!empty($expert['specialisation'])): ?>
                                    <span class="specialisation-tag"><?php echo e($expert['specialisation']); ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="expert-stats">
                                <span class="stat-item">
                                    🏅 <strong><?php echo $years > 0 ? $years : e($expert['experience']); ?></strong> Years Experience
                                </span>
                                <span class="stat-item">
                                    🛠️ <strong><?php echo (int)($expert['completed_repairs'] ?? 0); ?></strong> Repairs Completed
                                </span>
                            </div>

                            <p class="expert-bio">
                                <?php
                                $bioText = $expert['bio'] ?: ($expert['description'] ?: 'No bio provided yet.');
                                // Trim long bios to 120 characters
                                echo htmlspecialchars(mb_strimwidth($bioText, 0, 120, '...'));
                                ?>
                            </p>
                        </div>

                        <div class="card-footer">
                            <a href="pages/expert-review.php?expert_id=<?php echo $expert['expert_id']; ?>" class="btn-view-reviews">
                                Read reviews
                            </a>
                            <a href="pages/browse-services.php?expert=<?php echo $expert['expert_id']; ?>" class="btn-view-services">
                                View Expert's Services &rarr;
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if (!$experts): ?>
                <div class="empty-experts">
                    <h3>No experts found in this category.</h3>
                    <p>Try switching to another category to view our artisans.</p>
                </div>
            <?php endif; ?>
        </div>

    </main>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

</body>

</html>