<?php

require_once __DIR__ . '/../config/app.php';

$dsn = sprintf(
    'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
    box_config('db_host'),
    box_config('db_port'),
    box_config('db_name')
);
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, box_config('db_user'), box_config('db_password'), $options);
} catch (PDOException $e) {
    error_log('[DATABASE] ' . $e->getMessage());
    http_response_code(500);
    exit('Database connection failed. Please check your local configuration.');
}