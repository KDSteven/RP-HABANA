<?php
// session_start();
// include 'config/db.php';

// // Logging function
// function logAction($conn, $action, $details, $user_id = null, $branch_id = null) {
//     if (!$user_id && isset($_SESSION['user_id'])) {
//         $user_id = $_SESSION['user_id'];
//     }
//     if (!$branch_id && isset($_SESSION['branch_id'])) {
//         $branch_id = $_SESSION['branch_id'];
//     }

//     $stmt = $conn->prepare("INSERT INTO logs (user_id, action, details, timestamp, branch_id) VALUES (?, ?, ?, NOW(), ?)");
//     $stmt->bind_param("issi", $user_id, $action, $details, $branch_id);
//     $stmt->execute();
//     $stmt->close();
// }

// if ($_SERVER['REQUEST_METHOD'] === 'POST') {
//     $barcode       = trim($_POST['barcode']);
//     $productName   = trim($_POST['product_name'] ?? '');
//     $category      = trim($_POST['category'] ?? '');
//     $price         = floatval($_POST['price'] ?? 0);
//     $markupPrice   = floatval($_POST['markup_price'] ?? 0);
//     $retailPrice   = $price + ($price * ($markupPrice / 100));
//     $ceilingPoint  = intval($_POST['ceiling_point'] ?? 0);
//     $criticalPoint = intval($_POST['critical_point'] ?? 0);
//     $vat           = floatval($_POST['vat'] ?? 0);
// $expirationDate = !empty($_POST['expiration_date']) ? $_POST['expiration_date'] : NULL;
//     $brandName     = trim($_POST['brand_name'] ?? '');

//     // ✅ Stocks & Branch ID
//     $stocks   = intval($_POST['stocks'] ?? 0);
//     $branchId = $_SESSION['branch_id'] ?? ($_POST['branch_id'] ?? null);

//     // Validate barcode uniqueness
//     $check = $conn->prepare("SELECT product_id FROM products WHERE barcode = ?");
//     $check->bind_param("s", $barcode);
//     $check->execute();
//     $check->store_result();

//     if ($check->num_rows > 0) {
//         $_SESSION['stock_message'] = "❌ This barcode already exists. Please use a unique barcode.";
//         $check->close();
//         header("Location: inventory.php?stock=error");
//         exit();
//     }
//     $check->close();

//     // Validation
//     if ($criticalPoint > $ceilingPoint) {
//         $_SESSION['stock_message'] = "❌ Critical Point cannot be greater than Ceiling Point.";
//         header("Location: inventory.php?stock=error");
//         exit();
//     }
//     if ($stocks > $ceilingPoint) {
//         $_SESSION['stock_message'] = "❌ Stocks cannot be greater than Ceiling Point.";
//         header("Location: inventory.php?stock=error");
//         exit();
//     }
//     if ($stocks < 0 || $price < 0) {
//         $_SESSION['stock_message'] = "❌ Invalid values for stock or price.";
//         header("Location: inventory.php?stock=error");
//         exit();
//     }

//     try {
//         // Insert into products
//         if ($expirationDate === null) {
//             $stmt = $conn->prepare("INSERT INTO products 
//                 (barcode, product_name, category, price, markup_price, retail_price, ceiling_point, critical_point, vat, expiration_date, brand_name) 
//                 VALUES (?,?,?,?,?,?,?,?,?,NULL,?)");

//             $stmt->bind_param(
//                 "sssdddiids", 
//                 $barcode, $productName, $category,
//                 $price, $markupPrice, $retailPrice,
//                 $ceilingPoint, $criticalPoint, $vat,
//                 $brandName
//             );
//         } else {
//             $stmt = $conn->prepare("INSERT INTO products 
//                 (barcode, product_name, category, price, markup_price, retail_price, ceiling_point, critical_point, vat, expiration_date, brand_name) 
//                 VALUES (?,?,?,?,?,?,?,?,?,?,?)");

//             $stmt->bind_param(
//                 "sssdddiidss", 
//                 $barcode, $productName, $category,
//                 $price, $markupPrice, $retailPrice,
//                 $ceilingPoint, $criticalPoint, $vat,
//                 $expirationDate, $brandName
//             );
//         }

//         $stmt->execute();
//         $productId = $conn->insert_id;
//         $stmt->close();

