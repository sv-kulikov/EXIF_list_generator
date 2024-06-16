<?php

namespace Sv\Photo\ExifStats;

class TxtWriter
{
    public function writeYearAndMonthStats(string $txtStatsFileName, array $monthlyStats, array $filesData): void
    {
        if (is_file($txtStatsFileName)) {
            unlink($txtStatsFileName);
        }

        file_put_contents($txtStatsFileName, "Photos: " . sizeof($filesData) . "\n\n", FILE_APPEND);

        file_put_contents($txtStatsFileName, "Year stats:\n", FILE_APPEND);
        foreach ($monthlyStats as $year => $yearData) {
            file_put_contents($txtStatsFileName, $year . " = " . ($yearData['photosCount'] ?? 0) . "\n", FILE_APPEND);
        }
        file_put_contents($txtStatsFileName, "\n", FILE_APPEND);

        file_put_contents($txtStatsFileName, "Year-month stats:\n", FILE_APPEND);
        foreach ($monthlyStats as $year => $months) {
            file_put_contents($txtStatsFileName, $year . "\n", FILE_APPEND);
            foreach ($months['monthly'] as $month => $data) {
                if (is_numeric($month)) {
                    file_put_contents($txtStatsFileName, ' ' . $month . " = " . ($data['photosCount'] ?? '0') . "\n", FILE_APPEND);
                }
            }
        }
        file_put_contents($txtStatsFileName, "\n", FILE_APPEND);

    }

    public function writeYearAndMonthStatsWithDetails(string $txtStatsFileName, array $monthlyStats, Math $math): void
    {
        file_put_contents($txtStatsFileName, "Year-month stats with details:\n", FILE_APPEND);

        foreach ($monthlyStats as $year => $yearData) {
            file_put_contents($txtStatsFileName, $year . "\n", FILE_APPEND);
            foreach ($yearData['monthly'] as $month => $data) {
                file_put_contents($txtStatsFileName, ' ' . $month . " \n", FILE_APPEND);

                foreach ($data as $dataKey => $dataValue) {
                    if (!is_array($dataValue) || str_contains($dataKey, 'Avg')) {

                        if (in_array($dataKey, ['exposureTimeAvg', 'shutterSpeedValueAvg'])) {
                            file_put_contents($txtStatsFileName, '  ' . $dataKey . " = " . $math->convertDecimalToFraction($dataValue) . "\n", FILE_APPEND);
                        } else {
                            file_put_contents($txtStatsFileName, '  ' . $dataKey . " = " . $dataValue . "\n", FILE_APPEND);
                        }


                    } elseif (in_array($dataKey, ['cameraVendor', 'cameraModel', 'lensNameCleared'])) {
                        file_put_contents($txtStatsFileName, '  ' . $dataKey . "\n", FILE_APPEND);
                        foreach ($dataValue as $dataValueKey => $dataValueValue) {
                            file_put_contents($txtStatsFileName, '   ' . $dataValueKey . " = " . $dataValueValue . " (" . $math->calculatePercentage($dataValueValue, $dataValue) . ")\n", FILE_APPEND);
                        }
                    }

                }
            }
        }

        file_put_contents($txtStatsFileName, "\n", FILE_APPEND);

    }

