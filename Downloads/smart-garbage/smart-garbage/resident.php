<?php
// RESIDENT: can only see their own bin and their own invoices.
require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/layout.php';
$u = require_role('resident');

$s = db()->prepare('SELECT invoice_no, amount, status, issued_at FROM invoices WHERE resident_id = ? ORDER BY issued_at DESC');
$s->execute([$u['id']]);
$invoices = $s->fetchAll();
$due = 0;
foreach ($invoices as $i) if ($i['status'] === 'Unpaid') $due += $i['amount'];

page_start('My bin', $u); ?>
<h2>My bin</h2>
<div class="grid" id="bins" data-role="resident" data-csrf="<?= h(csrf()) ?>"></div>

<h2>Payment details</h2>
<div class="panel">
  <p><strong>Balance due: KES <?= number_format($due, 2) ?></strong></p>
  <?php if (!$invoices): ?><p class="empty">No invoices yet.</p><?php else: ?>
  <table><thead><tr><th>Invoice</th><th>Date</th><th>Amount (KES)</th><th>Status</th></tr></thead><tbody>
  <?php foreach ($invoices as $i): ?>
    <tr><td><?= h($i['invoice_no']) ?></td><td><?= h(substr($i['issued_at'], 0, 10)) ?></td>
    <td><?= number_format($i['amount'], 2) ?></td>
    <td><span class="tag <?= $i['status'] === 'Paid' ? 'g' : 'w' ?>"><?= h($i['status']) ?></span></td></tr>
  <?php endforeach; ?>
  </tbody></table>
  <?php endif; ?>
</div>
<?php page_end();
