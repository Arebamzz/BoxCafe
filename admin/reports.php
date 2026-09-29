<?php
require 'headerAdmin.php';
require_once 'db_connect.php';

// Redirect if not logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

/* ==========================
   OLD FUNCTION (DO NOT DELETE)
   ========================== */
function getSales($pdo, $interval) {
    $stmt = $pdo->prepare("
        SELECT SUM(total_price) AS total_sales
        FROM orders
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL $interval)
          AND status = 'Completed'
    ");
    $stmt->execute();
    return $stmt->fetchColumn() ?? 0;
}

// Keep old cards (Completed orders)
$dailySales   = getSales($pdo, '1 DAY');
$weeklySales  = getSales($pdo, '7 DAY');
$monthlySales = getSales($pdo, '1 MONTH');

/* ==========================
   NEW: Stripe/Payments sales (PAID)
   ========================== */
function paidSumInterval($pdo, $interval) {
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(amount_rm), 0)
        FROM payments
        WHERE status = 'PAID'
          AND created_at >= DATE_SUB(NOW(), INTERVAL $interval)
    ");
    $stmt->execute();
    return (float)$stmt->fetchColumn();
}

function paidSumToday($pdo) {
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(amount_rm), 0)
        FROM payments
        WHERE status = 'PAID'
          AND DATE(created_at) = CURDATE()
    ");
    $stmt->execute();
    return (float)$stmt->fetchColumn();
}

function paidSumAllTime($pdo) {
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(amount_rm), 0)
        FROM payments
        WHERE status = 'PAID'
    ");
    $stmt->execute();
    return (float)$stmt->fetchColumn();
}

$paidToday   = paidSumToday($pdo);
$paidWeekly  = paidSumInterval($pdo, '7 DAY');
$paidMonthly = paidSumInterval($pdo, '1 MONTH');
$paidAllTime = paidSumAllTime($pdo);

/* ==========================
   CHART FILTER: daily / monthly / yearly
   ========================== */
$mode = $_GET['mode'] ?? 'daily';
if (!in_array($mode, ['daily', 'monthly', 'yearly'], true)) {
    $mode = 'daily';
}

$labels = [];
$values = [];

