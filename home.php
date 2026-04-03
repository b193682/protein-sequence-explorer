<?php
// Load database connection and login information.
session_start();
require_once 'login.php';
require_once 'db_connect.php';

//Define recent analyses array
$recent_analyses = [];
// Query five most recent analyses to be displayed on home page.
// Database communication code adapted from (PHP manual, PDO::prepare), (phpdelusions.net/pdo_examples) and (PDO MYSQL QUERY FORMATTING, StackOverflow, 2019).
try {
    $stmt = $pdo->query('
        SELECT analysis_id, protein_family, taxonomic_group, created_at
        FROM analyses
        ORDER BY created_at DESC
        LIMIT 5
    ');
    // Fetch returned rows into an array to display in table.
    $recent_analyses = $stmt->fetchAll();
} catch (PDOException $e) {
    // Show no recent analyses if database query fails
    $recent_analyses = [];
}
?>
<!DOCTYPE html>
<html>
<head>
   <title>Protein Sequence Explorer Website</title>
   <!-- Link to shared stylesheet -->
   <link rel='stylesheet' href='style.css'>
</head>

<body>

<?php include 'menu.php'; ?>

<h1>Protein Sequence Explorer Website</h1>
<!-- Protein GIF displayed on home page -->
<!-- Adapted from (HTML Images, W3Schools) -->
<img src='Protein_Dynamics_Cytochrome_C_2NEW_small.gif' alt='Protein animation' style='width:80px;height:80px;'>
<p style='font-size: 0.8rem;'>
Image obtained from (Richard Wheeler, Wikimedia Commons, 2022).
</p>

<script>
// Validation that numeric fields are not empty, min length is not larger than max length and that required fields are not blank. 
// Adapted from (How TO - JS Validation For Empty Fields, W3Schools)
function validate(form) {
   let fail = "";
   
   if(form.max_sequences.value != "" && isNaN(form.max_sequences.value)) {
     fail += "Please enter a Maximum number of sequences.\n";
   }
   
   if (form.min_length.value != "" && isNaN(form.min_length.value)) {
     fail += "Please enter a Minimum sequence length.\n";
   }
   
   if (form.max_length.value != "" && isNaN(form.max_length.value)) {
     fail += "Please enter a Maximum sequence length.\n";
   }
   
   if (form.min_length.value != "" && form.max_length.value !="") {
     if (parseInt(form.min_length.value) > parseInt(form.max_length.value)) {
         fail += "Minimum sequence length cannot be greater than maximum sequence length"
     }
   }
   
   if(form.protein_family.value == "") {
   fail += "Please enter a Protein Name ";
   }
   
   if (form.taxonomic_group.value == "") {
   fail += "Please enter a Taxonomic Group ";
   }
   // Submit form if there are no errors.
   // Adapted from (HTML Form Validation, W3Schools).
   if (fail == "") return true;
   alert(fail);
   return false;
}
// Show or hide numeric input based on user's selection. Custom input is visible, or predefined value is shown.
// Adapted from (How to Toggle Hide and Show, W3Schools),(Document: getElementById() method, MDN Web Docs) and (Forcing a Change in the Value of the Hidden Field before Submitting a Form, StackOverflow, 2016).
function toggleCustomInput(selectId, customId, hiddenId) {
    var select = document.getElementById(selectId);
    var customInput = document.getElementById(customId);
    var hiddenInput = document.getElementById(hiddenId);

    if (select.value === 'custom') {
        customInput.style.display = 'inline-block';
        hiddenInput.value = "";
    } else {
        customInput.style.display = 'none';
        customInput.value = "";
        hiddenInput.value = select.value;
    }
}

function updateHiddenInputs() {
    copyValue('max_sequences_choice', 'max_sequences_custom', 'max_sequences');
    copyValue('min_length_choice', 'min_length_custom', 'min_length');
    copyValue('max_length_choice', 'max_length_custom', 'max_length');
}

function copyValue(selectId, customId, hiddenId) {
    var select = document.getElementById(selectId);
    var customInput = document.getElementById(customId);
    var hiddenInput = document.getElementById(hiddenId);

    if (select.value === 'custom') {
        hiddenInput.value = customInput.value;
    } else {
        hiddenInput.value = select.value;
    }
}
</script>

<p>
<strong>Please enter a protein family and taxonomic group to return sequences.</strong>
</p>
<!-- Adapted from (Form Onsubmit="Return Validate()" Issue", StackOverflow, 2014). --> 
<form action='indexp.php' method='post' onsubmit='updateHiddenInputs(); return validate(this)'>
  
      <p>
          <label for='protein_family'>Protein Family:</label><br>
          <input type='text' id='protein_family' name='protein_family'>
      </p>
      
      <p>
          <label for='taxonomic_group'>Taxonomic Group:</label><br>
          <input type='text' id='taxonomic_group' name='taxonomic_group'>
      </p>

      <!-- Submit button that begins analysis request -->
    <input type='submit' value='Run Analysis' class='button button1'>
</p>
     
      <h2>Advanced Options</h2>
      
      <p>
          <label for='max_sequences_choice'>Maximum number of sequences:</label><br>
          <!-- Dropdown of predefined values and custom option -->
          <select id='max_sequences_choice' onchange='toggleCustomInput(this, 'max_sequences_custom')'>
              <option value='' selected disabled>-- Select --</option>
              <option value='custom'>Custom</option>
              <option value='20'>20</option>
              <option value='50' >50</option>
              <option value='100'>100</option>
              <option value='200'>200</option>
              
          </select>
          <!-- Custom numeric box -->
          <input type='number' id='max_sequences_custom' min='1' placeholder='Enter custom value' style='display:none;'>  <!-- Hidden field submitted with form -->
          <input type='hidden' id='max_sequences' name='max_sequences' value='50'>
      </p>

      <p>
          <label for='min_length_choice'>Minimum sequence length:</label><br>
          <!-- Dropdown of predefined values and custom option -->
          <select id='min_length_choice' onchange='toggleCustomInput(this, 'min_length_custom')'>
              <option value='' selected disabled>-- Select --</option>
              <option value='custom'>Custom</option>
              <option value=''>No minimum filter</option>
              <option value='50'>50</option>
              <option value='100'>100</option>
              <option value='200'>200</option>
              <option value='300'>300</option>
          </select>
          <!-- Custom numeric box -->
          <input type='number' id='min_length_custom' min='1' placeholder='Enter custom value' style='display:none;'>   <!-- Hidden field submitted with form -->
          <input type='hidden' id='min_length' name='min_length' value=''>
      </p>

<p>
    <label for='max_length_choice'>Maximum sequence length:</label><br>
    <!-- Dropdown of predefined values and custom option -->
    <select id='max_length_choice' onchange='toggleCustomInput(this, 'max_length_custom')'>
        <option value='' selected disabled>-- Select --</option>
        <option value='custom'>Custom</option>
        <option value=''>No maximum filter</option>
        <option value='300'>300</option>
        <option value='500'>500</option>
        <option value='1000'>1000</option>
        <option value='2000'>2000</option>
    </select>
    <!-- Custom numeric box -->
    <input type='number' id='max_length_custom' min='1' placeholder='Enter custom value' style='display:none;'>
    <!-- Hidden field submitted with form -->
    <input type='hidden' id='max_length' name='max_length' value=''>
</p>
<p>       <!-- Link to example dataset page -->
          <a href='example.php' class='button button2'>Use Example Dataset</a>
      </p>
<p>
      </form>
      
      <h2>Recent Analyses</h2>

<?php if (!empty($recent_analyses)): ?>
    <!-- Display the five most recent analyses in a summary table. -->
    <!-- Adapted from (W3Schools, HTML Tables). -->
    <table border='1' cellpadding='6' cellspacing='0'>
        <tr>
            <th>Date/Time</th>
            <th>Protein Name</th>
            <th>Taxonomic Group</th>
        </tr>

        <?php foreach ($recent_analyses as $analysis): ?>
            <tr>
                <td><!-- Link recent analysis to analysis page -->
                    <!-- Adapted from (PHP manual, urlencode, htmlspecialchars). -->
                    <a href='revisit_analyses.php?analysis_id=<?php echo urlencode((string)$analysis['analysis_id']); ?>'>
                        <?php
                        echo htmlspecialchars(
                            isset($analysis['created_at'])
                                ? $analysis['created_at']
                                : ('Analysis #' . $analysis['analysis_id'])
                        );
                        ?>
                    </a>
                </td>
                <td><?php echo htmlspecialchars($analysis['protein_family']); ?></td>
                <td><?php echo htmlspecialchars($analysis['taxonomic_group']); ?></td>
            </tr>
        <?php endforeach; ?>
    </table>

    <p> <!-- Link to page storing all previous analyses -->
        <a href='all_analyses.php'>View all analyses</a>
    </p>
<?php else: ?>
    <p>No previous analyses found.</p>
<?php endif; ?>
  </body>
  </html>

