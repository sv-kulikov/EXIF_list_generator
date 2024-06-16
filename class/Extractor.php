<?php

namespace Sv\Photo\ExifStats;

class Extractor
{
    public function extractExifData(array $exif, string $file, array &$filesData, bool $exifToolIsAvailable, Converter $converter): void
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
            $commandToExecute = "\"" . str_replace("\\", "/", __DIR__) . "/../utils/exiftool.exe\" -charset filename=cp1251 -json -LensID \"" . $file . "\"";
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
        $filesData[$file]['exposureTime'] = eval('return ' . ($exif['ExposureTime'] ?? '0/1') . ';');
        $filesData[$file]['fNumberRaw'] = $exif['FNumber'] ?? 'unknownFNumber';
        $filesData[$file]['fNumber'] = eval('return ' . ($exif['FNumber'] ?? '0/1') . ';');
        $filesData[$file]['iso'] = $exif['ISOSpeedRatings'] ?? 'unknownISO';
        $filesData[$file]['focalLengthRaw'] = $exif['FocalLength'] ?? 'unknownFocalLength';
        $filesData[$file]['focalLength'] = eval('return ' . ($exif['FocalLength'] ?? '0/1') . ';');
        $filesData[$file]['shutterSpeedValueRaw'] = $exif['ShutterSpeedValue'] ?? 'unknownShutterSpeedValue';
        $filesData[$file]['shutterSpeedValue'] = eval('return ' . ($exif['ShutterSpeedValue'] ?? '0/1') . ';');
        $filesData[$file]['apertureValueRaw'] = $exif['ApertureValue'] ?? 'unknownApertureValue';
        $filesData[$file]['apertureValue'] = eval('return ' . ($exif['ApertureValue'] ?? '0/1') . ';');
        $filesData[$file]['exposureBiasValueRaw'] = $exif['ExposureBiasValue'] ?? 'unknownExposureBiasValue';
        $filesData[$file]['exposureBiasValue'] = eval('return ' . ($exif['ExposureBiasValue'] ?? '0/1') . ';');
        $filesData[$file]['flashRaw'] = $exif['Flash'] ?? 'unknownFlashSettings';
        $filesData[$file]['flash'] = $converter->parseFlash($exif['Flash'] ?? -1);
        $filesData[$file]['exposureProgramRaw'] = $exif['ExposureProgram'] ?? 'unknownExposureProgram';
        $filesData[$file]['exposureProgram'] = $converter->parseExposureProgram($exif['ExposureProgram'] ?? -1);
        $filesData[$file]['meteringModeRaw'] = $exif['MeteringMode'] ?? 'unknownMeteringMode';
        $filesData[$file]['meteringMode'] = $converter->parseMeteringMode($exif['MeteringMode'] ?? -1);
        $filesData[$file]['whiteBalanceRaw'] = $exif['WhiteBalance'] ?? 'unknownWhiteBalance';
        $filesData[$file]['whiteBalance'] = $converter->parseWhiteBalance($exif['WhiteBalance'] ?? -1);
        $filesData[$file]['exposureModeRaw'] = $exif['ExposureMode'] ?? 'unknownExposureMode';
        $filesData[$file]['exposureMode'] = $converter->parseExposureMode($exif['ExposureMode'] ?? -1);
        $filesData[$file]['colorSpaceRaw'] = $exif['ColorSpace'] ?? 'unknownColorSpace';
        $filesData[$file]['colorSpace'] = $converter->parseColorSpace($exif['ColorSpace'] ?? -1);
        $filesData[$file]['contrast'] = $exif['Contrast'] ?? 'unknownContrast';
        $filesData[$file]['saturation'] = $exif['Saturation'] ?? 'unknownSaturation';
        $filesData[$file]['sharpness'] = $exif['Sharpness'] ?? 'unknownSharpness';

        $filesData[$file]['gpsLatitude'] = $exif['GPSLatitude'] ?? 'unknownGPSLatitude';
        $filesData[$file]['gpsLongitude'] = $exif['GPSLongitude'] ?? 'unknownGPSLongitude';
        $filesData[$file]['gpsAltitude'] = $exif['GPSAltitude'] ?? 'unknownGPSAltitude';
        $filesData[$file]['gpsDateTime'] = $exif['GPSDateTime'] ?? 'unknownGPSDateTime';

        $filesData[$file]['exifVersion'] = $exif['ExifVersion'] ?? 'unknownExifVersion';
    }

    public function extractExifDataViaExifTool(string $file, array &$filesData, Converter $converter, Math $math): void
    {
        $commandToExecute = "\"" . str_replace("\\", "/", __DIR__) . "/../utils/exiftool\" -charset filename=cp1251 -json \"" . $file . "\"";
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
        $filesData[$file]['exposureTimeRaw'] = $math->convertDecimalToFraction($exif['ExposureTime'] ?? 0);
        $filesData[$file]['exposureTime'] = $exif['ExposureTime'] ?? 'unknownExposureTime';
        $filesData[$file]['fNumberRaw'] = ($exif['FNumber'] ?? 'x') . '/1';
        $filesData[$file]['fNumber'] = $exif['FNumber'] ?? 'unknownFNumber';
        $filesData[$file]['iso'] = $exif['ISO'] ?? 'unknownISO';
        $filesData[$file]['focalLengthRaw'] = (int)($exif['FocalLength'] ?? '0') . '/1';
        $filesData[$file]['focalLength'] = (int)($exif['FocalLength'] ?? 0);
        $filesData[$file]['shutterSpeedValueRaw'] = $math->convertDecimalToFraction($exif['ShutterSpeedValue'] ?? 0);
        $filesData[$file]['shutterSpeedValue'] = $exif['ShutterSpeedValue'] ?? 'unknownShutterSpeedValue';
        $filesData[$file]['apertureValueRaw'] = ($exif['ApertureValue'] ?? 'x') . '/1';
        $filesData[$file]['apertureValue'] = $exif['ApertureValue'] ?? 'unknownApertureValue';
        $filesData[$file]['exposureBiasValueRaw'] = '0/' . ($exif['ExposureCompensation'] ?? 'x');
        $filesData[$file]['exposureBiasValue'] = $exif['ExposureCompensation'] ?? 'unknownExposureBiasValue';
        $filesData[$file]['flashRaw'] = $converter->reverseParseFlash($exif['Flash'] ?? '');
        $filesData[$file]['flash'] = $exif['Flash'] ?? 'unknownFlashSettings';
        $filesData[$file]['exposureProgramRaw'] = $converter->reverseParseExposureProgram($exif['ExposureProgram'] ?? '');
        $filesData[$file]['exposureProgram'] = $exif['ExposureProgram'] ?? 'unknownExposureProgram';
        $filesData[$file]['meteringModeRaw'] = $converter->reverseParseMeteringMode($exif['MeteringMode'] ?? '');
        $filesData[$file]['meteringMode'] = $exif['MeteringMode'] ?? 'unknownMeteringMode';
        $filesData[$file]['whiteBalanceRaw'] = $converter->reverseParseWhiteBalance($exif['WhiteBalance'] ?? '');
        $filesData[$file]['whiteBalance'] = $exif['WhiteBalance'] ?? 'unknownWhiteBalance';
        $filesData[$file]['exposureModeRaw'] = $converter->reverseParseExposureMode($exif['ExposureMode'] ?? '');
        $filesData[$file]['exposureMode'] = $exif['ExposureMode'] ?? 'unknownExposureMode';
        $filesData[$file]['colorSpaceRaw'] = $converter->reverseParseColorSpace($exif['ColorSpace'] ?? '');
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
}