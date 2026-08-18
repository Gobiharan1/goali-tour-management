<?php
require_once __DIR__ . '/config/config.php';
if (!empty($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
} else {
    header('Location: login.php');
}
exit;
