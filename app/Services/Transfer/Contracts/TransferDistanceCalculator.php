<?php

namespace App\Services\Transfer\Contracts;

interface TransferDistanceCalculator
{
    /**
     * @return array{distance_km:float,duration_minutes:int}
     */
    public function between(float $originLat, float $originLng, float $destLat, float $destLng): array;
}

