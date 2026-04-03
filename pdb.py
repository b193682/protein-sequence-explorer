# Import required libraries.
import sys
import json
import requests
from Bio import SeqIO
# RCSB PDB search API endpoint used to query protein structures by sequence.
# https://search.rcsb.org/
SEARCH_URL = 'https://search.rcsb.org/rcsbsearch/v2/query'
# Search PDB for a sequnce match.
def search_pdb(sequence):
    # Construct a JSON query.
    # Adapted from (retrieveing information from the PDB using the Web API, MoISSI Education).
    query = {
        'query': {
            'type': 'terminal',
            'service': 'sequence',
            'parameters': {
                'evalue_cutoff': 1,
                'identity_cutoff': 0.5,
                'target': 'pdb_protein_sequence',
                'value': sequence
            }
        },
        'return_type': 'entry',
        'request_options': {
            'paginate': {
                'start': 0,
                'rows': 5
            }
        }
    }

    try:
        # Send POST request to PDB API with JSON query.
        # Adapted from (https://docs.python-requests.org/)
        response = requests.post(SEARCH_URL, json=query, timeout=30)
        response.raise_for_status()
        # Parse JSON response
        # Adapted from (json, Python3).
        data = response.json()
        # Check results were returned.
        if "result_set" in data and len(data["result_set"]) > 0:
        # Extract matching PDB ID.
            pdb_id = data["result_set"][0]["identifier"]
            # Return result and link to PDB entry
            return {
                'match_status': 'found',
                'pdb_id': pdb_id,
                'structure_url': f'https://www.rcsb.org/structure/{pdb_id}'
            }
    except Exception:
        pass
        
    return {
        'match_status': 'not_found',
        'pdb_id': None,
        'structure_url': None
    }

def main():
    # Read FASTA file path.
    # Adapted from (sys, Python3).
    fasta_file = sys.argv[1]
    results = []
    # Parse PASTA file.
    #Adapted from (SeqIO, Biopython).
    for record in SeqIO.parse(fasta_file, 'fasta'):
        hit = search_pdb(str(record.seq))
        results.append({
            'sequence_accession': record.id,
            'pdb_id': hit['pdb_id'],
            'structure_url': hit['structure_url'],
            'match_status': hit['match_status']
        })
    # Output results as JSON for PHP file.
    print(json.dumps(results))
# Python entry point check.
# Adapted from (__main__, Python3).
if __name__ == '__main__':
    main()