<?php
require_once __DIR__ . '/includes/database.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: user_login.php");
    exit();
}

$conn = box_database();
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

include("header.php");
?>
<link rel="stylesheet" href="merchandiseStyle.css?v=3">


<div class="merch-hero">
  <div class="merch-hero-inner">
    <h1>BOX Merchandise</h1>
    <p>BOXKL x GAWKEY — limited drop, official collab items.</p>
  </div>
</div>


<div class="merch-page">
  <div class="merch-wrap">

    <div class="merch-head">
      <h1>Shop the drop</h1>
      <p>Add your favourite items to cart. Use remark for size / color / etc.</p>
    </div>

    <div class="merch-grid">
      <?php
      $sql = "SELECT * FROM merchandise";
      $result = $conn->query($sql);

      if ($result && $result->num_rows > 0) {
          while ($item = $result->fetch_assoc()) {
              $id    = (int)$item["id"];
              $name  = htmlspecialchars($item["name"] ?? '');
              $desc  = htmlspecialchars($item["description"] ?? '');
              $img   = htmlspecialchars($item["image"] ?? '');
              $price = number_format((float)$item["price"], 2);

              echo '
              <div class="merch-card">

                <div class="merch-img">
                  <img src="images/merchandise/' . $img . '" alt="' . $name . '">
                </div>

                <div class="merch-body">
                  <h2 class="merch-title">' . $name . '</h2>
                  <p class="merch-desc">' . $desc . '</p>
                  <div class="merch-price">RM ' . $price . '</div>

                  <form class="merch-form" method="post" action="add_to_cart.php">
                    <input type="hidden" name="product_id" value="' . $id . '">
                    <input type="hidden" name="type" value="merchandise">
                    <input type="hidden" name="quantity" value="1">

                    <div>
                      <div class="merch-remark-label">Remark (optional)</div>
                      <textarea class="merch-remark" name="remark" rows="2" placeholder="Size, color, etc."></textarea>
                    </div>

                    <button class="merch-btn" type="submit">Add to Cart</button>
                  </form>
                </div>

              </div>';
          }
      } else {
          echo '<div class="merch-empty">No merchandise available right now.</div>';
      }

      $conn->close();
      ?>
    </div>

  </div>
</div>

<?php include("footer.php"); ?>
