<?php
// pos_cart_partial.php (with qty +/- and remove buttons)
if (!isset($_SESSION)) session_start();
include 'config/db.php';
require_once 'functions.php';

$displaySubtotal = 0.0;
$displayVAT      = 0.0;

$cartItems = $_SESSION['cart'] ?? [];
$jsCart    = [];

/* ---------- Build rows + compute totals ---------- */
foreach ($cartItems as &$item) {
    if (($item['type'] ?? '') === 'product') {
        $pid  = (int)($item['product_id'] ?? 0);

        // Fetch canonical pricing each render (trust server)
        $stmt = $conn->prepare("SELECT product_name, price, markup_price, vat, expiration_date FROM products WHERE product_id=?");
        $stmt->bind_param("i", $pid);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $name     = $row['product_name'] ?? ($item['name'] ?? 'Unknown');
        $price    = finalPrice((float)($row['price'] ?? 0), (float)($row['markup_price'] ?? 0));
        $vatRate  = (float)($row['vat'] ?? 0) / 100;
        $qty      = (int)($item['qty'] ?? 1);
        $exp      = $row['expiration_date'] ?? ($item['expiration'] ?? '');

        // Update item snapshot for client/UI
        $item['name']       = $name;
        $item['price']      = $price;
        $item['vatRate']    = $vatRate;      // decimal (e.g., 0.12)
        $item['expiration'] = $exp;

        $lineVAT   = $price * $qty * $vatRate;
        $lineSub   = $price * $qty;
        $displaySubtotal += $lineSub;
        $displayVAT      += $lineVAT;

        $jsCart[] = [
            'name'  => $name,
            'price' => $price,
            'qty'   => $qty,
            'vat'   => $vatRate * 100,       // percent for JS view
        ];
    } else {
        // Service line
        $sid     = (int)($item['service_id'] ?? 0);
        $name    = $item['name']  ?? 'Service';
        $price   = (float)($item['price'] ?? 0);
        $vatRate = (float)($item['vat'] ?? 0) / 100;
        $qty     = (int)($item['qty'] ?? 1);

        $lineVAT   = $price * $qty * $vatRate;
        $lineSub   = $price * $qty;
        $displaySubtotal += $lineSub;
        $displayVAT      += $lineVAT;

        // Normalize for UI
        $item['name']       = $name;
        $item['price']      = $price;
        $item['vatRate']    = $vatRate;
        $item['expiration'] = ''; // services have no expiration

        $jsCart[] = [
            'name'  => $name,
            'price' => $price,
            'qty'   => $qty,
            'vat'   => $vatRate * 100,
        ];
    }
}
unset($item);

$grandTotal = $displaySubtotal + $displayVAT;
?>

<div class="cart-box">
  <h3><i class="fas fa-shopping-cart"></i> Current Transaction</h3>

  <?php if (empty($cartItems)): ?>
    <p class="text-muted">Your cart is empty.</p>
  <?php else: ?>
    <table class="table align-middle table-sm">
      <thead>
        <tr>
          <th>Item</th>
          <th class="text-end">Price</th>
          <th class="text-center">Qty</th>
          <th class="text-end">VAT</th>
          <th class="text-end">Total</th>
          <th class="text-center">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($cartItems as $item): ?>
          <?php
            $isProduct = (($item['type'] ?? '') === 'product');
            $name      = $item['product_name'] ?? $item['name'] ?? ($isProduct ? 'Product' : 'Service');
            $qty       = (int)($item['qty'] ?? 1);
            $price     = (float)($item['price'] ?? 0);
            $vatRate   = (float)($item['vatRate'] ?? 0); // decimal (e.g., 0.12)
            $lineVAT   = $price * $qty * $vatRate;
            $lineSub   = $price * $qty;
            $lineGrand = $lineSub + $lineVAT;

            $rowAttrs  = '';
            if ($isProduct) {
                $exp = $item['expiration'] ?? '';
                // Mark expirable rows for your toast scanner
                $rowAttrs = 'class="expirable" data-expiration="'.htmlspecialchars((string)$exp, ENT_QUOTES).'"';
            }

            $idAttr = $isProduct
              ? (int)($item['product_id'] ?? 0)
              : (int)($item['service_id'] ?? 0);

            $typeAttr = $isProduct ? 'product' : 'service';
          ?>
          <tr <?= $rowAttrs ?> data-category="<?= htmlspecialchars($item['category'] ?? '', ENT_QUOTES) ?>">
            <td>
              <?= htmlspecialchars($name, ENT_QUOTES) ?>
              <?php if ($isProduct): ?>
                <br><small class="text-muted">#<?= (int)($item['product_id'] ?? 0) ?></small>
              <?php else: ?>
                <br><small class="text-muted">SVC #<?= (int)($item['service_id'] ?? 0) ?></small>
              <?php endif; ?>
            </td>

            <td class="text-end">₱<?= number_format($price, 2) ?></td>

            <!-- Qty with +/- controls -->
            <td class="text-center">
              <div class="btn-group btn-group-sm" role="group" aria-label="qty">
                <button
                  type="button"
                  class="btn btn-outline-secondary btn-decrease"
                  data-type="<?= $typeAttr ?>"
                  data-id="<?= $idAttr ?>"
                  title="Decrease">
                  −
                </button>
                <span class="px-2 d-inline-block" style="min-width:32px;"><?= $qty ?></span>
                <button
                  type="button"
                  class="btn btn-outline-secondary btn-increase"
                  data-type="<?= $typeAttr ?>"
                  data-id="<?= $idAttr ?>"
                  title="Increase">
                  +
                </button>
              </div>
            </td>

            <td class="text-end">₱<?= number_format($lineVAT, 2) ?></td>
            <td class="text-end">₱<?= number_format($lineGrand, 2) ?></td>

            <!-- Remove -->
            <td class="text-center">
              <button
                type="button"
                class="btn btn-sm btn-outline-danger btn-remove"
                data-type="<?= $typeAttr ?>"
                data-id="<?= $idAttr ?>"
                title="Remove item">
                <i class="fas fa-trash"></i>
              </button>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>

<div class="card totals-box mt-3 p-3">
  <h5>
    Subtotal
    <span class="subtotal" data-value="<?= number_format($displaySubtotal, 2, '.', '') ?>">
      ₱<?= number_format($displaySubtotal, 2) ?>
    </span>
  </h5>

  <h5>
    VAT
    <span class="vat" data-value="<?= number_format($displayVAT, 2, '.', '') ?>">
      ₱<?= number_format($displayVAT, 2) ?>
    </span>
  </h5>

  <h4 class="final-total">
    TOTAL
    <span class="grand" data-value="<?= number_format($grandTotal, 2, '.', '') ?>">
      ₱<?= number_format($grandTotal, 2) ?>
    </span>
  </h4>

  <hr>
  <h5>Discount <span id="displayDiscount">₱0.00</span></h5>
  <h5>Payment  <span id="displayPayment">₱0.00</span></h5>
  <h5>Change   <span id="displayChange">₱0.00</span></h5>
</div>

<script>
// Keep your JS consumer in sync
const cartItems = <?= json_encode($jsCart) ?>;
</script>
