<?php
require_once __DIR__ . '/includes/database.php';
session_start();

$conn = box_database();

if (empty($_SESSION['cart'])) {
  header("Location: cart.php"); exit;
}
$prefillPhone = '';
if (!empty($_SESSION['user_id'])) {
  $uid = (int)$_SESSION['user_id'];
  $u = $conn->prepare("SELECT phone FROM users WHERE user_id = ? LIMIT 1");
  $u->bind_param("i", $uid);
  $u->execute();
  $u->bind_result($dbPhone);
  if ($u->fetch() && !empty($dbPhone)) $prefillPhone = $dbPhone;
  $u->close();
}


$total = 0.0;
$items = [];
foreach ($_SESSION['cart'] as $item) {
  $type     = $item['type'];
  $id       = (int)$item['product_id'];
  $quantity = (int)$item['quantity'];
  $remark   = $item['remark'] ?? '';

  $sql = ($type === 'merchandise')
      ? "SELECT name, price FROM merchandise WHERE id = $id"
      : "SELECT name, price FROM menu_items WHERE id = $id";

  $result = $conn->query($sql);
  if ($result && $row = $result->fetch_assoc()) {
    $name     = $row['name'];
    $price    = (float)$row['price'];
    $subtotal = $price * $quantity;
    $total   += $subtotal;

    $items[] = [
      'name'     => $name,
      'qty'      => $quantity,
      'remark'   => $remark,
      'subtotal' => $subtotal,
    ];
  }
}

// Basic order reference (pass this to other pages)
$orderRef = 'BOX-' . date('YmdHis') . '-' . rand(100,999);

include("header.php");
?>
<link rel="stylesheet" href="styleCart.css">

