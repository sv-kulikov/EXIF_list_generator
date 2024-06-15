<?php

// Some setup: add or remove file extensions as needed
$extensions = ['jpg', 'jpeg', 'cr2', 'cr3'];
$ignoreJpegsInCaseOfRawPresence = true;

// Check if the script is called with the correct number of arguments
if ($argc < 2) {
    echo "Usage: php elg.php DIR_NAME [CSV_DETAILS_FILE_NAME=elg.csv] [TXT_STATS_FILE_NAME=elg.txt]\n";
    exit(1);
}

// Get the arguments
$startDirectoryName = $argv[1];
$csvDetailsFileName = $argv[2] ?? 'elg.csv';
$txtStatsFileName = $argv[3] ?? 'elg.txt';

// Some info output
echo "Directory to scan: " . $startDirectoryName . "\n";
echo "CSV Details File Name: " . $csvDetailsFileName . "\n";
echo "TXT Stats File Name: " . $txtStatsFileName . "\n";
echo "\n";

// Check if "exiftool" is available (needed for lens name extraction)
if (file_exists('./utils/exiftool.exe')) {
    $exifToolIsAvailable = true;
    echo "Exiftool found. It will be used for lens name extraction and CR* processing in case of internal PHP Exif library failure.\n";
} else {
    $exifToolIsAvailable = false;
    echo "Exiftool not found. Lens name extraction will not be possible, CR* processing in case of internal PHP Exif library failure will not be possible.\n";
}

echo "\n";

// Find files
echo "Scanning...\n";
$files = findFiles($startDirectoryName, $extensions);
echo "Done. Files found: " . sizeof($files) . "\n";

echo "\n";

// Analyze files
echo "Analyzing files...\n";
$filesData = [];
$filesProcessed = 0;
$filesToProcess = sizeof($files);
foreach ($files as $file) {
    $filesData[$file] = initData();

    $filesData[$file]['name'] = pathinfo($file, PATHINFO_BASENAME);
    $filesData[$file]['fullName'] = $file;
    $filesData[$file]['size'] = filesize($file);
    $filesData[$file]['sizeHumanReadable'] = formatBytes(filesize($file));

    $exif = @exif_read_data($file);
    if (!$exif) {
        if ($exifToolIsAvailable) {
            extractExifDataViaExifTool($file, $filesData);
        }
    } else {
        extractExifData($exif, $file, $filesData, $exifToolIsAvailable);
    }

    echo "Files processed: " . ++$filesProcessed . "/" . $filesToProcess . "\r";
}
echo "\nDone.\n";
echo "\n";

// Unset JPEGs if RAWs are present
if ($ignoreJpegsInCaseOfRawPresence) {
    $potentialDuplicates = [];
    echo "Searching for a JPEGs with existing RAW sources. These JPEGs will be ignored.\n";
    foreach ($filesData as $file => $data) {
        $data['unixtime'] = mktime(
            $data['dateH'],
            $data['dateI'],
            $data['dateS'],
            $data['dateM'],
            $data['dateD'],
            $data['dateY']);
        $data['nameWithoutExtension'] = pathinfo($data['name'], PATHINFO_FILENAME);
        $data['isJpeg'] = str_contains(strtolower(pathinfo($data['name'], PATHINFO_EXTENSION)), 'jp');
        $potentialDuplicates[$data['nameWithoutExtension']][] = $data;
    }

    foreach ($potentialDuplicates as $file => $data) {
        if (count($data) > 1) {
            foreach ($data as $testKeyOuterLevel => $testDataOuterLevel) {
                if (!$testDataOuterLevel['isJpeg']) {
                    continue;
                }
                foreach ($data as $testKeyInnerLevel => $testDataInnerLevel) {
                    if ($testDataOuterLevel['fullName'] != $testDataInnerLevel['fullName']) {
                        if (abs($testDataInnerLevel['unixtime'] - $testDataOuterLevel['unixtime']) <= 10) {
                            echo "Unsetting JPEG [" . $testDataOuterLevel['fullName'] . "] because of RAW [" . $testDataInnerLevel['fullName'] . "].\n";
                            unset($filesData[$testDataOuterLevel['fullName']]);
                        }
                    }
                }
            }
        }
    }
    echo "Done.\n";
    echo "\n";
}

// Generate CSV data
if (is_file($csvDetailsFileName)) {
    unlink($csvDetailsFileName);
}

$fileResource = fopen($csvDetailsFileName, 'w');
fputcsv($fileResource, array_keys(reset($filesData)));
fclose($fileResource);

$fileResource = fopen($csvDetailsFileName, 'a');
foreach ($filesData as $file => $data) {
    fputcsv($fileResource, $data);
}
fclose($fileResource);

// Generate TXT stats
if (is_file($txtStatsFileName)) {
    unlink($txtStatsFileName);
}

// Generate Y-m stats
$monthlyStats = [];
foreach ($filesData as $file => $data) {
    $year = (int)$data['dateY'];
    $month = (int)$data['dateM'];
    if (!isset($monthlyStats[$year]['monthly'][$month])) {
        $monthlyStats[$year]['monthly'][$month]['photosCount'] = 1;
    } else {
        $monthlyStats[$year]['monthly'][$month]['photosCount'] += 1;
    }
    if (!isset($monthlyStats[$year]['total']['photosCount'])) {
        $monthlyStats[$year]['total']['photosCount'] = 1;
    } else {
        $monthlyStats[$year]['total']['photosCount'] += 1;
    }
}

