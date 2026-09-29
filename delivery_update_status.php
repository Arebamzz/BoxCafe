<?php
require_once __DIR__ . '/includes/database.php';
$orderId = (int)($_GET['order_id'] ?? 0);
$status  = $_GET['s'] ?? 'PREPARING';

$allowed = ['PREPARING','OUT_FOR_DELIVERY','DELIVERED','CANCELLED'];
if ($orderId <= 0) die("Invalid order.");
if (!in_array($status, $allowed, true)) die("Invalid status.");

$conn = box_database();
if ($conn->connect_error) die("DB error.");

$stmt = $conn->prepare("UPDATE orders SET delivery_status=? WHERE order_id=?");
$stmt->bind_param("si", $status, $orderId);
$stmt->execute();
$stmt->close();
$conn->close();

header("Location: delivery_track.php?order_id=".$orderId);
exit;
