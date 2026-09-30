<?php
// Returns only the bins (and fields) that the signed-in role is allowed to see.
require __DIR__ . '/../includes/config.php';
header('Content-Type: application/json');
$u = user();
if (!$u) { http_response_code(401); echo json_encode(['error' => 'Not signed in']); exit; }
$pdo = db();
if ($u['role'] === 'resident') {
    $s = $pdo->prepare('SELECT id, bin_code, location, fill_percent, updated_at FROM bins WHERE resident_id = ? ORDER BY bin_code');
    $s->execute([$u['id']]);
} elseif ($u['role'] === 'collector') {
    $s = $pdo->query('SELECT id, bin_code, location, fill_percent, updated_at FROM bins ORDER BY fill_percent DESC');
} else {
    $s = $pdo->query('SELECT b.id, b.bin_code, b.location, b.fill_percent, b.updated_at, u.full_name AS owner FROM bins b LEFT JOIN users u ON u.id = b.resident_id ORDER BY b.fill_percent DESC');
}
echo json_encode(['bins' => $s->fetchAll()]);
