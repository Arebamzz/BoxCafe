<?php 
require 'headerAdmin.php';
require_once 'db_connect.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $price = $_POST['price'];
    $description = $_POST['description'];

    // Prevent warning if key is missing
    $subcategory_id = isset($_POST['subcategory_id']) ? $_POST['subcategory_id'] : null;

    // Promo fields (optional)
    $promo_active = isset($_POST['promo_active']) ? 1 : 0;
    $discount_type = isset($_POST['discount_type']) && $_POST['discount_type'] !== '' ? $_POST['discount_type'] : null;
    $discount_value = isset($_POST['discount_value']) && $_POST['discount_value'] !== '' ? $_POST['discount_value'] : null;
    $promo_start = isset($_POST['promo_start']) && $_POST['promo_start'] !== '' ? $_POST['promo_start'] : null;
    $promo_end = isset($_POST['promo_end']) && $_POST['promo_end'] !== '' ? $_POST['promo_end'] : null;

    // If promo not active, force null to avoid confusion
    if ($promo_active == 0) {
        $discount_type = null;
        $discount_value = null;
        $promo_start = null;
        $promo_end = null;
    }

    $image_path = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $target_dir = "../images/";
        $image_path = basename($_FILES['image']['name']);
        $target_file = $target_dir . $image_path;
        move_uploaded_file($_FILES['image']['tmp_name'], $target_file);
    }

    if ($subcategory_id) {
        // Insert with promo columns
        $stmt = $pdo->prepare("INSERT INTO menu_items 
            (subcategory_id, name, price, discount_type, discount_value, promo_start, promo_end, promo_active, description, image_path) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
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
            "images/" . $image_path
        ]);

        $message = "✅ Product successfully added to menu!";
    } else {
        $message = "⚠️ Please select a subcategory before submitting.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Product - BoxCafe</title>
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
        .success {
            color: green;
            text-align: center;
            margin-bottom: 1rem;
        }
        .error {
            color: red;
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
        .hint{
            font-size: 12px;
            color:#666;
            margin-top: -8px;
            margin-bottom: 10px;
        }
        .checkbox{
            display:flex;
            gap:10px;
            align-items:center;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>

<h2 style="text-align: center;">Add New Menu Item</h2>

<?php if ($message): ?>
    <p class="<?= str_starts_with($message, '✅') ? 'success' : 'error' ?>"><?= $message ?></p>
<?php endif; ?>

<form method="post" enctype="multipart/form-data">
    <input type="text" name="name" placeholder="Product Name" required>
    <textarea name="description" placeholder="Description" rows="4"></textarea>
    <input type="number" step="0.01" name="price" placeholder="Price (RM)" required>

    <label>Subcategory:</label>
    <select name="subcategory_id" required>
        <option value="">-- Select Subcategory --</option>
        <option value="1">Main Course</option>
        <option value="2">Pasta</option>
        <option value="3">Breakfast and Salad</option>
        <option value="4">Snacks</option>
        <option value="5">Burger and Sandwich</option>
        <option value="6">Coffee</option>
        <option value="7">Non-Coffee</option>
    </select>

    <!-- Promo / Discount -->
    <div class="promo-box">
        <h3 style="margin:0 0 10px;">Promotion / Discount</h3>

        <div class="checkbox">
            <input type="checkbox" id="promo_active" name="promo_active" value="1">
            <label for="promo_active" style="margin:0;">Enable promo for this item</label>
        </div>
        <div class="hint">If promo is not enabled, user will see normal price.</div>

        <label>Discount Type:</label>
        <select name="discount_type">
            <option value="">-- No Discount Type --</option>
            <option value="percent">Percent (%)</option>
            <option value="fixed">Fixed (RM)</option>
        </select>

        <label>Discount Value:</label>
        <input type="number" step="0.01" name="discount_value" placeholder="Example: 20 (for 20%) OR 5 (RM5 off)">

        <div class="row">
            <div>
                <label>Promo Start Date:</label>
                <input type="date" name="promo_start">
            </div>
            <div>
                <label>Promo End Date:</label>
                <input type="date" name="promo_end">
            </div>
        </div>
        <div class="hint">Leave dates empty to make promo always valid (when enabled).</div>
    </div>

    <input type="file" name="image" accept="image/*" required>
    <button type="submit">Add Product</button>
</form>

</body>
</html>

<?php require 'footerAdmin.php'; ?>
