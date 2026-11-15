<?php
require_once __DIR__ . '/../includes/helpers.php';   // starts the session, url(), e(), requireRole()
require_once __DIR__ . '/../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . url('pages/contact.php'));
    exit;
}

// 1. Read and clean the submitted fields
$fullName    = trim($_POST['full_name'] ?? '');
$email       = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
$subjectType = trim($_POST['subject_type'] ?? '');
$message     = trim($_POST['message'] ?? '');

if (empty($fullName) || empty($email) || empty($subjectType) || empty($message)) {
    header('Location: ' . url('pages/contact.php?status=empty'));
    exit;
}

// 2. Work out who sent it (Customer / Expert / Guest) from the email
$userType = 'Guest';

// Registered customer?
$stmtCustomer = mysqli_prepare($conn, "SELECT customer_id FROM customers WHERE email = ?");
mysqli_stmt_bind_param($stmtCustomer, 's', $email);
mysqli_stmt_execute($stmtCustomer);
mysqli_stmt_store_result($stmtCustomer);

if (mysqli_stmt_num_rows($stmtCustomer) > 0) {
    $userType = 'Customer';
}
mysqli_stmt_close($stmtCustomer);

// Otherwise, registered expert?
if ($userType === 'Guest') {
    $stmtExpert = mysqli_prepare($conn, "SELECT expert_id FROM experts WHERE email = ?");
    mysqli_stmt_bind_param($stmtExpert, 's', $email);
    mysqli_stmt_execute($stmtExpert);
    mysqli_stmt_store_result($stmtExpert);

    if (mysqli_stmt_num_rows($stmtExpert) > 0) {
        $userType = 'Expert';
    }
    mysqli_stmt_close($stmtExpert);
}

// 3. Save it to the inquiries table
$insertStmt = mysqli_prepare(
    $conn,
    "INSERT INTO inquiries (full_name, email, user_type, subject_type, message) VALUES (?, ?, ?, ?, ?)"
);
mysqli_stmt_bind_param($insertStmt, "sssss", $fullName, $email, $userType, $subjectType, $message);

if (mysqli_stmt_execute($insertStmt)) {
    mysqli_stmt_close($insertStmt);
    mysqli_close($conn);
    header('Location: ' . url('pages/contact.php?status=success'));
    exit;
} else {
    mysqli_stmt_close($insertStmt);
    mysqli_close($conn);
    header('Location: ' . url('pages/contact.php?status=error'));
    exit;
}
