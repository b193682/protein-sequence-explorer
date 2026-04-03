<?php
// Load database connection and login information.
require_once 'login.php';
require_once 'db_connect.php';
//Define variables.
$row = null;
$message = '';
$available_outputs = [];
// Ensure that an analysis_id has been provided.
//Adapted from (PHP manual, is_numeric, if).
if (isset($_GET['analysis_id']) && is_numeric($_GET['analysis_id'])) {
    $analysis_id = (int)$_GET['analysis_id'];
    // Fetch selected analysis row from the database.
    // Database communication code adapted from (PHP manual, PDO::prepare) and (phpdelusions.net/pdo_examples).
    $stmt = $pdo->prepare('
        SELECT analysis_id, protein_family, taxonomic_group, use_example,
               max_sequences, min_length, max_length, created_at
        FROM analyses
        WHERE analysis_id = ?
    ');
    $stmt->execute([$analysis_id]);
    $row = $stmt->fetch();
    // Create an output directory.
    // Adapted from (PHP manual, mkdir) and (Create a folder if it doesn't already exist, StackOverflow, 2010).
    if ($row) {
        $output_path = __DIR__ . '/outputs/analysis_' . $analysis_id;

        // The sequence results page is always made available.
        // Adapted from (PHP manual, urlencode()).
        $available_outputs[] = [
            'title' => 'Sequences',
            'description' => 'View the retrieved and filtered protein sequences for this analysis.',
            'url' => 'results.php?analysis_id=' . urlencode((string)$analysis_id)
        ];

        // Check alignment output in database, and provide link to view.
        $stmt_align = $pdo->prepare('SELECT * FROM alignment_output WHERE analysis_id = ?');
        $stmt_align->execute([$analysis_id]);
        $alignment_row = $stmt_align->fetch();

        if ($alignment_row) {
            $available_outputs[] = [
                'title' => 'Alignment and Conservation',
                'description' => 'View the Clustal Omega alignment summary and conservation plot.',
                'url' => 'alignment.php?analysis_id=' . urlencode((string)$analysis_id)
            ];
        }

        // Check pairwise identity output in database, and provide link to view.
        $stmt_identity = $pdo->prepare('SELECT * FROM identity_output WHERE analysis_id = ?');
        $stmt_identity->execute([$analysis_id]);
        $identity_row = $stmt_identity->fetch();

        if ($identity_row) {
            $available_outputs[] = [
                'title' => 'Pairwise Identity Analysis',
                'description' => 'View the identity summary statistics and heatmap.',
                'url' => 'pairwise.php?analysis_id=' . urlencode((string)$analysis_id)
            ];
        }

        // Check motif output in database, and provide link to view.
        // Adapted from (PHP manual PDOStatement::fetchColumn()).
        $stmt_motif = $pdo->prepare('SELECT COUNT(*) FROM motif_output WHERE analysis_id = ?');
        $stmt_motif->execute([$analysis_id]);
        $motif_count = (int)$stmt_motif->fetchColumn();

        if ($motif_count > 0) {
            $available_outputs[] = [
                'title' => 'Motif Analysis',
                'description' => 'View motif hits, summary counts, and motif plot.',
                'url' => 'motifs.php?analysis_id=' . urlencode((string)$analysis_id)
            ];
        }

        // Optional PDB page 
        $available_outputs[] = [
            'title' => 'Protein Structures (PDB)',
            'description' => 'Check available protein structure information.',
            'url' => 'pdb_structures.php?analysis_id=' . urlencode((string)$analysis_id)
        ];
    } else {
        $message = 'No analysis found for that ID.';
    }
} else {
    $message = 'Invalid analysis_id.';
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Analysis History</title>
    <link rel='stylesheet' href='style.css'>
    <!-- Link to shared stylesheet -->
</head>
<body>

<?php include 'menu.php'; ?>

<h1>Analysis History</h1>

<?php if ($row): ?>

    <h2>Original Search</h2>
    <!-- Displays original analysis in table -->
    <!-- Adapted from (W3Schools, HTML Tables). -->
    <table border='1' cellpadding='6' cellspacing='0'>
        <tr><th>Field</th><th>Value</th></tr>
        <tr><td>Analysis ID</td><td><?php echo htmlspecialchars((string)$row['analysis_id']); ?></td></tr>
        <tr><td>Date/Time</td><td><?php echo htmlspecialchars($row['created_at']); ?></td></tr>
        <tr><td>Protein family</td><td><?php echo htmlspecialchars($row['protein_family']); ?></td></tr>
        <tr><td>Taxonomic group</td><td><?php echo htmlspecialchars($row['taxonomic_group']); ?></td></tr>
        <tr><td>Use example dataset</td><td><?php echo (int)$row['use_example'] === 1 ? 'Yes' : 'No'; ?></td></tr>
        <tr><td>Maximum number of sequences</td><td><?php echo $row['max_sequences'] !== null ? htmlspecialchars((string)$row['max_sequences']) : 'Not set'; ?></td></tr>
        <tr><td>Minimum sequence length</td><td><?php echo $row['min_length'] !== null ? htmlspecialchars((string)$row['min_length']) : 'Not set'; ?></td></tr>
        <tr><td>Maximum sequence length</td><td><?php echo $row['max_length'] !== null ? htmlspecialchars((string)$row['max_length']) : 'Not set'; ?></td></tr>
    </table>

    <h2>Available Outputs</h2>
    
    <!-- Display outputs as interactive cards -->
    <!-- Adapted from (How to CSS Cards, W3Schools)).-->
    <?php foreach ($available_outputs as $output): ?>
    <div class='output-card' onclick="window.location.href='<?php echo htmlspecialchars($output['url']); ?>'">
        <h2><?php echo htmlspecialchars($output['title']); ?></h2>
        <p><?php echo htmlspecialchars($output['description']); ?></p>
    </div>
    <?php endforeach; ?>

    <p><a href='all_analyses.php'>Back to all analyses</a></p>

<?php else: ?>
    <p><?php echo htmlspecialchars($message); ?></p>
<?php endif; ?>

</body>
</html>