import os
# Set a writable directory to avoid permission errors.
# Adapted from (MPLCONFIGDIR Error in Matplotlib, StackOverflow, 2011).
os.environ["MPLCONFIGDIR"] = "/localdisk/home/s2223599/tmp_mpl"
# Import required libraries for reading sequence alignments, numerical operations, plotting heatmaps and tabular data.
from Bio import AlignIO
import numpy as np
import matplotlib.pyplot as plt
import sys
import pandas as pd
# Read in arguments which are defined in PHP file.
# Adapted from (Python sys.argv documentation).
alignment_file = sys.argv[1]
matrix_file = sys.argv[2]
heatmap_file = sys.argv[3]

# Summary file created based on matrix.
summary_file = matrix_file.replace(".tsv", "_summary.tsv")


# MSA read in FASTA format.
# Adapted from (Biopython AlignIO documentation).
alignment = AlignIO.read(alignment_file, "fasta")

# Extract identifiers.
names = [record.id for record in alignment]
seqs = [str(record.seq) for record in alignment]
# No. sequences.
n = len(seqs)

# Calculates percent identity between a pair of sequences.
def percent_identity(seq1, seq2):
    matches = 0
    compared = 0

    for a, b in zip(seq1, seq2):
        if a != "-" and b != "-":
            compared += 1
            if a == b:
                matches += 1

    return (matches / compared) * 100 if compared > 0 else 0.0


# Create empty matrix.
# Adapted from (numpy.zeros, NumPy).
matrix = np.zeros((n, n), dtype=float)
# Fill matrix with pairwose identity scores
for i in range(n):
    for j in range(n):
        matrix[i, j] = percent_identity(seqs[i], seqs[j])

# Roundt.
matrix = np.round(matrix, 2)

# Convert matrix to pandas dataframe with labels
# Adapted from (pandas.DataFrame, Pandas)
df = pd.DataFrame(matrix, index=names, columns=names)
df.insert(0, "Accession", df.index)
# Save matrix as CSV
# Adapted from (Pandas, pandas.DataFrame.to_csv
df.to_csv(matrix_file, sep="\t", index=False, float_format="%.2f")

# Summary stats from the upper triangle only
upper_values = []
identical_pairs = 0
high_similarity_pairs = 0
low_similarity_pairs = 0

for i in range(n):
    for j in range(i + 1, n):
        value = matrix[i, j]
        upper_values.append(value)
        # Count identical sequences
        if value == 100.0:
            identical_pairs += 1
        # Count high similarity pairs
        if 70.0 <= value < 100.0:
            high_similarity_pairs += 1
        # Count low similarity pairs
        if 0.0 < value < 20.0:
            low_similarity_pairs += 1
# Compute statistical summaries
# Adapted from (Statistics, numpy)
if upper_values:
    mean_identity = round(float(np.mean(upper_values)), 2)
    median_identity = round(float(np.median(upper_values)), 2)
    max_identity = round(float(np.max(upper_values)), 2)
    min_identity = round(float(np.min(upper_values)), 2)
else:
    mean_identity = 0.0
    median_identity = 0.0
    max_identity = 0.0
    min_identity = 0.0
# Store in DataFrame
summary_df = pd.DataFrame([
    ["num_sequences", n],
    ["mean_identity", mean_identity],
    ["median_identity", median_identity],
    ["max_identity", max_identity],
    ["min_identity", min_identity],
    ["identical_pairs", identical_pairs],
    ["high_similarity_pairs", high_similarity_pairs],
    ["low_similarity_pairs", low_similarity_pairs],
], columns=["metric", "value"])
# Save statistics as TSV
summary_df.to_csv(summary_file, sep="\t", index=False)

# Heatmap
# Adapted from (Annotated heatmap, matplotlib) and (Heatmaps in Python, plotly)
fig, ax = plt.subplots(figsize=(12, 10))

im = ax.imshow(matrix)

ax.set_xticks(range(len(names)))
ax.set_xticklabels(names, rotation=90, fontsize=6)

ax.set_yticks(range(len(names)))
ax.set_yticklabels(names, fontsize=6)

cbar = fig.colorbar(im, ax=ax)
cbar.set_label("Percent identity")

ax.set_title("Pairwise Identity Heatmap")

fig.tight_layout()
fig.savefig(heatmap_file, dpi=300)
plt.close(fig)