$minYear = min(array_keys($monthlyStats));
$maxYear = max(array_keys($monthlyStats));
for ($currentYear = $minYear; $currentYear <= $maxYear; $currentYear++) {
    for ($currentMonth = 1; $currentMonth <= 12; $currentMonth++) {
        if (!isset($monthlyStats[$currentYear]['monthly'][$currentMonth]['photosCount'])) {
            $monthlyStats[$currentYear]['monthly'][$currentMonth]['global']['photosCount'] = 0;
            $monthlyStats[$currentYear]['monthly'][$currentMonth] = [];
        }
    }
    ksort($monthlyStats[$currentYear]);
}
ksort($monthlyStats);

file_put_contents($txtStatsFileName, "Photos: " . sizeof($filesData) . "\n\n", FILE_APPEND);

file_put_contents($txtStatsFileName, "Year stats:\n", FILE_APPEND);
foreach ($monthlyStats as $year => $yearData) {
    file_put_contents($txtStatsFileName, $year . " = " . $yearData['total']['photosCount']. "\n", FILE_APPEND);
}
file_put_contents($txtStatsFileName, "\n", FILE_APPEND);

file_put_contents($txtStatsFileName, "Year-month stats:\n", FILE_APPEND);
foreach ($monthlyStats as $year => $months) {
    file_put_contents($txtStatsFileName, $year. "\n", FILE_APPEND);
    foreach ($months['monthly'] as $month => $data) {
        if (is_numeric($month)) {
            file_put_contents($txtStatsFileName, ' ' . $month. " = " . ($data['photosCount'] ?? '0'). "\n", FILE_APPEND);
        }
    }
}
file_put_contents($txtStatsFileName, "\n", FILE_APPEND);

// Interesting stats
$overallStats = [];
foreach ($filesData as $file => $data) {
    $year = (int)$data['dateY'];
    $month = (int)$data['dateM'];

    if (!isset($monthlyStats[$year]['monthly'][$month]['cameraVendor'][$data['cameraVendor']])) {
        $monthlyStats[$year]['monthly'][$month]['cameraVendor'][$data['cameraVendor']] = 1;
    } else {
        $monthlyStats[$year]['monthly'][$month]['cameraVendor'][$data['cameraVendor']] += 1;
    }
    if (!isset($monthlyStats[$year]['total']['cameraVendor'][$data['cameraVendor']])) {
        $monthlyStats[$year]['total']['cameraVendor'][$data['cameraVendor']] = 1;
    } else {
        $monthlyStats[$year]['total']['cameraVendor'][$data['cameraVendor']] += 1;
    }
    if (!isset($overallStats['cameraVendor'][$data['cameraVendor']])) {
        $overallStats['cameraVendor'][$data['cameraVendor']] = 1;
    } else {
        $overallStats['cameraVendor'][$data['cameraVendor']] += 1;
    }

    if (!isset($monthlyStats[$year]['monthly'][$month]['cameraModel'][$data['cameraModel']])) {
        $monthlyStats[$year]['monthly'][$month]['cameraModel'][$data['cameraModel']] = 1;
    } else {
        $monthlyStats[$year]['monthly'][$month]['cameraModel'][$data['cameraModel']] += 1;
    }
    if (!isset($monthlyStats[$year]['total']['cameraModel'][$data['cameraModel']])) {
        $monthlyStats[$year]['total']['cameraModel'][$data['cameraModel']] = 1;
    } else {
        $monthlyStats[$year]['total']['cameraModel'][$data['cameraModel']] += 1;
    }
    if (!isset($overallStats['cameraModel'][$data['cameraModel']])) {
        $overallStats['cameraModel'][$data['cameraModel']] = 1;
    } else {
        $overallStats['cameraModel'][$data['cameraModel']] += 1;
    }

    if (!isset($monthlyStats[$year]['monthly'][$month]['lensNameCleared'][$data['lensNameCleared']])) {
        $monthlyStats[$year]['monthly'][$month]['lensNameCleared'][$data['lensNameCleared']] = 1;
    } else {
        $monthlyStats[$year]['monthly'][$month]['lensNameCleared'][$data['lensNameCleared']] += 1;
    }
    if (!isset($monthlyStats[$year]['yearly']['lensNameCleared'][$data['lensNameCleared']])) {
        $monthlyStats[$year]['total']['lensNameCleared'][$data['lensNameCleared']] = 1;
    } else {
        $monthlyStats[$year]['total']['lensNameCleared'][$data['lensNameCleared']] += 1;
    }
    if (!isset($overallStats['lensNameCleared'][$data['lensNameCleared']])) {
        $overallStats['lensNameCleared'][$data['lensNameCleared']] = 1;
    } else {
        $overallStats['lensNameCleared'][$data['lensNameCleared']] += 1;
    }

    $monthlyStats[$year]['monthly'][$month]['size'][] = $data['size'];
    $monthlyStats[$year]['monthly'][$month]['height'][] = $data['height'];
    $monthlyStats[$year]['monthly'][$month]['width'][] = $data['width'];
    $monthlyStats[$year]['monthly'][$month]['pixels'][] = $data['height'] * $data['width'];
    $monthlyStats[$year]['monthly'][$month]['megaPixelsHumanReadable'][] = calculateMegapixels($data['width'], $data['height']);

    $monthlyStats[$year]['total']['size'][] = $data['size'];
    $monthlyStats[$year]['total']['height'][] = $data['height'];
    $monthlyStats[$year]['total']['width'][] = $data['width'];
    $monthlyStats[$year]['total']['pixels'][] = $data['height'] * $data['width'];
    $monthlyStats[$year]['total']['megaPixelsHumanReadable'][] = calculateMegapixels($data['width'], $data['height']);

    $monthlyStats[$year]['monthly'][$month]['exposureTime'][] = $data['exposureTime'];
    $monthlyStats[$year]['monthly'][$month]['fNumber'][] = $data['fNumber'];
    $monthlyStats[$year]['monthly'][$month]['iso'][] = $data['iso'];
    $monthlyStats[$year]['monthly'][$month]['focalLength'][] = $data['focalLength'];
    $monthlyStats[$year]['monthly'][$month]['shutterSpeedValue'][] = $data['shutterSpeedValue'];
    $monthlyStats[$year]['monthly'][$month]['apertureValue'][] = $data['apertureValue'];

    $monthlyStats[$year]['total']['exposureTime'][] = $data['exposureTime'];
    $monthlyStats[$year]['total']['fNumber'][] = $data['fNumber'];
    $monthlyStats[$year]['total']['iso'][] = $data['iso'];
    $monthlyStats[$year]['total']['focalLength'][] = $data['focalLength'];
    $monthlyStats[$year]['total']['shutterSpeedValue'][] = $data['shutterSpeedValue'];
    $monthlyStats[$year]['total']['apertureValue'][] = $data['apertureValue'];

    $overallStats['exposureTime'][] = $data['exposureTime'];
    $overallStats['fNumber'][] = $data['fNumber'];
    $overallStats['iso'][] = $data['iso'];
    $overallStats['focalLength'][] = $data['focalLength'];
    $overallStats['shutterSpeedValue'][] = $data['shutterSpeedValue'];
    $overallStats['apertureValue'][] = $data['apertureValue'];

}

