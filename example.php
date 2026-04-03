<?php
// Defines the analysis ID for the example dataset.
$example_analysis_id = 73;
?>
<!DOCTYPE html>
<html>
<head>
    <title>Example Dataset</title>
    <!-- Link to shared stylesheet -->
    <link rel='stylesheet' href='style.css'>
</head>
<body>

<?php include 'menu.php'; ?>

<h1>Example Dataset</h1>

<p>
This example dataset demonstrates the website analysis functionality using the protein family
<strong>glucose-6-phosphatase</strong> within the taxonomic group <strong>Aves</strong>.
</p>

<p>
Use the links below to explore the available outputs for this example analysis:
</p>
<!-- Output cards act as a clickable element using JS navigation -->
<!-- Adapted from (How to CSS Cards, W3Schools) and (Window:location, MDN Web Docs). -->
<div class="output-card" onclick="window.location='results.php?analysis_id=<?php echo $example_analysis_id; ?>'">
    <h2>Sequences</h2>
    <p>View the retrieved and filtered protein sequences.</p>
</div>

<div class="output-card" onclick="window.location='pdb_structures.php?analysis_id=<?php echo $example_analysis_id; ?>'">
    <h2>Protein Structures (PDB)</h2>
    <p>Explore available protein structure matches.</p>
</div>

<div class="output-card" onclick="window.location='alignment.php?analysis_id=<?php echo $example_analysis_id; ?>'">
    <h2>Alignment</h2>
    <p>View the multiple sequence alignment and conservation.</p>
</div>

<div class="output-card" onclick="window.location='pairwise.php?analysis_id=<?php echo $example_analysis_id; ?>'">
    <h2>Pairwise Identity</h2>
    <p>View sequence similarity statistics and heatmap.</p>
</div>

<div class="output-card" onclick="window.location='motifs.php?analysis_id=<?php echo $example_analysis_id; ?>'">
    <h2>Motif Analysis</h2>
    <p>Explore detected protein motifs and summaries.</p>
</div>
</body>
</html>