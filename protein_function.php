/* Retrieve protein sequences from NCBI in FASTA format using Entrez Direct Tools. */
function fetch_ncbi_protein_fasta(string $protein_family, string $taxonomic_group, string $output_file): bool
{
/* Construct an NCBI Entrez query with protein name and organism. */
/* Adapted from (Entrez Direct, Kans, NCBI, 2025). */
    $query = sprintf(
        '%s[Protein Name] AND %s[Organism]',
        $protein_family,
        $taxonomic_group
    );
    /* Build a shell command using Entrez Direct. */
    /* Adapted from (PHP manual, escapeshellarg(), shell_exec()) and (Entrez Direct, Kans, NCBI, 2025). */
    $command =
        'esearch -db protein -query ' . escapeshellarg($query) .
        ' | efetch -format fasta > ' . escapeshellarg($output_file) . ' 2>/dev/null';

    shell_exec($command);
    /* Verify that the output file exists before returning results. */
    return file_exists($output_file) && filesize($output_file) > 0;
}

