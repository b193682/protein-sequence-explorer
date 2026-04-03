<?php
session_start();
// Load database connection and login information.
require_once 'db_connect.php';
require_once 'login.php';
// Define empty error message to be filled if validation fails.
//
$errormsg = '';
// Proceeds if Protein family and taxonomic group have been submitted.
// Database communication code adapted from (PHP manual, PDO::prepare) and (phpdelusions.net/pdo_examples).
//Adapted from (PHP manual, is_numeric, if).
if(isset($_POST['protein_family']) &&
   isset($_POST['taxonomic_group'])) {
   // Saves values into session
   // Adapted from (PHP manual, session_start).
   $_SESSION['protein_family'] = trim($_POST['protein_family']);
   $_SESSION['taxonomic_group'] = trim($_POST['taxonomic_group']);
   $_SESSION['use_example'] = isset($_POST['use_example']) ? 1 : 0;
   
   $_SESSION['max_sequences'] = isset($_POST['max_sequences']) ? trim($_POST['max_sequences']) : '';
   $_SESSION['min_length'] = isset($_POST['min_length']) ? trim($_POST['min_length']) : '';
   $_SESSION['max_length'] = isset($_POST['max_length']) ? trim($_POST['max_length']) : '';
   // Stored session values as variables to simplify further validation.
   $protein_family = $_SESSION['protein_family'];
   $taxonomic_group = $_SESSION['taxonomic_group'];
   $use_example = $_SESSION['use_example'];
   $max_sequences = $_SESSION['max_sequences'];
   $min_length = $_SESSION['min_length'];
   $max_length = $_SESSION['max_length'];
   // Additional server side validation.
   // Adapted from (Validate Integer in Form Input Using Is_numeric, StackOverflow, 2013).
   if (empty($protein_family)) {
        $errormsg = 'Protein family is required.';
   } elseif (empty($taxonomic_group)) {
       $errormsg = 'Taxonomic group is required.';
   } elseif (!empty($max_sequences) && (!is_numeric($max_sequences) or (int)$max_sequences <= 0)) {
       $errormsg = 'Maximum number of sequences must be a positive number';
   } elseif (!empty($min_length) && (!is_numeric($min_length) or (int)$min_length <= 0)) {
        $errormsg = 'Minimum sequence length must be a positive number.';
   } elseif (!empty($max_length) && (!is_numeric($max_length) or (int)$max_length <= 0)) {
        $errormsg = 'Maximum sequence length must be a positive number.';
   } elseif (!empty($min_length) && !empty($max_length) && (int)$min_length > (int)$max_length) {
        $errormsg = 'Minimum sequence length cannot be greater than maximum sequence length.';
   }  
   
   
   // Analysis request inserted into database.
   // Adapted from (PHP manual PDO:prepare) and (phpdelusions.net INSERT query using PDO).
   if ($errormsg === '') {
       $stmt = $pdo->prepare('
           INSERT INTO analyses
           (protein_family, taxonomic_group, use_example, max_sequences, min_length, max_length)
           VALUES (?, ?, ?, ?, ?, ?)
       ');
       // Adapted from (phpdelusions.net/pdo_examples).
       $stmt->execute([
           $protein_family,
           $taxonomic_group,
           $use_example,
           $max_sequences !== '' ? (int)$max_sequences : null,
           $min_length !== '' ? (int)$min_length : null,
           $max_length !== '' ? (int)$max_length : null
       ]);
       // Retrieve the analysis ID generated to redirect user to relevant results page.
       // Adapted from (PHP manual PDO::lastInsertId()) .
       $analysis_id = $pdo->lastInsertId();
       // User redirected to the results page or sent back to form if no POST submission
       // Adapted from (PHP manual header()).
       header('Location: results.php?analysis_id=' . $analysis_id);
       exit();
   }
       
} else {
    header('Location: complib.php');
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Analysis Request Complete</title>
    <!-- Link to shared stylesheet -->
    <link rel='stylesheet' href='style.css'>
</head>
<body>

<?php include 'menu.php'; ?>

<h1>Analysis Request Complete</h1>

<?php if ($errormsg !== ''): ?>
    <!-- Display error in html --> 
    <!-- Adapted from (PHP manual htmlspecialchars) -->
    <p><?php echo htmlspecialchars($errormsg); ?></p>
    <!-- Link back to form -->
    <p><a href='complib.php'>Back</a></p>
<?php endif; ?>

</body>
</html>


