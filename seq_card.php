<?php
// Load database connection and login information.
require_once 'db_connect.php';
require_once 'login.php';

// Parse FASTA file into header and sequence array of records.
// Adapted from (PHP manual file(), str_starts_with(), substr()), (NCBI FASTA Format, GenBank, NCBI).
function parse_fasta_file(string $fasta_file): array
{
    $records = [];
    // Return empty array if FASTA file is missng or empty.
    // Adapted from (PHP manual file_exists()).
    if (!file_exists($fasta_file) || filesize($fasta_file) === 0) {
        return $records;
    }

    $lines = file($fasta_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    $header = null;
    $sequence = '';

    foreach ($lines as $line) {
        if (str_starts_with($line, '>')) {
            if ($header !== null) {
                $records[] = [$header, $sequence];
            }
            $header = substr($line, 1);
            $sequence = '';
        } else {
            $sequence .= trim($line);
        }
    }
    
    if ($header !== null) {
        $records[] = [$header, $sequence];
    }

    return $records;
}
// Write a single FASTA record to file for EMBOSS analysis downstream.
// Adapted from (PHP manual file_put_contents()).
function write_single_fasta(string $file, string $header, string $sequence): void
{
    $content = '>' . $header . PHP_EOL;
    $content .= $sequence . PHP_EOL;
    file_put_contents($file, $content);
}
// Extract summary values from EMBOSS pepstats.
// Adapted from (EMBOSS pepstats manual), (PHP manual stripos(), preg_split(), preg_match()).
function extract_pepstats_summary(string $pepstats_file): array
{
    $summary = [
        'molecular_weight' => 'Not found',
        'isoelectric_point' => 'Not found',
        'charge' => 'Not found'
    ];

    if (!file_exists($pepstats_file)) {
        return $summary;
    }

    $lines = file($pepstats_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        $trimmed = trim($line);

        if (stripos($trimmed, 'Molecular weight') !== false) {
            $parts = preg_split('/\s+/', $trimmed);
            $summary['molecular_weight'] = end($parts);
        }

        if (stripos($trimmed, 'Isoelectric Point') !== false) {
            $parts = preg_split('/\s+/', $trimmed);
            $summary['isoelectric_point'] = end($parts);
        }

        if (stripos($trimmed, 'Charge') === 0 || stripos($trimmed, 'Charge =') !== false) {
            if (preg_match('/Charge[^-\d]*([-\d\.]+)/i', $trimmed, $m)) {
                $summary['charge'] = $m[1];
            }
        }
    }

    return $summary;
}
//Define variables.
$row = null;
$message = '';
$record = null;
$analysis_id = null;
$seq_index = null;
// Ensure that an analysis_id has been provided.
//Adapted from (PHP manual, is_numeric, if).
if (
    isset($_GET['analysis_id'], $_GET['seq']) &&
    is_numeric($_GET['analysis_id']) &&
    is_numeric($_GET['seq'])
) {
    $analysis_id = (int) $_GET['analysis_id'];
    $seq_index = (int) $_GET['seq'];
    // Fetch selected analysis row from the database.
    // Database communication code adapted from (PHP manual, PDO::prepare) and (phpdelusions.net/pdo_examples).
    $stmt = $pdo->prepare('SELECT * FROM analyses WHERE analysis_id = ?');
    $stmt->execute([$analysis_id]);
    $row = $stmt->fetch();
    // Create an output directory.
    // Adapted from (PHP manual, mkdir) and (Create a folder if it doesn't already exist, StackOverflow, 2010).
    if ($row) {
        $output_path = __DIR__ . '/outputs/analysis_' . $analysis_id;
        $filtered_fasta = $output_path . '/filtered.fasta';
        // Parse all filtered FASTA records for analysis.
        $records = parse_fasta_file($filtered_fasta);

        if (isset($records[$seq_index])) {
            $record = $records[$seq_index];
            [$header, $sequence] = $record;
            // Parse header into accession, protein name and organism.
            // Adapted from (PHP manual preg_match()), (NCBI FASTA Format, GenBank, NCBI), (Regex101).
            preg_match('/^(\S+)\s+(.*)\s+\[([^\]]+)\]$/', $header, $matches);
            
            $accession = $matches[1] ?? 'Unknown';
            $protein_name = $matches[2] ?? $header;
            $organism = $matches[3] ?? 'Unknown';
            // Define file paths for analysis outputs.
            $single_fasta = $output_path . '/sequence_' . $seq_index . '.fasta';
            $pepstats_file = $output_path . '/sequence_' . $seq_index . '_pepstats.txt';
            $hydropathy_base = $output_path . '/sequence_' . $seq_index . '_hydropathy';
            // Write FASTA record for EMBOSS tools.
            write_single_fasta($single_fasta, $header, $sequence);
            // If report does not exist run EMBOSS pepstats.
            // Adapted from (EMBOSS pepstats) and (PHP manual shell_exec(), escapeshellarg()).
            if (!file_exists($pepstats_file) || filesize($pepstats_file) === 0) {
                $pepstats_cmd = 'pepstats -sequence ' . escapeshellarg($single_fasta) .
                                ' -outfile ' . escapeshellarg($pepstats_file) . ' 2>&1';
                shell_exec($pepstats_cmd);
            }

            $hydropathy_png = $hydropathy_base . '.1.png';
            // Run EMBOSS pepwindow if hyropathy plot does not exist.
            // Adapted from (EMBOSS pepwindow), (PHP manual shell_exec(), escapeshellarg()).
            if (!file_exists($hydropathy_png) || filesize($hydropathy_png) === 0) {
                $pepwindow_cmd = 'pepwindow -sequence ' . escapeshellarg($single_fasta) .
                                 ' -graph png -goutfile ' . escapeshellarg($hydropathy_base) . ' 2>&1';
                shell_exec($pepwindow_cmd);
            }
            // Extract summary values from pepstats output.
            $pepstats_summary = extract_pepstats_summary($pepstats_file);
        } else {
            $message = 'Sequence index not found.';
        }
    } else {
        $message = 'Analysis not found.';
    }
} else {
    $message = 'Invalid request.';
}
?>
<!DOCTYPE html>
<html 
<head>
    <title>Sequence Card</title>
    <link rel='stylesheet' href='style.css'>
    <!-- Link to shared stylesheet -->
    <style>
    /* Adapted from (How to create a card with CSS, W3Schools), (CSS Grid, W3Schools) */
    
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        /* Card for selected sequence */
        .card {
            max-width: 900px;
            margin: 0 auto;
            border: 1px solid #ccc;
            border-radius: 12px;
            padding: 20px;
            background: #f9f9f9;
        }
        /* Grid layout for summary boxes */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin: 20px 0;
        }

        .stat-box {
            background: white;
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 15px;
            text-align: center;
        }

        .stat-box h3 {
            margin-top: 0;
            font-size: 1rem;
            color: #444;
        }

        .stat-box p {
            font-size: 1.2rem;
            margin: 0;
            font-weight: bold;
        }
        /* Styling for pepstats reports */
        pre {
            background: white;
            border: 1px solid #ddd;
            padding: 12px;
            overflow-x: auto;
        }
        /* Image styling for the hydropathy plot */
        img {
            max-width: 100%;
            height: auto;
            border: 1px solid #ccc;
            background: white;
            padding: 8px;
        }
    </style>
