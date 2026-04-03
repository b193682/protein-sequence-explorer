<!-- Navigation bar containing links to other pages. -->
<!-- All code adapted from https://www.w3schools.com/howto/howto_js_dropdown.asp. -->
<div class='navbar'>
<!-- Navigation links. -->
  <a href='home.php'>Home</a>
  <a href='about.php'>About</a>
  <a href='help.php'>Help</a>
  <a href='credits.php'>Statement of Credits</a>
  <a href='all_analyses.php'>Revisit Analysis</a>

<!-- Displays dropdown if an analysis_id exists. -->
<!-- Adapted from (PHP manual isset, is_numeric). -->
<?php if (isset($analysis_id) && is_numeric($analysis_id)): ?>
  <div class='dropdown'>
    <!-- Button toggles drop down visibility. -->
    <button class='dropbtn' onclick='toggleDropdown()'>
      Analysis
    </button>
    <!-- Dropdown content. -->
    <div class='dropdown-content" id="analysisDropdown'>
      <!-- Adapted from (PHP manual, urlencode). -->
      <a href='results.php?analysis_id=<?php echo urlencode((string)$analysis_id); ?>'>Sequences</a>
      <a href='pdb_structures.php?analysis_id=<?php echo urlencode((string)$analysis_id); ?>'>Protein Structures (PDB)</a>
      <a href='alignment.php?analysis_id=<?php echo urlencode((string)$analysis_id); ?>'>Alignment</a>
      <a href='add_analyses.php?analysis_id=<?php echo urlencode((string)$analysis_id); ?>'>Pairwise Identity</a>
      <a href='motifs.php?analysis_id=<?php echo urlencode((string)$analysis_id); ?>'>Motif Analysis</a>
    </div>
  </div>
<?php endif; ?>

</div>

<script>
// Function to toggle dropdown visibility.
function toggleDropdown() {
  document.getElementById('analysisDropdown').classList.toggle('show');
}
// Close dropdown when clicking outside the button.
window.onclick = function(e) {
  if (!e.target.matches('.dropbtn')) {
    var dropdown = document.getElementById('analysisDropdown');
    // If dropdown is visible, hide it.
    if (dropdown.classList.contains('show')) {
      dropdown.classList.remove('show');
    }
  }
}
</script>
