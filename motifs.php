<?php
// Load database connection and login information.
require_once 'db_connect.php';
require_once 'login.php';
// Define variables
$row = null;
$message = '';
$motif_summary = [];
$motif_details = [];
// Ensure that an analysis_id has been provided.
//Adapted from (PHP manual, is_numeric, if).
if (isset($_GET['analysis_id']) && is_numeric($_GET['analysis_id'])) {
    $analysis_id = (int) $_GET['analysis_id'];
    // Database communication code adapted from (PHP manual, PDO::prepare) and (phpdelusions.net/pdo_examples).
    $stmt = $pdo->prepare('SELECT * FROM analyses WHERE analysis_id = ?');
    $stmt->execute([$analysis_id]);
    $row = $stmt->fetch();
    // Create an output directory.
    // Adapted from (PHP manual, mkdir) and (Create a folder if it doesn't already exist, StackOverflow, 2010).
    if ($row) {
        $output_path = __DIR__ . '/outputs/analysis_' . $analysis_id;
        $filtered_fasta = $output_path . '/filtered.fasta';
        $motif_report = $output_path . '/motifs.tbl';
        // Stop if filtered FASTA input is missing
        // Adapted from (PHP manual, file_exists()).
        if (!file_exists($filtered_fasta) || filesize($filtered_fasta) === 0) {
            $message = 'Filtered FASTA file not found or empty.';
        } else {
            // Run EMBOSS patmatmotifs on filtered FASTA files.
            // Adapted from (EMBOSS patmatmotifs manual), (PHP manual escapeshellarg) and (PHP manual shell_exec()).
            $patmatmotifs_cmd = 'patmatmotifs -sequence ' . escapeshellarg($filtered_fasta) .
                                ' -outfile ' . escapeshellarg($motif_report) .
                                ' -rformat2 table 2>&1';

            $patmatmotifs_output = shell_exec($patmatmotifs_cmd);
            // Show command output if motif report is not created.
            // Adapted from (PHP manual, NULL).
            if (!file_exists($motif_report) || filesize($motif_report) === 0) {
                $message = 'Motif report was not created.<br><pre>' . htmlspecialchars($patmatmotifs_output) . '</pre>';
            } else {
                $hits = [];
                $current_sequence = null;
                // Read EMBOSS results into array for parsing
                // Adapted from (PHP manual, file()).
                $lines = file($motif_report, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

                foreach ($lines as $line) {
                    $trimmed = trim($line);
                    // Detect sequence headers in motif, then extract sequence accession for motif results.
                    // Adapted from (PHP manual preg_split(), str_starts_with()).
                    if (str_starts_with($trimmed, '# Sequence:')) {
                        $after = str_replace('# Sequence:', '', $trimmed);
                        $parts = preg_split('/\s+/', trim($after));
                        $current_sequence = $parts[0] ?? null;
                        continue;
                    }
                    // Split non-header lines to parse numeric columns
                    // Adapted from (PHP manual preg_split()).
                    $parts = preg_split('/\s+/', $trimmed);
                    // Line is treated as motif hit if it has numeric columns as expected and has a current sequence associated with it.
                    if (
                        count($parts) >= 4 &&
                        is_numeric($parts[0]) &&
                        is_numeric($parts[1]) &&
                        is_numeric($parts[2]) &&
                        $current_sequence !== null
                    ) {
                        $start = (int) $parts[0];
                        $end = (int) $parts[1];
                        $score = (int) $parts[2];
                        // Joins remaining tokens into motif name 
                        // Adapted from (PHP manual, array_slice()).
                        $motif = implode(' ', array_slice($parts, 3));
                        // Store each parsed motif hit in an array to insert into database.
                        $hits[] = [
                            'sequence_accession' => $current_sequence,
                            'motif_name' => $motif,
                            'prosite_accession' => null,
                            'start_position' => $start,
                            'end_position' => $end,
                            'match_summary' => 'Score ' . $score . '; motif ' . $motif
                        ];
                    }
                }
                // Remove previously stored motif results before inserting new outputs
                // Adapted from (PHP manual PDO::prepare()).
                $delete_stmt = $pdo->prepare('DELETE FROM motif_output WHERE analysis_id = ?');
                $delete_stmt->execute([$analysis_id]);

                if (!empty($hits)) {
                    // Insert parsed motif hits into table.
                    // Adapted from (phpdelusions.net INSERT query using PDO).
                    $insert_stmt = $pdo->prepare('
                        INSERT INTO motif_output
                        (analysis_id, sequence_accession, motif_name, prosite_accession, start_position, end_position, match_summary)
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                    ');

                    foreach ($hits as $hit) {
                        $insert_stmt->execute([
                            $analysis_id,
                            $hit['sequence_accession'],
                            $hit['motif_name'],
                            $hit['prosite_accession'],
                            $hit['start_position'],
                            $hit['end_position'],
                            $hit['match_summary']
                        ]);
                    }

                    $message = count($hits) . ' motif hit(s) inserted successfully.';
                } else {
                    $message = 'Motif scan completed, but no motif hits were found.';
                }
                // Motifs are summarised by name and accession
                // Adapted from (PHP manual PDO::prepare).
                $summary_stmt = $pdo->prepare('
                    SELECT 
                        prosite_accession, 
                        motif_name, 
                        COUNT(*) AS hit_count
                    FROM motif_output
                    WHERE analysis_id = ?
                    GROUP BY prosite_accession, motif_name
                    ORDER BY hit_count DESC
                ');
                $summary_stmt->execute([$analysis_id]);
                $motif_summary = $summary_stmt->fetchAll();
                // Output paths for the summary and plot image.
                $plot_file = $output_path . '/motif_plot.png';
                $csv_file = $output_path . '/motif_counts.csv';

if (!empty($motif_summary)) {
    // Write to a CSV file for python plotting
    // Adapted from (PHP manual fopen(), fclose(), fputcsv()).
    $fp = fopen($csv_file, 'w');

    foreach ($motif_summary as $summary_row) {
        fputcsv($fp, [$summary_row['motif_name'], $summary_row['hit_count']]);
    }

    fclose($fp);
    // Define the python and plottng paths
    $python = __DIR__ . '/myenv/bin/python';
    $plot_script = __DIR__ . '/motif_plot.py';
    // Run the plotting script with output paths
    // Adapted from (PHP manual escapeshellarg(), shell_exec()).
    $plot_cmd = escapeshellarg($python) . ' ' .
                escapeshellarg($plot_script) . ' ' .
                escapeshellarg($csv_file) . ' ' .
                escapeshellarg($plot_file) . ' 2>&1';

    $plot_output = shell_exec($plot_cmd);
}
// Retrieve motif hits for HTML table             
$detail_stmt = $pdo->prepare('
    SELECT sequence_accession, motif_name, prosite_accession, start_position, end_position, match_summary
    FROM motif_output
    WHERE analysis_id = ?
    ORDER BY sequence_accession, start_position
');
$detail_stmt->execute([$analysis_id]);
$motif_details = $detail_stmt->fetchAll();
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
    <title>Motif Analysis</title>
     <!-- Link to shared stylesheet -->
    <link rel='stylesheet' href='style.css'>
</head>
<body>
<?php include 'menu.php'; ?>
    <h1>Motif Analysis</h1>

    <?php if ($message): ?>
    <!-- Display status for motif analysis -->
        <p><?php echo $message; ?></p>
    <?php endif; ?>

    <?php if ($row): ?>
        <!-- Display analysis ID -->
        <!-- Adapted from (PHP manual htmlspecialchars) -->
        <p><strong>Analysis ID:</strong> <?php echo htmlspecialchars($analysis_id); ?></p>
        
    <?php if (!empty($motif_summary)): ?>
        <h2>Motif Summary</h2>
        <!-- Summary table showing total counts and motifs for analyses -->
        <!-- Adapted from (W3Schools, HTML Tables). -->
        <table border='1' cellpadding='5'>
            <tr>
             <th>Motif</th>
             <th>Count</th>
        </tr>
        <?php foreach ($motif_summary as $summary_row): ?>
            <tr>
                <td><?php echo htmlspecialchars($summary_row['motif_name']); ?></td>
                <td><?php echo htmlspecialchars($summary_row['hit_count']); ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

<?php if (!empty($motif_details)): ?>
    <h2>Detailed Motif Hits</h2>
    <!-- Detailed table with accession, coordinates and summary -->
    <!-- Adapted from (W3Schools, HTML Tables). -->
    <table border='1' cellpadding='5'>
        <tr>
            <th>Sequence Accession</th>
            <th>Motif</th>
            <th>Start</th>
            <th>End</th>
            <th>Summary</th>
        </tr>
        <?php foreach ($motif_details as $detail_row): ?>
            <tr>
                <td><?php echo htmlspecialchars($detail_row['sequence_accession']); ?></td>
                <td><?php echo htmlspecialchars($detail_row['motif_name']); ?></td>
                <td><?php echo htmlspecialchars($detail_row['start_position']); ?></td>
                <td><?php echo htmlspecialchars($detail_row['end_position']); ?></td>
                <td><?php echo htmlspecialchars($detail_row['match_summary']); ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>
    
<?php if (isset($plot_file) && file_exists($plot_file)): ?>
    <h2>Motif Frequency Plot</h2>
    <!-- Displays motif frequency plot with relative output path -->
    <!-- Adapted from (PHP manual basename(), urlencode()) and (Display Image using Php, StackOverflow, 2024). -->
    <img src='<?php echo 'outputs/analysis_' . urlencode($analysis_id) . '/motif_plot.png'; ?>' alt='Motif Frequency Plot' width='600'>
<?php endif; ?>
<?php endif; ?>
</body>
</html>