foreach ($monthlyStats as $year => $months) {
    foreach ($months['monthly'] as $month => $data) {

        $monthlyStats[$year]['monthly'][$month]['sizeAvg'] = calculateAverage($monthlyStats[$year]['monthly'][$month]['size'] ?? []);
        $monthlyStats[$year]['monthly'][$month]['heightAvg'] = calculateAverage($monthlyStats[$year]['monthly'][$month]['height'] ?? []);
        $monthlyStats[$year]['monthly'][$month]['widthAvg'] = calculateAverage($monthlyStats[$year]['monthly'][$month]['width'] ?? []);
        $monthlyStats[$year]['monthly'][$month]['pixelsAvg'] = calculateAverage($monthlyStats[$year]['monthly'][$month]['pixels'] ?? []);
        $monthlyStats[$year]['monthly'][$month]['pixelsAvgHumanReadable'] = calculateMegapixels($monthlyStats[$year]['monthly'][$month]['widthAvg'] ?? 0, $monthlyStats[$year]['monthly'][$month]['heightAvg'] ?? 0);

        $monthlyStats[$year]['yearly']['sizeAvg'] = calculateAverage($monthlyStats[$year]['yearly']['size'] ?? []);
        $monthlyStats[$year]['yearly']['heightAvg'] = calculateAverage($monthlyStats[$year]['yearly']['height'] ?? []);
        $monthlyStats[$year]['yearly']['widthAvg'] = calculateAverage($monthlyStats[$year]['yearly']['width'] ?? []);
        $monthlyStats[$year]['yearly']['pixelsAvg'] = calculateAverage($monthlyStats[$year]['yearly']['pixels'] ?? []);
        $monthlyStats[$year]['yearly']['pixelsAvgHumanReadable'] = calculateMegapixels($monthlyStats[$year]['yearly']['widthAvg'] ?? 0, $monthlyStats[$year]['yearly']['heightAvg'] ?? 0);

        $monthlyStats[$year]['monthly'][$month]['exposureTimeAvg'] = calculateAverage($monthlyStats[$year]['monthly'][$month]['exposureTime'] ?? []);
        $monthlyStats[$year]['monthly'][$month]['fNumberAvg'] = calculateAverage($monthlyStats[$year]['monthly'][$month]['fNumber'] ?? []);
        $monthlyStats[$year]['monthly'][$month]['isoAvg'] = calculateAverage($monthlyStats[$year]['monthly'][$month]['iso'] ?? []);
        $monthlyStats[$year]['monthly'][$month]['focalLengthAvg'] = calculateAverage($monthlyStats[$year]['monthly'][$month]['focalLength'] ?? []);
        $monthlyStats[$year]['monthly'][$month]['shutterSpeedValueAvg'] = calculateAverage($monthlyStats[$year]['monthly'][$month]['shutterSpeedValue'] ?? []);
        $monthlyStats[$year]['monthly'][$month]['apertureValueAvg'] = calculateAverage($monthlyStats[$year]['monthly'][$month]['apertureValue'] ?? []);

        $monthlyStats[$year]['exposureTimeAvg'] = calculateAverage($monthlyStats[$year]['yearly']['exposureTime'] ?? []);
        $monthlyStats[$year]['fNumberAvg'] = calculateAverage($monthlyStats[$year]['yearly']['fNumber'] ?? []);
        $monthlyStats[$year]['isoAvg'] = calculateAverage($monthlyStats[$year]['yearly']['iso'] ?? []);
        $monthlyStats[$year]['focalLengthAvg'] = calculateAverage($monthlyStats[$year]['yearly']['focalLength'] ?? []);
        $monthlyStats[$year]['shutterSpeedValueAvg'] = calculateAverage($monthlyStats[$year]['yearly']['shutterSpeedValue'] ?? []);
        $monthlyStats[$year]['apertureValueAvg'] = calculateAverage($monthlyStats[$year]['yearly']['apertureValue'] ?? []);
    }
}

