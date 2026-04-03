<?php
// Load database connection and login information.
require_once 'login.php';
require_once 'db_connect.php';
// Defines how many analyses to show per page, reads page number from URL, only accept positive values.
// Adapted from (PDO LIMIT and OFFSET, StackOverflow, 2011) and (PHP Pagination, GeeksforGeeks, 2018).
$per_page = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) && (int)$_GET['page'] > 0
    ? (int)$_GET['page']
    : 1;
// Calculate offset for current page.
// Adapted from (Calculating item offset for pagination, StackOverflow, 2010).
$offset = ($page - 1) * $per_page;
// Count total number of analyses in table.
// Database communication code adapted from (PHP manual, PDO::prepare, PDOStatement:fetchColumn) and (phpdelusions.net/pdo_examples).
$total_stmt = $pdo->query('SELECT COUNT(*) FROM analyses');
$total_rows = (int)$total_stmt->fetchColumn();
// Calculates pages needed to display all results.
//Adapted from (PHP ceil() Function, W3Schools).
$total_pages = (int)ceil($total_rows / $per_page);
// Fetch the analysis records.
$stmt = $pdo->prepare('
    SELECT analysis_id, protein_family, taxonomic_group, created_at
    FROM analyses
    ORDER BY created_at DESC
    LIMIT ? OFFSET ?
');
// Adapted from (PDO LIMIT and OFFSET, StackOverflow, 2011), (How to Apply BindValue Method in LIMIT Clause?, StackOverflow, 2010).
$stmt->bindValue(1, $per_page, PDO::PARAM_INT);
$stmt->bindValue(2, $offset, PDO::PARAM_INT);
$stmt->execute();
// Fetch rows for display in the table.
$analyses = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>All Analyses</title>
    <!-- Link to shared stylesheet -->
    <link rel='stylesheet' href='style.css'>
</head>
<body>

<?php include 'menu.php'; ?>

<h1>All Previous Analyses</h1>

<?php if (!empty($analyses)): ?>
    <!-- Display analyses in a HTML table -->
    <!-- Adapted from (W3Schools, HTML Tables). -->
    <table border='1' cellpadding='6' cellspacing='0'>
        <tr>
            <th>Date/Time</th>
            <th>Protein Name</th>
            <th>Taxonomic Group</th>
        </tr>

        <?php foreach ($analyses as $analysis): ?>
            <tr>
                <td>
                    <!-- Link analyses dates to its revisit page. -->
                    <!-- Adapted from (PHP manual, urlencode) and (How to escape URL-parameter to prevent HTML injection, StackOverflow, 2018). -->
                    <a href='revisit_analyses.php?analysis_id=<?php echo urlencode((string)$analysis['analysis_id']); ?>'>
                        <?php echo htmlspecialchars($analysis['created_at']); ?>
                    </a>
                </td>
                <td><?php echo htmlspecialchars($analysis['protein_family']); ?></td>
                <td><?php echo htmlspecialchars($analysis['taxonomic_group']); ?></td>
            </tr>
        <?php endforeach; ?>
    </table>

    <p> <!-- Adapted from (Page View in Php, StackOverflow, 2020). -->
        <?php if ($page > 1): ?>
            <!-- Show a previous link when user is not on the first page -->
            <a href='all_analyses.php?page=<?php echo $page - 1; ?>'>Previous</a>
        <?php endif; ?>

        <?php if ($page < $total_pages): ?>
            <!-- Show a Next link when there are more pages to display. -->
            <?php if ($page > 1) echo ' | '; ?>
            <a href='all_analyses.php?page=<?php echo $page + 1; ?>'>Next</a>
        <?php endif; ?>
    </p>
    <!-- Show current page indicator -->
    <p>Page <?php echo htmlspecialchars((string)$page); ?> of <?php echo htmlspecialchars((string)$total_pages); ?></p>
<?php else: ?>
    <!-- Displays if table contains no rows -->
    <p>No analyses found.</p>
<?php endif; ?>

</body>
</html>