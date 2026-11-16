<?php
// Sign-in page for the admin area. Admin accounts live in their own
// `admins` table and are tracked with $_SESSION['admin_id'], separate from
// customer/expert logins.
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/login-guard.php';

if (isset($_SESSION['admin_id'])) {
    header('Location: inquiries.php');
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // Same brute-force protection as the customer login (includes/login-guard.php)
    $waitMinutes = loginLockedMinutes('admin', $username);

    $admin = null;
    if ($waitMinutes === 0) {
        $stmt = mysqli_prepare($conn, "SELECT admin_id, username, password FROM admins WHERE username = ?");
        mysqli_stmt_bind_param($stmt, 's', $username);
        mysqli_stmt_execute($stmt);
        $admin = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    }

    $passwordOk = $waitMinutes === 0 && $admin && password_verify($password, $admin['password']);
    if ($waitMinutes === 0) {
        recordLoginAttempt('admin', $username, (bool) $passwordOk);
    }

    if ($passwordOk) {
        // New session ID on privilege change, so a pre-planted session
        // cookie can't be reused to ride along on this login.
        session_regenerate_id(true);
        $_SESSION['admin_id']       = (int) $admin['admin_id'];
        $_SESSION['admin_username'] = $admin['username'];
        header('Location: inquiries.php');
        exit;
    }

    $error = $waitMinutes > 0
        ? "Too many failed attempts. Please wait {$waitMinutes} minute" . ($waitMinutes === 1 ? '' : 's') . '.'
        : 'Incorrect username or password.';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | Mendly</title>
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f4f6f9;
            color: #333;
        }

        form {
            width: min(360px, 90vw);
            padding: 30px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.06);
        }

        h1 {
            margin: 0 0 20px;
            font-size: 22px;
            color: #2c3e50;
        }

        label {
            display: block;
            margin: 14px 0 6px;
            font-weight: 600;
            font-size: 14px;
        }

        input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccd3dc;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 15px;
        }

        button {
            width: 100%;
            margin-top: 22px;
            padding: 11px;
            border: none;
            border-radius: 4px;
            background: #4a69bd;
            color: #fff;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
        }

        .error {
            padding: 10px;
            border-radius: 4px;
            background: #fdecea;
            color: #b3261e;
            font-size: 14px;
        }

        .back {
            display: block;
            margin-top: 16px;
            text-align: center;
            color: #4a69bd;
            font-size: 14px;
        }
    </style>
</head>

<body>
    <form method="post" action="login.php">
        <?php echo csrfField(); ?>
        <h1>Mendly Admin</h1>

        <?php if ($error): ?>
            <p class="error"><?php echo e($error); ?></p>
        <?php endif; ?>

        <label for="username">Username</label>
        <input type="text" id="username" name="username" autocomplete="username" required>

        <label for="password">Password</label>
        <input type="password" id="password" name="password" autocomplete="current-password" required>

        <button type="submit">Sign in</button>
        <a class="back" href="../index.php">&larr; Back to Mendly</a>
    </form>
</body>

</html>
