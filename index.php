<?php
// Tax calculator

// Standard deduction amount
const STANDARD_DEDUCTION = 15000;

// Tax brackets: rate, start, end
const BRACKETS = [
    [0.10, 0,      11600],
    [0.12, 11600,  47150],
    [0.22, 47150,  100525],
    [0.24, 100525, 191950],
    [0.32, 191950, 243725],
    [0.35, 243725, 609350],
    [0.37, 609350, INF],
];

// Starting values
$errors = [];
$result = null;
$name = '';
$grossRaw = '';
$deductionsRaw = '';

// Only run when the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the form values
    $name = trim($_POST['name'] ?? '');
    $grossRaw = trim($_POST['gross_income'] ?? '');
    $deductionsRaw = trim($_POST['deductions'] ?? '');

    // Check the values
    if ($name === '') {
        $errors[] = 'Please enter your name.';
    }
    if (!is_numeric($grossRaw) || (float)$grossRaw < 0) {
        $errors[] = 'Gross income must be a non-negative number.';
    }
    if (!is_numeric($deductionsRaw) || (float)$deductionsRaw < 0) {
        $errors[] = 'Total deductions must be a non-negative number.';
    }

    // Calculate if there are no errors
    if (!$errors) {
        $gross = (float)$grossRaw;
        $deductions = (float)$deductionsRaw;

        // Use the standard deduction if deductions are lower
        if ($deductions < STANDARD_DEDUCTION) {
            $deductions = STANDARD_DEDUCTION;
        }

        // Adjusted gross income
        $agi = max(0, $gross - $deductions);

        $taxByBracket = [];
        $totalTax = 0.0;

        // Go through each bracket
        foreach (BRACKETS as [$rate, $low, $high]) {
            // Income inside this bracket
            $taxable = max(0, min($agi, $high) - $low);
            $tax = $taxable * $rate;

            // Save the tax and add it to the total
            $taxByBracket[(int)round($rate * 100)] = $tax;
            $totalTax += $tax;
        }

        // Save the results
        $result = [
            'gross' => $gross,
            'deductions' => $deductions,
            'agi' => $agi,
            'brackets' => $taxByBracket,
            'total' => $totalTax,
            // Avoid dividing by zero
            'pctGross' => $gross > 0 ? $totalTax / $gross * 100 : 0,
            'pctAgi' => $agi > 0 ? $totalTax / $agi * 100 : 0,
        ];
    }
}

// Format a number as money
function money(float $n): string { return '$' . number_format($n, 2); }

// Escape special characters
function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Tax Calculator</title>
    <style>
        body { font-family: sans-serif; max-width: 600px; margin: 2rem auto; padding: 0 1rem; }
        label { display: block; margin-top: 1rem; }
        input { padding: .4rem; width: 100%; box-sizing: border-box; }
        button { margin-top: 1rem; padding: .5rem 1rem; }
        .error { color: #b00020; }
    </style>
</head>
<body>
    <h1>Federal Tax Calculator</h1>

    <!-- Error messages -->
    <?php if ($errors): ?>
        <ul class="error">
            <?php foreach ($errors as $e): ?><li><?= h($e) ?></li><?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <!-- Input form -->
    <form method="post" action="">
        <label>Name
            <input type="text" name="name" value="<?= h($name) ?>" required>
        </label>
        <label>Gross Income
            <input type="text" name="gross_income" value="<?= h($grossRaw) ?>" required>
        </label>
        <label>Total Deductions
            <input type="text" name="deductions" value="<?= h($deductionsRaw) ?>" required>
        </label>
        <button type="submit">Calculate</button>
    </form>

    <!-- Results -->
    <?php if ($result): ?>
        <hr>
        <h2>Tax Calculator Results for <?= h($name) ?></h2>
        <p>
            Gross Income : <?= money($result['gross']) ?><br>
            Total Deductions : <?= money($result['deductions']) ?><br>
            Adjusted Gross Income : <?= money($result['agi']) ?><br>
            <?php foreach ($result['brackets'] as $rate => $tax): ?>
                Taxes Owed at <?= $rate ?>% bracket : <?= money($tax) ?><br>
            <?php endforeach; ?>
            <strong>Total Taxes Owed : <?= money($result['total']) ?></strong><br>
            Taxes Owed as percentage of gross income: <?= number_format($result['pctGross'], 2) ?>%<br>
            Taxes Owed as percentage of adjusted gross income: <?= number_format($result['pctAgi'], 2) ?>%
        </p>
    <?php endif; ?>
</body>
</html>
