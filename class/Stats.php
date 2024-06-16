<?php

namespace Sv\Photo\ExifStats;

class Stats
{
    public function generateInitialStats(array $filesData): array
    {
        $monthlyStats = [];
        foreach ($filesData as $data) {
            $year = (int)$data['dateY'];
            $month = (int)$data['dateM'];
            if (!isset($monthlyStats[$year]['monthly'][$month])) {
                $monthlyStats[$year]['monthly'][$month]['photosCount'] = 1;
            } else {
                $monthlyStats[$year]['monthly'][$month]['photosCount'] += 1;
            }
            if (!isset($monthlyStats[$year]['photosCount'])) {
                $monthlyStats[$year]['photosCount'] = 1;
            } else {
                $monthlyStats[$year]['photosCount'] += 1;
            }
        }

        $minYear = min(array_keys($monthlyStats));
        $maxYear = max(array_keys($monthlyStats));
        for ($currentYear = $minYear; $currentYear <= $maxYear; $currentYear++) {
            for ($currentMonth = 1; $currentMonth <= 12; $currentMonth++) {
                if (!isset($monthlyStats[$currentYear]['monthly'][$currentMonth]['photosCount'])) {
                    $monthlyStats[$currentYear]['monthly'][$currentMonth] = [];
                    $monthlyStats[$currentYear]['monthly'][$currentMonth]['photosCount'] = 0;
                }
            }
            ksort($monthlyStats[$currentYear]);
        }
        ksort($monthlyStats);
        return $monthlyStats;
    }

