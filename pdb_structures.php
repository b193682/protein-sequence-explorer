<?php
// Load database connection and login information.
require_once 'db_connect.php';
require_once 'login.php';
# Define variables
$row = null;
$message = '';
$structure_rows = [];

// Ensure that an analysis_id has been provided.
// Adapted from (PHP manual, is_numeric, if).
// Database communication code adapted from (PHP manual, PDO::prepare) and (phpdelusions.net/pdo_examples).
if (isset($_GET['analysis_id']) && is_numeric($_GET['analysis_id'])) {
    $analysis_id = (int) $_GET['analysis_id'];
    // Fetch selected analysis row from the database.
    $stmt = $pdo->prepare('SELECT * FROM analyses WHERE analysis_id = ?');
    $stmt->execute([$analysis_id]);
    $row = $stmt->fetch();
    // Create an output directory.
    // Adapted from (PHP manual, mkdir) and (Create a folder if it doesn't already exist, StackOverflow, 2010).
    if ($row) {
        $output_path = __DIR__ . '/outputs/analysis_' . $analysis_id;
        $filtered_fasta = $output_path . '/filtered.fasta';
        // Stop if FASTA file is missing
        if (!file_exists($filtered_fasta) || filesize($filtered_fasta) === 0) {
            $message = 'Filtered FASTA not found.';
        } else {
            # Define python and script used to query the PDB.
            $python = __DIR__ . '/myenv/bin/python';
            $script = __DIR__ . '/pdb.py';
            // Run the python script on the FASTA file.
            //Adapted from (PHP manual shell_exec, escapeshellarg) and (What's the difference between escapeshellarg and escapeshellcmd, StackOverflow, 2009).
            $cmd = escapeshellarg($python) . ' ' .
                   escapeshellarg($script) . ' ' .
                   escapeshellarg($filtered_fasta) . ' 2>&1';
           
            $json_output = shell_exec($cmd);
            // JSON is turned into a PHP array.
            $structure_data = json_decode($json_output, true);
            // Displays script if result fails.
            if (!is_array($structure_data)) {
                $message = 'Failed to retrieve structure data.<br><pre>' . htmlspecialchars($json_output) . '</pre>';
            } else {
                // Removes previously stored structures for this analysis before inserting new results.
                $delete_stmt = $pdo->prepare('DELETE FROM structure_output WHERE analysis_id = ?');
                $delete_stmt->execute([$analysis_id]);
                
                $insert_stmt = $pdo->prepare('
                    INSERT INTO structure_output
                    (analysis_id, sequence_accession, pdb_id, structure_url, match_status)
                    VALUES (?, ?, ?, ?, ?)
                ');
                // Insert structure match rows into database.
                foreach ($structure_data as $row_data) {
                    $insert_stmt->execute([
                        $analysis_id,
                        $row_data['sequence_accession'],
                        $row_data['pdb_id'],
                        $row_data['structure_url'],
                        $row_data['match_status']
                    ]);
                }
                # Display stored rows in HTML table.
                $stmt = $pdo->prepare('
                    SELECT sequence_accession, pdb_id, structure_url, match_status
                    FROM structure_output
                    WHERE analysis_id = ?
                    ORDER BY sequence_accession
                ');
                $stmt->execute([$analysis_id]);
                $structure_rows = $stmt->fetchAll();
            }
        }
    } else {
        $message = 'Analysis not found.';
    }
} else {
    $message = 'No valid analysis_id provided.';
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Protein Data Bank Structures</title>
    <link rel='stylesheet' href='style.css'> 
</head>
<body>
<?php include 'menu.php'; ?>
<?php if ($message !== ''): ?>
    <!-- Display status to the user -->
    <p><?php echo $message; ?></p>
<?php endif; ?>

<?php if (!empty($structure_rows)): ?>
    <h2>Protein Structures</h2>
    <!-- Display one row per sequence, including whether a match was found, PDB ID and a link to the PDB page. -->
    <!-- Adapted from (W3Schools, HTML Tables). -->
    <table border='1' cellpadding='6'>
        <tr>
            <th>Sequence</th>
            <th>Status</th>
            <th>PDB ID</th>
            <th>Link</th>
        </tr>

        <?php foreach ($structure_rows as $structure_row): ?>
            <tr>
                <td><?php echo htmlspecialchars($structure_row['sequence_accession']); ?></td>
                <td><?php echo htmlspecialchars($structure_row['match_status']); ?></td>
                <td><?php echo htmlspecialchars($structure_row['pdb_id'] ?? 'N/A'); ?></td>
                <td>
                    <?php if (!empty($structure_row['structure_url'])): ?>
                        <!-- Opens the PDB structure in a new tab if it exists -->
                        <a href='<?php echo htmlspecialchars($structure_row['structure_url']); ?>' target='_blank'>View in PDB</a>
                    <?php else: ?>
                        No structure found
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>
<p><em>Please note: Any linked PDB structure is a candidate match and may not correspond exactly to the protein sequence</em></p>
</body>
</html>