foreach ($overallStats as $ovsKey => $ovsData) {
    $overallStats[$ovsKey . 'Avg'] = calculateAverage($ovsData ?? []);
}

file_put_contents($txtStatsFileName, "Year-month stats with details:\n", FILE_APPEND);

foreach ($monthlyStats as $year => $yearData) {
    file_put_contents($txtStatsFileName, $year. "\n", FILE_APPEND);
    foreach ($yearData['monthly'] as $month => $data) {
        file_put_contents($txtStatsFileName, ' ' . $month. " \n", FILE_APPEND);

        foreach ($data as $dataKey => $dataValue) {
            if (is_array($dataValue) || !str_contains($dataKey, 'Avg')) {
                continue;
            }
            file_put_contents($txtStatsFileName, '  ' . $dataKey. " = " . $dataValue. "\n", FILE_APPEND);
        }
    }
}

file_put_contents($txtStatsFileName, "\n", FILE_APPEND);
file_put_contents($txtStatsFileName, "Year stats with details:\n", FILE_APPEND);

foreach ($monthlyStats as $year => $yearData) {
    file_put_contents($txtStatsFileName, $year. "\n", FILE_APPEND);
        foreach ($yearData as $dataKey => $dataValue) {
            if (is_array($dataValue)) {
                continue;
            }
            file_put_contents($txtStatsFileName, ' ' . $dataKey. " = " . $dataValue. "\n", FILE_APPEND);
        }
}


file_put_contents($txtStatsFileName, "\n", FILE_APPEND);
file_put_contents($txtStatsFileName, "Overall stats: \n", FILE_APPEND);

$valuesWithDetails = [];

foreach ($valuesWithDetails as $dataKey => $dataValue) {
    file_put_contents($txtStatsFileName, ' ' . $dataKey. " = " . $dataValue. "\n", FILE_APPEND);
}

file_put_contents($txtStatsFileName, "Just details:\n", FILE_APPEND);
foreach ($valuesWithDetails as $dataKey => $dataValue) {
        file_put_contents($txtStatsFileName, ' ' . $dataKey. " = " . $dataValue. "\n", FILE_APPEND);
}

file_put_contents($txtStatsFileName, "\n", FILE_APPEND);


function findFiles($startDir, $extensions): array
{
    $filesFound = [];

    $dirIterator = new RecursiveDirectoryIterator($startDir);
    $iterator = new RecursiveIteratorIterator($dirIterator);

    foreach ($iterator as $file) {

        if ($file->isDir() && $file->getFilename() != '.' && $file->getFilename() != '..') {
            echo "Scanning directory: " . $file->getPathname() . "\n";
        }

        if ($file->isFile()) {
            $ext = strtolower(pathinfo($file->getFilename(), PATHINFO_EXTENSION));
            if (in_array($ext, $extensions)) {
                $filesFound[] = $file->getPathname();
            }
        }
    }

    return $filesFound;
}

function formatBytes(int|float $bytes, int $precision = 2): string
{
    $units = array('B', 'KB', 'MB', 'GB', 'TB');

    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);

    $tmpResult = round($bytes, $precision);

    while (strrpos($tmpResult, '.') >= strlen($tmpResult) - $precision) {
        $tmpResult .= '0';
    }

    return $tmpResult . ' ' . $units[$pow];
}

