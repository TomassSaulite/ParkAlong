<?php

namespace App\Support;

class Duration
{
    public static function humanize(int $minutes): string
    {
        $hours = intdiv(max(0, $minutes), 60);
        $remainingMinutes = max(0, $minutes) % 60;

        if ($hours === 0) {
            return "{$remainingMinutes}m";
        }

        if ($remainingMinutes === 0) {
            return "{$hours}h";
        }

        return "{$hours}h {$remainingMinutes}m";
    }
}
