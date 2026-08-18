<?php
require_once __DIR__ . '/config/config.php';
require_login();

$tours = db()->query("SELECT t.*, c.name category_name FROM tours t JOIN categories c ON c.id=t.category_id WHERE t.status='recycled' ORDER BY t.deleted_at DESC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    if ($_POST['action'] === 'restore') {
        db()->prepare("UPDATE tours SET status='active', deleted_at=NULL WHERE id=?")->execute([$id]);
    } elseif ($_POST['action'] === 'delete_forever') {
        db()->prepare("DELETE FROM tours WHERE id=?")->execute([$id]);
    }
    header('Location: recycle_bin.php');
    exit;
}
?>
<!doctype html>
<html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Recycle Bin — Goali Tours</title><link rel="stylesheet" href="assets/css/style.css">
</head><body><div class="form-shell">
<div class="form-header"><div><a href="dashboard.php" class="back">← Dashboard</a><h1>Recycle Bin</h1></div></div>
<div class="table-wrap"><table><thead><tr><th>Package</th><th>Category</th><th>Deleted</th><th>Actions</th></tr></thead><tbody>
<?php foreach($tours as $tour): ?><tr>
<td><?= e($tour['tour_name']) ?><br><span class="muted"><?= e($tour['package_id']) ?></span></td>
<td><?= e($tour['category_name']) ?></td><td><?= e($tour['deleted_at']) ?></td>
<td>
<form method="post" class="inline"><input type="hidden" name="id" value="<?= (int)$tour['id'] ?>"><button class="btn small-btn" name="action" value="restore">Restore</button><button class="btn danger small-btn" name="action" value="delete_forever" onclick="return confirm('Delete permanently?')">Delete Forever</button></form>
</td></tr><?php endforeach; ?>
</tbody></table></div>
</div></body></html>
