<?php
// load dependencies
require "/app/vendor/autoload.php";
// load PDF class
require_once __DIR__."/classes/plotpdf.php";

// defaults
$filename = "deck.pdf";
$size = "A6";
$mode = "FrontAndBack";

// overwrite defaults with command-line parameters
if (isset($argv) && count($argv) >= 2) {
  $filename = $argv[1];
}
if (isset($argv) && count($argv) >= 3) {
  $size = $argv[2];
}
if (isset($argv) && count($argv) >= 4) {
  $mode = $argv[3];
}

$outputFile = realpath(__DIR__."/../output")."/".$filename;

echo "Going to generate ".$outputFile."\n";

$plotPdf = new PlotPDF($size);
$plotPdf->setPrintMode($mode);
$plotPdf->generatePDFFromJsonFile(realpath(__DIR__."/../deck.json"));
$plotPdf->writeContents($outputFile);
