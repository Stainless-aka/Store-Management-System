<?php
session_start();
require 'config.php';
if (!isset($_SESSION['user'])) { header('Location: login.php'); exit(); }

// Enable PDO exceptions for debugging
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// ---- Daily Sales (Last 30 Days) ----
$daily_labels = [];
$daily_data = [];
for ($i = 29; $i >= 0; $i--) {
  $d = date('Y-m-d', strtotime("-{$i} days"));
  $daily_labels[] = $d;
  $s = $pdo->prepare('SELECT COALESCE(SUM(total_gain), 0) FROM sales WHERE DATE(sold_at) = ?');
  $s->execute([$d]);
  $daily_data[] = (float)$s->fetchColumn();
}

// ---- Monthly Sales (Last 12 Months) ----
$monthly_labels = [];
$monthly_data = [];
for ($i = 11; $i >= 0; $i--) {
  $m = date('Y-m', strtotime("-{$i} months"));
  $monthly_labels[] = $m;
  $s = $pdo->prepare('SELECT COALESCE(SUM(total_gain), 0) FROM sales WHERE DATE_FORMAT(sold_at, "%Y-%m") = ?');
  $s->execute([$m]);
  $monthly_data[] = (float)$s->fetchColumn();
}

// ---- Yearly Sales (Last 5 Years) ----
$yearly_labels = [];
$yearly_data = [];
$current_year = date('Y');
for ($y = $current_year - 4; $y <= $current_year; $y++) {
  $yearly_labels[] = (string)$y;
  $s = $pdo->prepare('SELECT COALESCE(SUM(total_gain), 0) FROM sales WHERE DATE_FORMAT(sold_at, "%Y") = ?');
  $s->execute([(string)$y]);
  $yearly_data[] = (float)$s->fetchColumn();
}

// Encode safely
$daily_labels_json = json_encode($daily_labels ?? []);
$daily_data_json = json_encode($daily_data ?? []);
$monthly_labels_json = json_encode($monthly_labels ?? []);
$monthly_data_json = json_encode($monthly_data ?? []);
$yearly_labels_json = json_encode($yearly_labels ?? []);
$yearly_data_json = json_encode($yearly_data ?? []);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Reports - ABBA RISKY & CO Store</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<style>
  body { background:#f8f9fa; font-family:"Poppins", sans-serif; }
  .report-title { font-size: 1.3rem; font-weight: 700; color:#198754; }
  .report-box { border:1px solid #e9ecef; background:#fff; border-radius:0.75rem; padding:20px; box-shadow:0 2px 6px rgba(0,0,0,0.05); }
  @media print { .no-print { display:none !important; } body { background:#fff; } }
  .naira::before { content: "₦"; margin-right:3px; }
</style>
</head>
<body>
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <div class="report-title"><i class="bi bi-bar-chart"></i> ABBA RISKY &amp; CO Store Report</div>
      <small class="text-muted">Professional Sales Summary</small>
    </div>
    <div class="no-print">
      <button class="btn btn-success btn-sm" onclick="window.print()"><i class="bi bi-printer"></i> Print / Save PDF</button>
      <a href="index.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back</a>
    </div>
  </div>

  <div class="row g-3">
    <div class="col-lg-12">
      <div class="report-box mb-3">
        <h6 class="fw-semibold"><i class="bi bi-calendar-week"></i> Daily Sales (Last 30 Days)</h6>
        <canvas id="chartDaily" height="100"></canvas>
      </div>
    </div>

    <div class="col-lg-6">
      <div class="report-box mb-3">
        <h6 class="fw-semibold"><i class="bi bi-calendar3"></i> Monthly Sales (Last 12 Months)</h6>
        <canvas id="chartMonthly" height="100"></canvas>
      </div>
    </div>

    <div class="col-lg-6">
      <div class="report-box mb-3">
        <h6 class="fw-semibold"><i class="bi bi-calendar-range"></i> Yearly Sales (Last 5 Years)</h6>
        <canvas id="chartYearly" height="100"></canvas>
      </div>
    </div>
  </div>

  <hr class="my-4">

  <h6 class="fw-semibold mb-3"><i class="bi bi-table"></i> Sales Table (Last 30 Days)</h6>
  <?php
    $from = date('Y-m-d', strtotime('-29 days')) . ' 00:00:00';
    $stmt = $pdo->prepare('SELECT s.*, p.name FROM sales s JOIN products p ON p.id = s.product_id WHERE s.sold_at >= ? ORDER BY s.sold_at DESC');
    $stmt->execute([$from]);
    $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);
  ?>

  <?php if (empty($sales)): ?>
    <div class="alert alert-warning"><i class="bi bi-info-circle"></i> No sales data available in the last 30 days.</div>
  <?php else: ?>
  <div class="table-responsive">
    <table class="table table-striped table-hover align-middle">
      <thead class="table-success">
        <tr><th>#</th><th>Product</th><th>Quantity</th><th>Sold At</th><th>Total Gain</th></tr>
      </thead>
      <tbody>
        <?php foreach ($sales as $s): ?>
        <tr>
          <td><?php echo $s['id']; ?></td>
          <td><?php echo htmlspecialchars($s['name']); ?></td>
          <td><?php echo $s['quantity_sold']; ?></td>
          <td><?php echo date('Y-m-d H:i', strtotime($s['sold_at'])); ?></td>
          <td class="naira"><?php echo number_format($s['total_gain'], 2); ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<script>
const moneyFormat = v => new Intl.NumberFormat('en-NG', { style: 'currency', currency: 'NGN' }).format(v);

new Chart(document.getElementById('chartDaily'), {
  type: 'line',
  data: { labels: <?php echo $daily_labels_json; ?>, datasets: [{ label: 'Daily Gain (₦)', data: <?php echo $daily_data_json; ?>, borderColor: '#198754', fill: true, backgroundColor: 'rgba(25,135,84,0.08)', tension: 0.3 }] },
  options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { ticks: { callback: v => moneyFormat(v) } } } }
});

new Chart(document.getElementById('chartMonthly'), {
  type: 'bar',
  data: { labels: <?php echo $monthly_labels_json; ?>, datasets: [{ label: 'Monthly Gain (₦)', data: <?php echo $monthly_data_json; ?>, backgroundColor: '#0d6efd' }] },
  options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { ticks: { callback: v => moneyFormat(v) } } } }
});

new Chart(document.getElementById('chartYearly'), {
  type: 'bar',
  data: { labels: <?php echo $yearly_labels_json; ?>, datasets: [{ label: 'Yearly Gain (₦)', data: <?php echo $yearly_data_json; ?>, backgroundColor: '#157347' }] },
  options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { ticks: { callback: v => moneyFormat(v) } } } }
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