    public function generateDetailedStats(array $filesData, array &$monthlyStats, array &$overallStats, Math $math): void
    {

        $overallStats = [];
        foreach ($filesData as $data) {
            $year = (int)$data['dateY'];
            $month = (int)$data['dateM'];

            if (!isset($monthlyStats[$year]['monthly'][$month]['cameraVendor'][$data['cameraVendor']])) {
                $monthlyStats[$year]['monthly'][$month]['cameraVendor'][$data['cameraVendor']] = 1;
            } else {
                $monthlyStats[$year]['monthly'][$month]['cameraVendor'][$data['cameraVendor']] += 1;
            }
            if (!isset($monthlyStats[$year]['cameraVendor'][$data['cameraVendor']])) {
                $monthlyStats[$year]['cameraVendor'][$data['cameraVendor']] = 1;
            } else {
                $monthlyStats[$year]['cameraVendor'][$data['cameraVendor']] += 1;
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
            if (!isset($monthlyStats[$year]['cameraModel'][$data['cameraModel']])) {
                $monthlyStats[$year]['cameraModel'][$data['cameraModel']] = 1;
            } else {
                $monthlyStats[$year]['cameraModel'][$data['cameraModel']] += 1;
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
            if (!isset($monthlyStats[$year]['lensNameCleared'][$data['lensNameCleared']])) {
                $monthlyStats[$year]['lensNameCleared'][$data['lensNameCleared']] = 1;
            } else {
                $monthlyStats[$year]['lensNameCleared'][$data['lensNameCleared']] += 1;
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
            $monthlyStats[$year]['monthly'][$month]['megaPixelsHumanReadable'][] = $math->calculateMegapixels($data['width'], $data['height']);

            $monthlyStats[$year]['total']['size'][] = $data['size'];
            $monthlyStats[$year]['total']['height'][] = $data['height'];
            $monthlyStats[$year]['total']['width'][] = $data['width'];
            $monthlyStats[$year]['total']['pixels'][] = $data['height'] * $data['width'];
            $monthlyStats[$year]['total']['megaPixelsHumanReadable'][] = $math->calculateMegapixels($data['width'], $data['height']);

            $monthlyStats[$year]['monthly'][$month]['exposureTime'][] = str_contains($data['exposureTime'], '/') ? eval('return ' . $data['exposureTime'] . ';') : $data['exposureTime'];
            $monthlyStats[$year]['monthly'][$month]['fNumber'][] = $data['fNumber'];
            $monthlyStats[$year]['monthly'][$month]['iso'][] = $data['iso'];
            $monthlyStats[$year]['monthly'][$month]['focalLength'][] = $data['focalLength'];
            $monthlyStats[$year]['monthly'][$month]['shutterSpeedValue'][] = str_contains($data['shutterSpeedValue'], '/') ? eval('return ' . $data['shutterSpeedValue'] . ';') : $data['shutterSpeedValue'];
            $monthlyStats[$year]['monthly'][$month]['apertureValue'][] = $data['apertureValue'];

            $monthlyStats[$year]['total']['exposureTime'][] = str_contains($data['exposureTime'], '/') ? eval('return ' . $data['exposureTime'] . ';') : $data['exposureTime'];
            $monthlyStats[$year]['total']['fNumber'][] = $data['fNumber'];
            $monthlyStats[$year]['total']['iso'][] = $data['iso'];
            $monthlyStats[$year]['total']['focalLength'][] = $data['focalLength'];
            $monthlyStats[$year]['total']['shutterSpeedValue'][] = str_contains($data['shutterSpeedValue'], '/') ? eval('return ' . $data['shutterSpeedValue'] . ';') : $data['shutterSpeedValue'];
            $monthlyStats[$year]['total']['apertureValue'][] = $data['apertureValue'];

            $overallStats['exposureTime'][] = str_contains($data['exposureTime'], '/') ? eval('return ' . $data['exposureTime'] . ';') : $data['exposureTime'];
            $overallStats['fNumber'][] = $data['fNumber'];
            $overallStats['iso'][] = $data['iso'];
            $overallStats['focalLength'][] = $data['focalLength'];
            $overallStats['shutterSpeedValue'][] = str_contains($data['shutterSpeedValue'], '/') ? eval('return ' . $data['shutterSpeedValue'] . ';') : $data['shutterSpeedValue'];
            $overallStats['apertureValue'][] = $data['apertureValue'];

        }

        foreach ($monthlyStats as $year => $months) {
            foreach ($months['monthly'] as $month => $data) {

                if (!isset($monthlyStats[$year]['monthly'][$month]['photosCount'])) {
                    $monthlyStats[$year]['monthly'][$month]['photosCount'] = 0;
                }

                $monthlyStats[$year]['monthly'][$month]['sizeAvg'] = $math->calculateAverage($monthlyStats[$year]['monthly'][$month]['size'] ?? []);
                $monthlyStats[$year]['monthly'][$month]['sizeAvgHumanReadable'] = $math->formatBytes($monthlyStats[$year]['monthly'][$month]['sizeAvg']);
                $monthlyStats[$year]['monthly'][$month]['heightAvg'] = $math->calculateAverage($monthlyStats[$year]['monthly'][$month]['height'] ?? []);
                $monthlyStats[$year]['monthly'][$month]['widthAvg'] = $math->calculateAverage($monthlyStats[$year]['monthly'][$month]['width'] ?? []);
                $monthlyStats[$year]['monthly'][$month]['pixelsAvg'] = $math->calculateAverage($monthlyStats[$year]['monthly'][$month]['pixels'] ?? []);
                $monthlyStats[$year]['monthly'][$month]['pixelsAvgHumanReadable'] = $math->calculateMegapixels($monthlyStats[$year]['monthly'][$month]['widthAvg'], $monthlyStats[$year]['monthly'][$month]['heightAvg']);

                $monthlyStats[$year]['monthly'][$month]['exposureTimeAvg'] = $math->calculateAverage($monthlyStats[$year]['monthly'][$month]['exposureTime'] ?? []);
                $monthlyStats[$year]['monthly'][$month]['fNumberAvg'] = $math->calculateAverage($monthlyStats[$year]['monthly'][$month]['fNumber'] ?? []);
                $monthlyStats[$year]['monthly'][$month]['isoAvg'] = $math->calculateAverage($monthlyStats[$year]['monthly'][$month]['iso'] ?? []);
                $monthlyStats[$year]['monthly'][$month]['focalLengthAvg'] = $math->calculateAverage($monthlyStats[$year]['monthly'][$month]['focalLength'] ?? []);
                $monthlyStats[$year]['monthly'][$month]['shutterSpeedValueAvg'] = $math->calculateAverage($monthlyStats[$year]['monthly'][$month]['shutterSpeedValue'] ?? []);
                $monthlyStats[$year]['monthly'][$month]['apertureValueAvg'] = $math->calculateAverage($monthlyStats[$year]['monthly'][$month]['apertureValue'] ?? []);
            }
        }


        foreach ($monthlyStats as $year => $months) {

            $monthlyStats[$year]['sizeAvg'] = $math->calculateAverage($monthlyStats[$year]['total']['size'] ?? []);
            $monthlyStats[$year]['sizeAvgHumanReadable'] = $math->formatBytes($monthlyStats[$year]['sizeAvg']);
            $monthlyStats[$year]['heightAvg'] = $math->calculateAverage($monthlyStats[$year]['total']['height'] ?? []);
            $monthlyStats[$year]['widthAvg'] = $math->calculateAverage($monthlyStats[$year]['total']['width'] ?? []);
            $monthlyStats[$year]['pixelsAvg'] = $math->calculateAverage($monthlyStats[$year]['total']['pixels'] ?? []);
            $monthlyStats[$year]['pixelsAvgHumanReadable'] = $math->calculateMegapixels($monthlyStats[$year]['heightAvg'], $monthlyStats[$year]['widthAvg']);

            $monthlyStats[$year]['exposureTimeAvg'] = $math->calculateAverage($monthlyStats[$year]['total']['exposureTime'] ?? []);
            $monthlyStats[$year]['fNumberAvg'] = $math->calculateAverage($monthlyStats[$year]['total']['fNumber'] ?? []);
            $monthlyStats[$year]['isoAvg'] = $math->calculateAverage($monthlyStats[$year]['total']['iso'] ?? []);
            $monthlyStats[$year]['focalLengthAvg'] = $math->calculateAverage($monthlyStats[$year]['total']['focalLength'] ?? []);
            $monthlyStats[$year]['shutterSpeedValueAvg'] = $math->calculateAverage($monthlyStats[$year]['total']['shutterSpeedValue'] ?? []);
            $monthlyStats[$year]['apertureValueAvg'] = $math->calculateAverage($monthlyStats[$year]['total']['apertureValue'] ?? []);
        }

    }

    public function generateCsvStats(array $monthlyStats): array
    {
        $csvReadyStats = [];
        $cameraVendors = [];
        $cameraModels = [];
        $lensNames = [];
        foreach ($monthlyStats as $year => $month) {
            foreach ($month['monthly'] as $monthNumber => $data) {
                $csvReadyStats[$year . '_' . $monthNumber] = [
                    'year' => $year,
                    'month' => $monthNumber,
                    'photosCount' => $data['photosCount'] ?? 0,
                    'sizeAvg' => $data['sizeAvg'] ?? 0,
                    'sizeAvgHumanReadable' => $data['sizeAvgHumanReadable'] ?? '0 B',
                    'heightAvg' => $data['heightAvg'] ?? 0,
                    'widthAvg' => $data['widthAvg'] ?? 0,
                    'pixelsAvg' => $data['pixelsAvg'] ?? 0,
                    'pixelsAvgHumanReadable' => $data['pixelsAvgHumanReadable'] ?? '0 MP',
                    'exposureTimeAvg' => $data['exposureTimeAvg'] ?? 0,
                    'fNumberAvg' => $data['fNumberAvg'] ?? 0,
                    'isoAvg' => $data['isoAvg'] ?? 0,
                    'focalLengthAvg' => $data['focalLengthAvg'] ?? 0,
                    'shutterSpeedValueAvg' => $data['shutterSpeedValueAvg'] ?? 0,
                    'apertureValueAvg' => $data['apertureValueAvg'] ?? 0,
                ];
                if (isset($data['cameraVendor'])) {
                    foreach ($data['cameraVendor'] as $vendorName => $stub) {
                        $cameraVendors[$vendorName] = 0;
                    }
                }
                if (isset($data['cameraModel'])) {
                    foreach ($data['cameraModel'] as $modelName => $stub) {
                        $cameraModels[$modelName] = 0;
                    }

                }
                if (isset($data['lensNameCleared'])) {
                    foreach ($data['lensNameCleared'] as $lensName => $stub) {
                        $lensNames[$lensName] = 0;
                    }

                }
            }

        }

        foreach ($monthlyStats as $year => $month) {
            foreach ($month['monthly'] as $monthNumber => $data) {
                $csvReadyStats[$year . '_' . $monthNumber]['cameraVendors'] = $cameraVendors;
                $csvReadyStats[$year . '_' . $monthNumber]['cameraModels'] = $cameraModels;
                $csvReadyStats[$year . '_' . $monthNumber]['lensNames'] = $lensNames;

                if (isset($data['cameraVendor'])) {
                    foreach ($data['cameraVendor'] as $vendor => $count) {
                        $csvReadyStats[$year . '_' . $monthNumber]['cameraVendors'][$vendor] = $count;
                    }
                }

                if (isset($data['cameraModel'])) {
                    foreach ($data['cameraModel'] as $model => $count) {
                        $csvReadyStats[$year . '_' . $monthNumber]['cameraModels'][$model] = $count;
                    }
                }

                if (isset($data['lensNameCleared'])) {
                    foreach ($data['lensNameCleared'] as $lens => $count) {
                        $csvReadyStats[$year . '_' . $monthNumber]['lensNames'][$lens] = $count;
                    }
                }
            }
        }
        return $csvReadyStats;
    }

    public function generateStatsForCamerasAndLenses(array $filesData): array
    {
        $cameraModels = [];
        $lensNames = [];
        foreach ($filesData as $file) {
            $cameraModels[$file['cameraModel']]['iso'][] = $file['iso'];
            $cameraModels[$file['cameraModel']]['exposureTime'][] = $file['exposureTime'];
            $cameraModels[$file['cameraModel']]['fNumber'][] = $file['fNumber'];
            $cameraModels[$file['cameraModel']]['focalLength'][] = $file['focalLength'];

            $lensNames[$file['lensNameCleared']]['iso'][] = $file['iso'];
            $lensNames[$file['lensNameCleared']]['exposureTime'][] = $file['exposureTime'];
            $lensNames[$file['lensNameCleared']]['fNumber'][] = $file['fNumber'];
            $lensNames[$file['lensNameCleared']]['focalLength'][] = $file['focalLength'];
        }

        return [
            'cameraModels' => $cameraModels,
            'lensNames' => $lensNames,
        ];
    }

}