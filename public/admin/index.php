<?php

declare(strict_types=1);

require __DIR__ . '/../../src/bootstrap.php';

use Salon\Csrf;
use Salon\Env;
use Salon\ReportRepository;

$hash = Env::get('ADMIN_PASSWORD_HASH');
$reports = new ReportRepository($db);
$error = null;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!Csrf::valid($_POST['csrf'] ?? null)) {
        $error = 'Session expired. Please try again.';
    } elseif (isset($_POST['password'])) {
        if ($hash && password_verify((string) $_POST['password'], $hash)) {
            session_regenerate_id(true);
            $_SESSION['admin'] = true;
            header('Location: ./', true, 303);
            exit;
        }
        $error = 'Wrong password.';
    } elseif (isset($_POST['logout'])) {
        $_SESSION = [];
        session_destroy();
        header('Location: ./', true, 303);
        exit;
    } elseif (!empty($_SESSION['admin']) && isset($_POST['id'], $_POST['status'])) {
        $reports->setStatus((int) $_POST['id'], (string) $_POST['status']);
        header('Location: ./', true, 303);
        exit;
    }
}

$authed = !empty($_SESSION['admin']);
$today = (new DateTimeImmutable('today'))->format('Y-m-d');
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="robots" content="noindex">
	<title>Salon Admin</title>
	<link rel="stylesheet" href="../css/bootstrap.min.css">
</head>
<body class="bg-light">
<main class="container py-5">
	<h1 class="h3 mb-4">Salon Admin</h1>

	<?php if (!$hash): ?>
		<div class="alert alert-warning">Admin is disabled. Set <code>ADMIN_PASSWORD_HASH</code> in your environment (see README).</div>
	<?php elseif (!$authed): ?>
		<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
		<form method="post" class="card card-body" style="max-width: 360px">
			<?= Csrf::field() ?>
			<label for="password">Password</label>
			<input type="password" id="password" name="password" class="form-control mb-3" required autofocus>
			<button class="btn btn-primary">Sign in</button>
		</form>
	<?php else: ?>
		<form method="post" class="mb-4"><?= Csrf::field() ?><button name="logout" value="1" class="btn btn-sm btn-outline-secondary">Sign out</button></form>

		<h2 class="h5">Upcoming appointments</h2>
		<div class="table-responsive mb-5">
		<table class="table table-sm table-striped bg-white">
			<thead><tr><th>Date</th><th>Time</th><th>No.</th><th>Name</th><th>Contact</th><th>Service</th><th>Status</th></tr></thead>
			<tbody>
			<?php foreach ($reports->upcoming($today) as $a): ?>
				<tr>
					<td><?= e($a['apt_date']) ?></td>
					<td><?= e(substr((string) $a['apt_time'], 0, 5)) ?></td>
					<td><?= e($a['apt_number']) ?></td>
					<td><?= e($a['name']) ?></td>
					<td><?= e($a['email']) ?><br><?= e($a['phone']) ?></td>
					<td><?= e($a['service']) ?></td>
					<td>
						<form method="post" class="form-inline">
							<?= Csrf::field() ?>
							<input type="hidden" name="id" value="<?= e($a['id']) ?>">
							<select name="status" class="form-control form-control-sm mr-1" aria-label="Status">
								<?php foreach (['pending', 'confirmed', 'completed', 'cancelled'] as $s): ?>
									<option<?= $s === $a['status'] ? ' selected' : '' ?>><?= e($s) ?></option>
								<?php endforeach; ?>
							</select>
							<button class="btn btn-sm btn-outline-primary">Save</button>
						</form>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		</div>

		<h2 class="h5">Bookings by service (all time, excluding cancelled)</h2>
		<table class="table table-sm bg-white" style="max-width: 560px">
			<thead><tr><th>Service</th><th class="text-right">Bookings</th><th class="text-right">Booked value</th></tr></thead>
			<tbody>
			<?php foreach ($reports->byService() as $r): ?>
				<tr><td><?= e($r['service']) ?></td><td class="text-right"><?= e($r['bookings']) ?></td><td class="text-right">$<?= e(number_format((float) $r['booked_value'], 2)) ?></td></tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>
</main>
</body>
</html>
