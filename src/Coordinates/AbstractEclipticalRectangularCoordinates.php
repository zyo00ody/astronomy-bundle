<?php

namespace Andrmoel\AstronomyBundle\Coordinates;

class AbstractEclipticalRectangularCoordinates
{
    protected $X = 0;
    protected $Y = 0;
    protected $Z = 0;

    public function __construct(float $X, float $Y, float $Z)
    {
        $this->X = $X;
        $this->Y = $Y;
        $this->Z = $Z;
    }

    public function getX(): float
    {
        return $this->X;
    }

    public function getY(): float
    {
        return $this->Y;
    }

    public function getZ(): float
    {
        return $this->Z;
    }
}
