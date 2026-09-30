<?php
require __DIR__ . '/includes/config.php';
$_SESSION = [];
session_destroy();
redirect('login.php');
