<?php

namespace App\Helpers;

class TimeHelper
{
    /**
     * Format a number of minutes as "Xh Ym" or "Ym".
     * e.g. 75 → "1h 15m", 20 → "20m"
     */
    public static function formatMinutes(int $minutes): string
    {
        if ($minutes <= 0) {
            return '0m';
        }

        $hours = intdiv($minutes, 60);
        $mins  = $minutes % 60;

        if ($hours > 0 && $mins > 0) {
            return "{$hours}h {$mins}m";
        }

        if ($hours > 0) {
            return "{$hours}h";
        }

        return "{$mins}m";
    }
}
