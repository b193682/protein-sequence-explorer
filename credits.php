<!DOCTYPE html>
<html>
<head>
    <title>Statement of Credits</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

<?php include 'menu.php'; ?>

<h1>Statement of Credits</h1>

<h2>General Acknowledgements</h2>
<p>
A large amount of my code is adapted from  IWDD course materials, including lecture content and directed learning PHP files. In addition to this, the style guides and interactive elements of my website were largely produced from code from W3Schools.com. For PHP code, phpdelusions.net and PHP manual were predominantly used. Specific citations are provided in code comments.
</p>
<h2> Data Sources and Packages</h2>
<p>
Protein sequencing data is retrieved from the NCBI database using Entrez Direct E-utilities. Protein structure data is obtained from the Protein Data Bank (PDB). Biopython is used for identity matrix calculations and querying the PDB. NumPy is used for identity matrix calculation. Pandas is used to create the identity matrix DataFrame, and Matplotlib plotting functionality is utilised to create the motif bar chart and percent identity heatmap.
</p>

<h2> References </h2>
<p>
ChatGPT 5.3 was used for debugging syntax errors in Python and PHP script. 
I also used it to streamline my EDirect query, so I could diagnose that efetch was slowing my requests after the esearch command. 
<p>
It supported in improving the structure of my pagination code, my Python code to calculate percent identity, and troubleshooting my API request to PDB when I was not recieveing results. It was also used to refine my existing code for styling the sequence cards I produced and the pairwise identity matrix page cards.
</p>
<p>
It provided guidance using the EMBOSS packages and Clustal Omega, specifically with improving the construction of my command line calls within PHP. It also supported integrating tools for generating conservation plots and extracting alignment statistics.
</p>
<p>
ChatGPT 5.3 also assisted in refining custom functions I made, with the assistance of referenced resources. This included functions to read and extract sequence records from FASTA files, parse EMBOSS output files and extract identifiers such as accessions, and to define my custom input box in home.php. It helped me to correct my regex used when parsing FASTA files. The suggested logic provided was adapted and reviewed before being incorporated into the final code.
</p>


<strong>These references can also be found in code comments;</strong>

<ul>
<li>'__main__ - Top-Level Code Environment - Python 3.10.4 Documentation.' Docs.python.org, docs.python.org/3/library/__main__.html.

<li>2daaa. 'MPLCONFIGDIR Error in Matplotlib.' Stack Overflow, 29 Apr. 2011, stackoverflow.com/questions/5833623/mplconfigdir-error-in-matplotlib.

<li>Akersh. 'PHP Preg_split() Not Capturing the Split in the String.' Stack Overflow, 25 Jan. 2012, stackoverflow.com/questions/9011240/php-preg-split-not-capturing-the-split-in-the-string.

<li>Amandasaurus. 'What's the Difference between Escapeshellarg and Escapeshellcmd?' Stack Overflow, 10 Dec. 2009, stackoverflow.com/questions/1881582/whats-the-difference-between-escapeshellarg-and-escapeshellcmd.

<li>Ash. 'Can I Filter an $_GET Array in a Foreach Loop.' Stack Overflow, 3 Feb. 2014, stackoverflow.com/questions/21528961/can-i-filter-an-get-array-in-a-foreach-loop.

<li>Bret. 'How to Escape URL-Parameter to Prevent HTML Injection.' Stack Overflow, 5 Jan. 2018, stackoverflow.com/questions/48121608/how-to-escape-url-parameter-to-prevent-html-injection.

<li>brilliant. 'How to Write into a File in PHP?' Stack Overflow, 20 Nov. 2009, stackoverflow.com/questions/1768894/how-to-write-into-a-file-in-php.

<li>Cock P.J.A. et al. (2009). Biopython: freely available Python tools for computational molecular biology and bioinformatics. Bioinformatics, 25(11), 1422-1423.

<li>'Document.getElementById().' MDN Web Docs, developer.mozilla.org/en-US/docs/Web/API/Document/getElementById.

<li>'EMBOSS: Patmatmotifs Manual.' Bioinformatics.nl, 2018, emboss.bioinformatics.nl/cgi-bin/emboss/help/patmatmotifs.

<li>'EMBOSS: Pepstats Manual.' Bioinformatics.nl, 2026, www.bioinformatics.nl/cgi-bin/emboss/help/pepstats. 

<li>'EMBOSS: Plotcon Manual.' Bioinformatics.nl, 2026, www.bioinformatics.nl/cgi-bin/emboss/help/plotcon.

<li>'FASTA Format for Nucleotide Sequences.' Www.ncbi.nlm.nih.gov, www.ncbi.nlm.nih.gov/genbank/fastaformat/.

