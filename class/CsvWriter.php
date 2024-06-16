<?php

namespace Sv\Photo\ExifStats;

class CsvWriter
{
    public function writeCsvData(string $csvDetailsFileName, array &$filesData): void
    {
        if (is_file($csvDetailsFileName)) {
            unlink($csvDetailsFileName);
        }

        $fileResource = fopen($csvDetailsFileName, 'w');
        fputcsv($fileResource, array_keys(reset($filesData)));
        fclose($fileResource);

        $fileResource = fopen($csvDetailsFileName, 'a');
        foreach ($filesData as $data) {
            fputcsv($fileResource, $data);
        }
        fclose($fileResource);

    }

    public function writeCsvStats(string $csvStatsFileName, array &$csvReadyStats): void
    {
        if (is_file($csvStatsFileName)) {
            unlink($csvStatsFileName);
        }

        $oneLine = reset($csvReadyStats);
        $headerArray = [];
        foreach ($oneLine as $key => $value) {
            if (!is_array($value)) {
                $headerArray[] = $key;
            } else {
                foreach ($value as $subKey => $subValue) {
                    $headerArray[] = $key . ' "' . $subKey . '"';
                }
            }
        }

        $fileResource = fopen($csvStatsFileName, 'w');
        fputcsv($fileResource, $headerArray);
        fclose($fileResource);

        $fileResource = fopen($csvStatsFileName, 'a');
        foreach ($csvReadyStats as $value) {
            $dataArray = [];
            foreach ($value as $subValue) {
                if (!is_array($subValue)) {
                    $dataArray[] = $subValue;
                } else {
                    foreach ($subValue as $subSubValue) {
                        $dataArray[] = $subSubValue;
                    }
                }
            }
            fputcsv($fileResource, $dataArray);
        }
        fclose($fileResource);

    }



}