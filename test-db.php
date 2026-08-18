<?php

require_once __DIR__ . '/config/config.php';

try {

    db();

    echo "Database connected successfully!";

} catch (PDOException $e) {

    echo "Database connection failed: " . $e->getMessage();

}