<li>'File:Protein Dynamics Cytochrome c 2NEW Small.gif - Wikimedia Commons.' Wikimedia.org, 2022, commons.wikimedia.org/wiki/File:Protein_Dynamics_Cytochrome_C_2NEW_small.gif. 

<li>'Form Onsubmit='Return Validate()' Issue.' Stack Overflow, 21 Jan. 2014, stackoverflow.com/questions/21270786/form-onsubmit-return-validate-issue.

<li>GeeksforGeeks. 'PHP Form Validation.' GeeksforGeeks, Apr. 2024, www.geeksforgeeks.org/php/php-form-validation/. 

<li>'PHP Pagination | Set 3.' GeeksforGeeks, 22 Mar. 2018, www.geeksforgeeks.org/php/php-pagination-set-3/. 

<li>H, Nathan. 'How to Apply BindValue Method in LIMIT Clause?' Stack Overflow, 16 Feb. 2010, stackoverflow.com/questions/2269840/how-to-apply-bindvalue-method-in-limit-clause.

<li>Harris C.R. et al. (2020). Array programming with NumPy. Nature, 585, 357-362.

<li>Harry. 'Forcing a Change in the Value of the Hidden Field before Submitting a Form.' Stack Overflow, 30 June 2016, stackoverflow.com/questions/38130297/forcing-a-change-in-the-value-of-the-hidden-field-before-submitting-a-form.

<li>Hassan. 'How to Check If a Row Exist in the Database Using PDO?' Stack Overflow, 15 Aug. 2012, stackoverflow.com/questions/11974613/how-to-check-if-a-row-exist-in-the-database-using-pdo.

<li>'How to Toggle between Hiding and Showing an Element.' Www.w3schools.com, www.w3schools.com/howto/howto_js_toggle_hide_show.asp.

<li>Hunter J.D. (2007). Matplotlib: A 2D graphics environment. Computing in Science & Engineering, 9(3), 90-95.

<li>'INSERT Query Using PDO.' Treating PHP Delusions, 2026, phpdelusions.net/pdo_examples/insert. 

<li>'Introduction to SeqIO Biopython.' Biopython.org, biopython.org/wiki/SeqIO.

<li>'Entrez Direct: E-Utilities on the UNIX Command Line.' Nih.gov, National Center for Biotechnology Information (US), 9 Dec. 2019, www.ncbi.nlm.nih.gov/books/NBK179288/.

<li>malik727. 'PDO MYSQL QUERY FORMATTING.' Stack Overflow, 2 Feb. 2019, stackoverflow.com/questions/54496320/pdo-mysql-query-formatting.

<li>Matplotlib. 'Creating Annotated Heatmaps - Matplotlib 3.5.2 Documentation.' Matplotlib.org, matplotlib.org/stable/gallery/images_contours_and_fields/image_annotated_heatmap.html.

<li>'Matplotlib.pyplot - Matplotlib 3.5.2 Documentation.' Matplotlib.org, matplotlib.org/stable/api/pyplot_summary.html.

<li>McKinney W. (2010). Data structures for statistical computing in Python. Proceedings of the 9th Python in Science Conference.

<li>Meng, Jiew. 'Calculating Item Offset for Pagination.' Stack Overflow, 19 Aug. 2010, stackoverflow.com/questions/3520996/calculating-item-offset-for-pagination.

<li>'Online Regex Tester and Debugger: PHP, PCRE, Python, Golang and JavaScript.' Regex101.com, regex101.com.

<li>oshirowanen. 'PHP PDO How to Fetch a Single Row?' Stack Overflow, 28 Mar. 2011, stackoverflow.com/questions/5456626/php-pdo-how-to-fetch-a-single-row.

<li>'Page View in Php.' Stack Overflow, 14 Feb. 2020, stackoverflow.com/questions/60229199/page-view-in-php.

<li>pandas. 'Pandas.DataFrame - Pandas 1.2.4 Documentation.' Pandas.pydata.org, 2023, pandas.pydata.org/docs/reference/api/pandas.DataFrame.html.

<li>'Pandas.DataFrame.to_csv - Pandas 1.2.4 Documentation.' Pandas.pydata.org, pandas.pydata.org/docs/reference/api/pandas.DataFrame.to_csv.html.

<li>petehallw. 'PHP Echo to Display Image HTML.' Stack Overflow, 26 Sept. 2014, stackoverflow.com/questions/26065495/php-echo-to-display-image-html.

<li>'Php - Go back to Previous Page.' Stack Overflow, stackoverflow.com/questions/2548566/go-back-to-previous-page.

<li>'PHP: Header - Manual.' Php.net, 2010, www.php.net/manual/en/function.header.php.

