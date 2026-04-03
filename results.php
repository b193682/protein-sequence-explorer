<?php
// Load database connection and login information.
require_once 'db_connect.php';
require_once 'login.php';

// Retrieve protein FASTA records from NCBI using Entrez Direct.
// Adapted from (PHP manual shell_exec(), escapeshellarg()) and (Entrez Direct, Kans, NCBI, 2025).
function fetch_ncbi_protein_fasta(string $protein_family, string $taxonomic_group, string $output_file, int $retmax = 50): bool
{
    // Use NCBI API key stored in login.php to authenticate requests.
    global $ncbi_api_key;

    $query = $protein_family . ' AND ' . $taxonomic_group . '[Organism]';
    // Absolute paths to Entrez Direct on server.
    $esearch = '/home/s2223599/edirect/esearch';
    $efetch = '/home/s2223599/edirect/efetch';
    // Run esearch and keep only the first retmax IDs, join them in list for efetch.
    $uid_command = 'bash -lc ' . escapeshellarg(
        'export HOME=/home/s2223599; ' .
        'export NCBI_API_KEY=' . escapeshellarg($ncbi_api_key) . '; ' .
        $esearch . ' -db protein -query ' . escapeshellarg($query) .
        ' | ' . $efetch . ' -format uid' .
        ' | head -n ' . (int)$retmax .
        ' | paste -sd, -'
    );
    
    $ids = trim(shell_exec($uid_command) ?? '');

    // If no IDs were returned, report failure.
    if ($ids === '') {
        return false;
    }

   // Fetch selected protein IDs from NCBI request FASTA format and write results to output file.
   // Adapted from (Entrez Direct, Kans, NCBI, 2025).
    $fetch_command = 'bash -lc ' . escapeshellarg(
        'export HOME=/home/s2223599; ' .
        'export NCBI_API_KEY=' . escapeshellarg($ncbi_api_key) . '; ' .
        $efetch . ' -db protein -id ' . escapeshellarg($ids) .
        ' -format fasta'
    ) . ' > ' . escapeshellarg($output_file) . ' 2>&1';

    shell_exec($fetch_command);
    // Check that output files exist and are not empty.
    if (!file_exists($output_file) || filesize($output_file) === 0) {
        return false;
    }
    // Inspect first line, should begin with '>'.
    // Adapted from (PHP manual fopen(), fgets(), fclose(), str_starts_with()).
    $handle = fopen($output_file, 'r');
    $first_line = $handle ? fgets($handle) : false;

    if ($handle) {
        fclose($handle);
    }

    return $first_line !== false && str_starts_with($first_line, '>');
}
// Define variables.
$row = null;
$records = [];
$raw_records = 0;
$filtered_count = 0;
$output_fasta = '';
$raw_fasta = '';
$message = '';
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
        $raw_fasta = $output_path . '/raw.fasta';
        $output_fasta = $output_path . '/filtered.fasta';

        if (!is_dir($output_path)) {
            mkdir($output_path, 0777, true);
        }
        // If no raw FASTA found, load example dataset or retrieve records from NCBI.
        if (!file_exists($raw_fasta) || filesize($raw_fasta) === 0) {
            if ((int)$row['use_example'] === 1) {
                $example_fasta = __DIR__ . '/aves_g6pc_example.fasta';
                
                // Copy example FASTA into analysis folder.
                // Adapted from (PHP manual, copy()).
                if (file_exists($example_fasta)) {
                    copy($example_fasta, $raw_fasta);
                    $message = 'Example dataset loaded.';
                } else {
                    $message = 'Example FASTA file not found.';
                }
            } else {
            // Use max_sequences from analysis row as the retrieval cap, or default at 50.
              $retmax = $row['max_sequences'] ?? 50;

              $ok = fetch_ncbi_protein_fasta(
    $row['protein_family'],
    $row['taxonomic_group'],
    $raw_fasta,
    (int)$retmax
);

                if ($ok) {
                    $message = 'NCBI FASTA retrieved successfully.';
                } else {
                    $message = 'No FASTA sequences were retrieved from NCBI.';
                }
            }
        } else {
            $message = 'Existing raw FASTA found.';
        }
        // Parse and filter raw FASTA files.
        if (file_exists($raw_fasta) && filesize($raw_fasta) > 0) {
        // Read into array
        // Adapted from (PHP manual file()).
            $lines = file($raw_fasta, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

            $header = null;
            $sequence = '';
            // Parse records line by line starting with '>'
            // Adapted from (PHP manual str_starts_with(), substr(), trim()).
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
            // Save final record.
            if ($header !== null) {
                $records[] = [$header, $sequence];
            }

            $raw_records = count($records);

            $filtered_records = [];
            // Filter unwanted FASTA records.
            // Adapted from (PHP manual strtolower(), strpos(), srtlen()).
            foreach ($records as [$header, $sequence]) {
                $header_lower = strtolower($header);
                $sequence_length = strlen($sequence);

                if (strpos($header_lower, 'partial') !== false) continue;
                if (strpos($header_lower, 'unverified') !== false) continue;
                if (strpos($header_lower, 'low quality') !== false) continue;
                if (strpos($header_lower, 'predicted') !== false) continue;

                if ($row['min_length'] !== null && $sequence_length < (int)$row['min_length']) continue;
                if ($row['max_length'] !== null && $sequence_length > (int)$row['max_length']) continue;

                $filtered_records[] = [$header, $sequence];
            }
            // If a max sequence count is set, enforce this.
            // Adapted from (PHP manual array_slice()). 
            if ($row['max_sequences'] !== null) {
                $filtered_records = array_slice($filtered_records, 0, (int)$row['max_sequences']);
            }

            $filtered_count = count($filtered_records);
            $records = $filtered_records;

            $fasta_content = '';

            foreach ($records as [$header, $sequence]) {
                $fasta_content .= '>' . $header . PHP_EOL;
                $fasta_content .= $sequence . PHP_EOL;
            }
            // Save filtered FASTA for downstream analysis. 
            // Adapted from (PHP manual, file_put_contents()).
            file_put_contents($output_fasta, $fasta_content);
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
    <title>Analysis Results</title>
    <link rel='stylesheet' href='style.css'>
    <!-- Link to shared stylesheet -->
</head>
<body>

<?php include 'menu.php'; ?>

<h1>Analysis Results</h1>

<?php if ($row): ?>

<p><strong>Protein family:</strong> <?php echo htmlspecialchars($row['protein_family']); ?></p>

<p><strong>Taxonomic group:</strong> <?php echo htmlspecialchars($row['taxonomic_group']); ?></p>

<p><strong>Use example dataset?</strong> <?php echo (int)$row['use_example'] === 1 ? 'Yes' : 'No'; ?></p>

<p><strong>Maximum number of sequences:</strong>
    <?php echo $row['max_sequences'] !== null ? htmlspecialchars($row['max_sequences']) : 'Not set'; ?>
</p>

<p><strong>Minimum sequence length:</strong>
    <?php echo $row['min_length'] !== null ? htmlspecialchars($row['min_length']) : 'Not set'; ?>
</p>

<p><strong>Maximum sequence length:</strong>
    <?php echo $row['max_length'] !== null ? htmlspecialchars($row['max_length']) : 'Not set'; ?>
</p>

<p><strong>Status:</strong> <?php echo htmlspecialchars($message); ?></p>
<p><strong>Raw FASTA file:</strong> <?php echo htmlspecialchars($raw_fasta); ?></p>
<p><strong>Filtered FASTA file:</strong> <?php echo htmlspecialchars($output_fasta); ?></p>
<p><strong>Raw sequences:</strong> <?php echo htmlspecialchars((string)$raw_records); ?></p>
<p><strong>Filtered sequences:</strong> <?php echo htmlspecialchars((string)$filtered_count); ?></p>
<p><strong>Sequences removed:</strong> <?php echo htmlspecialchars((string)($raw_records - $filtered_count)); ?></p>

<h2>Retrieved Sequences</h2>

<p><strong>Please select a Protein Name to recieve molecular identity information</strong></p>

<?php if (!empty($records)): ?>
    <!-- Adapted from (W3Schools, HTML Tables). -->
    <table border='1' cellpadding='6' cellspacing='0'>
        <tr>
            <th>Accession</th>
            <th>Protein Name</th>
            <th>Organism</th>
            <th>Sequence Length</th>
        </tr>

        <?php foreach ($records as $i => [$header, $sequence]): ?>
              <?php
              // Parse FASTA header into accession, protein name, and organism using regex -->
              // Adapted from (PHP manual preg_match()), (NCBI FASTA Format, GenBank, NCBI), (Regex101).
              preg_match('/^(\S+)\s+(.*)\s+\[([^\]]+)\]$/', $header, $matches);

              $accession = $matches[1] ?? 'Unknown';
              $protein_name = $matches[2] ?? $header;
              $organism = $matches[3] ?? 'Unknown';
              $sequence_length = strlen($sequence);
              ?>
              <tr>
                  <td><?php echo htmlspecialchars($accession); ?></td>
                  <td>
                  <!-- Link protein name to the sequence detail card -->
                  <a href='seq_card.php?analysis_id=<?php echo urlencode($analysis_id); ?>&seq=<?php echo urlencode($i); ?>'>
        <?php echo htmlspecialchars($protein_name); ?>
    </a>
</td>
                  <td><?php echo htmlspecialchars($organism); ?></td>
                  <td><?php echo htmlspecialchars((string)$sequence_length); ?></td>
              </tr>
<?php endforeach; ?>
    </table>
<?php else: ?>
    <p>No FASTA records found.</p>
<?php endif; ?>

<?php else: ?>
    <p><?php echo htmlspecialchars($message); ?></p>
<?php endif; ?>

</body>
</html>