//         // Insert into inventory
//         $stmt2 = $conn->prepare("INSERT INTO inventory (product_id, branch_id, stock) VALUES (?, ?, ?)");
//         $stmt2->bind_param("iii", $productId, $branchId, $stocks);

//         if ($stmt2->execute()) {
//             $stmt2->close();

//             // Log action
//             logAction($conn, "Add Product", "Added product '$productName' (ID: $productId) with stock $stocks to branch ID $branchId");

//             $_SESSION['stock_message'] = "✅ Product '$productName' added successfully with stock: $stocks (Branch ID: $branchId)";
//             header('Location: inventory.php?ap=added');
//             exit();
//         } else {
//             $_SESSION['stock_message'] = "❌ Error adding to inventory: " . $stmt2->error;
//             $stmt2->close();
//             header('Location: inventory.php?ap=error');
//             exit();
//         }
//     } catch (mysqli_sql_exception $e) {
//         $_SESSION['stock_message'] = "❌ Database error: " . $e->getMessage();
//         header("Location: inventory.php?stock=error");
//         exit();
//     }
// }

// add_product.php
session_start();
require 'config/db.php';

/**
 * Logging helper (kept from your version)
 */
function logAction($conn, $action, $details, $user_id = null, $branch_id = null) {
    if (!$user_id && isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
    }
    if (!$branch_id && isset($_SESSION['branch_id'])) {
        $branch_id = $_SESSION['branch_id'];
    }
    $stmt = $conn->prepare("INSERT INTO logs (user_id, action, details, timestamp, branch_id) VALUES (?, ?, ?, NOW(), ?)");
    $stmt->bind_param("issi", $user_id, $action, $details, $branch_id);
    $stmt->execute();
    $stmt->close();
}

/**
 * =========================
 * BARCODE HELPERS (place here)
 * =========================
 */


// EAN-13 checksum + builder (numeric only) – use if you prefer EAN-13 labels
function ean13CheckDigit(string $base12): int {
    $sum = 0;
    for ($i = 0; $i < 12; $i++) {
        $d = (int)$base12[$i];
        $sum += ($i % 2 === 0) ? $d : $d * 3;
    }
    return (10 - ($sum % 10)) % 10;
}
function makeEan13FromId(int $productId): string {
    // 200 = internal prefix (not GS1-registered). For retail, register GS1.
    $base12 = '200' . str_pad((string)$productId, 9, '0', STR_PAD_LEFT);
    return $base12 . ean13CheckDigit($base12);
}

