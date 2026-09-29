<?php
require_once __DIR__ . '/../includes/database.php';
session_start();
$conn = box_database();

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle add merchandise
if (isset($_POST['add_merch'])) {
    $name = $_POST['name'];
    $price = $_POST['price'];
    $desc = $_POST['description'];
    $image = $_FILES['image']['name'];
    $target = "../images/merchandise/" . basename($image);

    if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
        $stmt = $conn->prepare("INSERT INTO merchandise (name, price, description, image) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sdss", $name, $price, $desc, $image);
        $stmt->execute();
        $stmt->close();
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $conn->query("DELETE FROM merchandise WHERE id = $id");
}

include("headerAdmin.php");
?>
<style>
    body {
        font-family: Arial, sans-serif;
        background: #f4f4f4;
        padding: 2rem;
    }
    h2 {
        margin-bottom: 1.5rem;
    }
    a.button {
        padding: 0.6rem 1rem;
        background: #111;
        color: white;
        text-decoration: none;
        border-radius: 4px;
        margin-bottom: 1rem;
        display: inline-block;
    }
    table {
        width: 100%;
        background: white;
        border-collapse: collapse;
        box-shadow: 0 4px 10px rgba(0,0,0,0.05);
    }
    th, td {
        padding: 1rem;
        border: 1px solid #ddd;
        text-align: left;
    }
    img {
        max-width: 60px;
        border-radius: 6px;
    }
    .action-buttons a {
        margin-right: 0.5rem;
        color: #007BFF;
        text-decoration: none;
    }
    form.add-merch-form {
        background: #fff;
        padding: 1.5rem;
        border-radius: 6px;
        margin-bottom: 2rem;
        box-shadow: 0 4px 12px rgba(0,0,0,0.06);
        max-width: 600px;
    }
    form.add-merch-form input,
    form.add-merch-form textarea,
    form.add-merch-form button {
        width: 100%;
        margin-bottom: 1rem;
        padding: 0.8rem;
        border: 1px solid #ccc;
        border-radius: 4px;
    }
    form.add-merch-form button {
        background: #0071e3;
        color: white;
        font-weight: bold;
        cursor: pointer;
        border: none;
        transition: background 0.3s ease;
    }
    form.add-merch-form button:hover {
        background: #005bb5;
    }
</style>

<h2>Admin: Manage Merchandise</h2>

<form method="post" enctype="multipart/form-data" class="add-merch-form">
    <input type="text" name="name" placeholder="Name" required>
    <input type="number" name="price" placeholder="Price (RM)" step="0.01" required>
    <textarea name="description" placeholder="Description" required></textarea>
    <input type="file" name="image" accept="image/*" required>
    <button type="submit" name="add_merch">+ Add Merchandise</button>
</form>

<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Price (RM)</th>
            <th>Description</th>
            <th>Image</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $result = $conn->query("SELECT * FROM merchandise ORDER BY id DESC");
        while ($item = $result->fetch_assoc()):
        ?>
        <tr>
            <td><?= $item['id'] ?></td>
            <td><?= htmlspecialchars($item['name']) ?></td>
            <td><?= number_format($item['price'], 2) ?></td>
            <td><?= htmlspecialchars($item['description']) ?></td>
            <td>
                <img src="../images/merchandise/<?= htmlspecialchars($item['image']) ?>" alt="<?= $item['name'] ?>">
            </td>
            <td class="action-buttons">
                <!-- Add edit page if needed later -->
                <a href="merchAdmin.php?delete=<?= $item['id'] ?>" onclick="return confirm('Delete this item?')">Delete</a>
            </td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<?php include("footerAdmin.php"); ?>
