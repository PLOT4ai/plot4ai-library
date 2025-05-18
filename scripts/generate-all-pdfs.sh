#!/bin/bash

# this script does two things:
# 1. the most important: it generates the plot4ai card deck as PDF in 4 different formats
# 2. optionally, it will validate all the urls in the json. plot4ai references a lot of valuable sources
#    and to keep them up to date, this check can be used if they still exist

# Check if 'validate-urls' is passed as an argument
VALIDATE_URLS=false

for arg in "$@"; do
    if [[ "$arg" == "validate-urls" ]]; then
        VALIDATE_URLS=true
    fi
done

# Execute url_validator.sh if 'validate-urls' is passed
if [[ "$VALIDATE_URLS" == true ]]; then
    echo "Running url validation..."
    bash scripts/url_validator.sh -f deck.json -s
fi

# Validate deck.json
if jq empty deck.json; then
  #php scripts/generate-qr.php
  php scripts/generate-pdf.php plot4ai-A4.pdf A4 FrontAndBack
  #php scripts/generate-pdf.php plot4ai-A6.pdf A6 FrontAndBack
  #php scripts/generate-pdf.php plot4ai-A6-frontsides.pdf A6 Fronts
  #php scripts/generate-pdf.php plot4ai-A6-backsides.pdf A6 Backs
  echo "Done"
else
  echo "Error: deck.json contains invalid JSON; please check the contents."
  exit 1
fi
