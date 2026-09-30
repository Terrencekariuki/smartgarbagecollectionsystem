<?php
// ADMIN: the only role that sees residents, collectors, collection records, alerts and invoices.
require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/layout.php';
$u = require_role('admin');
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'register') {
        $name = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $pass = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? '';
        $code = trim($_POST['bin_code'] ?? '');
        $loc = trim($_POST['location'] ?? '');
        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($pass) < 8
            || !in_array($role, ['resident', 'collector'], true)
            || ($role === 'resident' && $code !== '' && $loc === '')) {
            flash('Check the form: name, valid email, password of 8+ characters and a bin location are required.');
        } else {
            try {
                $pdo->beginTransaction();
                $pdo->prepare('INSERT INTO users (full_name, email, phone, password_hash, role) VALUES (?,?,?,?,?)')
                    ->execute([$name, $email, $phone, password_hash($pass, PASSWORD_DEFAULT), $role]);
                $uid = (int)$pdo->lastInsertId();
                if ($role === 'resident' && $code !== '') {
                    $pdo->prepare('INSERT INTO bins (bin_code, location, resident_id) VALUES (?,?,?)')->execute([$code, $loc, $uid]);
                }
                $pdo->commit();
                flash($name . ' registered as ' . $role . '.');
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                flash('That email or bin code is already in use.');
            }
        }
    }
    if ($action === 'invoice') {
        $rid = (int)($_POST['resident_id'] ?? 0);
        $amt = filter_var($_POST['amount'] ?? '', FILTER_VALIDATE_FLOAT);
        $chk = $pdo->prepare("SELECT id FROM users WHERE id = ? AND role = 'resident'");
        $chk->execute([$rid]);
        if ($chk->fetch() && $amt !== false && $amt > 0) {
            $no = 'INV-' . strtoupper(bin2hex(random_bytes(3)));
            $pdo->prepare('INSERT INTO invoices (invoice_no, resident_id, amount) VALUES (?,?,?)')->execute([$no, $rid, $amt]);
            flash($no . ' issued.');
        } else {
            flash('Choose a resident and enter an amount above zero.');
        }
    }
    if ($action === 'paid') {
        $pdo->prepare("UPDATE invoices SET status = 'Paid' WHERE id = ?")->execute([(int)($_POST['invoice_id'] ?? 0)]);
        flash('Invoice marked as paid.');
    }
    if ($action === 'test') {
        $fill = add_reading((int)($_POST['bin_id'] ?? 0), (int)($_POST['distance'] ?? 0));
        flash($fill === null ? 'Bin not found.' : 'Test reading saved. Bin is now ' . $fill . '% full.');
    }
    redirect('admin.php');
}

$residents = $pdo->query("SELECT id, full_name FROM users WHERE role = 'resident' ORDER BY full_name")->fetchAll();
$people = $pdo->query("SELECT full_name, email, phone, role, created_at FROM users WHERE role <> 'admin' ORDER BY role, full_name")->fetchAll();
$bins = $pdo->query('SELECT id, bin_code FROM bins ORDER BY bin_code')->fetchAll();
$collections = $pdo->query('SELECT c.collected_at, c.fill_before, b.bin_code, u.full_name FROM collections c JOIN bins b ON b.id = c.bin_id JOIN users u ON u.id = c.collector_id ORDER BY c.collected_at DESC LIMIT 20')->fetchAll();
$alerts = $pdo->query('SELECT message, created_at FROM notifications ORDER BY created_at DESC LIMIT 20')->fetchAll();
$invoices = $pdo->query('SELECT i.id, i.invoice_no, i.amount, i.status, i.issued_at, u.full_name FROM invoices i JOIN users u ON u.id = i.resident_id ORDER BY i.issued_at DESC LIMIT 30')->fetchAll();

page_start('Admin overview', $u); ?>
<section class="kpis" id="kpis"></section>
<h2>All bins</h2>
<div class="grid" id="bins" data-role="admin" data-csrf="<?= h(csrf()) ?>"></div>

