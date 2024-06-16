<?php

namespace Sv\Photo\ExifStats;

class Converter
{
    public function parseFlash($flashCode) : string
    {
        return match ($flashCode) {
            0 => 'Flash did not fire',
            1 => 'Flash fired',
            5 => 'Strobe return light not detected',
            7 => 'Strobe return light detected',
            9 => 'Flash fired, compulsory flash mode',
            13 => 'Flash fired, compulsory flash mode, return light not detected',
            15 => 'Flash fired, compulsory flash mode, return light detected',
            16 => 'Flash did not fire, compulsory flash mode',
            24 => 'Flash did not fire, auto mode',
            25 => 'Flash fired, auto mode',
            29 => 'Flash fired, auto mode, return light not detected',
            31 => 'Flash fired, auto mode, return light detected',
            32 => 'No flash function',
            65 => 'Flash fired, red-eye reduction mode',
            default => 'Unknown flash setting',
        };
    }

    public function parseExposureProgram($exposureProgramCode) : string
    {
        return match ($exposureProgramCode) {
            0 => 'Not defined',
            1 => 'Manual',
            2 => 'Normal program',
            3 => 'Aperture priority',
            4 => 'Shutter priority',
            5 => 'Creative program (biased toward depth of field)',
            6 => 'Action program (biased toward fast shutter speed)',
            7 => 'Portrait mode (for close-up photos with the background out of focus)',
            8 => 'Landscape mode (for landscape photos with the background in focus)',
            default => 'Unknown exposure program',
        };
    }


    public function parseMeteringMode($meteringModeCode) : string
    {
        return match ($meteringModeCode) {
            0 => 'Unknown',
            1 => 'Average',
            2 => 'Center-weighted average',
            3 => 'Spot',
            4 => 'Multi-spot',
            5 => 'Pattern',
            6 => 'Partial',
            255 => 'Other',
            default => 'unknownMeteringMode',
        };
    }

    public function parseWhiteBalance($whiteBalanceCode) : string
    {
        return match ($whiteBalanceCode) {
            0 => 'Auto',
            1 => 'Manual',
            default => 'Unknown white balance',
        };
    }

    public function parseExposureMode($exposureModeCode) : string
    {
        return match ($exposureModeCode) {
            0 => 'Auto',
            1 => 'Manual',
            2 => 'Auto bracket',
            default => 'Unknown exposure mode',
        };
    }

    public function parseColorSpace($colorSpace) : string
    {
        return match ($colorSpace) {
            1 => 'sRGB',
            65535 => 'Adobe RGB',
            default => 'Unknown color space',
        };
    }

    public function reverseParseFlash($flashDescription) : int
    {
        return match ($flashDescription) {
            'Flash did not fire' => 0,
            'No Flash' => 0,
            'Flash fired' => 1,
            'Strobe return light not detected' => 5,
            'Strobe return light detected' => 7,
            'Flash fired, compulsory flash mode' => 9,
            'Flash fired, compulsory flash mode, return light not detected' => 13,
            'Flash fired, compulsory flash mode, return light detected' => 15,
            'Flash did not fire, compulsory flash mode' => 16,
            'Flash did not fire, auto mode' => 24,
            'Flash fired, auto mode' => 25,
            'Flash fired, auto mode, return light not detected' => 29,
            'Flash fired, auto mode, return light detected' => 31,
            'No flash function' => 32,
            'Flash fired, red-eye reduction mode' => 65,
            default => -1,
        };
    }

    public function reverseParseExposureProgram($exposureProgramDescription) : int
    {
        return match ($exposureProgramDescription) {
            'Not defined' => 0,
            'Manual' => 1,
            'Normal program' => 2,
            'Aperture priority' => 3,
            'Shutter priority' => 4,
            'Creative program (biased toward depth of field)' => 5,
            'Action program (biased toward fast shutter speed)' => 6,
            'Portrait mode (for close-up photos with the background out of focus)' => 7,
            'Landscape mode (for landscape photos with the background in focus)' => 8,
            default => -1,
        };
    }

    public function reverseParseMeteringMode($meteringModeDescription) : int
    {
        return match ($meteringModeDescription) {
            'Unknown' => 0,
            'Average' => 1,
            'Center-weighted average' => 2,
            'Spot' => 3,
            'Multi-spot' => 4,
            'Pattern' => 5,
            'Partial' => 6,
            'Other' => 255,
            default => -1,
        };
    }

    public function reverseParseWhiteBalance($whiteBalanceDescription) : int
    {
        return match ($whiteBalanceDescription) {
            'Auto' => 0,
            'Manual' => 1,
            default => -1,
        };
    }

    public function reverseParseExposureMode($exposureModeDescription) : int
    {
        return match ($exposureModeDescription) {
            'Auto' => 0,
            'Manual' => 1,
            'Auto bracket' => 2,
            default => -1,
        };
    }

    public function reverseParseColorSpace($colorSpaceDescription) : int
    {
        return match ($colorSpaceDescription) {
            'sRGB' => 1,
            'Adobe RGB' => 65535,
            default => -1,
        };
    }
}