<?php
session_start();
require 'config.php';
if (!isset($_SESSION['user'])) {
  header('Location: login.php');
  exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = (int)($_POST['product_id'] ?? 0);
    $quantity = max(1, (int)($_POST['quantity'] ?? 1));
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('SELECT * FROM products WHERE id=? FOR UPDATE');
        $stmt->execute([$product_id]);
        $p = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$p) {
            $pdo->rollBack();
            $_SESSION['errors'] = ['Product not found'];
            header('Location: sales.php');
            exit();
        }
        if ($p['quantity'] < $quantity) {
            $pdo->rollBack();
            $_SESSION['errors'] = ['Insufficient stock'];
            header('Location: sales.php');
            exit();
        }
        $gain_per_item = $p['sell_price'] - $p['cost_price'];
        $total_gain = $gain_per_item * $quantity;
        $ins = $pdo->prepare('INSERT INTO sales (product_id, quantity_sold, sold_price, gain_per_item, total_gain) VALUES (?,?,?,?,?)');
        $ins->execute([$product_id, $quantity, $p['sell_price'], $gain_per_item, $total_gain]);
        $upd = $pdo->prepare('UPDATE products SET quantity = quantity - ? WHERE id=?');
        $upd->execute([$quantity, $product_id]);
        $pdo->commit();
        $_SESSION['flash'] = '✅ Sale recorded successfully.';
        header('Location: sales.php');
        exit();
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $_SESSION['errors'] = ['Error: '.$e->getMessage()];
        header('Location: sales.php');
        exit();
    }
}

$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';
$params = [];
$sql = 'SELECT s.*, p.name FROM sales s JOIN products p ON p.id = s.product_id WHERE 1=1';
if ($from) { $sql .= ' AND s.sold_at >= ?'; $params[] = $from . ' 00:00:00'; }
if ($to) { $sql .= ' AND s.sold_at <= ?'; $params[] = $to . ' 23:59:59'; }
$sql .= ' ORDER BY s.sold_at DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$sales = $stmt->fetchAll(PDO::FETCH_ASSOC);
$products = $pdo->query('SELECT * FROM products ORDER BY name ASC')->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Sales Dashboard - Abba Risky Store</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<style>
  body {
    background-color: #f8f9fa;
  }
  .card {
    border: none;
    border-radius: 1rem;
    box-shadow: 0 3px 10px rgba(0,0,0,0.08);
  }
  .btn-success {
    background: linear-gradient(45deg, #28a745, #20c997);
    border: none;
  }
  .btn-success:hover {
    background: linear-gradient(45deg, #218838, #17a589);
  }
  .table thead {
    background: #198754;
    color: white;
  }
  .naira::before {
    content: '₦';
    font-weight: 600;
    margin-right: 2px;
  }
</style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-success mb-4">
  <div class="container">
    <a class="navbar-brand fw-bold" href="index.php"><i class="bi bi-shop"></i> Abba Risky Store</a>
    <div class="d-flex">
      <a href="index.php" class="btn btn-outline-light btn-sm"><i class="bi bi-arrow-left"></i> Back</a>
    </div>
  </div>
</nav>

<div class="container">
  <div class="mb-4">
    <h3 class="fw-bold text-success"><i class="bi bi-bar-chart-line"></i> Sales & Reports</h3>
    <p class="text-muted">Record product sales and view transaction history.</p>
  </div>

  <?php if (!empty($_SESSION['flash'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <?php echo htmlspecialchars($_SESSION['flash']); unset($_SESSION['flash']); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <?php if (!empty($_SESSION['errors'])): ?>
    <?php foreach($_SESSION['errors'] as $e): ?>
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($e); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endforeach; unset($_SESSION['errors']); ?>
  <?php endif; ?>

  <!-- Quick Sale -->
  <div class="card mb-4 p-4">
    <h5 class="mb-3"><i class="bi bi-cart-check"></i> Quick Sell</h5>
    <form method="post" class="row g-3 align-items-end">
      <input type="hidden" name="action" value="add_sale">
      <div class="col-md-6">
        <label class="form-label">Product</label>
        <select name="product_id" class="form-select" required>
          <option value="">Select product</option>
          <?php foreach($products as $p): ?>
            <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?> (Stock: <?= $p['quantity'] ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label">Quantity</label>
        <input type="number" name="quantity" class="form-control" value="1" min="1" required>
      </div>
      <div class="col-md-3">
        <button class="btn btn-success w-100"><i class="bi bi-check-circle"></i> Record Sale</button>
      </div>
    </form>
  </div>

  <!-- Filter Section -->
  <div class="card mb-4 p-4">
    <h5 class="mb-3"><i class="bi bi-funnel"></i> Filter by Date</h5>
    <form method="get" class="row g-3 align-items-end">
      <div class="col-md-4">
        <label class="form-label">From</label>
        <input type="date" name="from" class="form-control" value="<?= htmlspecialchars($from); ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label">To</label>
        <input type="date" name="to" class="form-control" value="<?= htmlspecialchars($to); ?>">
      </div>
      <div class="col-md-4 d-flex gap-2">
        <button class="btn btn-outline-success w-50"><i class="bi bi-search"></i> Filter</button>
        <a class="btn btn-outline-secondary w-50" href="sales.php"><i class="bi bi-arrow-repeat"></i> Reset</a>
      </div>
    </form>
  </div>

  <!-- Sales Table -->
  <div class="card p-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h5 class="mb-0"><i class="bi bi-list-ul"></i> Sales History</h5>
      <a href="export_sales_pdf.php" class="btn btn-outline-danger btn-sm"><i class="bi bi-file-earmark-pdf"></i> Export PDF</a>
    </div>
    <div class="table-responsive">
      <table class="table table-hover align-middle">
        <thead>
          <tr>
            <th>#</th>
            <th>Product</th>
            <th>Qty Sold</th>
            <th>Date</th>
            <th>Gain/item</th>
            <th>Total Gain</th>
          </tr>
        </thead>
        <tbody>
          <?php if (count($sales) > 0): ?>
            <?php foreach ($sales as $s): ?>
              <tr>
                <td><?= $s['id']; ?></td>
                <td><?= htmlspecialchars($s['name']); ?></td>
                <td><?= $s['quantity_sold']; ?></td>
                <td><?= $s['sold_at']; ?></td>
                <td class="naira"><?= number_format($s['gain_per_item'], 2); ?></td>
                <td class="naira"><?= number_format($s['total_gain'], 2); ?></td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr><td colspan="6" class="text-center text-muted">No sales records found.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
