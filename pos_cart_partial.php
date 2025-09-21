<?php
// pos_cart_partial.php
if (!isset($_SESSION)) session_start();
include 'config/db.php';
require_once 'functions.php';

$displaySubtotal = 0.0;
$displayVAT = 0.0;
$cartItems = $_SESSION['cart'] ?? [];
$jsCart = [];

$nearingExpirationProducts = [];

foreach ($_SESSION['cart'] ?? [] as $item) {
    if (($item['type'] ?? '') === 'product' && !empty($item['expiration'])) {
        $expiration = new DateTime($item['expiration']);
        $today = new DateTime();
        
        // Show alert 1 year early
        $expiration->modify('-1 year');
        $diffDays = (int)$today->diff($expiration)->format('%r%a');

        if ($diffDays <= 365 && $diffDays >= 0) { // 1 year or less
            $nearingExpirationProducts[] = $item['name'];
        }
    }
}


foreach ($cartItems as &$item) {
    if ($item['type'] === 'product') {
        $stmt = $conn->prepare("SELECT product_name, price, markup_price, vat, expiration_date FROM products WHERE product_id=?");
        $stmt->bind_param("i", $item['product_id']);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $price = finalPrice($row['price'], $row['markup_price']);
        $vatRate = ($row['vat'] ?? 0) / 100;
        $item['name'] = $row['product_name'] ?? 'Unknown';
        $item['price'] = $price;
        $item['vatRate'] = $vatRate;
        $item['expiration'] = $row['expiration_date'] ?? '';

        $lineVAT = $price * (int)$item['qty'] * $vatRate;
        $subtotal = $price * (int)$item['qty'];

        $displaySubtotal += $subtotal;
        $displayVAT += $lineVAT;

        $jsCart[] = [
            'name' => $item['name'],
            'price' => $price,
            'qty' => (int)$item['qty'],
            'vat' => $vatRate * 100
        ];
    } else { // service
        $price = (float)$item['price'];
        $vatRate = (float)($item['vat'] ?? 0) / 100;

        $lineVAT = $price * (int)$item['qty'] * $vatRate;
        $subtotal = $price * (int)$item['qty'];

        $displaySubtotal += $subtotal;
        $displayVAT += $lineVAT;

        $jsCart[] = [
            'name' => $item['name'] ?? 'Service',
            'price' => $price,
            'qty' => (int)$item['qty'],
            'vat' => $vatRate * 100
        ];

        $item['expiration'] = ''; // services have no expiration
    }
}
unset($item);

$grandTotal = $displaySubtotal + $displayVAT;
?>

<div class="cart-box">
  <h3>🛒 Current Transaction</h3>

  <?php if (empty($cartItems)): ?>
    <p class="text-muted">Your cart is empty.</p>
  <?php else: ?>
    <table>
      <thead>
        <tr>
          <th>Item</th>
          <th>Qty</th>
          <th>Price</th>
          <th>VAT</th>
          <th>Total</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($cartItems as $item): 
            $qty = (int)$item['qty'];
            $lineVAT = $item['price'] * $qty * ($item['vatRate'] ?? 0);
            $subtotal = $item['price'] * $qty;
        ?>
        
<tr 
  class="<?= !empty($item['expiration']) ? 'expirable' : '' ?>" 
  data-expiration="<?= htmlspecialchars($item['expiration'] ?? '') ?>" 
  data-category="<?= htmlspecialchars($item['category'] ?? '') ?>"
>
    <td><?= htmlspecialchars($item['product_name'] ?? $item['name']) ?></td>
    <td><?= (int)$item['qty'] ?></td>
    <td>₱<?= number_format($item['price'], 2) ?></td>
    <td>₱<?= number_format(($item['price'] * $item['qty']) * 0.12, 2) ?></td>
    <td>₱<?= number_format(($item['price'] * $item['qty']) * 1.12, 2) ?></td>
</tr>


        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>

<div class="card totals-box mt-3">
  <h5>Subtotal <span>₱<?= number_format($displaySubtotal, 2) ?></span></h5>
  <h5>VAT <span>₱<?= number_format($displayVAT, 2) ?></span></h5>
  <h4 class="final-total">TOTAL <span>₱<?= number_format($grandTotal, 2) ?></span></h4>
  <hr>
  <h5>Discount <span id="displayDiscount">₱0.00</span></h5>
  <h5>Payment <span id="displayPayment">₱0.00</span></h5>
  <h5>Change <span id="displayChange">₱0.00</span></h5>
</div>

<script>
// Pass PHP cart to JS for payment & VAT calculations
const cartItems = <?= json_encode($jsCart) ?>;
</script>
<script>
function checkExpirationToasts() {
  document.querySelectorAll("tr.expirable").forEach(row => {
    const expStr = row.dataset.expiration;
    if (!expStr) return;

    const expDate = new Date(expStr.replace(/-/g,'/'));
    const today = new Date();
    const diffDays = Math.ceil((expDate - today) / (1000 * 60 * 60 * 24));
    const productName = row.querySelector("td")?.textContent?.trim() || "Product";

    const toastEl = document.getElementById("expirationToast");
    if (!toastEl) return;

    if (diffDays <= 0) {
      toastEl.querySelector(".toast-body").textContent =
        `❌ "${productName}" has already expired!`;
      new bootstrap.Toast(toastEl).show();
    } else if (diffDays <= 365) {
      toastEl.querySelector(".toast-body").textContent =
        `⚠️ "${productName}" is near expiration (${diffDays} days left)`;
      new bootstrap.Toast(toastEl).show();
    }
  });
}
</script>
