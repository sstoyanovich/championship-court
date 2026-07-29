<?php

namespace App\Services;

use Carbon\Carbon;

class SpotlightOvrCalculator
{
    /**
     * Suggest max OVR based on date (optional helper for reference).
     * Actual max OVR should be provided as a parameter to the command.
     *
     * @param Carbon $date
     * @return int
     */
    public function suggestMaxOvr(Carbon $date): int
    {
        $month = $date->month;
        $weekInMonth = $this->getWeekInMonth($date);

        // Base OVR progression: November (86) to March (96)
        // Linear interpolation between months
        $baseOvr = 86 + (($month - 11) * 2.5);
        
        // If month is before November, use November baseline
        if ($month < 11) {
            $baseOvr = 86;
        }
        
        // If month is after March, cap at 99
        if ($month > 3) {
            $baseOvr = 96;
        }

        // Add weekly progression (+0.5 per week)
        $weekOffset = ($weekInMonth - 1) * 0.5;
        
        $suggestedOvr = min(99, round($baseOvr + $weekOffset));
        
        return max(86, $suggestedOvr); // Minimum 86
    }

    /**
     * Get the week number within the month (1-4).
     *
     * @param Carbon $date
     * @return int
     */
    private function getWeekInMonth(Carbon $date): int
    {
        $firstDay = $date->copy()->startOfMonth();
        $dayOfMonth = $date->day;
        
        // Calculate which week of the month (1-4)
        $weekInMonth = ceil($dayOfMonth / 7);
        
        return min(4, max(1, $weekInMonth));
    }
}