function extractExifData($exif, $file, &$filesData, $exifToolIsAvailable): void
{
    $filesData[$file]['mimeType'] = $exif['MimeType'] ?? 'unknownMimeType';

    $filesData[$file]['height'] = $exif['COMPUTED']['Height'] ?? 0;
    $filesData[$file]['width'] = $exif['COMPUTED']['Width'] ?? 0;

    $fileDateTime = strtotime($exif['DateTimeOriginal'] ?? date("Y-m-d H:i:s", filemtime($file)));
    $filesData[$file]['dateY'] = date('Y', $fileDateTime);
    $filesData[$file]['dateM'] = date('m', $fileDateTime);
    $filesData[$file]['dateD'] = date('d', $fileDateTime);
    $filesData[$file]['dateH'] = date('H', $fileDateTime);
    $filesData[$file]['dateI'] = date('i', $fileDateTime);
    $filesData[$file]['dateS'] = date('s', $fileDateTime);

    $filesData[$file]['cameraVendor'] = $exif['Make'] ?? 'unknownVendor';
    $filesData[$file]['cameraModel'] = $exif['Model'] ?? 'unknownModel';
    $filesData[$file]['lens'] = $exif['UndefinedTag:0xA434'] ?? 'unknownLens';

    if ($exifToolIsAvailable) {
        $commandToExecute = "\"utils/exiftool.exe\" -json -LensID \"" . $file . "\"";
        $jsonOutput = shell_exec($commandToExecute);
        $exifJsonData = json_decode($jsonOutput, true);

        if (!empty($exifJsonData) && isset($exifJsonData[0]['LensID'])) {
            $lensName = $exifJsonData[0]['LensID'];

            // Extract the lens name before any "|" character, if present
            if (preg_match("/^([^|]*)/", $lensName, $matchesClean)) {
                $filesData[$file]['lensName'] = $lensName;
                $filesData[$file]['lensNameCleared'] = $matchesClean[1];
            } else {
                $filesData[$file]['lensName'] = $lensName;
                $filesData[$file]['lensNameCleared'] = $lensName; // Use full lens name if no "|" is found
            }
        } else {
            $filesData[$file]['lensName'] = 'unknownLens';
            $filesData[$file]['lensNameCleared'] = 'unknownLens';
        }

    } else {
        $filesData[$file]['lensName'] = 'unknownLens';
        $filesData[$file]['lensNameCleared'] = 'unknownLens';
    }

    $filesData[$file]['software'] = $exif['Software'] ?? 'unknownSoftware';

    $filesData[$file]['exposureTimeRaw'] = $exif['ExposureTime'] ?? 'unknownExposureTime';
    $filesData[$file]['exposureTime'] = eval('return ' . ($exif['ExposureTime'] ?? '0/1'). ';');
    $filesData[$file]['fNumberRaw'] = $exif['FNumber'] ?? 'unknownFNumber';
    $filesData[$file]['fNumber'] = eval('return ' . ($exif['FNumber'] ?? '0/1'). ';');
    $filesData[$file]['iso'] = $exif['ISOSpeedRatings'] ?? 'unknownISO';
    $filesData[$file]['focalLengthRaw'] = $exif['FocalLength'] ?? 'unknownFocalLength';
    $filesData[$file]['focalLength'] = eval('return ' . ($exif['FocalLength'] ?? '0/1'). ';');
    $filesData[$file]['shutterSpeedValueRaw'] = $exif['ShutterSpeedValue'] ?? 'unknownShutterSpeedValue';
    $filesData[$file]['shutterSpeedValue'] = eval('return ' . ($exif['ShutterSpeedValue'] ?? '0/1') . ';');
    $filesData[$file]['apertureValueRaw'] = $exif['ApertureValue'] ?? 'unknownApertureValue';
    $filesData[$file]['apertureValue'] = eval('return ' . ($exif['ApertureValue'] ?? '0/1') . ';');
    $filesData[$file]['exposureBiasValueRaw'] = $exif['ExposureBiasValue'] ?? 'unknownExposureBiasValue';
    $filesData[$file]['exposureBiasValue'] = eval('return ' . ($exif['ExposureBiasValue'] ?? '0/1'). ';');
    $filesData[$file]['flashRaw'] = $exif['Flash'] ?? 'unknownFlashSettings';
    $filesData[$file]['flash'] = parseFlash($exif['Flash'] ?? -1);
    $filesData[$file]['exposureProgramRaw'] = $exif['ExposureProgram'] ?? 'unknownExposureProgram';
    $filesData[$file]['exposureProgram'] = parseExposureProgram($exif['ExposureProgram'] ?? -1);
    $filesData[$file]['meteringModeRaw'] = $exif['MeteringMode'] ?? 'unknownMeteringMode';
    $filesData[$file]['meteringMode'] = parseMeteringMode($exif['MeteringMode'] ?? -1);
    $filesData[$file]['whiteBalanceRaw'] = $exif['WhiteBalance'] ?? 'unknownWhiteBalance';
    $filesData[$file]['whiteBalance'] = parseWhiteBalance($exif['WhiteBalance'] ?? -1);
    $filesData[$file]['exposureModeRaw'] = $exif['ExposureMode'] ?? 'unknownExposureMode';
    $filesData[$file]['exposureMode'] = parseExposureMode($exif['ExposureMode'] ?? -1);
    $filesData[$file]['colorSpaceRaw'] = $exif['ColorSpace'] ?? 'unknownColorSpace';
    $filesData[$file]['colorSpace'] = parseColorSpace($exif['ColorSpace'] ?? -1);
    $filesData[$file]['contrast'] = $exif['Contrast'] ?? 'unknownContrast';
    $filesData[$file]['saturation'] = $exif['Saturation'] ?? 'unknownSaturation';
    $filesData[$file]['sharpness'] = $exif['Sharpness'] ?? 'unknownSharpness';

    $filesData[$file]['gpsLatitude'] = $exif['GPSLatitude'] ?? 'unknownGPSLatitude';
    $filesData[$file]['gpsLongitude'] = $exif['GPSLongitude'] ?? 'unknownGPSLongitude';
    $filesData[$file]['gpsAltitude'] = $exif['GPSAltitude'] ?? 'unknownGPSAltitude';
    $filesData[$file]['gpsDateTime'] = $exif['GPSDateTime'] ?? 'unknownGPSDateTime';

    $filesData[$file]['exifVersion'] = $exif['ExifVersion'] ?? 'unknownExifVersion';
}

