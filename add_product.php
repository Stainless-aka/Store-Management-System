<?php
session_start();
require 'config.php';
if (!isset($_SESSION['user'])) { header('Location: login.php'); exit(); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $cost = (float)str_replace([',',' '],['',''],$_POST['cost_price'] ?? 0);
        $sell = (float)str_replace([',',' '],['',''],$_POST['sell_price'] ?? 0);
        $qty = max(0,(int)($_POST['quantity'] ?? 0));
        if ($name === '' || $cost <= 0 || $sell <= 0) {
            $_SESSION['errors'] = ['Invalid input.'];
            header('Location: add_product.php');
            exit();
        }
        $stmt = $pdo->prepare('INSERT INTO products (name,cost_price,sell_price,quantity) VALUES (?,?,?,?)');
        $stmt->execute([$name,$cost,$sell,$qty]);
        $_SESSION['flash'] = 'Product added successfully.';
        header('Location: index.php');
        exit();
    } elseif ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $cost = (float)str_replace([',',' '],['',''],$_POST['cost_price'] ?? 0);
        $sell = (float)str_replace([',',' '],['',''],$_POST['sell_price'] ?? 0);
        $qty = max(0,(int)($_POST['quantity'] ?? 0));
        if ($id<=0 || $name==='') {
            $_SESSION['errors']=['Invalid data.'];
            header('Location:add_product.php'); exit();
        }
        $stmt = $pdo->prepare('UPDATE products SET name=?,cost_price=?,sell_price=?,quantity=? WHERE id=?');
        $stmt->execute([$name,$cost,$sell,$qty,$id]);
        $_SESSION['flash']='Product updated successfully.';
        header('Location:index.php'); exit();
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id>0) {
            $stmt=$pdo->prepare('DELETE FROM products WHERE id=?');
            $stmt->execute([$id]);
            $_SESSION['flash']='Product deleted.';
        }
        header('Location:index.php'); exit();
    }
}

$edit = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$product = null;
if ($edit>0) {
    $stmt=$pdo->prepare('SELECT * FROM products WHERE id=?');
    $stmt->execute([$edit]);
    $product=$stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo $product ? 'Edit Product' : 'Add Product'; ?> - ABBA RISKY & CO Store</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/css/style.css" rel="stylesheet">
  <style>
    body {
      background: linear-gradient(135deg, #f8f9fa, #e8f5e9);
      font-family: 'Poppins', sans-serif;
    }
    .card {
      border: none;
      border-radius: 1rem;
      box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    .btn-success {
      background: linear-gradient(90deg, #198754, #157347);
      border: none;
    }
    .btn-success:hover {
      background: linear-gradient(90deg, #157347, #146c43);
    }
    .form-label {
      font-weight: 500;
    }
    .navbar {
      background: linear-gradient(90deg,#198754,#0d6efd);
    }
  </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark shadow-sm">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold" href="index.php"><i class="bi bi-box-seam"></i> ABBA RISKY &amp; CO Store</a>
    <div class="d-flex ms-auto">
      <a href="index.php" class="btn btn-outline-light btn-sm me-2"><i class="bi bi-speedometer2"></i> Dashboard</a>
      <a href="logout.php" class="btn btn-outline-light btn-sm"><i class="bi bi-box-arrow-right"></i> Logout</a>
    </div>
  </div>
</nav>

<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-lg-6 col-md-8">
      <div class="card p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h4 class="mb-0 text-success"><i class="bi bi-pencil-square"></i> <?php echo $product ? 'Edit Product' : 'Add New Product'; ?></h4>
          <a href="index.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back</a>
        </div>
        <hr>
        <form method="post" class="mt-3">
          <?php if ($product): ?>
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
          <?php else: ?>
            <input type="hidden" name="action" value="add">
          <?php endif; ?>

          <div class="mb-3">
            <label class="form-label">Product Name</label>
            <input name="name" class="form-control form-control-lg" required
                   value="<?php echo $product ? htmlspecialchars($product['name']) : ''; ?>"
                   placeholder="Enter product name">
          </div>

          <div class="row mb-3">
            <div class="col-md-6">
              <label class="form-label">Cost Price (₦)</label>
              <input name="cost_price" class="form-control form-control-lg" required
                     value="<?php echo $product ? $product['cost_price'] : ''; ?>"
                     placeholder="0.00">
            </div>
            <div class="col-md-6">
              <label class="form-label">Selling Price (₦)</label>
              <input name="sell_price" class="form-control form-control-lg" required
                     value="<?php echo $product ? $product['sell_price'] : ''; ?>"
                     placeholder="0.00">
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">Quantity Available</label>
            <input name="quantity" class="form-control form-control-lg" type="number"
                   value="<?php echo $product ? $product['quantity'] : '0'; ?>"
                   placeholder="Enter stock quantity">
          </div>

          <div class="d-grid mt-4">
            <button class="btn btn-success btn-lg shadow-sm">
              <i class="bi <?php echo $product ? 'bi-check-circle' : 'bi-plus-circle'; ?>"></i>
              <?php echo $product ? 'Save Changes' : 'Add Product'; ?>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
