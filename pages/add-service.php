<?php
require_once __DIR__ . '/../includes/helpers.php';   // starts the session, url(), e(), requireRole()

include __DIR__ . '/../includes/db.php';

// Must be logged in as an expert - customers don't have services to list.
$expertId    = requireRole('expert');
$currentPage = 'my_services';

$welcome = isset($_GET['welcome']);
$added   = isset($_GET['added']);

// ?id=N switches this page into edit mode for one of the expert's own
// services; the same form and actions/save-service.php handle both cases.
$editId  = (int) ($_GET['id'] ?? 0);
$service = [
    'service_id'         => 0,
    'category_id'        => 0,
    'title'              => '',
    'description'        => '',
    'starting_price'     => '',
    'estimated_duration' => '',
];

if ($editId > 0) {
    $editStmt = mysqli_prepare(
        $conn,
        "SELECT service_id, category_id, title, description, starting_price, estimated_duration
         FROM services WHERE service_id = ? AND expert_id = ?"
    );
    mysqli_stmt_bind_param($editStmt, 'ii', $editId, $expertId);
    mysqli_stmt_execute($editStmt);
    $found = mysqli_fetch_assoc(mysqli_stmt_get_result($editStmt));
    mysqli_stmt_close($editStmt);

    if (!$found) {
        header('Location: ' . url('pages/my-services.php?msg=not_found'));
        exit;
    }
    $service = $found;
}

$isEdit = $service['service_id'] > 0;

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
    <!-- All relative links on this page resolve from the project root -->
    <base href="<?php echo e(url()); ?>">
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title><?php echo $isEdit ? 'Edit Service' : 'Add a Service'; ?> | Mendly</title>

    <link rel="stylesheet" href="assets/css/global.css">
    <link rel="stylesheet" href="assets/css/add-service.css">
</head>

<body>

<?php include __DIR__ . '/../includes/header.php'; ?>

<main class="add-service-container">

    <nav class="breadcrumb" aria-label="Breadcrumb">
        <a href="index.php">Home</a>
        <span>&rsaquo;</span>
        <a href="pages/my-services.php">My Services</a>
        <span>&rsaquo;</span>
        <span class="breadcrumb-current"><?php echo $isEdit ? 'Edit Service' : 'Add a Service'; ?></span>
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
                    <a href="pages/add-service.php" class="btn-save-solid">Add Another Service</a>
                    <a href="pages/my-services.php" class="btn-save-outline">Go to My Services</a>
                </div>
            </div>

        <?php else: ?>

            <h2><?php echo $isEdit ? 'Edit Service' : 'Add a Service'; ?> <span class="heading-leaf">🌿</span></h2>
            <p class="add-service-intro">
                <?php echo $isEdit
                    ? 'Update the details customers see on this listing.'
                    : 'Tell customers what repair service you offer. You can add more services later from My Services.'; ?>
            </p>

            <?php if ($formError): ?>
                <p class="error-message banner-error"><?php echo htmlspecialchars($formError); ?></p>
            <?php endif; ?>

            <form action="actions/save-service.php" method="post" novalidate>
                <input type="hidden" name="serviceId" value="<?php echo (int) $service['service_id']; ?>">

                <div class="form-row">
                    <div class="form-group">
                        <label for="categoryId">Category</label>
                        <select id="categoryId" name="categoryId">
                            <option value="">Select a category</option>
                            <?php while ($category = mysqli_fetch_assoc($categoryResult)): ?>
                                <option value="<?php echo (int) $category['category_id']; ?>" <?php echo (int) $category['category_id'] === (int) $service['category_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['category_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                        <span class="error-message" id="categoryIdError"></span>
                    </div>

                    <div class="form-group">
                        <label for="title">Service Title</label>
                        <input type="text" id="title" name="title" placeholder="e.g. Luxury Bag Repair" value="<?php echo e($service['title']); ?>">
                        <span class="error-message" id="titleError"></span>
                    </div>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="4" placeholder="Describe what this service covers"><?php echo e($service['description']); ?></textarea>
                    <span class="error-message" id="descriptionError"></span>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="startingPrice">Starting Price (RM)</label>
                        <input type="text" id="startingPrice" name="startingPrice" placeholder="e.g. 80" value="<?php echo e($service['starting_price']); ?>">
                        <span class="error-message" id="startingPriceError"></span>
                    </div>

                    <div class="form-group">
                        <label for="estimatedDuration">Estimated Duration</label>
                        <input type="text" id="estimatedDuration" name="estimatedDuration" placeholder="e.g. 3-5 Days" value="<?php echo e($service['estimated_duration']); ?>">
                        <span class="error-message" id="estimatedDurationError"></span>
                    </div>
                </div>

                <div class="add-service-actions">
                    <button type="submit" class="btn-save-solid"><?php echo $isEdit ? 'Save Changes' : 'Add Service'; ?></button>
                    <a href="<?php echo $isEdit ? 'pages/my-services.php' : 'index.php'; ?>" class="skip-link"><?php echo $isEdit ? 'Cancel' : 'Skip for now'; ?></a>
                </div>

            </form>

        <?php endif; ?>

    </div>

</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script src="assets/js/main.js"></script>
<script src="assets/js/add-service.js"></script>

</body>
</html>
