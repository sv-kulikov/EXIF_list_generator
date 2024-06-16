<?php

namespace Sv\Photo\ExifStats;

require __DIR__ . '/' . 'class/autoload.php';

// Some setup: add or remove file extensions as needed
$extensions = ['jpg', 'jpeg', 'cr2', 'cr3'];
$ignoreJpegsInCaseOfRawPresence = true;
$csvDetailsFileName = 'elg.csv';
$csvStatsFileName = 'elg_stats.csv';
$txtStatsFileName = 'elg.txt';


// Check if the script is called with the correct number of arguments
if ($argc < 2) {
    echo "Usage: php elg.php DIR_NAME [MORE_DIR_NAMES ...]\n";
    exit(1);
}

// Prepare objects
$math = new Math();
$converter = new Converter();
$scanner = new Scanner();
$extractor = new Extractor();
$csvWriter = new CsvWriter();
$txtWriter = new TxtWriter();
$stats = new Stats();

// Get the arguments
if (!is_array($argv)) {
    $argv = [];
}
array_shift($argv);
$startDirectoryNames = $argv;

// Some info output
foreach ($startDirectoryNames as $startDirectoryName) {
    echo "Directory to scan: " . $startDirectoryName . "\n";
}
echo "CSV Details File Name: " . $csvDetailsFileName . "\n";
echo "CSV Stats File Name: " . $csvStatsFileName . "\n";
echo "TXT Stats File Name: " . $txtStatsFileName . "\n";
echo "\n";

// Check if "exiftool" is available (needed for lens name extraction)
if (file_exists(__DIR__ . '/utils/exiftool.exe')) {
    $exifToolIsAvailable = true;
    echo "Exiftool found. It will be used for lens name extraction and CR* processing in case of internal PHP Exif library failure.\n";
} else {
    $exifToolIsAvailable = false;
    echo "Exiftool not found. Lens name extraction will not be possible, CR* processing in case of internal PHP Exif library failure will not be possible.\n";
}

echo "\n";

$files = [];
foreach ($startDirectoryNames as $startDirectoryName) {
    echo "Scanning [" . $startDirectoryName. "]...\n";
    $files = array_merge($files, $scanner->findFiles($startDirectoryName, $extensions));
}
echo "Done. Files found: " . sizeof($files) . "\n\n";
if (sizeof($files) == 0) {
    exit();
}

echo "Analyzing files...\n";
$filesData = $scanner->analyzeFiles($extractor, $math, $converter, $files, $exifToolIsAvailable);
echo "\nDone.\n\n";

// Unset JPEGs if RAWs are present
if ($ignoreJpegsInCaseOfRawPresence) {
    echo "Searching for a JPEGs with existing RAW sources. These JPEGs will be ignored.\n";
    $scanner->ignoreJpegsInCaseOfRawPresence($filesData);
    echo "Done.\n\n";
}

echo "Writing CSV detailed data... ";
$csvWriter->writeCsvData($csvDetailsFileName, $filesData);
echo "Done.\n";

$monthlyStats = $stats->generateInitialStats($filesData);

echo "Writing TXT year and month data... ";
$txtWriter->writeYearAndMonthStats($txtStatsFileName, $monthlyStats, $filesData);
echo "Done.\n";

$overallStats = [];
$stats->generateDetailedStats($filesData, $monthlyStats, $overallStats, $math);

echo "Writing TXT year and month data with details... ";
$txtWriter->writeYearAndMonthStatsWithDetails($txtStatsFileName, $monthlyStats, $math);
echo "Done.\n";

echo "Writing TXT year data with details... ";
$txtWriter->writeYearStatsWithDetails($txtStatsFileName, $monthlyStats, $math);
echo "Done.\n";

echo "Writing TXT overall data... ";
$txtWriter->writeOverallStats($txtStatsFileName, $overallStats, $math);
echo "Done.\n";

$statsForCamerasAndLenses = $stats->generateStatsForCamerasAndLenses($filesData);

echo "Writing TXT stats for cameras and lenses... ";
$txtWriter->writeStatsForCamerasAndLenses($txtStatsFileName, $statsForCamerasAndLenses, $math);
echo "Done.\n";

$csvReadyStats = $stats->generateCsvStats($monthlyStats);

echo "Writing CSV stats... ";
$csvWriter->writeCsvStats($csvStatsFileName, $csvReadyStats);
echo "Done.\n\n";

echo "All Done! Have a nice day!";