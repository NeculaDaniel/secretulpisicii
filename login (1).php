<?php
ob_start(); // Previne erori de header
session_start();

// HASH-ul tau original
$admin_hash = '$2y$10$tnV94PUM94i4NX6CQRM36u4nIJRhxl6HrsB6Brj0eOR5TBsTvfcSu'; 
$login_success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['password']) && password_verify($_POST['password'], $admin_hash)) {
        
        // Login Reusit
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
        $login_success = true;
        
        // Incercam redirect PHP
        header('Location: admin.php');
    } else {
        $error = "Parolă incorectă!";
    }
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin</title>
    <style>
        body { background: #111827; height: 100vh; display: flex; align-items: center; justify-content: center; font-family: sans-serif; margin: 0; }
        .box { background: white; padding: 2rem; border-radius: 0.5rem; width: 100%; max-width: 350px; text-align: center; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); }
        input { width: 100%; padding: 0.5rem; margin-bottom: 1rem; border: 1px solid #ccc; border-radius: 0.25rem; }
        button { width: 100%; padding: 0.75rem; background: #2563eb; color: white; border: none; border-radius: 0.25rem; font-weight: bold; cursor: pointer; }
        .error { color: #dc2626; background: #fee2e2; padding: 0.5rem; margin-bottom: 1rem; }
        .success { color: #166534; background: #dcfce7; padding: 1rem; margin-bottom: 1rem; }
    </style>
</head>
<body>
    <div class="box">
        <h2 style="margin-top:0;">🔒 Login</h2>

        <?php if ($login_success): ?>
            <div class="success">
                <p>Login Reușit!</p>
                <script>window.location.href = 'admin.php';</script>
                <a href="admin.php" style="font-weight:bold; text-decoration:underline;">Click aici pentru Dashboard</a>
            </div>
        <?php else: ?>
            
            <?php if (isset($error)): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST">
                <input type="password" name="password" placeholder="Parolă Admin" required autofocus>
                <button type="submit">Intră</button>
            </form>

        <?php endif; ?>
    </div>
</body>
</html>
<?php ob_end_flush(); ?>