<li>'PHP: Fopen - Manual.' Php.net, 2025, www.php.net/manual/en/function.fopen.php.

<li>'PHP: Fputcsv - Manual.' Php.net, 2024, www.php.net/manual/en/function.fputcsv.php.

<li>'PHP: Mkdir - Manual.' Www.php.net, www.php.net/manual/en/function.mkdir.php.

<li>'PHP: NULL - Manual.' Www.php.net, www.php.net/manual/en/language.types.null.php.

<li>'PHP: PDO::Prepare - Manual.' Www.php.net, www.php.net/manual/en/pdo.prepare.php.

<li>'PHP: PDOStatement::Fetch - Manual.' Php.net, 2022, www.php.net/manual/en/pdostatement.fetch.php.

<li>'PHP: Session_start - Manual.' Php.net, 2019, www.php.net/manual/en/function.session-start.php.

<li>'PHP: Strings - Manual.' Www.php.net, www.php.net/manual/en/language.types.string.php.

<li>'PHP: Strlen - Manual.' Www.php.net, www.php.net/manual/en/function.strlen.php.

<li>'PHP: Strpos - Manual.' Www.php.net, www.php.net/manual/en/function.strpos.php.

<li>'PHP: Trim - Manual.' Www.php.net, www.php.net/manual/en/function.trim.php.

<li>'PHP: Urlencode - Manual.' Www.php.net, www.php.net/manual/en/function.urlencode.php.

<li>plotlygraphs. 'Heatmaps.' Plotly.com, 3 July 2019, plotly.com/python/heatmaps/.

<li>proyb2. 'PDO LIMIT and OFFSET.' Stack Overflow, 1 Apr. 2011, stackoverflow.com/questions/5508993/pdo-limit-and-offset.

<li>Python. 'Sys - System-Specific Parameters and Functions - Python 3.7.3 Documentation.' Python.org, 2010, docs.python.org/3/library/sys.html.

<li>Python Software Foundation. 'Csv - CSV File Reading and Writing - Python 3.8.1 Documentation.' Python.org, 2020, docs.python.org/3/library/csv.html.

<li>'Json - JSON Encoder and Decoder - Python 3.8.3rc1 Documentation.' Docs.python.org, 2023, docs.python.org/3/library/json.html.

<li>'RCSB PDB Search API: Understanding and Using.' Rcsb.org, 2019, search.rcsb.org/.

<li>'Requests: HTTP for HumansTM - Requests 2.27.1 Documentation.' Docs.python-Requests.org, docs.python-requests.org/.

<li>Ross. 'Display Image Using Php.' Stack Overflow, 2024, stackoverflow.com/questions/13662996/display-image-using-php.

<li>Sievers, Fabian, and Desmond G. Higgins. 'Clustal Omega for Making Accurate Alignments of Many Protein Sequences.' Protein Science, vol. 27, no. 1, 30 Oct. 2017, pp. 135-145.

<li>'Statistics - NumPy V1.24 Manual.' Numpy.org, numpy.org/doc/stable/reference/routines.statistics.html.

<li>The PHP Group. 'PHP: Htmlspecialchars - Manual.' Php.net, 2019, www.php.net/manual/en/function.htmlspecialchars.php.

<li>user8392790. 'Parse a Fasta File Using PHP.' Stack Overflow, 4 Mar. 2019, stackoverflow.com/questions/54980654/parse-a-fasta-file-using-php.

<li>W3Schools. 'CSS Grid Layout.' W3schools.com, 2019, www.w3schools.com/css/css_grid.asp.

<li>'HTML Images.' W3schools.com, 2019, www.w3schools.com/html/html_images.asp.

<li>'HTML Tables.' W3schools.com, 2019, www.w3schools.com/html/html_tables.asp.

<li>'JavaScript Form Validation.' W3schools.com, 2019, www.w3schools.com/js/js_validation.asp.

<li>'PHP for Loops.' Www.w3schools.com, www.w3schools.com/php/php_looping_foreach.asp.

<li>'W3Schools.com.' W3schools.com, 2026, www.w3schools.com/howto/howto_css_cards.asp.

<li>'W3Schools.com.' W3schools.com, 2026, www.w3schools.com/php/func_math_ceil.asp.

<li>'W3Schools.com.' W3schools.com, 2026, www.w3schools.com/howto/howto_js_validation_empty_input.asp.

<li>'W3Schools.com.' W3schools.com, 2026, www.w3schools.com/howto/howto_custom_select.asp.

<li>'Window: Location Property - Web APIs | MDN.' Developer.mozilla.org, developer.mozilla.org/en-US/docs/Web/API/Window/location.

</ul>
</p>






