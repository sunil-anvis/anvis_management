<?php

namespace App\Services;

use InvalidArgumentException;

class AmountCalculator
{
    public function calculate(
        string $buyType,
        string $deliverable,
        float $volume,
        float $bid,
        float $buyingPrice
    ): float {

        $buyType = strtoupper(trim($buyType));
        $deliverable = strtolower(trim($deliverable));

        $mapping = [
            'CPC'   => 'clicks',
            'CPM'   => 'impressions',
            'CPV'   => 'views',
            'CPE'   => 'engagements',
            'CPL'   => 'leads',
            'FIXED' => 'fixed',
        ];

        if (!isset($mapping[$buyType])) {
            throw new InvalidArgumentException('Invalid Buy Type.');
        }

        // Validate Buy Type & Deliverable
        if ($mapping[$buyType] !== $deliverable) {
            throw new InvalidArgumentException(
                "Deliverable '{$deliverable}' is not valid for Buy Type '{$buyType}'."
            );
        }

        switch ($buyType) {

            case 'CPM':
                return ($volume * $bid) / 1000;

            case 'FIXED':
                return $buyingPrice;

            case 'CPC':
            case 'CPV':
            case 'CPE':
            case 'CPL':
                return $volume * $bid;

            default:
                throw new InvalidArgumentException('Calculation not available.');
        }
    }
}