function extractExifDataViaExifTool($file, &$filesData): void
{
    $commandToExecute = "\"utils/exiftool\" -json \"" . $file . "\"";
    $json = shell_exec($commandToExecute);
    $exif = json_decode($json, true)[0];

    $filesData[$file]['mimeType'] = $exif['MIMEType'] ?? 'unknownMimeType';
    $filesData[$file]['height'] = $exif['ImageHeight'] ?? 0;
    $filesData[$file]['width'] = $exif['ImageWidth'] ?? 0;

    $fileDateTime = strtotime($exif['FileModifyDate'] ?? date("Y-m-d H:i:s", filemtime($file)));
    $filesData[$file]['dateY'] = date('Y', $fileDateTime);
    $filesData[$file]['dateM'] = date('m', $fileDateTime);
    $filesData[$file]['dateD'] = date('d', $fileDateTime);
    $filesData[$file]['dateH'] = date('H', $fileDateTime);
    $filesData[$file]['dateI'] = date('i', $fileDateTime);
    $filesData[$file]['dateS'] = date('s', $fileDateTime);

    $filesData[$file]['cameraVendor'] = $exif['Make'] ?? 'unknownVendor';
    $filesData[$file]['cameraModel'] = $exif['Model'] ?? 'unknownModel';
    $filesData[$file]['lens'] = $exif['LensID'] ?? 'unknownLens';


    if (!empty($exif['LensID'])) {
        $lensName = $exif['LensID'];

        // Extract the lens name before any "|" character, if present
        if (preg_match("/^([^|]*)/", $lensName, $matchesClean)) {
            $filesData[$file]['lensName'] = $lensName;
            $filesData[$file]['lensNameCleared'] = $matchesClean[1];
        } else {
            $filesData[$file]['lensName'] = $lensName;
            $filesData[$file]['lensNameCleared'] = $lensName; // Use full lens name if no "|" is found
        }
    } else {
        $filesData[$file]['lensName'] = 'unknownLens';
        $filesData[$file]['lensNameCleared'] = 'unknownLens';
    }

    $filesData[$file]['software'] = $exif['Software'] ?? 'unknownSoftware';
    $filesData[$file]['exposureTimeRaw'] = convertDecimalToFraction($exif['ExposureTime'] ?? 0);
    $filesData[$file]['exposureTime'] = $exif['ExposureTime'] ?? 'unknownExposureTime';
    $filesData[$file]['fNumberRaw'] = ($exif['FNumber'] ?? 'x') . '/1';
    $filesData[$file]['fNumber'] = $exif['FNumber'] ?? 'unknownFNumber';
    $filesData[$file]['iso'] = $exif['ISO'] ?? 'unknownISO';
    $filesData[$file]['focalLengthRaw'] = (int)($exif['FocalLength'] ?? '0') . '/1';
    $filesData[$file]['focalLength'] = (int)($exif['FocalLength'] ?? 0);
    $filesData[$file]['shutterSpeedValueRaw'] = convertDecimalToFraction($exif['ShutterSpeedValue'] ?? 0);
    $filesData[$file]['shutterSpeedValue'] = $exif['ShutterSpeedValue'] ?? 'unknownShutterSpeedValue';
    $filesData[$file]['apertureValueRaw'] = ($exif['ApertureValue'] ?? 'x') . '/1';
    $filesData[$file]['apertureValue'] = $exif['ApertureValue'] ?? 'unknownApertureValue';
    $filesData[$file]['exposureBiasValueRaw'] = '0/' . ($exif['ExposureCompensation'] ?? 'x');
    $filesData[$file]['exposureBiasValue'] = $exif['ExposureCompensation'] ?? 'unknownExposureBiasValue';
    $filesData[$file]['flashRaw'] = reverseParseFlash($exif['Flash'] ?? '');
    $filesData[$file]['flash'] = $exif['Flash'] ?? 'unknownFlashSettings';
    $filesData[$file]['exposureProgramRaw'] = reverseParseExposureProgram($exif['ExposureProgram'] ?? '');
    $filesData[$file]['exposureProgram'] = $exif['ExposureProgram'] ?? 'unknownExposureProgram';
    $filesData[$file]['meteringModeRaw'] = reverseParseMeteringMode($exif['MeteringMode'] ?? '');
    $filesData[$file]['meteringMode'] = $exif['MeteringMode'] ?? 'unknownMeteringMode';
    $filesData[$file]['whiteBalanceRaw'] = reverseParseWhiteBalance($exif['WhiteBalance'] ?? '');
    $filesData[$file]['whiteBalance'] = $exif['WhiteBalance'] ?? 'unknownWhiteBalance';
    $filesData[$file]['exposureModeRaw'] = reverseParseExposureMode($exif['ExposureMode'] ?? '');
    $filesData[$file]['exposureMode'] = $exif['ExposureMode'] ?? 'unknownExposureMode';
    $filesData[$file]['colorSpaceRaw'] = reverseParseColorSpace($exif['ColorSpace'] ?? '');
    $filesData[$file]['colorSpace'] = $exif['ColorSpace'] ?? 'unknownColorSpace';

    $filesData[$file]['contrast'] = $exif['Contrast'] ?? 'unknownContrast';
    $filesData[$file]['saturation'] = $exif['Saturation'] ?? 'unknownSaturation';
    $filesData[$file]['sharpness'] = $exif['Sharpness'] ?? 'unknownSharpness';

    $filesData[$file]['gpsLatitude'] = $exif['GPSLatitude'] ?? 'unknownGPSLatitude';
    $filesData[$file]['gpsLongitude'] = $exif['GPSLongitude'] ?? 'unknownGPSLongitude';
    $filesData[$file]['gpsAltitude'] = $exif['GPSAltitude'] ?? 'unknownGPSAltitude';
    $filesData[$file]['gpsDateTime'] = $exif['GPSDateTime'] ?? 'unknownGPSDateTime';

    $filesData[$file]['exifVersion'] = $exif['ExifVersion'] ?? 'unknownExifVersion';
}