<style>
.methods { display:grid; gap:12px; grid-template-columns: repeat(auto-fit,minmax(260px,1fr)); margin-top:12px; }
.method-card {
  border:1px solid #eee; border-radius:14px; background:#fff; padding:14px;
  cursor:pointer; transition: box-shadow .2s, border-color .2s;
}
.method-card:hover { box-shadow:0 10px 26px rgba(0,0,0,.06); border-color:#e5e7eb; }
.method-head { display:flex; align-items:center; gap:10px; }
.method-head img { width:28px; height:28px; object-fit:contain; }
.method-title { font-weight:600; }
.method-desc { color:#666; font-size:13px; margin-top:2px; }
.method-card input[type=radio] { accent-color:#16a34a; }
.notice { text-align:center; color:#6b7280; font-size:13px; margin-top:8px; }

.section-card{max-width:860px;margin:14px auto;background:#fff;border:1px solid #eee;border-radius:30px;padding:40px}
.row{display:grid;grid-template-columns:1fr 1fr;gap:12px}
label{display:block;font-size:13px;color:#444;margin-top:10px}
input[type=text], textarea, select{width:100%;padding:10px;border:1px solid #ddd;border-radius:10px;margin-top:6px}
.small{font-size:12px;color:#6b7280;margin-top:6px}
.totals{max-width:860px;margin:12px auto;text-align:right}
.totals .line{display:flex;justify-content:flex-end;gap:14px;margin-top:6px;color:#333}
.totals .line strong{min-width:120px;text-align:right}
</style>

<div class="container">
  <h1>Checkout</h1>

  <!-- Order summary -->
  <table>
    <tr><th>Product</th><th>Qty</th><th>Price</th></tr>
    <?php foreach ($items as $it): ?>
      <tr>
        <td>
          <strong><?= htmlspecialchars($it['name']) ?></strong><br>
          <?php if (!empty($it['remark'])): ?>
            <small>Remark: <?= htmlspecialchars($it['remark']) ?></small>
          <?php endif; ?>
        </td>
        <td><?= (int)$it['qty'] ?></td>
        <td>RM<?= number_format($it['subtotal'], 2) ?></td>
      </tr>
    <?php endforeach; ?>
  </table>

  <!-- ✅ Totals (dynamic with delivery) -->
  <div class="totals">
    <div class="line"><span>Subtotal</span><strong id="subtotalTxt">RM<?= number_format($total, 2) ?></strong></div>
    <div class="line"><span>Delivery Fee</span><strong id="deliveryTxt">RM0.00</strong></div>
    <div class="line" style="font-size:18px"><span>Total</span><strong id="totalTxt">RM<?= number_format($total, 2) ?></strong></div>
  </div>

  <!-- ✅ Delivery/Pickup info -->
  <div class="section-card">
    <div style="display:flex; gap:16px; align-items:center; flex-wrap:wrap;">
      <div style="font-weight:600;">Order Type</div>
      <label style="margin:0;"><input type="radio" name="order_type_pick" value="PICKUP" checked> Pickup</label>
      <label style="margin:0;"><input type="radio" name="order_type_pick" value="DELIVERY"> Delivery</label>
    </div>

    <div id="deliveryFields" style="display:none; margin-top:10px;">
      <div class="row">
        <div>
          <label>Delivery Zone</label>
          <select id="delivery_zone" name="delivery_zone">
            <option value="A" data-fee="5">Zone A (Near) — RM5</option>
            <option value="B" data-fee="8">Zone B (Mid) — RM8</option>
            <option value="C" data-fee="12">Zone C (Far) — RM12</option>
          </select>
          <div class="small"></div>
        </div>
        <div>
          <label>Phone Number</label>
       <input
  type="text"
  id="customer_phone"
  name="customer_phone"
  value="<?= htmlspecialchars($prefillPhone) ?>"
  readonly
>



          <div class="small"></div>
        </div>
      </div>

      <label>Delivery Address</label>
      <textarea id="delivery_address" name="delivery_address" rows="3" placeholder="Full address..."></textarea>

      <label>Delivery Note (optional)</label>
      <textarea id="delivery_note" name="delivery_note" rows="2" placeholder="Ring bell / call first..."></textarea>
    </div>
  </div>

  <!-- Method selector -->
  <form id="payForm" method="post" action="#" style="max-width:860px;margin:16px auto">
    <!-- keep a hidden field so backend can read simple 'method' too -->
    <input type="hidden" name="method" id="methodField" value="stripe">
    <input type="hidden" name="order_ref" value="<?= htmlspecialchars($orderRef) ?>">

    <!-- ✅ delivery hidden fields sent to ALL payment methods -->
    <input type="hidden" name="order_type" id="orderTypeField" value="PICKUP">
    <input type="hidden" name="delivery_fee" id="deliveryFeeField" value="0">
    <input type="hidden" name="delivery_zone" id="deliveryZoneField" value="">
    <input type="hidden" name="delivery_address" id="deliveryAddressField" value="">
    <input type="hidden" name="delivery_note" id="deliveryNoteField" value="">
    <input type="hidden" name="customer_phone" id="customerPhoneField" value="">

    <!-- ✅ amount will be updated dynamically -->
    <input type="hidden" name="amount" id="amountField" value="<?= number_format($total, 2, '.', '') ?>">

    <div class="methods">

      <!-- Card (Stripe) -->
      <label class="method-card">
        <div class="method-head">
          <img src="https://img.icons8.com/ios-filled/50/bank-card-front-side.png" alt="">
          <div>
            <div class="method-title">Card (Stripe)</div>
            <div class="method-desc">Secure card payment via Stripe Checkout.</div>
          </div>
          <div style="margin-left:auto"><input type="radio" name="method_pick" value="card" checked></div>
        </div>
        <div style="margin-top:10px; color:#666; font-size:13px;">
          You’ll be redirected to Stripe.
        </div>
      </label>

      <!-- DuitNow QR (Manual) -->
      <label class="method-card">
        <div class="method-head">
          <img src="https://img.icons8.com/ios-filled/50/qr-code.png" alt="">
          <div>
            <div class="method-title">DuitNow QR (Manual)</div>
            <div class="method-desc">Scan & pay, then upload your receipt for verification.</div>
          </div>
          <div style="margin-left:auto"><input type="radio" name="method_pick" value="duitnow"></div>
        </div>
        <div class="notice" style="margin-top:10px;">
          We’ll verify your receipt and mark the order as paid.
        </div>
      </label>

      <!-- Pay at Counter -->
      <label class="method-card">
        <div class="method-head">
          <img src="https://img.icons8.com/ios-filled/50/cash-register.png" alt="">
          <div>
            <div class="method-title">Pay at Counter</div>
            <div class="method-desc">Reserve your order and pay when you pick up / delivery COD.</div>
          </div>
          <div style="margin-left:auto"><input type="radio" name="method_pick" value="counter"></div>
        </div>
        <div class="notice" style="margin-top:10px;">We’ll record your order as “Pay at Counter”.</div>
      </label>

    </div>

    <div style="text-align:center; margin-top:14px;">
      <a href="cart.php" class="btn btn-clear">Back to Cart</a>
      <button type="submit" id="continueBtn" class="btn" style="margin-left:10px;">Pay with Card (Stripe)</button>
    </div>

    <p class="notice">Stripe needs <code>checkout.php</code>. DuitNow posts to receipt upload. Counter posts to <code>counterProcess.php</code>.</p>
  </form>
</div>

<script>
(function () {
  const form = document.getElementById('payForm');
  const btn  = document.getElementById('continueBtn');

  // payment radios
  const payRadios = document.querySelectorAll('input[name="method_pick"]');
  const methodField = document.getElementById('methodField');

  // order type radios
  const typeRadios = document.querySelectorAll('input[name="order_type_pick"]');
  const deliveryBox = document.getElementById('deliveryFields');

  // delivery inputs
  const zoneSel = document.getElementById('delivery_zone');
  const addrEl  = document.getElementById('delivery_address');
  const noteEl  = document.getElementById('delivery_note');
  const phoneEl = document.getElementById('customer_phone');

  // hidden fields
  const orderTypeField = document.getElementById('orderTypeField');
  const deliveryFeeField = document.getElementById('deliveryFeeField');
  const deliveryZoneField = document.getElementById('deliveryZoneField');
  const deliveryAddressField = document.getElementById('deliveryAddressField');
  const deliveryNoteField = document.getElementById('deliveryNoteField');
  const customerPhoneField = document.getElementById('customerPhoneField');
  const amountField = document.getElementById('amountField');

  // totals UI
  const subtotal = <?= json_encode((float)$total) ?>;
  const subtotalTxt = document.getElementById('subtotalTxt');
  const deliveryTxt = document.getElementById('deliveryTxt');
  const totalTxt    = document.getElementById('totalTxt');

  function currentPay() {
    const r = Array.from(payRadios).find(x => x.checked);
    return r ? r.value : 'card';
  }

  function currentType() {
    const r = Array.from(typeRadios).find(x => x.checked);
    return r ? r.value : 'PICKUP';
  }

  function feeForZone() {
    if (!zoneSel) return 0;
    const opt = zoneSel.options[zoneSel.selectedIndex];
    const fee = parseFloat(opt.getAttribute('data-fee') || '0');
    return isNaN(fee) ? 0 : fee;
  }

  function updateTotalsAndHidden() {
    const type = currentType();
    let fee = 0;

    if (type === 'DELIVERY') {
      fee = feeForZone();
      deliveryBox.style.display = 'block';
    } else {
      deliveryBox.style.display = 'none';
    }

    // UI
    deliveryTxt.textContent = 'RM' + fee.toFixed(2);
    const grand = subtotal + fee;
    totalTxt.textContent = 'RM' + grand.toFixed(2);

    // hidden
    orderTypeField.value = type;
    deliveryFeeField.value = fee.toFixed(2);
    deliveryZoneField.value = (type === 'DELIVERY') ? (zoneSel ? zoneSel.value : '') : '';
    deliveryAddressField.value = (type === 'DELIVERY') ? (addrEl ? addrEl.value.trim() : '') : '';
    deliveryNoteField.value = (type === 'DELIVERY') ? (noteEl ? noteEl.value.trim() : '') : '';
    customerPhoneField.value = (type === 'DELIVERY') ? (phoneEl ? phoneEl.value.trim() : '') : '';
    amountField.value = grand.toFixed(2);
  }

  function setAction(m) {
    if (m === 'card') {
      methodField.value = 'stripe';
      form.action = '#'; // intercepted below
      btn.textContent = 'Pay with Card (Stripe)';
    } else if (m === 'counter') {
      methodField.value = 'pay_counter';
      form.action = 'counterProcess.php';
      btn.textContent = 'Continue';
    } else {
      methodField.value = 'duitnow_qr';
      form.action = '/BoxCafe/uploads/receipts/duitnow.php';
      btn.textContent = 'Continue';
    }
  }

  // validate delivery before submit
  function validateDeliveryIfNeeded() {
    if (currentType() !== 'DELIVERY') return true;

    const addr = (addrEl ? addrEl.value.trim() : '');
    const phone = (phoneEl ? phoneEl.value.trim() : '');

    if (!phone) { alert("Please enter phone number for delivery."); return false; }
    if (!addr)  { alert("Please enter delivery address."); return false; }
    return true;
  }

  // listeners
  payRadios.forEach(r => r.addEventListener('change', () => setAction(currentPay())));
  typeRadios.forEach(r => r.addEventListener('change', updateTotalsAndHidden));
  if (zoneSel) zoneSel.addEventListener('change', updateTotalsAndHidden);
  if (addrEl)  addrEl.addEventListener('input', updateTotalsAndHidden);
  if (noteEl)  noteEl.addEventListener('input', updateTotalsAndHidden);
  if (phoneEl) phoneEl.addEventListener('input', updateTotalsAndHidden);

  // init
  setAction(currentPay());
  updateTotalsAndHidden();

  // Stripe redirect
  form.addEventListener('submit', async (e) => {
    // always sync hidden fields before submit
    updateTotalsAndHidden();

    if (!validateDeliveryIfNeeded()) {
      e.preventDefault();
      return;
    }

    if (currentPay() !== 'card') return; // normal post for other methods

    e.preventDefault();
    btn.disabled = true;
    const prev = btn.textContent;
    btn.textContent = 'Redirecting...';

    try {
      const fd = new FormData(form);
      const res = await fetch('checkout.php', { method: 'POST', body: fd });
      const data = await res.json();
      if (data.url) window.location = data.url;
      else { alert(data.error || 'Could not create checkout session.'); btn.disabled=false; btn.textContent=prev; }
    } catch {
      alert('Network error creating checkout session.');
      btn.disabled=false; btn.textContent=prev;
    }
  });
})();
</script>

<?php include("footer.php"); ?>