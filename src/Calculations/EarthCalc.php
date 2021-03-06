<?php

namespace Andrmoel\AstronomyBundle\Calculations;

use Andrmoel\AstronomyBundle\Utils\AngleUtil;

class EarthCalc implements EarthCalcInterface
{
    /**
     * Same as sun's
     * @return float
     */
    public static function getMeanAnomaly(float $T): float
    {
        // Meeus chapter 22
//        $M = 357.52772
//            + 35999.050340 * $T
//            - 0.0001603 * pow($T, 2)
//            - pow($T, 3) / 300000;

        // Meeus 47.4
        $M = 357.5291092
            + 35999.0502909 * $T
            - 0.0001536 * pow($T, 2)
            + pow($T, 3) / 2449000;
        $M = AngleUtil::normalizeAngle($M);

        return $M;
    }

    public static function getEccentricity(float $T): float
    {
        // Meeus 25.4
        $e = 0.016708634
            - 0.000042037 * $T
            - 0.0000001267 * pow($T, 2);

        return $e;
    }

    public static function getLongitudeOfPerihelionOfOrbit(float $T): float
    {
        // Meeus 23
        $pi = 102.93735
            + 1.71946 * $T
            + 0.00046 * pow($T, 2);

        return $pi;
    }

    public static function getMeanObliquityOfEcliptic(float $T): float
    {
        $U = $T / 100;

        // Meeus 22.3
        $e0 = 84381.448
            - 4680.93 * $U
            - 1.55 * pow($U, 2)
            + 1999.25 * pow($U, 3)
            - 51.38 * pow($U, 4)
            - 249.67 * pow($U, 5)
            - 39.05 * pow($U, 6)
            + 7.12 * pow($U, 7)
            + 27.87 * pow($U, 8)
            + 5.79 * pow($U, 9)
            + 2.45 * pow($U, 10);
        $e0 = $e0 / 3600;

        return $e0;
    }

    public static function getTrueObliquityOfEcliptic(float $T): float
    {
        $e0 = self::getMeanObliquityOfEcliptic($T);
        $sumEps = self::getNutationInObliquity($T);

        // Meeus chapter 22
        $e = $e0 + $sumEps;

        return $e;
    }

    public static function getNutationInLongitude(float $T): float
    {
        // Meeus chapter 22
        $D = MoonCalc::getMeanElongation($T);
        $Msun = SunCalc::getMeanAnomaly($T);
        $Mmoon = MoonCalc::getMeanAnomaly($T);
        $F = MoonCalc::getArgumentOfLatitude($T);
        // Longitude of the ascending node of moon's mean orbit on ecliptic
        $O = 125.04452
            - 1934.136261 * $T
            + 0.0020708 * pow($T, 2)
            + pow($T, 3) / 450000;

        $sumPhi = 0;
        foreach (self::ARGUMENTS_NUTATION as $args) {
            $argMmoon = $args[0]; // Mean anomaly of moon
            $argMsun = $args[1]; // Mean anomaly of sun
            $argF = $args[2]; // Mean argument of perigee
            $argD = $args[3]; // Mean elongation of moon
            $argO = $args[4]; // Mean length of ascending knot of moon's orbit
            $argPhi1 = $args[5];
            $argPhi2 = $args[6];

            $tmpSum = $argD * $D + $argMsun * $Msun + $argMmoon * $Mmoon + $argF * $F + $argO * $O;

            $sumPhi += sin(deg2rad($tmpSum)) * ($argPhi1 + $argPhi2 * $T);
        }

        $sumPhi *= 0.0001 / 3600;

        return $sumPhi;
    }

    public static function getNutationInObliquity(float $T): float
    {
        // Meeus chapter 22
        $D = MoonCalc::getMeanElongation($T);
        $Msun = SunCalc::getMeanAnomaly($T);
        $Mmoon = MoonCalc::getMeanAnomaly($T);
        $F = MoonCalc::getArgumentOfLatitude($T);
        // Longitude of the ascending node of moon's mean orbit on ecliptic
        $O = 125.04452
            - 1934.136261 * $T
            + 0.0020708 * pow($T, 2)
            + pow($T, 3) / 450000;

        $sumEps = 0;
        foreach (self::ARGUMENTS_NUTATION as $args) {
            $argMmoon = $args[0]; // Mean anomaly of moon
            $argMsun = $args[1]; // Mean anomaly of sun
            $argF = $args[2]; // Mean argument of perigee
            $argD = $args[3]; // Mean elongation of moon
            $argO = $args[4]; // Mean length of ascending knot of moon's orbit
            $argEps1 = $args[7];
            $argEps2 = $args[8];

            $tmpSum = $argD * $D + $argMsun * $Msun + $argMmoon * $Mmoon + $argF * $F + $argO * $O;

            $sumEps += cos(deg2rad($tmpSum)) * ($argEps1 + $argEps2 * $T);
        }

        $sumEps *= 0.0001 / 3600;

        return $sumEps;
    }

    /**
     * Get equation of time [degrees]
     * @param float $T
     * @return float
     */
    public static function getEquationOfTimeInDegrees(float $T): float
    {
        $L0 = SunCalc::getMeanLongitude($T);
        $rightAscension = SunCalc::getApparentRightAscension($T);

        // TODO Use method with higher accuracy (Meeus p.166) 25.9
//        $rightAscension = 198.378178;

        $dPhi = EarthCalc::getNutationInLongitude($T);
        $e = EarthCalc::getTrueObliquityOfEcliptic($T);
        $eRad = deg2rad($e);

        // Meeus 28.1
        $E = $L0 - 0.0057183 - $rightAscension + $dPhi * cos($eRad);

        return $E;
    }

    /**
     * Get equation of time [minutes]
     * @param float $T
     * @return float
     */
    public static function getEquationOfTimeInMinutes(float $T): float
    {
        $E = self::getEquationOfTimeInDegrees($T);

        $Emin = $E / 360 * 1440;

        return $Emin;
    }
}
