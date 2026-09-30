<?php
// Database settings (XAMPP defaults). Do not commit real passwords to GitHub.
const DB_HOST = 'localhost';
const DB_NAME = 'smart_garbage';
const DB_USER = 'root';
const DB_PASS = '';
const API_KEY = 'change-this-key-before-deploying';   // the ESP32 sends this with each reading
const BIN_HEIGHT = 100;   // cm from the sensor to the bottom of an empty bin
const THRESHOLD  = 80;    // % full that triggers an alert

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

function db(): PDO {
    static $pdo = null;
    if (!$pdo) {
        $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
    return $pdo;
}

function h($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function user(): ?array { return $_SESSION['user'] ?? null; }
function redirect(string $to): void { header('Location: ' . $to); exit; }
function home_for(string $role): string { return $role . '.php'; }

// Every protected page calls this first. A user of any other role gets a 403.
function require_role(string $role): array {
    $u = user();
    if (!$u) redirect('login.php');
    if ($u['role'] !== $role) {
        http_response_code(403);
        exit('Access denied. This page is not available for your role.');
    }
    return $u;
}

function csrf(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . h(csrf()) . '">'; }
function check_csrf(): void {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(400);
        exit('Invalid form token. Go back, refresh the page and try again.');
    }
}
function flash(?string $m = null): ?string {
    if ($m !== null) { $_SESSION['flash'] = $m; return null; }
    $x = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $x;
}

// Saves a sensor reading, updates the bin and raises an alert when it becomes nearly full.
function add_reading(int $binId, int $dist): ?int {
    $dist = max(2, min(BIN_HEIGHT, $dist));
    $fill = (int)round((BIN_HEIGHT - $dist) / BIN_HEIGHT * 100);
    $pdo = db();
    $s = $pdo->prepare('SELECT bin_code, location, alerted FROM bins WHERE id = ?');
    $s->execute([$binId]);
    $bin = $s->fetch();
    if (!$bin) return null;
    $pdo->prepare('INSERT INTO readings (bin_id, distance_cm, fill_percent) VALUES (?,?,?)')->execute([$binId, $dist, $fill]);
    $alerted = (int)$bin['alerted'];
    if ($fill >= THRESHOLD && !$alerted) {
        $msg = $bin['bin_code'] . ' (' . $bin['location'] . ') is ' . $fill . '% full';
        $pdo->prepare('INSERT INTO notifications (bin_id, message) VALUES (?,?)')->execute([$binId, $msg]);
        $alerted = 1;
    } elseif ($fill < THRESHOLD) {
        $alerted = 0;
    }
    $pdo->prepare('UPDATE bins SET distance_cm = ?, fill_percent = ?, alerted = ? WHERE id = ?')->execute([$dist, $fill, $alerted, $binId]);
    return $fill;
}
