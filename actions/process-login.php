<?php
require_once __DIR__ . '/../includes/helpers.php';   // starts the session, url(), e(), requireRole()
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/login-guard.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . url('pages/login.php'));
    exit;
}

$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if ($email === '' || $password === '') {
    header('Location: ' . url('pages/login.php?error=missing_fields'));
    exit;
}

// Too many wrong passwords recently (for this email or from this IP)?
// Checked before the password, so a locked account can't be probed.
$waitMinutes = loginLockedMinutes('login', $email);
if ($waitMinutes > 0) {
    redirect('pages/login.php?error=locked&wait=' . $waitMinutes);
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
$passwordOk = $account && password_verify($password, $account['password']);
recordLoginAttempt('login', $email, $passwordOk);

if ($passwordOk) {
    // Fresh session ID + user details in the session (includes/auth.php)
    loginUser($role, $account);

    // "Remember me" ticked: keep this browser logged in for 30 days
    if (!empty($_POST['remember'])) {
        rememberLogin($conn, $role, (int) $account['id']);
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

    header('Location: ' . url($sendToAddService ? 'pages/add-service.php?welcome=1' : 'index.php'));
    exit;
} else {
    mysqli_close($conn);

    header('Location: ' . url('pages/login.php?error=invalid_credentials'));
    exit;
}
?>
