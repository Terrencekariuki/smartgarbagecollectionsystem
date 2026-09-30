<?php
require __DIR__ . '/includes/config.php';
if ($u = user()) redirect(home_for($u['role']));
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $s = db()->prepare('SELECT * FROM users WHERE email = ?');
    $s->execute([trim($_POST['email'] ?? '')]);
    $row = $s->fetch();
    if ($row && password_verify($_POST['password'] ?? '', $row['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user'] = ['id' => (int)$row['id'], 'name' => $row['full_name'], 'role' => $row['role']];
        redirect(home_for($row['role']));
    }
    $error = 'Email or password is incorrect.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Sign in | Smart Garbage Collection</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body class="login">
<form method="post" class="card-login">
  <h1>Smart Garbage Collection</h1>
  <p class="note">Sign in to see your dashboard.</p>
  <?php if ($error): ?><div class="flash err" role="alert"><?= h($error) ?></div><?php endif; ?>
  <?= csrf_field() ?>
  <label>Email<input type="email" name="email" required autofocus></label>
  <label>Password<input type="password" name="password" required></label>
  <button class="btn p">Sign in</button>
</form>
</body>
</html>
