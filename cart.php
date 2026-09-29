<?php
require_once __DIR__ . '/includes/database.php';
session_start();
$conn = box_database();


if (isset($_POST['remove_item'])) {
    $removeIndex = (int)($_POST['remove_index'] ?? -1);
    if (isset($_SESSION['cart'][$removeIndex])) {
        unset($_SESSION['cart'][$removeIndex]);
        $_SESSION['cart'] = array_values($_SESSION['cart']); // Reindex
    }
}


if (isset($_POST['clear_cart'])) {
    unset($_SESSION['cart']);
}

include("header.php");
?>
<link rel="stylesheet" href="styleCart.css">

<div class="container">
    <h1>Your Bag</h1>

    <?php if (empty($_SESSION['cart'])): ?>
        <p style="text-align:center;">Your cart is empty.</p>
        <div style="text-align:center;">
            <a href="menu.php" class="btn">Continue Shopping</a>
        </div>
    <?php else: ?>
        <table>
            <tr>
                <th>Product</th>
                <th>Qty</th>
                <th>Price</th>
                <th>Action</th>
            </tr>

            <?php 
            $total = 0.0;

            foreach ($_SESSION['cart'] as $index => $item): 
                $type     = $item['type'];
                $id       = (int)$item['product_id'];
                $quantity = (int)$item['quantity'];
                $remark   = $item['remark'] ?? '';

                // ✅ Choose correct table
                $sql = ($type === 'merchandise') 
                    ? "SELECT name, price FROM merchandise WHERE id = $id" 
                    : "SELECT name, price FROM menu_items WHERE id = $id";

                $result = $conn->query($sql);

                if ($result && $row = $result->fetch_assoc()):
                    $name     = $row['name'];
                    $price    = (float)$row['price'];  // stored in RM
                    $subtotal = $price * $quantity;
                    $total   += $subtotal;
            ?>
                <tr>
                    <td>
                        <strong><?= htmlspecialchars($name) ?></strong><br>
                        <?php if (!empty($remark)): ?>
                            <small>Remark: <?= htmlspecialchars($remark) ?></small>
                        <?php endif; ?>
                    </td>
                    <td><?= $quantity ?></td>
                    <td>RM<?= number_format($subtotal, 2) ?></td>
                    <td>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="remove_index" value="<?= $index ?>">
                            <button type="submit" name="remove_item" class="btn btn-remove">Remove</button>
                        </form>
                    </td>
                </tr>
                <?php endif; ?>
            <?php endforeach; ?>
        </table>

        <p class="total">Total: RM<?= number_format($total, 2) ?></p>

        <div style="text-align:center;">
  <a href="menu.php" class="btn">Add More Items</a>
  <form method="post" style="display:inline;">
    <button type="submit" name="clear_cart" class="btn btn-clear">Clear Cart</button>
  </form>

  <!-- New: go to method selector -->
  <a href="paymentOptions.php" class="btn" style="margin-left:10px;">
    Checkout
  </a>
</div>


     
        <script>
        (function () {
          const btn = document.getElementById('checkoutBtn');
          if (!btn) return;

          btn.addEventListener('click', async () => {
            btn.disabled = true;
            const original = btn.textContent;
            btn.textContent = 'Redirecting...';

            try {
              const res = await fetch('checkout.php', { method: 'POST' });
              const data = await res.json();
              if (data.url) {
                window.location = data.url; // Go to Stripe-hosted Checkout
              } else {
                alert(data.error || 'Could not create checkout session.');
                btn.disabled = false; 
                btn.textContent = original;
              }
            } catch (err) {
              alert('Network error creating checkout session.');
              btn.disabled = false; 
              btn.textContent = original;
            }
          });
        })();
        </script>
    <?php endif; ?>
</div>

<?php include("footer.php"); ?>
