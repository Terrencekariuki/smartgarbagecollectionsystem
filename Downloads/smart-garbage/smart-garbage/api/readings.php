<?php
// The ESP32 sends: {"api_key":"...","bin_code":"BIN-01","distance_cm":34}
require __DIR__ . '/../includes/config.php';
header('Content-Type: application/json');
function fail(int $code, string $msg): void { http_response_code($code); echo json_encode(['error' => $msg]); exit; }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail(405, 'Use POST');
$in = json_decode(file_get_contents('php://input'), true) ?: $_POST;
if (!hash_equals(API_KEY, (string)($in['api_key'] ?? ''))) fail(401, 'Invalid API key');
$code = trim((string)($in['bin_code'] ?? ''));
$dist = filter_var($in['distance_cm'] ?? null, FILTER_VALIDATE_INT);
if ($code === '' || $dist === false) fail(422, 'bin_code and distance_cm are required');

$s = db()->prepare('SELECT id FROM bins WHERE bin_code = ?');
$s->execute([$code]);
$id = $s->fetchColumn();
if (!$id) fail(404, 'Unknown bin');
echo json_encode(['ok' => true, 'fill_percent' => add_reading((int)$id, $dist)]);
