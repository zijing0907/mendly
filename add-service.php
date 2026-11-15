<?php
session_start();

// Must be logged in, and must be an expert - customers don't have
// services to list.
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if (($_SESSION['role'] ?? '') !== 'expert') {
    header('Location: index.php');
    exit;
}

include("includes/db.php");

$welcome = isset($_GET['welcome']);
$added   = isset($_GET['added']);

$errorMessages = [
    'missing_fields'  => 'Please fill in all required fields.',
    'invalid_category' => 'Please choose a valid category.',
    'invalid_price'   => 'Please enter a valid starting price.',
    'server_error'    => 'Something went wrong saving your service. Please try again.',
];

$formError = isset($_GET['error'])
    ? ($errorMessages[$_GET['error']] ?? 'Something went wrong. Please try again.')
    : null;

// Categories for the dropdown - same query used on Browse Services
$categoryResult = mysqli_query($conn, 'SELECT category_id, category_name FROM categories ORDER BY category_name');

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Add a Service | Mendly</title>

    <link rel="stylesheet" href="assets/css/global.css">
    <link rel="stylesheet" href="assets/css/add-service.css">
</head>

<body>

<?php include("header.php"); ?>

<main class="add-service-container">

    <nav class="breadcrumb" aria-label="Breadcrumb">
        <a href="index.php">Home</a>
        <span>&rsaquo;</span>
        <span class="breadcrumb-current">Add a Service</span>
    </nav>

    <div class="add-service-card">

        <?php if ($welcome): ?>
            <p class="welcome-banner">
                🌿 Welcome to Mendly! Add your first service listing so customers can find you.
            </p>
        <?php endif; ?>

        <?php if ($added): ?>

            <div class="success-state">
                <h2>Service Added <span class="heading-leaf">🌿</span></h2>
                <p>Your service is now live and customers can find it on Browse Services.</p>

                <div class="success-actions">
                    <a href="add-service.php" class="btn-save-solid">Add Another Service</a>
                    <a href="index.php" class="btn-save-outline">Go to Home</a>
                </div>
            </div>

        <?php else: ?>

            <h2>Add a Service <span class="heading-leaf">🌿</span></h2>
            <p class="add-service-intro">
                Tell customers what repair service you offer. You can add more services later from your account.
            </p>

            <?php if ($formError): ?>
                <p class="error-message banner-error"><?php echo htmlspecialchars($formError); ?></p>
            <?php endif; ?>

            <form action="save-service.php" method="post" novalidate>

                <div class="form-row">
                    <div class="form-group">
                        <label for="categoryId">Category</label>
                        <select id="categoryId" name="categoryId">
                            <option value="">Select a category</option>
                            <?php while ($category = mysqli_fetch_assoc($categoryResult)): ?>
                                <option value="<?php echo (int) $category['category_id']; ?>">
                                    <?php echo htmlspecialchars($category['category_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                        <span class="error-message" id="categoryIdError"></span>
                    </div>

                    <div class="form-group">
                        <label for="title">Service Title</label>
                        <input type="text" id="title" name="title" placeholder="e.g. Luxury Bag Repair">
                        <span class="error-message" id="titleError"></span>
                    </div>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="4" placeholder="Describe what this service covers"></textarea>
                    <span class="error-message" id="descriptionError"></span>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="startingPrice">Starting Price (RM)</label>
                        <input type="text" id="startingPrice" name="startingPrice" placeholder="e.g. 80">
                        <span class="error-message" id="startingPriceError"></span>
                    </div>

                    <div class="form-group">
                        <label for="estimatedDuration">Estimated Duration</label>
                        <input type="text" id="estimatedDuration" name="estimatedDuration" placeholder="e.g. 3-5 Days">
                        <span class="error-message" id="estimatedDurationError"></span>
                    </div>
                </div>

                <div class="add-service-actions">
                    <button type="submit" class="btn-save-solid">Add Service</button>
                    <a href="index.php" class="skip-link">Skip for now</a>
                </div>

            </form>

        <?php endif; ?>

    </div>

</main>

<?php include("footer.php"); ?>

<script src="assets/js/main.js"></script>
<script src="assets/js/add-service.js"></script>

</body>
</html>