function parseFlash($flashCode)
{
    switch ($flashCode) {
        case 0:
            return 'Flash did not fire';
        case 1:
            return 'Flash fired';
        case 5:
            return 'Strobe return light not detected';
        case 7:
            return 'Strobe return light detected';
        case 9:
            return 'Flash fired, compulsory flash mode';
        case 13:
            return 'Flash fired, compulsory flash mode, return light not detected';
        case 15:
            return 'Flash fired, compulsory flash mode, return light detected';
        case 16:
            return 'Flash did not fire, compulsory flash mode';
        case 24:
            return 'Flash did not fire, auto mode';
        case 25:
            return 'Flash fired, auto mode';
        case 29:
            return 'Flash fired, auto mode, return light not detected';
        case 31:
            return 'Flash fired, auto mode, return light detected';
        case 32:
            return 'No flash function';
        case 65:
            return 'Flash fired, red-eye reduction mode';
        default:
            return 'Unknown flash setting';
    }
}

function parseExposureProgram($exposureProgramCode)
{
    switch ($exposureProgramCode) {
        case 0:
            return 'Not defined';
        case 1:
            return 'Manual';
        case 2:
            return 'Normal program';
        case 3:
            return 'Aperture priority';
        case 4:
            return 'Shutter priority';
        case 5:
            return 'Creative program (biased toward depth of field)';
        case 6:
            return 'Action program (biased toward fast shutter speed)';
        case 7:
            return 'Portrait mode (for close-up photos with the background out of focus)';
        case 8:
            return 'Landscape mode (for landscape photos with the background in focus)';
        default:
            return 'Unknown exposure program';
    }
}


function parseMeteringMode($meteringModeCode)
{
    switch ($meteringModeCode) {
        case 0:
            return 'Unknown';
        case 1:
            return 'Average';
        case 2:
            return 'Center-weighted average';
        case 3:
            return 'Spot';
        case 4:
            return 'Multi-spot';
        case 5:
            return 'Pattern';
        case 6:
            return 'Partial';
        case 255:
            return 'Other';
        default:
            return 'unknownMeteringMode';
    }
}

function parseWhiteBalance($whiteBalanceCode)
{
    switch ($whiteBalanceCode) {
        case 0:
            return 'Auto';
        case 1:
            return 'Manual';
        default:
            return 'Unknown white balance';
    }
}

function parseExposureMode($exposureModeCode)
{
    switch ($exposureModeCode) {
        case 0:
            return 'Auto';
        case 1:
            return 'Manual';
        case 2:
            return 'Auto bracket';
        default:
            return 'Unknown exposure mode';
    }
}

function parseColorSpace($colorSpace)
{
    switch ($colorSpace) {
        case 1:
            return 'sRGB';
        case 65535:
            return 'Adobe RGB';
        default:
            return 'Unknown color space';
    }
}

// Reverse parsing
function reverseParseFlash($flashDescription)
{
    switch ($flashDescription) {
        case 'Flash did not fire':
            return 0;
        case 'No Flash':
            return 0;
        case 'Flash fired':
            return 1;
        case 'Strobe return light not detected':
            return 5;
        case 'Strobe return light detected':
            return 7;
        case 'Flash fired, compulsory flash mode':
            return 9;
        case 'Flash fired, compulsory flash mode, return light not detected':
            return 13;
        case 'Flash fired, compulsory flash mode, return light detected':
            return 15;
        case 'Flash did not fire, compulsory flash mode':
            return 16;
        case 'Flash did not fire, auto mode':
            return 24;
        case 'Flash fired, auto mode':
            return 25;
        case 'Flash fired, auto mode, return light not detected':
            return 29;
        case 'Flash fired, auto mode, return light detected':
            return 31;
        case 'No flash function':
            return 32;
        case 'Flash fired, red-eye reduction mode':
            return 65;
        default:
            return -1;
    }
}