/**
 * Handle form submit
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Form fields
    $barcode       = trim($_POST['barcode'] ?? '');                 // can be empty to auto-generate
    $productName   = trim($_POST['product_name'] ?? '');
    $brandName     = trim($_POST['brand_name'] ?? '');
    $vat           = (float)($_POST['vat'] ?? 0);
    $price         = (float)($_POST['price'] ?? 0);
    $markupPrice   = (float)($_POST['markup_price'] ?? 0);
    $retailPrice   = $price + ($price * ($markupPrice / 100));
    $ceilingPoint  = (int)($_POST['ceiling_point'] ?? 0);
    $criticalPoint = (int)($_POST['critical_point'] ?? 0);
    $stocks        = (int)($_POST['stocks'] ?? 0);
    $branchId      = $_SESSION['branch_id'] ?? ($_POST['branch_id'] ?? null);

    // Expiration date may be empty
    $expirationDate = !empty($_POST['expiration_date']) ? $_POST['expiration_date'] : null;

    /**
     * Your form posts category_id, but products insert expects a category NAME.
     * Map id -> name here. If your DB stores category_id instead, adjust the INSERT accordingly.
     */
    $category_id = (int)($_POST['category_id'] ?? 0);
    $category    = '';
    if ($category_id > 0) {
        $cstmt = $conn->prepare("SELECT category_name FROM categories WHERE category_id=?");
        $cstmt->bind_param("i", $category_id);
        $cstmt->execute();
        $cstmt->bind_result($category);
        $cstmt->fetch();
        $cstmt->close();
    }

    // Basic validations
    if ($criticalPoint > $ceilingPoint) {
        $_SESSION['stock_message'] = "❌ Critical Point cannot be greater than Ceiling Point.";
        header("Location: inventory.php?stock=error");
        exit();
    }
    if ($stocks > $ceilingPoint) {
        $_SESSION['stock_message'] = "❌ Stocks cannot be greater than Ceiling Point.";
        header("Location: inventory.php?stock=error");
        exit();
    }
    if ($stocks < 0 || $price < 0) {
        $_SESSION['stock_message'] = "❌ Invalid values for stock or price.";
        header("Location: inventory.php?stock=error");
        exit();
    }

    // Only check uniqueness if user actually typed a barcode
    if ($barcode !== '') {
        $check = $conn->prepare("SELECT product_id FROM products WHERE barcode = ?");
        $check->bind_param("s", $barcode);
        $check->execute();
        $check->store_result();
        if ($check->num_rows > 0) {
            $_SESSION['stock_message'] = "❌ This barcode already exists. Please use a unique barcode.";
            $check->close();
            header("Location: inventory.php?stock=error");
            exit();
        }
        $check->close();
    }

    try {
        // Insert product (allow NULL barcode if empty)
        if ($expirationDate === null) {
            $stmt = $conn->prepare("
                INSERT INTO products 
                    (barcode, product_name, category, price, markup_price, retail_price, ceiling_point, critical_point, vat, expiration_date, brand_name) 
                VALUES (?,?,?,?,?,?,?,?,?,NULL,?)
            ");
            $barcodeParam = ($barcode !== '') ? $barcode : null;
            $stmt->bind_param(
                "sssdddiids",
                $barcodeParam,         // s (or null)
                $productName,          // s
                $category,             // s (mapped name)
                $price,                // d
                $markupPrice,          // d
                $retailPrice,          // d
                $ceilingPoint,         // i
                $criticalPoint,        // i
                $vat,                  // d
                $brandName             // s
            );
        } else {
            $stmt = $conn->prepare("
                INSERT INTO products 
                    (barcode, product_name, category, price, markup_price, retail_price, ceiling_point, critical_point, vat, expiration_date, brand_name) 
                VALUES (?,?,?,?,?,?,?,?,?,?,?)
            ");
            $barcodeParam = ($barcode !== '') ? $barcode : null;
            $stmt->bind_param(
                "sssdddiidss",
                $barcodeParam,         // s (or null)
                $productName,          // s
                $category,             // s
                $price,                // d
                $markupPrice,          // d
                $retailPrice,          // d
                $ceilingPoint,         // i
                $criticalPoint,        // i
                $vat,                  // d
                $expirationDate,       // s (date string)
                $brandName             // s
            );
        }

        $stmt->execute();
        $productId = (int)$conn->insert_id;
        $stmt->close();

        // If barcode was left blank, auto-generate now using the new product_id
        if ($barcode === '') {
            // Choose ONE: Code128 style (default below) OR EAN-13
            $auto = makeEan13FromId($productId);

            // Try-update; if unlikely duplicate, tweak and retry a few times
            $attempts = 0;
            do {
                $ok = true;
                $u = $conn->prepare("UPDATE products SET barcode=? WHERE product_id=?");
                $u->bind_param("si", $auto, $productId);
                try {
                    $u->execute();
                } catch (mysqli_sql_exception $e) {
                    if (stripos($e->getMessage(), 'Duplicate') !== false && $attempts < 3) {
                        $attempts++;
                        $auto = $auto . $attempts; // nudge and retry
                        $ok = false;
                    } else {
                        throw $e;
                    }
                }
                $u->close();
            } while (!$ok);
        }

        // Insert opening inventory for the chosen branch
        $stmt2 = $conn->prepare("INSERT INTO inventory (product_id, branch_id, stock) VALUES (?, ?, ?)");
        $stmt2->bind_param("iii", $productId, $branchId, $stocks);

        if ($stmt2->execute()) {
            $stmt2->close();

            // Log action
            logAction($conn, "Add Product", "Added product '$productName' (ID: $productId) with stock $stocks to branch ID $branchId");

            $_SESSION['stock_message'] = "✅ Product '$productName' added successfully with stock: $stocks (Branch ID: $branchId)";
            header('Location: inventory.php?ap=added');
            exit();
        } else {
            $_SESSION['stock_message'] = "❌ Error adding to inventory: " . $stmt2->error;
            $stmt2->close();
            header('Location: inventory.php?ap=error');
            exit();
        }

    } catch (mysqli_sql_exception $e) {
        $_SESSION['stock_message'] = "❌ Database error: " . $e->getMessage();
        header("Location: inventory.php?stock=error");
        exit();
    }
}

?>
