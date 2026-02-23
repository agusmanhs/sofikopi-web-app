<?php

namespace App\Helpers;

/**
 * Helper untuk kalkulasi lokasi GPS
 */
class LocationHelper
{
    /**
     * Menghitung jarak antara dua koordinat menggunakan Haversine Formula
     * 
     * @param float $lat1 Latitude titik 1
     * @param float $lon1 Longitude titik 1
     * @param float $lat2 Latitude titik 2
     * @param float $lon2 Longitude titik 2
     * @return float Jarak dalam meter
     */
    public static function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371000; // Radius bumi dalam meter

        $latFrom = deg2rad($lat1);
        $latTo = deg2rad($lat2);
        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos($latFrom) * cos($latTo) *
             sin($lonDelta / 2) * sin($lonDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c; // Jarak dalam meter
    }

    /**
     * Check apakah lokasi dalam radius yang ditentukan
     * 
     * @param float $userLat Latitude user
     * @param float $userLon Longitude user
     * @param float $targetLat Latitude target (kantor)
     * @param float $targetLon Longitude target (kantor)
     * @param int $radiusMeter Radius dalam meter
     * @return bool
     */
    public static function isWithinRadius(
        $userLat, 
        $userLon, 
        $targetLat, 
        $targetLon, 
        $radiusMeter
    ) {
        $distance = self::calculateDistance($userLat, $userLon, $targetLat, $targetLon);
        return $distance <= $radiusMeter;
    }

    /**
     * Mendapatkan jarak ke titik terdekat dari array lokasi
     * 
     * @param float $userLat Latitude user
     * @param float $userLon Longitude user
     * @param array $locations Array of ['latitude', 'longitude', 'radius_meter', ...]
     * @return array|null ['location' => ..., 'distance' => ..., 'is_within_radius' => ...]
     */
    public static function getNearestLocation($userLat, $userLon, array $locations)
    {
        if (empty($locations)) {
            return null;
        }

        $nearest = null;
        $minDistance = PHP_FLOAT_MAX;

        foreach ($locations as $location) {
            $distance = self::calculateDistance(
                $userLat, 
                $userLon, 
                (float) $location['latitude'], 
                (float) $location['longitude']
            );

            if ($distance < $minDistance) {
                $minDistance = $distance;
                $nearest = [
                    'location' => $location,
                    'distance' => round($distance, 2),
                    'is_within_radius' => $distance <= ($location['radius_meter'] ?? 100),
                ];
            }
        }

        return $nearest;
    }

    /**
     * Format jarak untuk display
     * 
     * @param float $meters Jarak dalam meter
     * @return string
     */
    public static function formatDistance($meters)
    {
        if ($meters < 1000) {
            return round($meters) . ' m';
        }
        return round($meters / 1000, 2) . ' km';
    }
}