function reverseParseExposureProgram($exposureProgramDescription)
{
    switch ($exposureProgramDescription) {
        case 'Not defined':
            return 0;
        case 'Manual':
            return 1;
        case 'Normal program':
            return 2;
        case 'Aperture priority':
            return 3;
        case 'Shutter priority':
            return 4;
        case 'Creative program (biased toward depth of field)':
            return 5;
        case 'Action program (biased toward fast shutter speed)':
            return 6;
        case 'Portrait mode (for close-up photos with the background out of focus)':
            return 7;
        case 'Landscape mode (for landscape photos with the background in focus)':
            return 8;
        default:
            return -1;
    }
}

function reverseParseMeteringMode($meteringModeDescription)
{
    switch ($meteringModeDescription) {
        case 'Unknown':
            return 0;
        case 'Average':
            return 1;
        case 'Center-weighted average':
            return 2;
        case 'Spot':
            return 3;
        case 'Multi-spot':
            return 4;
        case 'Pattern':
            return 5;
        case 'Partial':
            return 6;
        case 'Other':
            return 255;
        default:
            return -1;
    }
}

function reverseParseWhiteBalance($whiteBalanceDescription)
{
    switch ($whiteBalanceDescription) {
        case 'Auto':
            return 0;
        case 'Manual':
            return 1;
        default:
            return -1;
    }
}

function reverseParseExposureMode($exposureModeDescription)
{
    switch ($exposureModeDescription) {
        case 'Auto':
            return 0;
        case 'Manual':
            return 1;
        case 'Auto bracket':
            return 2;
        default:
            return -1;
    }
}

function reverseParseColorSpace($colorSpaceDescription)
{
    switch ($colorSpaceDescription) {
        case 'sRGB':
            return 1;
        case 'Adobe RGB':
            return 65535;
        default:
            return -1;
    }
}

function convertDecimalToFraction($decimal)
{
    $decimal = (float)$decimal;

    $denominator = 10;
    $numerator = round($decimal * $denominator);

    while ($numerator % 10 === 0 && $denominator % 10 === 0 && $denominator > 1) {
        $numerator /= 10;
        $denominator /= 10;
    }

    return "$numerator/$denominator";
}

function initData()
{
    return [
        'name' => 'n/a',
        'fullName' => 'n/a',
        'size' => 'n/a',
        'sizeHumanReadable' => 'n/a',
        'mimeType' => 'n/a',
        'height' => 'n/a',
        'width' => 'n/a',
        'dateY' => 'n/a',
        'dateM' => 'n/a',
        'dateD' => 'n/a',
        'dateH' => 'n/a',
        'dateI' => 'n/a',
        'dateS' => 'n/a',
        'cameraVendor' => 'n/a',
        'cameraModel' => 'n/a',
        'lens' => 'n/a',
        'lensName' => 'n/a',
        'lensNameCleared' => 'n/a',
        'software' => 'n/a',
        'exposureTimeRaw' => 'n/a',
        'exposureTime' => 'n/a',
        'fNumberRaw' => 'n/a',
        'fNumber' => 'n/a',
        'iso' => 'n/a',
        'focalLengthRaw' => 'n/a',
        'focalLength' => 'n/a',
        'shutterSpeedValueRaw' => 'n/a',
        'shutterSpeedValue' => 'n/a',
        'apertureValueRaw' => 'n/a',
        'apertureValue' => 'n/a',
        'exposureBiasValueRaw' => 'n/a',
        'exposureBiasValue' => 'n/a',
        'flashRaw' => 'n/a',
        'flash' => 'n/a',
        'exposureProgramRaw' => 'n/a',
        'exposureProgram' => 'n/a',
        'meteringModeRaw' => 'n/a',
        'meteringMode' => 'n/a',
        'whiteBalanceRaw' => 'n/a',
        'whiteBalance' => 'n/a',
        'exposureModeRaw' => 'n/a',
        'exposureMode' => 'n/a',
        'colorSpaceRaw' => 'n/a',
        'colorSpace' => 'n/a',
        'contrast' => 'n/a',
        'saturation' => 'n/a',
        'sharpness' => 'n/a',
        'gpsLatitude' => 'n/a',
        'gpsLongitude' => 'n/a',
        'gpsAltitude' => 'n/a',
        'gpsDateTime' => 'n/a',
        'exifVersion' => 'n/a'
    ];

}


function calculateMegapixels($width, $height) {

    // echo "{" . $width .  " " . $height . "}\n";

    $totalPixels = $width * $height;
    $megapixels = $totalPixels / 1000000;
    return round((float)$megapixels, 2);
}

function calculateAverage(double|array $numbers) : float {
    if (!is_array($numbers)) {
        $numbers = array($numbers);
    }

    $numbers = array_filter($numbers, 'is_numeric');

    $count = count($numbers);

    if ($count > 0) {
        $sum = array_sum($numbers ?? []);
        $average = $sum / $count;
        return $average;
    } else {
        return -1;
    }
}
