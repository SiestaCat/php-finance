<?php
// year.php

// Determine which year to display (defaults to current year)
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

// Connect to SQLite database
$db = new PDO('sqlite:db/finance.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Calculate monthly balances
$balances = [];
$totalBalance = 0;
for ($m = 1; $m <= 12; $m++) {
    $stmt = $db->prepare("SELECT SUM(amount) as balance FROM finance_entries WHERE year = ? AND month = ?");
    $stmt->execute([$year, $m]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $balance = $row['balance'] !== null ? $row['balance'] : 0;
    $balances[$m] = $balance;
    $totalBalance += $balance;
    $monthlyTotalBalances[$m] = $totalBalance;
}

// Get breakdown data: for each month, group entries by category
$breakdown = [];
$stmt = $db->prepare("SELECT month, category, SUM(amount) as total FROM finance_entries WHERE year = ? GROUP BY month, category ORDER BY month");
$stmt->execute([$year]);
while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
    $breakdown[] = $row;
}

// Get yearly breakdown: for entire year, group entries by category
$stmt = $db->prepare("SELECT category, SUM(amount) as total FROM finance_entries WHERE year = ? GROUP BY category ORDER BY total DESC");
$stmt->execute([$year]);
$yearlyBreakdown = $stmt->fetchAll(PDO::FETCH_ASSOC);

$prevYear = $year - 1;
$nextYear = $year + 1;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Year <?php echo $year; ?></title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .margin-top { margin-top: 20px; }
        .margin-bottom { margin-bottom: 20px; }
        .text-green { color: green; }
        .text-red { color: red; }
    </style>
</head>
<body>
<div class="container margin-top">
    <!-- Year header and navigation buttons -->
    <h1>Year <?php echo $year; ?></h1>
    <div class="margin-bottom">
        <a href="index.php" class="btn btn-success">Go HOME</a>
        <a href="year.php?year=<?php echo $prevYear; ?>" class="btn btn-secondary">&lt; <?php echo $prevYear; ?></a>
        <a href="year.php?year=<?php echo $nextYear; ?>" class="btn btn-secondary"><?php echo $nextYear; ?> &gt;</a>
    </div>
    
    <!-- Balances table -->
    <h3>Balances</h3>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Year</th>
                <th>Month</th>
                <th>Balance</th>
                <th>Total Balance</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php for($m = 1; $m <= 12; $m++): 
                $monthName = date('F', mktime(0,0,0,$m, 10));
                $balance = $balances[$m];
                $balanceClass = $balance >= 0 ? 'text-green' : 'text-red';
                $monthlyTotalBalance = $monthlyTotalBalances[$m];
                $monthlyTotalBalanceClass = $monthlyTotalBalance >= 0 ? 'text-green' : 'text-red';
            ?>
            <tr>
                <td><?php echo $year; ?></td>
                <td><?php echo $monthName; ?></td>
                <td class="<?php echo $balanceClass; ?>"><?php echo number_format($balance, 2); ?></td>
                <td class="<?php echo $monthlyTotalBalanceClass; ?>"><?php echo number_format($monthlyTotalBalance, 2); ?></td>
                <td>
                    <a href="index.php?year=<?php echo $year ?>&month=<?php echo urlencode($m); ?>" class="btn btn-secondary">View</a>
                </td>
            </tr>
            <?php endfor; ?>
            <!-- Total row -->
            <tr>
                <td></td>
                <td></td>
                <td><strong><?php echo number_format($totalBalance, 2); ?></strong></td>
                <td></td>
            </tr>
        </tbody>
    </table>

    <!-- Yearly Breakdown table (aggregated for entire year) -->
    <h3>Yearly Breakdown by Category</h3>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Category</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            <?php if(count($yearlyBreakdown) > 0): ?>
                <?php foreach($yearlyBreakdown as $entry): ?>
                <tr>
                    <td><?php echo htmlspecialchars($entry['category']); ?></td>
                    <td><?php echo number_format($entry['total'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="2">No data available.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    
    <!-- Gráfico con Chart.js -->
    <div class="margin-bottom">
        <canvas id="yearlyBreakdownChart"></canvas>
    </div>
    
    <!-- Breakdown table (per month) -->
    <h3>Monthly Breakdown</h3>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Month</th>
                <th>Category</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            <?php if(count($breakdown) > 0): ?>
                <?php foreach($breakdown as $entry): 
                    $monthName = date('F', mktime(0,0,0,$entry['month'], 10));
                ?>
                <tr>
                    <td><?php echo $monthName; ?></td>
                    <td><?php echo htmlspecialchars($entry['category']); ?></td>
                    <td><?php echo number_format($entry['total'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="3">No data available.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    
</div>

<!-- Script para inicializar Chart.js -->
<script>
// Preparar los datos desde PHP
var categories = <?php echo json_encode(array_column($yearlyBreakdown, 'category')); ?>;
var totals = <?php echo json_encode(array_column($yearlyBreakdown, 'total')); ?>;

var ctx = document.getElementById('yearlyBreakdownChart').getContext('2d');
var yearlyBreakdownChart = new Chart(ctx, {
    type: 'bar',
    data: {
        labels: categories,
        datasets: [{
            label: 'Total',
            data: totals,
            backgroundColor: 'rgba(75, 192, 192, 0.2)',
            borderColor: 'rgba(75, 192, 192, 1)',
            borderWidth: 1
        }]
    },
    options: {
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});
</script>
</body>
</html>
