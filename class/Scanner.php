<?php

namespace Sv\Photo\ExifStats;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class Scanner
{
    public function findFiles(string $startDir, array $extensions): array
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

    public function analyzeFiles(Extractor $extractor, Math $math, Converter $converter, array $files, bool $exifToolIsAvailable): array
    {
        $filesData = [];
        $filesProcessed = 0;
        $filesToProcess = sizeof($files);
        foreach ($files as $file) {
            echo "Processing file " . ++$filesProcessed . " of " . $filesToProcess . ".\r";
            $filesData[$file] = self::initData();

            $filesData[$file]['name'] = pathinfo($file, PATHINFO_BASENAME);
            $filesData[$file]['fullName'] = $file;
            $filesData[$file]['size'] = filesize($file);
            $filesData[$file]['sizeHumanReadable'] = $math->formatBytes(filesize($file));

            $exif = @exif_read_data($file);
            if (!$exif) {
                if ($exifToolIsAvailable) {
                    $extractor->extractExifDataViaExifTool($file, $filesData, $converter, $math);
                }
            } else {
                $extractor->extractExifData($exif, $file, $filesData, $exifToolIsAvailable, $converter);
            }
        }
        return $filesData;
    }

    public function ignoreJpegsInCaseOfRawPresence(array &$filesData) : void {
        $potentialDuplicates = [];
        foreach ($filesData as $data) {
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

        foreach ($potentialDuplicates as $data) {
            if (count($data) > 1) {
                foreach ($data as $testDataOuterLevel) {
                    if (!$testDataOuterLevel['isJpeg']) {
                        continue;
                    }
                    foreach ($data as $testDataInnerLevel) {
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

    }

    private function initData(): array
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

}