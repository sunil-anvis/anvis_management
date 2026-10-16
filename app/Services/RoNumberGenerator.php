<?php

namespace App\Services;

use App\Models\RoForm;
use Carbon\Carbon;

class RoNumberGenerator {


    public function generate(): string{

        $today = Carbon::now();

        if($today->month >=4){
            $startYear = $today->format('y');
            $endYear = $today->copy()->addYear()->format('y');
        }else{
            $startYear = $today->copy()->subYear()->format('y');
            $endYear = $today()->format('y');
        }

        $prefix = "AD{$startYear}{$endYear}";

        $lastRo = RoForm::where('ro_number','like', $prefix .'%')->latest('ro_number')->first();

        if(!$lastRo){
            $number = 1;
        }else{
            $lastNumber = (int) substr($lastRo->ro_number, -3);
            $number = $lastNumber + 1;
        }

        return $prefix. str_pad($number, 3, '0', STR_PAD_LEFT);
    }

}
