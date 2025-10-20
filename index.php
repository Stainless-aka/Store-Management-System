<?php
session_start();
require 'config.php';
if (!isset($_SESSION['user'])) { header('Location: login.php'); exit(); }

$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);
$errors = $_SESSION['errors'] ?? [];
unset($_SESSION['errors']);

$low_stock_threshold = 5;

// 🔍 Handle product search
$search = trim($_GET['search'] ?? '');
if ($search) {
    $stmt = $pdo->prepare('SELECT * FROM products WHERE name LIKE ? ORDER BY id ASC');
    $stmt->execute(["%$search%"]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $products = $pdo->query('SELECT * FROM products ORDER BY id ASC')->fetchAll(PDO::FETCH_ASSOC);
}

$total_products = (int)$pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
$total_stock = (int)$pdo->query('SELECT COALESCE(SUM(quantity),0) FROM products')->fetchColumn();
$low_stock_count = (int)$pdo->query('SELECT COUNT(*) FROM products WHERE quantity <= '.(int)$low_stock_threshold)->fetchColumn();

$today_start = date('Y-m-d') . " 00:00:00";
$sth = $pdo->prepare('SELECT COALESCE(SUM(total_gain),0) FROM sales WHERE sold_at >= ?');
$sth->execute([$today_start]);
$todays_gain = (float)$sth->fetchColumn();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>ABBA RISKY & CO Store - Dashboard</title>

  <!-- Advanced Bootstrap Styling -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/css/style.css" rel="stylesheet">

  <style>
    body { background: #f8f9fa; font-family: "Poppins", sans-serif; }
    .navbar { background: linear-gradient(90deg, #198754, #0d6efd); box-shadow: 0 2px 10px rgba(0,0,0,0.15); }
    .card { border: none; border-radius: 1rem; box-shadow: 0 3px 8px rgba(0,0,0,0.05); transition: transform 0.2s ease; }
    .card:hover { transform: scale(1.01); }
    .table th { background-color: #198754; color: white; }
    .btn { border-radius: 0.5rem; }
    .naira::before { content: "₦"; margin-right: 3px; }
    .search-bar input { border-radius: 2rem; padding: 0.75rem 1.25rem; border: 1px solid #ddd; }
  </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold" href="#"><i class="bi bi-shop"></i> ABBA RISKY &amp; CO Store</a>
    <div class="d-flex ms-auto align-items-center">
      <span class="text-white me-3 small">👤 <?php echo htmlspecialchars($_SESSION['user']); ?></span>
      <a class="btn btn-outline-light btn-sm" href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
    </div>
  </div>
</nav>

<main class="container py-5">
  <div class="row align-items-center mb-4">
    <div class="col-md-8">
      <h4 class="fw-semibold text-success">Dashboard Overview</h4>
      <p class="text-muted">Track your stock and sales performance easily.</p>
    </div>
    <div class="col-md-4 text-end">
      <a href="add_product.php" class="btn btn-success"><i class="bi bi-plus-lg"></i> Add Product</a>
      <a href="reports.php" class="btn btn-outline-secondary"><i class="bi bi-file-earmark-text"></i> Reports</a>
    </div>
  </div>

  <?php if($flash): ?>
    <div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle"></i> <?php echo htmlspecialchars($flash); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <?php foreach($errors as $e): ?>
    <div class="alert alert-danger alert-dismissible fade show"><i class="bi bi-exclamation-circle"></i> <?php echo htmlspecialchars($e); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endforeach; ?>

  <div class="row g-3 mb-4">
    <div class="col-md-3">
      <div class="card p-3 text-center">
        <small class="text-muted">Total Products</small>
        <div class="fs-4 fw-bold text-primary"><?php echo number_format($total_products); ?></div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card p-3 text-center">
        <small class="text-muted">Total Stock</small>
        <div class="fs-4 fw-bold text-success"><?php echo number_format($total_stock); ?></div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card p-3 text-center">
        <small class="text-muted">Today's Gain</small>
        <div class="fs-4 fw-bold text-success naira"><?php echo number_format($todays_gain,2); ?></div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card p-3 text-center">
        <small class="text-muted">Low Stock Items</small>
        <div class="fs-4 fw-bold text-danger"><?php echo number_format($low_stock_count); ?></div>
      </div>
    </div>
  </div>

  <div class="card p-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h5 class="fw-bold text-secondary"><i class="bi bi-box-seam"></i> Product List</h5>
      <form class="search-bar" method="get">
        <div class="input-group">
          <input type="text" class="form-control" name="search" placeholder="Search product..." value="<?php echo htmlspecialchars($search); ?>">
          <button class="btn btn-outline-success"><i class="bi bi-search"></i></button>
        </div>
      </form>
    </div>

    <div class="table-responsive">
      <table class="table table-hover align-middle">
        <thead class="table-success">
          <tr><th>#</th><th>Name</th><th>Sell Price</th><th>Qty</th><th>Actions</th></tr>
        </thead>
        <tbody>
          <?php if(empty($products)): ?>
            <tr><td colspan="5" class="text-center text-muted">No products found.</td></tr>
          <?php else: foreach($products as $p): $low=($p['quantity']<=5); ?>
          <tr class="<?php echo $low ? 'table-danger':''; ?>">
            <td><?php echo $p['id'];?></td>
            <td><?php echo htmlspecialchars($p['name']);?></td>
            <td class="naira"><?php echo number_format($p['sell_price'],2);?></td>
            <td><?php echo $p['quantity']; if($low) echo ' <span class="badge bg-danger">Low</span>'; ?></td>
            <td>
              <a class="btn btn-sm btn-primary" href="sales.php?product_id=<?php echo $p['id'];?>&auto=1"><i class="bi bi-cart-plus"></i></a>
              <a class="btn btn-sm btn-outline-primary" href="add_product.php?edit=<?php echo $p['id'];?>"><i class="bi bi-pencil-square"></i></a>
              <form method="post" action="add_product.php" class="d-inline" onsubmit="return confirm('Delete this product?');">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
              </form>
            </td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
