<?php

namespace App\Services\Monitoring;

/**
 * Single sursa pentru regula de bucket / poll interval.
 * Pe baza diferentei (in secunde) intre $to si $from, intoarce dimensiunea
 * bucket-ului in secunde. Aceeasi valoare e folosita drept interval de poll
 * pe frontend.
 */
class BucketResolver
{
    public static function secondsFor(int $diffSeconds): int
    {
        $minutes = max(0, $diffSeconds) / 60;

        return match (true) {
            $minutes <  20     => 1,
            $minutes <  100    => 5,
            $minutes <  720    => 60,
            $minutes <  4320   => 300,
            $minutes <  20160  => 900,
            $minutes <  86400  => 3600,
            default            => 86400,
        };
    }

    /**
     * Varianta pentru process_metrics sample-uite la 15s:
     * minim 15s sub 20 min (granularitatea sursei), apoi reguli identice.
     */
    public static function secondsForProcess(int $diffSeconds): int
    {
        $minutes = max(0, $diffSeconds) / 60;

        return match (true) {
            $minutes <  20     => 15,
            $minutes <  100    => 60,
            $minutes <  720    => 300,
            $minutes <  4320   => 900,
            $minutes <  20160  => 3600,
            $minutes <  86400  => 14400,
            default            => 86400,
        };
    }

    /**
     * Varianta pentru metrice sample-uite 1/min (ex. disk_usage):
     * minim 60s sub 12h (granularitatea sursei), apoi reguli identice.
     */
    public static function secondsForMinutely(int $diffSeconds): int
    {
        $minutes = max(0, $diffSeconds) / 60;

        return match (true) {
            $minutes <  720    => 60,
            $minutes <  4320   => 300,
            $minutes <  20160  => 900,
            $minutes <  86400  => 3600,
            default            => 86400,
        };
    }

    /**
     * Format de label pe axa X a chart-urilor, in functie de bucket si fereastra.
     */
    public static function labelFormat(int $bucketSeconds, int $diffSeconds): string
    {
        if ($bucketSeconds < 60)     return 'H:i:s';
        if ($diffSeconds   < 86400)  return 'H:i';
        if ($bucketSeconds >= 86400) return 'M j';
        return 'M j H:i';
    }
}
