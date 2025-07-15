package main

import (
	"bufio"
	"encoding/csv"
	"flag"
	"io"
	"log"
	"os"
	"strings"
)

// indexOf finds the index of target in slice, or -1 if not found
func indexOf(slice []string, target string) int {
	for i, v := range slice {
		if v == target {
			return i
		}
	}
	return -1
}

func main() {
	// Parse command-line flags for input and output paths
	inputPath := flag.String("input", "/home/schmidt/Desktop/scrapJob/cmd/trimmed-adresses/adresser.csv", "Path to input CSV file")
	outputPath := flag.String("output", "/home/schmidt/Desktop/scrapJob/cmd/trimmed-adresses/adresser_deduped.csv", "Path to output CSV file")
	flag.Parse()

	// Open input file
	inFile, err := os.Open(*inputPath)
	if err != nil {
		log.Fatalf("failed to open input file: %v", err)
	}
	defer inFile.Close()

	// Create a buffered CSV reader
	reader := csv.NewReader(bufio.NewReaderSize(inFile, 1024*1024))
	reader.LazyQuotes = true  // allow unescaped quotes

	// Create output file
	outFile, err := os.Create(*outputPath)
	if err != nil {
		log.Fatalf("failed to create output file: %v", err)
	}
	defer outFile.Close()

	// Create a buffered CSV writer
	writer := csv.NewWriter(bufio.NewWriterSize(outFile, 1024*1024))
	defer writer.Flush()

	// Read header row
	header, err := reader.Read()
	if err != nil {
		log.Fatalf("failed to read header: %v", err)
	}

	// Columns to keep
	columns := []string{"vejnavn", "husnr", "postnr", "postnrnavn"}

	// Determine indices of the required columns
	indices := make([]int, 0, len(columns))
	for _, col := range columns {
		idx := indexOf(header, col)
		if idx == -1 {
			log.Fatalf("column %s not found in header", col)
		}
		indices = append(indices, idx)
	}

	// Write trimmed header
	if err := writer.Write(columns); err != nil {
		log.Fatalf("failed to write header: %v", err)
	}

	// Map to track seen records and skip duplicates
	seen := make(map[string]struct{})

	// Process each record one by one
	for {
		record, err := reader.Read()
		if err == io.EOF {
			break
		}
		if err != nil {
			log.Printf("warning: skipping line due to error: %v", err)
			continue
		}

		// Extract only the needed fields
		trimmed := make([]string, len(indices))
		for i, idx := range indices {
			trimmed[i] = record[idx]
		}

		// Dedupe by key composed of all fields
		key := strings.Join(trimmed, "|")
		if _, exists := seen[key]; exists {
			continue
		}
		seen[key] = struct{}{}

		// Write only unique record
		if err := writer.Write(trimmed); err != nil {
			log.Fatalf("failed to write record: %v", err)
		}
	}

	log.Println("Deduplication complete. Output written to", *outputPath)
}
