#!/bin/bash

# Default whitelist of valid HTTP status codes
VALID_CODES=(200 301 302)

# Function to print usage information
usage() {
  echo "Usage: $0 -f <file> [-w <whitelist>] [-o <output-file>] [-v] [-s]"
  echo "  -f <file>        File containing plain text with URLs"
  echo "  -w <whitelist>   Comma-separated list of valid HTTP status codes (optional)"
  echo "  -o <output-file> File to write the output in CSV format (optional)"
  echo "  -v               Verbose mode: print progress output to standard output"
  echo "  -s               Summary mode: output totals per HTTP status code"
  exit 1
}

# Parse command-line arguments
OUTPUT_FILE=""
VERBOSE=false
SUMMARY=false
while getopts ":f:w:o:vs" opt; do
  case $opt in
    f) INPUT_FILE="$OPTARG" ;;
    w) IFS=',' read -r -a VALID_CODES <<< "$OPTARG" ;;
    o) OUTPUT_FILE="$OPTARG" ;;
    v) VERBOSE=true ;;
    s) SUMMARY=true ;;
    *) usage ;;
  esac
done

# Ensure the input file is provided
if [[ -z "$INPUT_FILE" ]]; then
  echo "Error: Input file is required."
  usage
fi

# Ensure the input file exists
if [[ ! -f "$INPUT_FILE" ]]; then
  echo "Error: File '$INPUT_FILE' does not exist."
  exit 1
fi

# Extract URLs from the file using a regex pattern
extract_urls() {
  grep -Eo 'https?://[a-zA-Z0-9./?=_-]*' "$1" | sort | uniq
}

# Check URL status
check_url() {
  local url="$1"
  local status_code

  # Get the HTTP status code using curl with a modern browser User-Agent
  status_code=$(curl -A "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36" -o /dev/null -s -w "%{http_code}" "$url")

  # Check if the status code is in the whitelist
  local result="INVALID"
  for code in "${VALID_CODES[@]}"; do
    if [[ "$status_code" == "$code" ]]; then
      result="VALID"
      break
    fi
  done

  # Increment status code count
  STATUS_COUNTS[$status_code]=$((STATUS_COUNTS[$status_code]+1))

  echo "$url,$status_code,$result" >> "$TMP_OUTPUT"
  if [[ "$VERBOSE" == true ]]; then
    echo "$url - $result ($status_code)"
  fi

  # Capture 404 URLs for warning
  if [[ "$status_code" == "404" ]]; then
    echo "$url" >> "$TMP_404_OUTPUT"
  fi
}

# Process the input file
process_file() {
  local file="$1"
  local urls

  urls=$(extract_urls "$file")
  if [[ -z "$urls" ]]; then
    echo "No URLs found in the file."
    return
  fi

  echo "Checking URLs..."
  TMP_OUTPUT=$(mktemp)
  TMP_404_OUTPUT=$(mktemp)
  echo "URL,Status Code,Result" > "$TMP_OUTPUT"
  declare -A validated_urls
  declare -A STATUS_COUNTS

  while read -r url; do
    if [[ -z "${validated_urls[$url]}" ]]; then
      check_url "$url"
      validated_urls[$url]=1
    fi
  done <<< "$urls"

  if [[ -n "$OUTPUT_FILE" ]]; then
    mv "$TMP_OUTPUT" "$OUTPUT_FILE"
    echo "Output written to $OUTPUT_FILE"
  fi

  if [[ "$VERBOSE" == true || (-z "$OUTPUT_FILE" && "$SUMMARY" == false) ]]; then
    cat "$TMP_OUTPUT"
  fi

  # Check and warn if any 404s were found
  if [[ -s "$TMP_404_OUTPUT" ]]; then
    echo -e "\nWarning: The following URLs returned 404 status:\n"
    cat "$TMP_404_OUTPUT"
  fi

  rm "$TMP_OUTPUT" "$TMP_404_OUTPUT"

  # Print summary if -s is specified
  if [[ "$SUMMARY" == true ]]; then
    echo -e "\nSummary of HTTP Status Codes:"
    for status in "${!STATUS_COUNTS[@]}"; do
      echo "HTTP $status: ${STATUS_COUNTS[$status]} occurrences"
    done
  fi
}

# Run the script
process_file "$INPUT_FILE"