<div class="two">
<div class="panel"><h3>Register a resident or collector</h3>
<form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="register">
  <label>Full name<input name="full_name" required maxlength="80"></label>
  <label>Email<input type="email" name="email" required maxlength="100"></label>
  <label>Phone<input name="phone" maxlength="20"></label>
  <label>Temporary password<input type="password" name="password" required minlength="8"></label>
  <label>Role<select name="role"><option value="resident">Resident</option><option value="collector">Garbage collector</option></select></label>
  <label>Bin code (residents)<input name="bin_code" maxlength="20" placeholder="BIN-05"></label>
  <label>Bin location (residents)<input name="location" maxlength="100" placeholder="Block D, Gate 2"></label>
  <button class="btn p">Register</button></form></div>

<div class="panel"><h3>Issue an invoice</h3>
<form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="invoice">
  <label>Resident<select name="resident_id" required><?php foreach ($residents as $r): ?><option value="<?= (int)$r['id'] ?>"><?= h($r['full_name']) ?></option><?php endforeach; ?></select></label>
  <label>Amount (KES)<input type="number" name="amount" min="1" step="0.01" value="300" required></label>
  <button class="btn p">Issue invoice</button></form>
<h3 style="margin-top:18px">Send a test sensor reading</h3>
<form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="test">
  <label>Bin<select name="bin_id"><?php foreach ($bins as $b): ?><option value="<?= (int)$b['id'] ?>"><?= h($b['bin_code']) ?></option><?php endforeach; ?></select></label>
  <label>Distance (cm)<input type="number" name="distance" min="2" max="100" value="15" required></label>
  <button class="btn">Send reading</button></form></div>
</div>

<h2>Residents and collectors</h2>
<div class="panel"><table><thead><tr><th>Name</th><th>Role</th><th>Email</th><th>Phone</th></tr></thead><tbody>
<?php foreach ($people as $p): ?><tr><td><?= h($p['full_name']) ?></td><td><?= h($p['role']) ?></td><td><?= h($p['email']) ?></td><td><?= h($p['phone']) ?></td></tr><?php endforeach; ?>
</tbody></table></div>

<div class="two">
<div><h2>Collector activity</h2><div class="panel">
<?php if (!$collections): ?><p class="empty">No collections recorded yet.</p><?php else: ?>
<table><thead><tr><th>When</th><th>Bin</th><th>Was full</th><th>Collector</th></tr></thead><tbody>
<?php foreach ($collections as $c): ?><tr><td><?= h($c['collected_at']) ?></td><td><?= h($c['bin_code']) ?></td><td><?= (int)$c['fill_before'] ?>%</td><td><?= h($c['full_name']) ?></td></tr><?php endforeach; ?>
</tbody></table><?php endif; ?></div></div>
<div><h2>Alerts</h2><div class="panel">
<?php if (!$alerts): ?><p class="empty">No alerts yet.</p><?php else: ?>
<table><thead><tr><th>When</th><th>Message</th></tr></thead><tbody>
<?php foreach ($alerts as $a): ?><tr><td><?= h($a['created_at']) ?></td><td><?= h($a['message']) ?></td></tr><?php endforeach; ?>
</tbody></table><?php endif; ?></div></div>
</div>

<h2>Invoices</h2>
<div class="panel"><?php if (!$invoices): ?><p class="empty">No invoices yet.</p><?php else: ?>
<table><thead><tr><th>Invoice</th><th>Resident</th><th>Amount (KES)</th><th>Status</th><th></th></tr></thead><tbody>
<?php foreach ($invoices as $i): ?><tr><td><?= h($i['invoice_no']) ?></td><td><?= h($i['full_name']) ?></td><td><?= number_format($i['amount'], 2) ?></td>
<td><span class="tag <?= $i['status'] === 'Paid' ? 'g' : 'w' ?>"><?= h($i['status']) ?></span></td>
<td><?php if ($i['status'] === 'Unpaid'): ?><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="paid"><input type="hidden" name="invoice_id" value="<?= (int)$i['id'] ?>"><button class="btn s">Mark paid</button></form><?php endif; ?></td></tr><?php endforeach; ?>
</tbody></table><?php endif; ?></div>
<?php page_end();
