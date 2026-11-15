<?php
session_start();
include("includes/db.php");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if ($email === '' || $password === '') {
    header('Location: login.php?error=missing_fields');
    exit;
}

// Look up the account by email — check customers first, then experts,
// since accounts now live in two separate tables instead of one.
$stmt = mysqli_prepare(
    $conn,
    "SELECT customer_id AS id, full_name, password, avatar, avatar_mime FROM customers WHERE email = ?"
);

mysqli_stmt_bind_param($stmt, 's', $email);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$account = mysqli_fetch_assoc($result);
$role    = 'customer';

mysqli_stmt_close($stmt);

if (!$account) {
    $stmt = mysqli_prepare(
        $conn,
        "SELECT expert_id AS id, full_name, password, avatar, avatar_mime, first_login_done FROM experts WHERE email = ?"
    );

    mysqli_stmt_bind_param($stmt, 's', $email);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $account = mysqli_fetch_assoc($result);
    $role    = 'expert';

    mysqli_stmt_close($stmt);
}

// password_verify() checks the submitted password against the stored hash
if ($account && password_verify($password, $account['password'])) {
    $_SESSION['user_id']   = $account['id'];
    $_SESSION['role']      = $role;
    $_SESSION['full_name'] = $account['full_name'];

    // The avatar is stored as raw binary data in the database, not a file
    // path — build it into a data URI (a text string with the whole
    // image encoded inline) so nav.php can keep using it directly as an
    // <img src="..."> with no other changes needed.
    if (!empty($account['avatar']) && !empty($account['avatar_mime'])) {
        $_SESSION['avatar'] = 'data:' . $account['avatar_mime'] . ';base64,' . base64_encode($account['avatar']);
    } else {
        $_SESSION['avatar'] = null;
    }

    // First-ever login for a newly registered expert: send them to set
    // up their first service listing instead of the home page. This is a
    // one-time nudge — mark it done right away so it never happens again
    // on future logins, whether or not they actually add a service now.
    $sendToAddService = $role === 'expert' && empty($account['first_login_done']);

    if ($sendToAddService) {
        $expertId = (int) $account['id'];

        $markDoneStmt = mysqli_prepare($conn, "UPDATE experts SET first_login_done = TRUE WHERE expert_id = ?");
        mysqli_stmt_bind_param($markDoneStmt, 'i', $expertId);
        mysqli_stmt_execute($markDoneStmt);
        mysqli_stmt_close($markDoneStmt);
    }

    mysqli_close($conn);

    header('Location: ' . ($sendToAddService ? 'add-service.php?welcome=1' : 'index.php'));
    exit;
} else {
    mysqli_close($conn);

    header('Location: login.php?error=invalid_credentials');
    exit;
}
?>
