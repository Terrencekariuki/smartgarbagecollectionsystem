<?php
// COLLECTOR: sees bin levels only (no resident names, phone numbers or payments).
require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/layout.php';
$u = require_role('collector');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $id = (int)($_POST['bin_id'] ?? 0);
    $s = db()->prepare('SELECT bin_code, fill_percent FROM bins WHERE id = ?');
    $s->execute([$id]);
    if ($b = $s->fetch()) {
        db()->prepare('INSERT INTO collections (bin_id, collector_id, fill_before) VALUES (?,?,?)')
            ->execute([$id, $u['id'], $b['fill_percent']]);
        add_reading($id, BIN_HEIGHT - 3);   // bin is empty again
        flash($b['bin_code'] . ' marked as collected.');
    }
    redirect('collector.php');
}

page_start('Bin levels', $u); ?>
<section class="kpis" id="kpis"></section>
<h2>Bin levels</h2>
<p class="note">Bins are sorted with the fullest first.</p>
<div class="grid" id="bins" data-role="collector" data-csrf="<?= h(csrf()) ?>"></div>
<?php page_end();
