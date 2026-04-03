<?php
// Load database connection and login information.
require_once 'db_connect.php';
require_once 'login.php';
//Define variables.
$row = null;
$message = '';
$python_output = '';
$matrix_file = '';
$heatmap_file = '';
$web_heatmap_file = '';
$identity_stats = [];
$web_matrix_file = '';

// Ensure that an analysis_id has been provided.
// Adapted from (PHP manual, is_numeric, if).
if (isset($_GET['analysis_id']) && is_numeric($_GET['analysis_id'])) {
    $analysis_id = (int) $_GET['analysis_id'];
    // Fetch analysis row.
    // Database communication code adapted from (PHP manual, PDO::prepare) and (phpdelusions.net/pdo_examples).
    $stmt = $pdo->prepare('SELECT * FROM analyses WHERE analysis_id = ?');
    $stmt->execute([$analysis_id]);
    $row = $stmt->fetch();
    // Create output folder.
    // Adapted from (PHP manual, mkdir). 
    if ($row) {
        $output_path = __DIR__ . '/outputs/analysis_' . $analysis_id;

        if (!is_dir($output_path)) {
            mkdir($output_path, 0777, true);
        }

        $aligned_fasta = $output_path . '/aligned.fasta';
        $filtered_fasta = $output_path . '/filtered.fasta';
        $matrix_file = $output_path . '/identity_matrix.tsv';
        $heatmap_file = $output_path . '/identity_heatmap.png';
        $summary_file = $output_path . '/identity_matrix_summary.tsv';
        
        // If no aligned FASTA has been produced yet, create one using Clustal Omega.
        //Adapted from (PHP manual shell_exec, escapeshellarg) and (What's the difference between escapeshellarg and escapeshellcmd, StackOverflow, 2009).
            // Clustal Omega code adapted from (Sievers and Higgins, 2017).
        if (!file_exists($aligned_fasta)) {

          if (file_exists($filtered_fasta)) {
          $align_cmd = 'clustalo -i ' . escapeshellarg($filtered_fasta) .
                     ' -o ' . escapeshellarg($aligned_fasta) .
                     ' --force --outfmt=fasta 2>&1';
                      shell_exec($align_cmd);
                      }
                      }

        if (file_exists($aligned_fasta)){
        // Run python script to create pairwose identity matrix TSV and heatmap PNG.
        // Adapted from (PHP manual, escapeshellarg(), shell_exec()).
        $python_cmd = escapeshellarg(__DIR__ . '/myenv/bin/python') . ' ' .
              escapeshellarg(__DIR__ . '/identity_matrix.py') . ' ' .
              escapeshellarg($aligned_fasta) . ' ' .
              escapeshellarg($matrix_file) . ' ' .
              escapeshellarg($heatmap_file) . ' 2>&1';

            $python_output = shell_exec($python_cmd);
            
            if (file_exists($matrix_file) && file_exists($heatmap_file)) {
                $message = 'Pairwise identity matrix and heatmap created successfully.';
                // Read summary statistics produced by Python.
                if (file_exists($summary_file) && filesize($summary_file) > 0) {
                    $handle = fopen($summary_file, 'r');

                    if ($handle !== false) {
                        $header = fgetcsv($handle, 0, "\t");

                        while (($summary_row = fgetcsv($handle, 0, "\t")) !== false) {
                            if (count($summary_row) >= 2) {
                                $identity_stats[$summary_row[0]] = $summary_row[1];
                            }
                        }

                        fclose($handle);
                    }
                }
                // Broswer compatible relative path to the matrix file.
                $web_matrix_file = 'outputs/analysis_' . $analysis_id . '/' . basename($matrix_file);

                // Check whether a row already exists for analysis.
                $checkexist = $pdo->prepare('SELECT * FROM identity_output WHERE analysis_id = ?');
                $checkexist->execute([$analysis_id]);
                $existing = $checkexist->fetch();

                if ($existing) {
                // Update database with new files.
                    $update = $pdo->prepare('
                        UPDATE identity_output
                        SET matrix_file_path = ?, heatmap_file_path = ?
                        WHERE analysis_id = ?
                    ');
                    $update->execute([
                        $matrix_file,
                        $heatmap_file,
                        $analysis_id
                    ]);
                } else {
                // Insert new database record for output.
                    $insert = $pdo->prepare('
                        INSERT INTO identity_output
                        (analysis_id, matrix_file_path, heatmap_file_path)
                        VALUES (?, ?, ?)
                    ');
                    $insert->execute([
                        $analysis_id,
                        $matrix_file,
                        $heatmap_file
                    ]);
                }
                // Browser accesible path to heatmap image.
                // Adapted from (PHP manual basename()). 
                 $web_heatmap_file = 'outputs/analysis_' . $analysis_id . '/' . basename($heatmap_file);
            } else {
                $message = 'Pairwise identity analysis failed.';
            }
        } else {
            $message = 'Aligned FASTA file not found.';
        }
    } else {
        $message = 'No analysis found.';
    }
} else {
    $message = 'Invalid analysis_id.';
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Pairwise Identity Analysis</title>
    <link rel='stylesheet' href='style.css'>
    <!-- Link to shared stylesheet -->
    <!-- Use CSS Grid for interactive cards -->
    <!-- Adapted from (CSS Grid Layout, W3Schools). -->
    <style>
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
            margin: 20px 0 30px 0;
        }
        /* Card for each summary statstic */
        /* Adapted from (CSS Cards, W3Schools). */
        .stat-card {
            background: #f5f7fa;
            border: 1px solid #d9e0e6;
            border-radius: 12px;
            padding: 18px;
            text-align: center;
            box-shadow: 0 2px 6px rgba(0,0,0,0.08);
        }

        .stat-value {
            font-size: 1.8rem;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .stat-label {
            font-size: 0.95rem;
            color: #555;
        }

        .analysis-image {
            max-width: 100%;
            height: auto;
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 8px;
            background: white;
        }
        /* Hover effect for the download link */
        /* Adapted from (CSS Hover, W3Schools). */
        .download-link {
            display: inline-block;
            margin-top: 15px;
            padding: 10px 14px;
            background: #eef3f8;
            border: 1px solid #ccd6e0;
            border-radius: 8px;
            text-decoration: none;
            color: #1f3b57;
        }

        .download-link:hover {
            background: #e2ebf3;
        }
    </style>
</head>
<body>

<?php include 'menu.php'; ?>

<h1>Pairwise Identity Analysis</h1>

<?php if ($row): ?>
    <p><strong>Protein family:</strong> <?php echo htmlspecialchars($row['protein_family']); ?></p>
    <p><strong>Taxonomic group:</strong> <?php echo htmlspecialchars($row['taxonomic_group']); ?></p>
<?php endif; ?>

<p><strong>Status:</strong> <?php echo htmlspecialchars($message); ?></p>

<?php if (!empty($identity_stats)): ?>
    <h2>Pairwise Identity Summary</h2>

    <div class='stat-grid'>
        <div class='stat-card'>
            <div class='stat-value'><?php echo htmlspecialchars((string)$identity_stats['num_sequences']); ?></div>
            <div class='stat-label'>Sequences</div>
        </div>

        <div class='stat-card'>
            <div class='stat-value'><?php echo htmlspecialchars((string)$identity_stats['mean_identity']); ?>%</div>
            <div class='stat-label'>Mean Identity</div>
        </div>

        <div class='stat-card'>
            <div class='stat-value'><?php echo htmlspecialchars((string)$identity_stats['median_identity']); ?>%</div>
            <div class='stat-label'>Median Identity</div>
        </div>

        <div class='stat-card'>
            <div class='stat-value'><?php echo htmlspecialchars((string)$identity_stats['max_identity']); ?>%</div>
            <div class='stat-label'>Max Identity</div>
        </div>

        <div class='stat-card'>
            <div class='stat-value'><?php echo htmlspecialchars((string)$identity_stats['min_identity']); ?>%</div>
            <div class='stat-label'>Min Identity</div>
        </div>

        <div class='stat-card'>
            <div class='stat-value'><?php echo htmlspecialchars((string)$identity_stats['identical_pairs']); ?></div>
            <div class='stat-label'>Identical Pairs</div>
        </div>

        <div class='stat-card'>
            <div class='stat-value'><?php echo htmlspecialchars((string)$identity_stats['high_similarity_pairs']); ?></div>
            <div class='stat-label'>Pairs = 70%</div>
        </div>

        <div class='stat-card'>
            <div class='stat-value'><?php echo htmlspecialchars((string)$identity_stats['low_similarity_pairs']); ?></div>
            <div class='stat-label'>Pairs &lt; 20%</div>
        </div>
    </div>

    <p>
        This dataset contains closely related and highly divergent sequence pairs, which can indicate conserved subgroups alongside broader evolutionary diversity.
    </p>
<?php endif; ?>
 
<?php if ($web_heatmap_file !== ''): ?>
    <h2>Pairwise Identity Heatmap</h2>
    <img class='analysis-image' src='<?php echo htmlspecialchars($web_heatmap_file); ?>' alt='Pairwise identity heatmap'>
<?php endif; ?>

<?php if ($web_matrix_file !== ''): ?>
    <p>
        <a class='download-link' href='<?php echo htmlspecialchars($web_matrix_file); ?>' download>
            Download Pairwise Identity Matrix
        </a>
    </p>
<?php endif; ?>

</body>
</html>
