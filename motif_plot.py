# Import required libraries for plotting and reading csv files.
import sys
import csv
import matplotlib.pyplot as plt
# Read in arguments
# Adapted from (Python sys.argv documentation.
input_file = sys.argv[1]
output_file = sys.argv[2]
# Store motif names and counts.
motifs = []
counts = []
# Read CSV files with motif frequency data, expecting motif_name and count.
# Adapted from (csv, Python 3).
with open(input_file, newline='') as f:
    reader = csv.reader(f)
    for row in reader:
        if len(row) >= 2:
            motifs.append(row[0])
            counts.append(int(row[1]))
# If no motif data is found exit
# Adapted from (sys, Python3).
if not motifs:
    print('No motif data found.')
    sys.exit(1)
# Create bar chart
# Adapted from (matplotlib.pyplot matplotlib)
plt.figure(figsize=(8, 5))
plt.bar(motifs, counts)
plt.xlabel('Motif')
plt.ylabel('Count')
plt.title('Motif Frequency')
plt.xticks(rotation=45, ha='right')
plt.tight_layout()
plt.savefig(output_file, dpi=200)
plt.close()