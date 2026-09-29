<?php
session_start();

// ✅ Force login before adding to cart
if (!isset($_SESSION['user_id'])) {
    header("Location: user_login.php");
    exit();
}

// ✅ Read data
$product_id = $_POST['product_id'];
$type = $_POST['type']; // 'menu' or 'merchandise'
$quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
$remark = isset($_POST['remark']) ? trim($_POST['remark']) : "";

// ✅ Initialize cart
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = array();
}

// ✅ Check for duplicate item (same product, type, and remark)
$found = false;
foreach ($_SESSION['cart'] as &$item) {
    if (
        $item['product_id'] == $product_id &&
        $item['type'] == $type &&
        $item['remark'] == $remark
    ) {
        $item['quantity'] += $quantity;
        $found = true;
        break;
    }
}

if (!$found) {
    $_SESSION['cart'][] = array(
        "product_id" => $product_id,
        "type" => $type,
        "quantity" => $quantity,
        "remark" => $remark
    );
}

// ✅ Redirect to cart
header("Location: cart.php");
exit;
?>
