<?php
session_start();

$pageTitle = "Submit Repair Request";

include 'includes/db.php';

/*
|--------------------------------------------------------------------------
| Temporary Login (Development Only)
|--------------------------------------------------------------------------
*/
$customerID = 1;

/*
    if (! isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
    }

    $customerID = (int) $_SESSION['user_id'];
*/

/*
|--------------------------------------------------------------------------
| Validate Service
|--------------------------------------------------------------------------
*/
if (! isset($_GET['service']) || ! is_numeric($_GET['service'])) {
  header("Location: browse-services.php");
  exit();
}

$serviceID = (int) $_GET['service'];

/*
|--------------------------------------------------------------------------
| Load Service
|--------------------------------------------------------------------------
*/
$sql = "
    SELECT
        s.service_id,
        s.title,
        s.description,
        s.starting_price,
        s.estimated_duration,
        u.user_id,
        u.full_name,
        u.location,
        c.category_name,
        (
            SELECT image_path
            FROM service_images
            WHERE service_id = s.service_id
            ORDER BY display_order
            LIMIT 1
        ) AS thumbnail,
        review_summary.average_rating,
        COALESCE(review_summary.total_reviews, 0) AS total_reviews
    FROM services s
    INNER JOIN users u ON s.expert_id = u.user_id
    INNER JOIN categories c ON s.category_id = c.category_id
    LEFT JOIN (
        SELECT
            rq.service_id,
            ROUND(AVG(r.rating), 1) AS average_rating,
            COUNT(r.review_id) AS total_reviews
        FROM repair_requests rq
        INNER JOIN reviews r ON rq.request_id = r.request_id
        GROUP BY rq.service_id
    ) review_summary ON review_summary.service_id = s.service_id
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

/*
|--------------------------------------------------------------------------
| Default Form Values
|--------------------------------------------------------------------------
*/
$itemName          = "";
$brand             = "";
$repairDescription = "";
$additionalNotes   = "";

$errors  = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  $itemName          = trim($_POST['item_name'] ?? '');
  $brand             = trim($_POST['brand'] ?? '');
  $repairDescription = trim($_POST['repair_description'] ?? '');
  $additionalNotes   = trim($_POST['additional_notes'] ?? '');

  if ($itemName === '') {
    $errors[] = "Item name is required.";
  }

  if ($repairDescription === '') {
    $errors[] = "Repair description is required.";
  }

  if (isset($_FILES['reference_images'])) {

    $totalFiles = count(array_filter($_FILES['reference_images']['name']));

    if ($totalFiles > 5) {
      $errors[] = "You can upload a maximum of 5 images.";
    }

    $allowedTypes = [
      'image/jpeg',
      'image/png',
      'image/webp',
    ];

    foreach ($_FILES['reference_images']['tmp_name'] as $index => $tmpName) {

      if ($_FILES['reference_images']['error'][$index] === UPLOAD_ERR_NO_FILE) {
        continue;
      }

      if ($_FILES['reference_images']['error'][$index] !== UPLOAD_ERR_OK) {
        $errors[] = "Failed to upload one of the images.";
        continue;
      }

      if ($_FILES['reference_images']['size'][$index] > 5 * 1024 * 1024) {
        $errors[] = "Each image must not exceed 5MB.";
      }

      $mimeType = mime_content_type($tmpName);

      if (! in_array($mimeType, $allowedTypes)) {
        $errors[] = "Only JPG, PNG and WEBP images are allowed.";
      }
    }
  }

  if (empty($errors)) {
    $sql = "
        INSERT INTO repair_requests
        (
            customer_id,
            service_id,
            item_name,
            brand,
            repair_description,
            additional_notes
        )
        VALUES
        (
            ?, ?, ?, ?, ?, ?
        )
        ";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param(
      $stmt,
      "iissss",
      $customerID,
      $serviceID,
      $itemName,
      $brand,
      $repairDescription,
      $additionalNotes
    );

    if (! mysqli_stmt_execute($stmt)) {
      $errors[] = "Unable to submit repair request.";
    } else {
      $requestID = mysqli_insert_id($conn);
    }

    if ($requestID && isset($_FILES['reference_images'])) {
      $uploadDir = "uploads/request-images/";

      if (! is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
      }

      foreach ($_FILES['reference_images']['tmp_name'] as $index => $tmpName) {

        if ($_FILES['reference_images']['error'][$index] !== UPLOAD_ERR_OK) {
          continue;
        }

        $extension = strtolower(
          pathinfo(
            $_FILES['reference_images']['name'][$index],
            PATHINFO_EXTENSION
          )
        );

        $fileName = uniqid("request_") . "." . $extension;

        $destination = $uploadDir . $fileName;

        if (move_uploaded_file($tmpName, $destination)) {

          $imageSQL = "
                    INSERT INTO request_images
                    (
                        request_id,
                        image_path
                    )
                    VALUES
                    (
                        ?, ?
                    )
                    ";

          $imageStmt = mysqli_prepare($conn, $imageSQL);

          mysqli_stmt_bind_param(
            $imageStmt,
            "is",
            $requestID,
            $destination
          );

          mysqli_stmt_execute($imageStmt);
        }
      }
    }
    $success = true;
    header("Location: my-requests.php?success=1");
    exit();
  }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Submit Repair Request</title>
  <link rel="stylesheet" href="/assets/css/global.css">
  <link rel="stylesheet" href="/assets/css/submit-request.css">
</head>

