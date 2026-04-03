<!DOCTYPE html>
<html>
<head>
    <title>About This Website</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

<?php include 'menu.php'; ?>

<h1>About</h1>

<p> This website allows users to retrieve and analyse protein sequences across protein families and taxonomic groups. The analysis results should provide information about protein conservation, functional domains, and structural properties.
</p>
<h2> Sequence Retrieval </h2>
<p>
Protein sequences are retrieved from the NCBI protein database, using <strong>Entrez Direct</strong>. This allows for the comparison of homologous proteins across species and provides evolutionary information sch as conserved sequences.
<p>

<h2> Sequence Filtering </h2>
<p>
Next, sequences are filtered automatically to remove partial and low-quality entries. Length filtering can also be applied by the user. 

This filtering functionality improves the reliability of analyses performed because it is less likely that data includes poorly annotated sequences.
</p>

<h2> Multiple Sequence Alignment </h2>

<p>
<strong>Clustal Omega</strong> is used to to align sequences and produce a multiple sequence alignment (MSA).

MSA identifies conserved residues across species and can highlight critical functional domains. Additionally, variable regions can indicate species-specific adaptations (Zhang et al., 2024)

Additionally, a conservation plot is generated using <strong>EMBOSS plotcon</strong>  to visualise conserved regions.
</p>

<h2> Pairwise Identity Analysis </h2>

<p>
Pairwise sequence identity is calculated between all sequences and visualised as a heatmap. This allows for the user to visualise similarities between proteins, where high identity suggests conserved function. Clusters of high similarity can also indicate protein subfamilies (Mullan, 2006). However, it should be noted that as identity is calculated after alignment, it's accuracy depends on the quality of the MSA. Highly divergent regions will reduce the biological significance of these results.
</p>


<h2> Motif Analysis </h2>
<p>
The <strong>PROSITE</strong> database is also used, in order to scan protein sequences for known motifs. Motifs are residues with particular features that can provide information about a protein's structure or function, and include protein hallmarks such as transmembrane regions and phosphorylation (Savojardo et al., 2023).
</p>

<h2> Protein Properties </h2>
<p>
Individual protein sequences are also analysed using <strong>EMBOSS pepstats and pepwindow</strong> to calculate properties such as molecular weights, isoelectric points and net charge. These qualities are vital to the characteristics of the protein, reflecting structural characteristics and protein interactions. 

Additionally, a hydropathy plot is generated in order for the user to assess hydrophobic and hydrophilic regions.
</p>


<h2> Help! </h2>
<p>
To provide more context about analysis retrieval, protein structures are retrieved by comparing input sequences against known structures in the Protein Data Bank (PDB) using a sequence similarity search. I specified a sequence identity threshold of 50%, meaning that at least half of the aligned amino acids must be identical between the query and matched structure. I chose this identity cut-off as it is commonly used in bioinformatics to identify homologous structures.

<p>
Pairwise sequence identity analysis is performed on the MSA dataset, calculated using only alignment positions where there are no sequence gaps. This ensures matches take place across directly comparable residues. A matrix stores the values where each cell is a percentage identity between a pair of sequences. Diagonal entries show 100%, as each sequence is being compared to itself. In order to avoid double counting duplicates, summary statistics were calculated from the upper triangle of the matrix only. High similarity and low similarity pairs were grouped. 
</p>
<h2> References </h2>
<p>
<ul>
<li>Addou, Sarah, et al. 'Domain-Based and Family-Specific Sequence Identity Thresholds Increase the Levels of Reliable Protein Function Transfer.' Journal of Molecular Biology, vol. 387, no. 2, 25 Dec. 2008, pp. 416–430, www.sciencedirect.com/science/article/abs/pii/S0022283608015660, https://doi.org/10.1016/j.jmb.2008.12.045.

<li>Castrense Savojardo, et al. 'Finding Functional Motifs in Protein Sequences with Deep Learning and Natural Language Models.' Current Opinion in Structural Biology, vol. 81, 1 Aug. 2023, pp. 102641-102641, https://doi.org/10.1016/j.sbi.2023.102641.

<li>Mullan, Lisa. 'Pairwise Sequence Alignment-It's All about Us!' Briefings in Bioinformatics, vol. 7, no. 1, 1 Mar. 2006, pp. 113-115, https://doi.org/10.1093/bib/bbk008.


<li>Zhang, Chenyue, et al. 'The Historical Evolution and Significance of Multiple Sequence Alignment in Molecular Structure and Function Prediction.' Biomolecules, vol. 14, no. 12, 29 Nov. 2024, pp. 1531-1531, pmc.ncbi.nlm.nih.gov/articles/PMC11673352/, https://doi.org/10.3390/biom14121531. 
</ul>
</p>

</body>
</html>