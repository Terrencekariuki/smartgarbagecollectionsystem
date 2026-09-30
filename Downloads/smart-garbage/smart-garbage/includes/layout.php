<?php
function page_start(string $title, array $u): void {
    $labels = ['admin' => 'Estate admin', 'collector' => 'Garbage collector', 'resident' => 'Resident']; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= h($title) ?> | Smart Garbage Collection</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<header>
  <div><h1>Smart Garbage Collection</h1><small><?= h($labels[$u['role']]) ?> dashboard</small></div>
  <div class="who"><span><?= h($u['name']) ?></span><a class="btn s" href="logout.php">Sign out</a></div>
</header>
<main>
<?php if ($m = flash()): ?><div class="flash" role="status"><?= h($m) ?></div><?php endif;
}
function page_end(): void { ?>
</main>
<script src="assets/app.js"></script>
</body>
</html>
<?php }