<body>

  <?php
  if (file_exists("header.php")) {
    include "header.php";
  }
  ?>

  <main class="submit-request-page">

    <section class="request-section">
      <div class="container">
        <div class="request-grid">

          <div class="request-form">
            <h1>Submit Repair Request</h1>

            <p class="page-description">
              Tell us about your item and the repair issue. The repair expert will review
              your request before providing a quotation.
            </p>

            <form action="" method="POST" enctype="multipart/form-data" class="repair-form">

              <?php if (! empty($errors)) { ?>
                <div class="error-box">
                  <ul>
                    <?php foreach ($errors as $error) { ?>
                      <li><?php echo htmlspecialchars($error); ?></li>
                    <?php } ?>
                  </ul>
                </div>
              <?php } ?>

              <!-- Item Information -->
              <section class="form-card">
                <h2>Item Information</h2>
                <div class="form-group">
                  <label for="item_name">
                    Item Name <span class="required">*</span>
                  </label>
                  <input
                    type="text"
                    id="item_name"
                    name="item_name"
                    maxlength="150"
                    required
                    value="<?php echo htmlspecialchars($itemName); ?>"
                    placeholder="e.g. Louis Vuitton Neverfull MM">
                </div>
                <div class="form-group">
                  <label for="brand">
                    Brand (Optional)
                  </label>
                  <input
                    type="text"
                    id="brand"
                    name="brand"
                    maxlength="100"
                    value="<?php echo htmlspecialchars($brand); ?>"
                    placeholder="e.g. Louis Vuitton">
                </div>
              </section>

              <!-- Repair Description -->
              <section class="form-card">
                <h2>Problem Description</h2>
                <div class="form-group">
                  <label for="repair_description">
                    Describe the issue
                    <span class="required">*</span>
                  </label>
                  <textarea
                    id="repair_description"
                    name="repair_description"
                    rows="7"
                    maxlength="1000"
                    required
                    placeholder="Describe the damage, missing parts, stains, scratches, or anything else that will help the repair expert understand the problem."><?php echo htmlspecialchars($repairDescription); ?></textarea>
                  <small>
                    Please provide as much detail as possible.
                  </small>
                </div>
              </section>

              <!-- Upload -->
              <section class="form-card">

                <h2>Reference Images</h2>
                <p class="upload-note">Upload clear photos to help the repair expert assess the item.</p>

                <div class="upload-box" id="upload-box">
                  <span class="upload-icon">📷</span>
                  <p>Click to upload images</p>
                  <small>Maximum 5 images (JPG, PNG, WEBP)</small>
                  <input
                    type="file"
                    id="reference_images"
                    name="reference_images[]"
                    multiple
                    accept=".jpg,.jpeg,.png,.webp"
                    hidden>
                </div>

                <div>
                  <span id="image-count">0 / 5 images selected</span>
                </div>

                <div id="image-preview"></div>

              </section>

              <!-- Notes -->
              <section class="form-card">
                <h2>Additional Notes</h2>
                <textarea
                  id="additional_notes"
                  name="additional_notes"
                  rows="5"
                  maxlength="500"
                  placeholder="Pickup preference, urgency, preferred contact method, or any additional information."><?php echo htmlspecialchars($additionalNotes); ?></textarea>
              </section>

              <!-- Button -->
              <div class="submit-area">
                <button type="submit" class="submit-btn">Submit Repair Request</button>
              </div>

            </form>
          </div>

          <aside class="request-summary">

            <div class="summary-card">

              <img
                class="summary-image"
                src="<?php echo htmlspecialchars($service['thumbnail']); ?>"
                alt="<?php echo htmlspecialchars($service['title']); ?>">

              <div class="summary-content">

                <span class="summary-category">
                  <?php echo htmlspecialchars($service['category_name']); ?>
                </span>
                <h2><?php echo htmlspecialchars($service['title']); ?></h2>
                <p class="expert-name">by <?php echo htmlspecialchars($service['full_name']); ?></p>

                <div class="summary-rating">⭐
                  <?php echo number_format($service['average_rating'] ?? 0, 1); ?>
                  <span>(<?php echo $service['total_reviews']; ?> Reviews)</span>
                </div>
              </div>

            </div>

            <div class="summary-box">

              <h3>Service Summary</h3>

              <div class="summary-row">
                <span>Starting Price</span>
                <strong>RM <?php echo number_format($service['starting_price'], 2); ?></strong>
              </div>

              <div class="summary-row">
                <span>Estimated Duration</span>
                <strong><?php echo htmlspecialchars($service['estimated_duration']); ?></strong>
              </div>

              <div class="summary-row">
                <span>Location</span>
                <strong><?php echo htmlspecialchars($service['location']); ?></strong>
              </div>

            </div>

            <div class="summary-box">

              <h3>How It Works</h3>

              <ul class="steps">

                <li>
                  <span class="step-number">1</span>
                  Submit your repair request
                </li>

                <li>
                  <span class="step-number">2</span>
                  Expert reviews your item
                </li>

                <li>
                  <span class="step-number">3</span>
                  Receive a quotation
                </li>

                <li>
                  <span class="step-number">4</span>
                  Repair begins after approval
                </li>

              </ul>

            </div>

            <div>
              <h3>Tips</h3>
              <ul class="tips">
                <li>Upload clear photos from different angles.</li>
                <li>Describe the damage in as much detail as possible.</li>
                <li>The final quotation will be confirmed after inspection.</li>
              </ul>
            </div>

          </aside>

        </div>
      </div>
    </section>

  </main>

  <script src="submit-request.js"></script>

</body>

</html>