    public function writeYearStatsWithDetails(string $txtStatsFileName, array $monthlyStats, Math $math): void
    {
        file_put_contents($txtStatsFileName, "Year stats with details:\n", FILE_APPEND);
        foreach ($monthlyStats as $year => $yearData) {
            file_put_contents($txtStatsFileName, $year . "\n", FILE_APPEND);
            foreach ($yearData as $dataKey => $dataValue) {
                if (!is_array($dataValue) || str_contains($dataKey, 'Avg')) {

                    if (in_array($dataKey, ['exposureTimeAvg', 'shutterSpeedValueAvg'])) {
                        file_put_contents($txtStatsFileName, '  ' . $dataKey . " = " . $math->convertDecimalToFraction($dataValue) . "\n", FILE_APPEND);
                    } else {
                        file_put_contents($txtStatsFileName, '  ' . $dataKey . " = " . $dataValue . "\n", FILE_APPEND);
                    }


                } elseif (in_array($dataKey, ['cameraVendor', 'cameraModel', 'lensNameCleared'])) {
                    file_put_contents($txtStatsFileName, '  ' . $dataKey . "\n", FILE_APPEND);
                    foreach ($dataValue as $dataValueKey => $dataValueValue) {
                        file_put_contents($txtStatsFileName, '   ' . $dataValueKey . " = " . $dataValueValue . " (" . $math->calculatePercentage($dataValueValue, $dataValue) . ")\n", FILE_APPEND);
                    }
                }

            }
        }
        file_put_contents($txtStatsFileName, "\n", FILE_APPEND);

    }

    public function writeOverallStats(string $txtStatsFileName, array $overallStats, Math $math): void
    {
        file_put_contents($txtStatsFileName, "Overall stats: \n", FILE_APPEND);

        foreach ($overallStats as $dataKey => $dataValue) {
            if (!is_array($dataValue) || str_contains($dataKey, 'Avg')) {
                file_put_contents($txtStatsFileName, '  ' . $dataKey . " = " . $dataValue . "\n", FILE_APPEND);
            } elseif (in_array($dataKey, ['cameraVendor', 'cameraModel', 'lensNameCleared'])) {
                file_put_contents($txtStatsFileName, '  ' . $dataKey . "\n", FILE_APPEND);
                foreach ($dataValue as $dataValueKey => $dataValueValue) {
                    file_put_contents($txtStatsFileName, '   ' . $dataValueKey . " = " . $dataValueValue . " (" . $math->calculatePercentage($dataValueValue, $dataValue) . ")\n", FILE_APPEND);
                }
            } else {
                if (in_array($dataKey, ['exposureTime', 'shutterSpeedValue'])) {
                    file_put_contents($txtStatsFileName, '  ' . $dataKey . "Avg = " . $math->convertDecimalToFraction($math->calculateAverage($dataValue)) . "\n", FILE_APPEND);
                } else {
                    file_put_contents($txtStatsFileName, '  ' . $dataKey . "Avg = " . $math->calculateAverage($dataValue) . "\n", FILE_APPEND);
                }
            }

        }

        file_put_contents($txtStatsFileName, "\n", FILE_APPEND);
    }



    public function writeStatsForCamerasAndLenses(string $txtStatsFileName, array $statsForCamerasAndLenses, Math $math): void
    {
        file_put_contents($txtStatsFileName, "Stats for cameras and lenses: \n", FILE_APPEND);
        file_put_contents($txtStatsFileName, "\n", FILE_APPEND);
        file_put_contents($txtStatsFileName, "Stats for cameras: \n", FILE_APPEND);
        foreach ($statsForCamerasAndLenses['cameraModels'] as $dataKey => $dataValue) {
            file_put_contents($txtStatsFileName, ' ' . $dataKey . "\n", FILE_APPEND);
            foreach ($dataValue as $subKey => $subValue) {
                file_put_contents($txtStatsFileName, '  ' . $subKey . "Avg = " . $math->calculateAverage($subValue) . "\n", FILE_APPEND);
            }
        }

        file_put_contents($txtStatsFileName, "\n", FILE_APPEND);
        file_put_contents($txtStatsFileName, "Stats for lenses: \n", FILE_APPEND);
        foreach ($statsForCamerasAndLenses['lensNames'] as $dataKey => $dataValue) {
            file_put_contents($txtStatsFileName, ' ' . $dataKey . "\n", FILE_APPEND);
            foreach ($dataValue as $subKey => $subValue) {
                file_put_contents($txtStatsFileName, '  ' . $subKey . "Avg = " . $math->calculateAverage($subValue) . "\n", FILE_APPEND);
            }
        }


        file_put_contents($txtStatsFileName, "\n", FILE_APPEND);
    }
}