</head>
<body>

<?php include 'menu.php'; ?>

<div class='card'>
    <h1>Sequence Card</h1>

    <?php if ($record): ?>
        <p><strong>Accession:</strong> <?php echo htmlspecialchars($accession); ?></p>
        <p><strong>Protein name:</strong> <?php echo htmlspecialchars($protein_name); ?></p>
        <p><strong>Organism:</strong> <?php echo htmlspecialchars($organism); ?></p>
        <p><strong>Sequence length:</strong> <?php echo htmlspecialchars((string)strlen($sequence)); ?></p>

        <div class='stats-grid'>
            <div class='stat-box'>
                <h3>Isoelectric Point</h3>
                <p><?php echo htmlspecialchars($pepstats_summary['isoelectric_point']); ?></p>
            </div>
            <div class='stat-box'>
                <h3>Charge</h3>
                <p><?php echo htmlspecialchars($pepstats_summary['charge']); ?></p>
            </div>
            <div class='stat-box'>
                <h3>Molecular Weight</h3>
                <p><?php echo htmlspecialchars($pepstats_summary['molecular_weight']); ?></p>
            </div>
        </div>

        <h2>Hydropathy Plot</h2>
        <!-- Display hydropathy plot output image -->
        <!-- Adapted from (PHP Echo to Display Image HTML, StackOverflow, 2014). -->
        <?php if (file_exists($hydropathy_png) && filesize($hydropathy_png) > 0): ?>
            <img src='<?php echo htmlspecialchars('outputs/analysis_' . $analysis_id . '/sequence_' . $seq_index . '_hydropathy.1.png'); ?>' alt='Hydropathy plot'>
        <?php else: ?>
            <p>Hydropathy plot not available.</p>
        <?php endif; ?>

        <h2>Sequence</h2>
        <pre><?php echo htmlspecialchars($sequence); ?></pre>

        <h2>Full pepstats report</h2>
        <?php if (file_exists($pepstats_file)): ?>
            <pre><?php echo htmlspecialchars(file_get_contents($pepstats_file)); ?></pre>
        <?php else: ?>
            <p>pepstats report not available.</p>
        <?php endif; ?>

        <p><?php echo htmlspecialchars($message); ?></p>
    <?php endif; ?>
</div>

</body>
</html>