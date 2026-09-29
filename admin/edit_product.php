<?php
require 'headerAdmin.php';
require_once 'db_connect.php';


// Redirect if not logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

// Fetch menu item by ID
if (!isset($_GET['id'])) {
    header("Location: products.php");
    exit;
}

$id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM menu_items WHERE id = ?");
$stmt->execute([$id]);
$item = $stmt->fetch();

if (!$item) {
    echo "Item not found!";
    exit;
}

$message = '';

// Handle update form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $price = $_POST['price'];
    $description = $_POST['description'];
    $subcategory_id = isset($_POST['subcategory_id']) ? $_POST['subcategory_id'] : $item['subcategory_id'];

    // Promo fields (optional)
    $promo_active = isset($_POST['promo_active']) ? 1 : 0;
    $discount_type = isset($_POST['discount_type']) && $_POST['discount_type'] !== '' ? $_POST['discount_type'] : null;
    $discount_value = isset($_POST['discount_value']) && $_POST['discount_value'] !== '' ? $_POST['discount_value'] : null;
    $promo_start = isset($_POST['promo_start']) && $_POST['promo_start'] !== '' ? $_POST['promo_start'] : null;
    $promo_end = isset($_POST['promo_end']) && $_POST['promo_end'] !== '' ? $_POST['promo_end'] : null;

    if ($promo_active == 0) {
        $discount_type = null;
        $discount_value = null;
        $promo_start = null;
        $promo_end = null;
    }

    // Handle image upload if a new image is provided
    $image_path = $item['image_path']; // keep existing
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $target_dir = "../images/";
        $file_name = basename($_FILES['image']['name']);
        $target_file = $target_dir . $file_name;
        move_uploaded_file($_FILES['image']['tmp_name'], $target_file);

        // store same format as add_product.php: "images/xxx"
        $image_path = "images/" . $file_name;
    }

    // Update menu item (include promo columns)
    $stmt = $pdo->prepare("UPDATE menu_items 
        SET subcategory_id = ?, name = ?, price = ?, discount_type = ?, discount_value = ?, promo_start = ?, promo_end = ?, promo_active = ?, description = ?, image_path = ?
        WHERE id = ?");
    $stmt->execute([
        $subcategory_id,
        $name,
        $price,
        $discount_type,
        $discount_value,
        $promo_start,
        $promo_end,
        $promo_active,
        $description,
        $image_path,
        $id
    ]);

    $message = "Menu item updated successfully!";

    // Refresh item data
    $stmt = $pdo->prepare("SELECT * FROM menu_items WHERE id = ?");
    $stmt->execute([$id]);
    $item = $stmt->fetch();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Product - BoxCafe</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f4f4;
            padding: 2rem;
        }
        form {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            max-width: 520px;
            margin: auto;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        }
        input[type="text"], input[type="number"], input[type="date"], textarea, input[type="file"], select {
            width: 100%;
            padding: 0.8rem;
            margin-bottom: 1rem;
            border: 1px solid #ccc;
            border-radius: 6px;
        }
        button {
            padding: 0.8rem 1.5rem;
            background: #111;
            color: white;
            border: none;
            border-radius: 6px;
        }
        img {
            max-width: 140px;
            margin-bottom: 1rem;
            border-radius: 6px;
            display:block;
        }
        .success {
            color: green;
            text-align: center;
            margin-bottom: 1rem;
        }
        .promo-box{
            border: 1px solid #eee;
            padding: 14px;
            border-radius: 10px;
            margin-bottom: 14px;
            background: #fafafa;
        }
        .row{
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }
        .checkbox{
            display:flex;
            gap:10px;
            align-items:center;
            margin-bottom: 10px;
        }
        .hint{
            font-size: 12px;
            color:#666;
            margin-top: -8px;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>

<h2 style="text-align: center;">Edit Menu Item</h2>

<?php if ($message): ?>
    <p class="success"><?= $message ?></p>
<?php endif; ?>

<form method="post" enctype="multipart/form-data">
    <input type="text" name="name" value="<?= htmlspecialchars($item['name']) ?>" placeholder="Product Name" required>
    <textarea name="description" placeholder="Description" rows="4"><?= htmlspecialchars($item['description']) ?></textarea>
    <input type="number" step="0.01" name="price" value="<?= $item['price'] ?>" placeholder="Price (RM)" required>

    <label>Subcategory:</label>
    <select name="subcategory_id" required>
        <option value="1" <?= ($item['subcategory_id']==1?'selected':'') ?>>Main Course</option>
        <option value="2" <?= ($item['subcategory_id']==2?'selected':'') ?>>Pasta</option>
        <option value="3" <?= ($item['subcategory_id']==3?'selected':'') ?>>Breakfast and Salad</option>
        <option value="4" <?= ($item['subcategory_id']==4?'selected':'') ?>>Snacks</option>
        <option value="5" <?= ($item['subcategory_id']==5?'selected':'') ?>>Burger and Sandwich</option>
        <option value="6" <?= ($item['subcategory_id']==6?'selected':'') ?>>Coffee</option>
        <option value="7" <?= ($item['subcategory_id']==7?'selected':'') ?>>Non-Coffee</option>
    </select>

    <!-- Promo / Discount -->
    <div class="promo-box">
        <h3 style="margin:0 0 10px;">Promotion / Discount</h3>

        <div class="checkbox">
            <input type="checkbox" id="promo_active" name="promo_active" value="1" <?= (!empty($item['promo_active']) ? 'checked' : '') ?>>
            <label for="promo_active" style="margin:0;">Enable promo for this item</label>
        </div>
        <div class="hint">User side will show cut price only if promo is enabled and valid date.</div>

        <label>Discount Type:</label>
        <select name="discount_type">
            <option value="">-- No Discount Type --</option>
            <option value="percent" <?= ($item['discount_type']=='percent'?'selected':'') ?>>Percent (%)</option>
            <option value="fixed" <?= ($item['discount_type']=='fixed'?'selected':'') ?>>Fixed (RM)</option>
        </select>

        <label>Discount Value:</label>
        <input type="number" step="0.01" name="discount_value" value="<?= htmlspecialchars($item['discount_value'] ?? '') ?>" placeholder="Example: 20 (for 20%) OR 5 (RM5 off)">

        <div class="row">
            <div>
                <label>Promo Start Date:</label>
                <input type="date" name="promo_start" value="<?= htmlspecialchars($item['promo_start'] ?? '') ?>">
            </div>
            <div>
                <label>Promo End Date:</label>
                <input type="date" name="promo_end" value="<?= htmlspecialchars($item['promo_end'] ?? '') ?>">
            </div>
        </div>
        <div class="hint">Leave dates empty = promo always valid (when enabled).</div>
    </div>

    <p>Current Image:</p>
    <img src="../<?= htmlspecialchars($item['image_path']) ?>" alt="Current Product Image">

    <input type="file" name="image" accept="image/*">
    <button type="submit">Update Product</button>
</form>

</body>
</html>
<?php require 'footerAdmin.php'; ?>
