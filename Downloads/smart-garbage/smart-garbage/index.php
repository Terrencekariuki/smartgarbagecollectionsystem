<?php
require __DIR__ . '/includes/config.php';
$u = user();
redirect($u ? home_for($u['role']) : 'login.php');
