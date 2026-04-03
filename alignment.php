<?php
// Load database connection and login information.
require_once 'db_connect.php';
require_once 'login.php';

//Define variables.
$row = null;
$message = '';
$sequence_quality = [];
$alignment_length = null;
$plotcon_output = '';
$infoalign_output = '';

//Parse FASTA files into array with header and sequence records.
//Adapted from (PHP manual file, str_starts_with, substr, trim) (Parse a fasta file using PHP, StackOverflow, 2019).
function parse_fasta_file(string $fasta_file): array
{
    $records = [];
    // Stop if the file is missing.
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
            //Removes '>' and starts new record.
            $header = substr($line, 1);
            $sequence = '';
        } else {
        // Appends lines together is over multiple lines
            $sequence .= trim($line);
        }
    }
    // Save FASTA record.
    if ($header !== null) {
        $records[] = [$header, $sequence];
    }

    return $records;
}
// Adapted from PHP manual for preg_split, trim and (PHP Preg_split() Not Capturing the Split in the String, StackOverflow, 2012).
function get_accession_from_header(string $header): string
{
    $parts = preg_split('/\s+/', trim($header));
    return $parts[0] ?? $header;
}
// Parse EMBOSS infoalign output into array for statistics
// Adapted from EMBOSS infoalign manual and PHP manual file, preg_split, is_numeric
function parse_infoalign_file(string $infoalign_file): array
{
    $rows = [];
    // Return empty result if the file does not exist
    if (!file_exists($infoalign_file) || filesize($infoalign_file) === 0) {
        return $rows;
    }
    // Read lines from infoalign output
    $lines = file($infoalign_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        $trimmed = trim($line);
        // Skip blank and comment lines
        if ($trimmed === '' || str_starts_with($trimmed, '#')) {
            continue;
        }
        // Split the whitespace-delimited columns in infoalign
        $parts = preg_split('/\s+/', $trimmed);

        if (count($parts) >= 5) {
            $name = $parts[0];
            // Store parsed values
            $rows[$name] = [
                'infoalign_seqlength' => is_numeric($parts[1]) ? (int)$parts[1] : null,
                'infoalign_alignlength' => is_numeric($parts[2]) ? (int)$parts[2] : null,
                'gap_runs' => is_numeric($parts[3]) ? (int)$parts[3] : null,
                'gap_count_infoalign' => is_numeric($parts[4]) ? (int)$parts[4] : null,
            ];
        }
    }

    return $rows;
}
// Ensure that an analysis_id has been provided.
//Adapted from (PHP manual, is_numeric, if).
if (isset($_GET['analysis_id']) && is_numeric($_GET['analysis_id'])) {
    $analysis_id = (int) $_GET['analysis_id'];
    // Fetch selected analysis row from the database.
    // Database communication code adapted from (PHP manual, PDO::prepare) and (phpdelusions.net/pdo_examples).
    $stmt = $pdo->prepare('SELECT * FROM analyses WHERE analysis_id = ?');
    $stmt->execute([$analysis_id]);
    $row = $stmt->fetch();
    // Create an output directory.
    // Adapted from (PHP manual, mkdir) and (Create a folder if it doesn't already exist, StackOverflow, 2010).
    if ($row) {
        $output_path = __DIR__ . '/outputs/analysis_' . $analysis_id;

        if (!is_dir($output_path)) {
            mkdir($output_path, 0777, true);
        }
        // Define input/output files for alignment results.
        $filtered_fasta = $output_path . '/filtered.fasta';
        $alignment_file = $output_path . '/aligned.aln';
        $alignment_fasta = $output_path . '/aligned.fasta';
        $conservation = $output_path . '/conservation';
        $conservation_plot = $output_path . '/conservation.1.png';
        $infoalign_file = $output_path . '/infoalign.txt';

        // Proceed if filtered FASTA exists
        if (file_exists($filtered_fasta) && filesize($filtered_fasta) > 0) {
            // Check if alignment outputs exist
            $checkexist = $pdo->prepare('SELECT * FROM alignment_output WHERE analysis_id = ?');
            $checkexist->execute([$analysis_id]);
            $existing = $checkexist->fetch();

            $have_saved_outputs = false;
            // Reuse previous outputs if they exist
            if (
                $existing &&
                !empty($existing['alignment_file_path']) &&
                !empty($existing['aligned_fasta_path']) &&
                !empty($existing['conservation_plot_path']) &&
                !empty($existing['infoalign_file_path']) &&
                file_exists($existing['alignment_file_path']) &&
                file_exists($existing['aligned_fasta_path']) &&
                file_exists($existing['conservation_plot_path']) &&
                file_exists($existing['infoalign_file_path'])
            ) {
                $alignment_file = $existing['alignment_file_path'];
                $alignment_fasta = $existing['aligned_fasta_path'];
                $conservation_plot = $existing['conservation_plot_path'];
                $infoalign_file = $existing['infoalign_file_path'];
                $alignment_length = $existing['alignment_length'];

                $message = 'Saved alignment results loaded.';
                $have_saved_outputs = true;
            }
            // Run alignment output if no saved outputs.
            //Adapted from (PHP manual shell_exec, escapeshellarg) and (What's the difference between escapeshellarg and escapeshellcmd, StackOverflow, 2009).
            // Clustal Omega code adapted from (Sievers and Higgins, 2017).
            if (!$have_saved_outputs) {
                // Run Clustal Omega to generate CLUSTAL alignment file.
                $clustal = 'clustalo -i ' . escapeshellarg($filtered_fasta) .
                           ' -o ' . escapeshellarg($alignment_file) .
                           ' --outfmt=clu --force 2>&1';

                $command_output = shell_exec($clustal);
                // Run Clustal Omega to generate a FASTA-format alignment file
                $clustal_align = 'clustalo -i ' . escapeshellarg($filtered_fasta) .
                                 ' -o ' . escapeshellarg($alignment_fasta) .
                                 ' --outfmt=fasta --force 2>&1';

                $command_output .= "\n" . shell_exec($clustal_align);
                // Continue if alignment files are created.
                if (file_exists($alignment_file) && file_exists($alignment_fasta)) {
                    $lines = file($alignment_fasta, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                    $sequence = '';
                    // Get alignment length from first aligned sequence.
                    foreach ($lines as $line) {
                        if (!str_starts_with($line, '>')) {
                            $sequence .= trim($line);
                        } elseif ($sequence !== '') {
                            break;
                        }
                    }

                    if ($sequence !== '') {
                        $alignment_length = strlen($sequence);
                    }
                    // Generate conservation plot from alignment using EMBOSS plotcon.
                    // Adapted from (EMBOSS plotcon manual). 
                    $plotcon = 'plotcon -sequences ' . escapeshellarg($alignment_fasta) .
                               ' -winsize 4 -graph png -goutfile ' . escapeshellarg($conservation) .
                               ' -auto 2>&1';

                    $plotcon_output = shell_exec($plotcon);
                    // Generate a table of alignment summary using EMBOSS infoalign.
                    // Adapted from (EMBOSS, infoalign). 
                    $infoalign_cmd = 'infoalign -sequence ' . escapeshellarg($alignment_fasta) .
                                     ' -outfile ' . escapeshellarg($infoalign_file) .
                                     ' -only -name -seqlength -alignlength -gaps -gapcount -auto 2>&1';

                    $infoalign_output = shell_exec($infoalign_cmd);
                    // Report whether conservation plot image was successfully generated).
                    if (file_exists($conservation_plot)) {
                        $message = 'Alignment completed successfully. Conservation plot created.';
                    } else {
                        $message = 'Alignment completed successfully, but conservation plot failed.';
                    }
                    // If alignment_output row exists, update it.
                    if ($existing) {
                        $update = $pdo->prepare('
                            UPDATE alignment_output
                            SET 
                                filtered_fasta_path = ?, 
                                alignment_file_path = ?, 
                                aligned_fasta_path = ?, 
                                alignment_length = ?,
                                conservation_plot_path = ?,
                                infoalign_file_path = ?
                            WHERE analysis_id = ?
                        ');
                        $update->execute([
                            $filtered_fasta,
                            $alignment_file,
                            $alignment_fasta,
                            $alignment_length,
                            $conservation_plot,
                            $infoalign_file,
                            $analysis_id
                        ]);
                    } else {
                        // Insert new row for alignment outputs.
                        $insert = $pdo->prepare('
                            INSERT INTO alignment_output
                            (analysis_id, filtered_fasta_path, alignment_file_path, aligned_fasta_path, alignment_length, conservation_plot_path, infoalign_file_path)
                            VALUES (?, ?, ?, ?, ?, ?, ?)
                        ');
                        $insert->execute([
                            $analysis_id,
                            $filtered_fasta,
                            $alignment_file,
                            $alignment_fasta,
                            $alignment_length,
                            $conservation_plot,
                            $infoalign_file
                        ]);
                    }
                } else {
                    $message = 'Alignment failed. No file created';
                }
            }
            // If aligned FASTA file exists, display statistics.
            if (file_exists($alignment_fasta)) {
                $original_records = parse_fasta_file($filtered_fasta);
                $aligned_records = parse_fasta_file($alignment_fasta);
                $infoalign_rows = parse_infoalign_file($infoalign_file);

                $original_lengths = [];
                // Record original sequence lengths 
                foreach ($original_records as [$header, $sequence]) {
                    $accession = get_accession_from_header($header);
                    $original_lengths[$accession] = strlen($sequence);
                }

                $sequence_quality = [];
                // Create summary rows for aligned sequences.
                foreach ($aligned_records as [$header, $sequence]) {
                    $accession = get_accession_from_header($header);

                    $aligned_length = strlen($sequence);
                    $gap_count = substr_count($sequence, '-');
                    // Use EMBOSS infoalign values if available.
                    if (isset($infoalign_rows[$accession])) {
                        if ($infoalign_rows[$accession]['infoalign_alignlength'] !== null) {
                            $aligned_length = $infoalign_rows[$accession]['infoalign_alignlength'];
                        }

                        if ($infoalign_rows[$accession]['gap_count_infoalign'] !== null) {
                            $gap_count = $infoalign_rows[$accession]['gap_count_infoalign'];
                        }
                    }
                    // Calculate percentage of gap alignment positions.
                    $gap_percent = $aligned_length > 0 ? ($gap_count / $aligned_length) * 100 : 0;
                    // Store the values used in sequence quality table.
                    $sequence_quality[] = [
                        'accession' => $accession,
                        'original_length' => $original_lengths[$accession] ?? 0,
                        'aligned_length' => $aligned_length,
                        'gap_count' => $gap_count,
                        'gap_percent' => round($gap_percent, 2),
                        'gap_runs' => $infoalign_rows[$accession]['gap_runs'] ?? null
                    ];
                }
            }
        } else {
            $message = 'Filtered FASTA file not found';
        }
    } else {
        $message = 'No analysis found';
    }
} else {
    $message = 'Invalid analysis_id';
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Run Analysis</title>
    <!-- Link to shared stylesheet -->
    <link rel='stylesheet' href='style.css'>
</head>
<body>

<?php include 'menu.php'; ?>

<h1>Run Analysis</h1>

<?php if ($row): ?>
    <!-- Show protein family and taxonomic group for the loaded analysis. -->
    <!-- Adapted from (PHP manual htmlspecialchars) -->
    <p><strong>Protein family:</strong> <?php echo htmlspecialchars($row['protein_family']); ?></p>
    <p><strong>Taxonomic group:</strong> <?php echo htmlspecialchars($row['taxonomic_group']); ?></p> 
<?php endif; ?>
<!-- Display the status message for the alignment workflow. -->
<p><strong>Status:</strong> <?php echo htmlspecialchars($message); ?></p>

<?php if (!empty($alignment_length)): ?>
    <!-- Display alignment length when available. -->
    <p><strong>Alignment length:</strong> <?php echo htmlspecialchars($alignment_length); ?></p>
<?php endif; ?>

<?php
$web_conservation_plot = '';
// Convert image path to a relative path
// Adapted from (PHP manual, urlencode, basename).
if (isset($conservation_plot) && file_exists($conservation_plot)) {
    $web_conservation_plot = 'outputs/analysis_' . urlencode($analysis_id) . '/' . basename($conservation_plot);
}
?>

<?php if ($web_conservation_plot !== ''): ?>
    <h2>Conservation Plot</h2>
    <!-- Display EMBOSS plotcon outpt image -->
    <!-- Adapted from (PHP Echo to Display Image HTML, StackOverflow, 2014). -->
    <img src='<?php echo htmlspecialchars($web_conservation_plot); ?>' alt='Conservation plot'>
<?php endif; ?>

<?php if (!empty($sequence_quality)): ?>
    <h2>Sequence Quality Summary</h2>
    <!-- Display sequence alignment quality metrics in a summary table. -->
    <!-- Adapted from (W3Schools, HTML Tables). -->
    <table border='1' cellpadding='6'>
        <tr>
            <th>Accession</th>
            <th>Original Length</th>
            <th>Aligned Length</th>
            <th>Gap Count</th>
            <th>Gap %</th>
            <th>Gap Runs</th>
        </tr>
        <?php foreach ($sequence_quality as $seq): ?>
            <tr>
                <td><?php echo htmlspecialchars($seq['accession']); ?></td>
                <td><?php echo htmlspecialchars((string)$seq['original_length']); ?></td>
                <td><?php echo htmlspecialchars((string)$seq['aligned_length']); ?></td>
                <td><?php echo htmlspecialchars((string)$seq['gap_count']); ?></td>
                <td><?php echo htmlspecialchars((string)$seq['gap_percent']); ?></td>
                <td><?php echo htmlspecialchars((string)($seq['gap_runs'] ?? '')); ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

<p>
A <strong>gap count</strong> represents the total number of alignment positions containing inserted gaps for a sequence, while <strong>gap runs</strong> represent the number of separate gap regions. High values indicate incomplete sequence coverage or poorer alignment to the rest of the sequences.
</p>

</body>
</html>