if ($mode === 'daily') {
    // last 30 days (including today)
    $stmt = $pdo->prepare("
        SELECT DATE(created_at) AS d, COALESCE(SUM(amount_rm),0) AS total
        FROM payments
        WHERE status='PAID'
          AND created_at >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)
        GROUP BY DATE(created_at)
        ORDER BY d ASC
    ");
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $map = [];
    foreach ($rows as $r) $map[$r['d']] = (float)$r['total'];

    for ($i = 29; $i >= 0; $i--) {
        $day = date('Y-m-d', strtotime("-$i days"));
        $labels[] = $day;
        $values[] = $map[$day] ?? 0;
    }

} elseif ($mode === 'monthly') {
    // last 12 months
    $stmt = $pdo->prepare("
        SELECT DATE_FORMAT(created_at, '%Y-%m') AS ym, COALESCE(SUM(amount_rm),0) AS total
        FROM payments
        WHERE status='PAID'
          AND created_at >= DATE_SUB(DATE_FORMAT(CURDATE(), '%Y-%m-01'), INTERVAL 11 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY ym ASC
    ");
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $map = [];
    foreach ($rows as $r) $map[$r['ym']] = (float)$r['total'];

    // build 12 months labels
    for ($i = 11; $i >= 0; $i--) {
        $ym = date('Y-m', strtotime(date('Y-m-01') . " -$i months"));
        $labels[] = $ym;
        $values[] = $map[$ym] ?? 0;
    }

} else { // yearly
    // last 5 years
    $stmt = $pdo->prepare("
        SELECT YEAR(created_at) AS y, COALESCE(SUM(amount_rm),0) AS total
        FROM payments
        WHERE status='PAID'
          AND created_at >= DATE_SUB(CURDATE(), INTERVAL 4 YEAR)
        GROUP BY YEAR(created_at)
        ORDER BY y ASC
    ");
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $map = [];
    foreach ($rows as $r) $map[(string)$r['y']] = (float)$r['total'];

    $currentYear = (int)date('Y');
    for ($y = $currentYear - 4; $y <= $currentYear; $y++) {
        $labels[] = (string)$y;
        $values[] = $map[(string)$y] ?? 0;
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Sales Reports - BoxCafe</title>
    <style>
        body { font-family: Arial, sans-serif; background:#f4f4f4; padding:2rem; }
        h2 { text-align:center; margin-bottom:1.2rem; }

        .top-actions{
            max-width: 1100px;
            margin: 0 auto 14px;
            display:flex;
            justify-content: space-between;
            align-items:center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn{
            display:inline-block;
            padding:10px 14px;
            border-radius:8px;
            text-decoration:none;
            font-size:14px;
            border:0;
            cursor:pointer;
        }
        .btn-dark{ background:#111; color:#fff; }
        .btn-dark:hover{ opacity:.92; }
        .btn-outline{ background:#fff; color:#111; border:1px solid #ddd; }
        .btn-outline:hover{ background:#f7f7f7; }

        .grid{
            max-width: 1100px;
            margin: 0 auto;
            display:grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 12px;
        }
        .card{
            background:#fff;
            padding: 16px;
            border-radius: 10px;
            box-shadow:0 4px 10px rgba(0,0,0,0.05);
        }
        .card h3{ margin:0 0 8px; font-size:14px; color:#333; }
        .amt{ font-size: 22px; font-weight: 800; color:#198754; }
        .sub{ margin-top:6px; font-size: 12px; color:#666; }

        .panel{
            max-width: 1100px;
            margin: 14px auto 0;
            background:#fff;
            padding: 16px;
            border-radius: 10px;
            box-shadow:0 4px 10px rgba(0,0,0,0.05);
        }

        .row{
            display:flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items:center;
            justify-content: space-between;
        }
        .filters{
            display:flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items:center;
        }
        select{
            padding:10px 12px;
            border:1px solid #ddd;
            border-radius:8px;
            font-size:14px;
            background:#fff;
        }
        .note{ margin-top:8px; font-size: 12px; color:#666; }

        .chart-wrap{
            margin-top: 14px;
            padding: 12px;
            border: 1px solid #eee;
            border-radius: 10px;
        }

        table{
            width:100%;
            border-collapse: collapse;
            margin-top: 14px;
        }
        th, td{
            border:1px solid #eee;
            padding:10px;
            text-align:left;
        }
        th{ background:#fafafa; }
    </style>
</head>
<body>

<h2>Sales Reports</h2>

<div class="top-actions">
    <a class="btn btn-outline" href="dashboard.php">← Back to Dashboard</a>

    <!-- If you already have print PDF page, keep it. If not, this is a quick print -->
    <button class="btn btn-dark" onclick="window.print()">Generate PDF (Print)</button>
</div>

<!-- Cards (PAID from payments) -->
<div class="grid">
    <div class="card">
        <h3>Total Sales (All Time)</h3>
        <div class="amt">RM <?= number_format($paidAllTime, 2) ?></div>
        <div class="sub">payments.status = PAID</div>
    </div>

    <div class="card">
        <h3>Today's Sales</h3>
        <div class="amt">RM <?= number_format($paidToday, 2) ?></div>
        <div class="sub">Paid today</div>
    </div>

    <div class="card">
        <h3>This Week's Sales (7 days)</h3>
        <div class="amt">RM <?= number_format($paidWeekly, 2) ?></div>
        <div class="sub">Last 7 days</div>
    </div>

    <div class="card">
        <h3>This Month's Sales</h3>
        <div class="amt">RM <?= number_format($paidMonthly, 2) ?></div>
        <div class="sub">Last 1 month</div>
    </div>
</div>

<!-- Keep your old Sales cards (Completed orders) -->
<div class="panel">
    <div class="row">
        <h3 style="margin:0;">(Old) Completed Orders Sales (kept)</h3>
        <div class="note">These are from <b>orders</b> where status='Completed'. Stripe uses <b>payments</b>.</div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Type</th>
                <th>Total (RM)</th>
            </tr>
        </thead>
        <tbody>
            <tr><td>Daily</td><td>RM <?= number_format((float)$dailySales, 2) ?></td></tr>
            <tr><td>Weekly</td><td>RM <?= number_format((float)$weeklySales, 2) ?></td></tr>
            <tr><td>Monthly</td><td>RM <?= number_format((float)$monthlySales, 2) ?></td></tr>
        </tbody>
    </table>
</div>

<!-- Chart panel -->
<div class="panel">
    <div class="row">
        <h3 style="margin:0;">Sales Chart (PAID Payments)</h3>

        <form class="filters" method="get">
            <select name="mode" onchange="this.form.submit()">
                <option value="daily"   <?= $mode==='daily'?'selected':'' ?>>Daily (Last 30 Days)</option>
                <option value="monthly" <?= $mode==='monthly'?'selected':'' ?>>Monthly (Last 12 Months)</option>
                <option value="yearly"  <?= $mode==='yearly'?'selected':'' ?>>Yearly (Last 5 Years)</option>
            </select>
        </form>
    </div>

    <div class="note">
        Chart uses: <b>payments.status='PAID'</b>
    </div>

    <div class="chart-wrap">
        <canvas id="salesChart" height="90"></canvas>
    </div>

    <div style="margin-top:12px;">
        <table>
            <thead>
                <tr>
                    <th><?= ($mode==='daily'?'Date':($mode==='monthly'?'Month':'Year')) ?></th>
                    <th>Total Sales (RM)</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$labels): ?>
                <tr><td colspan="2">No data.</td></tr>
            <?php else: ?>
                <?php for ($i=0; $i<count($labels); $i++): ?>
                    <tr>
                        <td><?= htmlspecialchars($labels[$i]) ?></td>
                        <td>RM <?= number_format((float)$values[$i], 2) ?></td>
                    </tr>
                <?php endfor; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  const labels = <?= json_encode($labels) ?>;
  const values = <?= json_encode($values) ?>;

  const ctx = document.getElementById('salesChart').getContext('2d');

  // Destroy if reloaded (safe)
  if (window.__salesChart) window.__salesChart.destroy();

  window.__salesChart = new Chart(ctx, {
    type: 'line',
    data: {
      labels: labels,
      datasets: [{
        label: 'Sales (RM)',
        data: values,
        tension: 0.35,
        fill: false
      }]
    },
    options: {
      responsive: true,
      interaction: { mode: 'index', intersect: false },
      plugins: {
        legend: { display: true },
        tooltip: {
          callbacks: {
            label: function(context){
              const v = context.parsed.y ?? 0;
              return 'RM ' + v.toFixed(2);
            }
          }
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            callback: function(value){ return 'RM ' + value; }
          }
        }
      }
    }
  });
</script>

</body>
</html>

<?php require 'footerAdmin.php'; ?>
