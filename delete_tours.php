<?php
require_once __DIR__ . '/config/config.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}

verify_csrf();

$ids = $_POST['tour_ids'] ?? [];
if ($ids && is_array($ids)) {
    $ids = array_values(array_filter(array_map('intval', $ids)));
    if ($ids) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        db()->prepare("UPDATE tours SET status='recycled', deleted_at=NOW() WHERE id IN ($placeholders)")
           ->execute($ids);
    }
}
header('Location: dashboard.php?category_id=' . (int)($_POST['category_id'] ?? 0));
exit;
