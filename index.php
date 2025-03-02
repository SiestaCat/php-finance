<?php
// index.php

// Connect to SQLite database (file will be created if it doesn’t exist)
$db = new PDO('sqlite:db/finance.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Create table if not exists
$db->exec("CREATE TABLE IF NOT EXISTS finance_entries (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    year INTEGER,
    month INTEGER,
    amount REAL,
    description TEXT,
    category TEXT
)");



// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    $year = intval($_POST['year']);
    $month = intval($_POST['month']);
    // (Optional) Clear any previous entries for this month/year before saving
    $stmt = $db->prepare("DELETE FROM finance_entries WHERE year = ? AND month = ?");
    $stmt->execute([$year, $month]);
    
    // Save each row if at least one field is filled
    if (isset($_POST['amount']) && is_array($_POST['amount'])) {
        $stmt = $db->prepare("INSERT INTO finance_entries (year, month, amount, description, category) VALUES (?, ?, ?, ?, ?)");
        for ($i = 0; $i < count($_POST['amount']); $i++) {
            $amount = $_POST['amount'][$i];
            $description = $_POST['description'][$i];
            $category = $_POST['category'][$i];
            if ($amount !== '' || $description !== '' || $category !== '') {
                $stmt->execute([$year, $month, $amount, $description, $category]);
            }
        }
    }
    
    // Redirect after saving
    header("Location: index.php?year=$year&month=$month");
    exit();
}

$year = array_key_exists('year', $_GET) ? intval($_GET['year']) : null;
$month = array_key_exists('month', $_GET) ? intval($_GET['month']) : null;

// Define current date values
$currentYear = $year ? $year : date('Y');
$currentMonth = $month ? $month : date('n'); // Numeric month (e.g. 3)
$time = strtotime(sprintf('%d-%s-01', $currentYear, $currentMonth));
$currentMonthText = date('F', $time); // Full month name (e.g. March)
$headerText = "Finance " . date('m/Y', $time); // Header displays current month/year (e.g. 03/2025)

// Calculate navigation values for buttons
$prevYear = $currentYear - 1;
$nextYear = $currentYear + 1;
$prevMonth_int = date('n', strtotime("-1 month", $time));
$prevMonth = date('F', strtotime("-1 month", $time));
$nextMonth_int = date('n', strtotime("+1 month", $time));
$nextMonth = date('F', strtotime("+1 month", $time));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo $headerText; ?></title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        .margin-top { margin-top: 20px; }
        .margin-bottom { margin-bottom: 20px; }
    </style>
</head>
<body>
<div class="container margin-top">
    <!-- Header -->
    <h1><?php echo $headerText; ?></h1>
    
    <!-- Button to view the year report -->
    <div class="margin-bottom">
        <a href="year.php?year=<?php echo $currentYear; ?>" class="btn btn-primary">View year report</a>
        <a href="index.php" class="btn btn-success">Go HOME</a>
    </div>
    
    <!-- Navigation buttons -->
    <div class="btn-group margin-bottom" role="group">
        <a href="?year=<?php echo $prevYear; ?>&month=<?php echo $currentMonth; ?>" class="btn btn-secondary">&lt; <?php echo $prevYear; ?></a>
        <a href="?year=<?php echo $currentYear; ?>&month=<?php echo $prevMonth_int; ?>" class="btn btn-secondary">&lt; <?php echo $prevMonth; ?></a>
        <button type="submit" form="financeForm" name="save" class="btn btn-success">Save</button>
        <a href="?year=<?php echo $currentYear; ?>&month=<?php echo $nextMonth_int; ?>" class="btn btn-secondary"><?php echo $nextMonth; ?> &gt;</a>
        <a href="?year=<?php echo $nextYear; ?>&month=<?php echo $currentMonth; ?>" class="btn btn-secondary"><?php echo $nextYear; ?> &gt;</a>
    </div>
    
    <!-- Placeholder text for balances -->
    <p>Balance between last month and current, Balance last month</p>
    
    <!-- Form to add entries -->
    <form id="financeForm" method="post" action="index.php">
        <!-- Hidden inputs for current year and month -->
        <input type="hidden" name="year" value="<?php echo $currentYear; ?>">
        <input type="hidden" name="month" value="<?php echo $currentMonth; ?>">
        <table class="table table-bordered" id="entriesTable">
            <thead>
                <tr>
                    <th>Amount</th>
                    <th>Description</th>
                    <th>Category</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <!-- Default row -->
                <tr>
                    <td><input type="text" name="amount[]" class="form-control"></td>
                    <td><input type="text" name="description[]" class="form-control"></td>
                    <td><input type="text" name="category[]" class="form-control"></td>
                    <td><button type="button" class="btn btn-danger removeRow">Remove</button></td>
                </tr>
            </tbody>
        </table>
        <button type="button" id="addRow" class="btn btn-info">Add Row</button>
    </form>
</div>

<!-- jQuery (for dynamic row addition/removal) -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script>
$(document).ready(function(){
    // Add new row when "Add Row" is clicked
    $("#addRow").click(function(){
        var newRow = `<tr>
            <td><input type="text" name="amount[]" class="form-control"></td>
            <td><input type="text" name="description[]" class="form-control"></td>
            <td><input type="text" name="category[]" class="form-control"></td>
            <td><button type="button" class="btn btn-danger removeRow">Remove</button></td>
        </tr>`;
        $("#entriesTable tbody").append(newRow);
    });
    
    // Remove a row when "Remove" button is clicked
    $(document).on('click', '.removeRow', function(){
        $(this).closest('tr').remove();
    });
    
    // Populate table with existing entries from PHP variable
    if (entries && entries.length > 0) {
        $("#entriesTable tbody").empty(); // Remove the default row if entries exist
        $.each(entries, function(index, entry) {
            var row = `<tr>
                <td><input type="text" name="amount[]" class="form-control" value="${entry.amount}"></td>
                <td><input type="text" name="description[]" class="form-control" value="${entry.description}"></td>
                <td><input type="text" name="category[]" class="form-control" value="${entry.category}"></td>
                <td><button type="button" class="btn btn-danger removeRow">Remove</button></td>
            </tr>`;
            $("#entriesTable tbody").append(row);
        });
    }
});

<?php
$entries = [];
$stmt = $db->prepare("SELECT * FROM finance_entries WHERE year = ? AND month = ?");
$stmt->execute([$currentYear, $currentMonth]);
while($row = $stmt->fetch(PDO::FETCH_OBJ)){
    $entries[] = $row;
}
?>
var entries = <?php echo json_encode($entries, JSON_PRETTY_PRINT); ?>;
</script>
</body>
</html>
