<?php

namespace App\Services;

use App\Models\RoForm;
use Carbon\Carbon;

class RoNumberGenerator
{
    /**
     * Generate the next available RO number for the current fiscal year.
     * If an RO is cancelled (and not deleted), its RO number can be reused.
     * RO numbers that are approved, rejected, deleted, or active cannot be reused.
     */
    public function generate(): string
    {
        $today = Carbon::now();

        if ($today->month >= 4) {
            $startYear = $today->format('y');
            $endYear = $today->copy()->addYear()->format('y');
        } else {
            $startYear = $today->copy()->subYear()->format('y');
            $endYear = $today->format('y');
        }

        $prefix = "AD{$startYear}{$endYear}";

        // Collect all taken numbers (status != cancelled OR deleted_at is not null)
        $takenNumbers = RoForm::withTrashed()
            ->where('ro_number', 'like', $prefix . '%')
            ->where(function ($query) {
                $query->where('status', '!=', 'cancelled')
                    ->orWhereNotNull('deleted_at');
            })
            ->pluck('ro_number')
            ->map(function ($roNumber) use ($prefix) {
                $numStr = substr($roNumber, strlen($prefix));
                return is_numeric($numStr) ? (int) $numStr : null;
            })
            ->filter(fn ($num) => !is_null($num) && $num > 0)
            ->flip()
            ->all();

        // Find the lowest positive integer that is not taken
        $number = 1;
        while (isset($takenNumbers[$number])) {
            $number++;
        }

        return $prefix . str_pad($number, 3, '0', STR_PAD_LEFT);
    }
}

