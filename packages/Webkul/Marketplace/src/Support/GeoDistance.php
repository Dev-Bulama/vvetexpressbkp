<?php

namespace Webkul\Marketplace\Support;

class GeoDistance
{
    /**
     * Great-circle distance between two points in kilometres. This is a
     * straight-line estimate, not a real road distance - callers that have
     * a routing API available (Google Routes) should prefer that and only
     * fall back to this when no route service is configured.
     */
    public static function haversineKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadiusKm = 6371;

        $latDelta = deg2rad($lat2 - $lat1);
        $lngDelta = deg2rad($lng2 - $lng1);

        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($lngDelta / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadiusKm * $c;
    }
}
