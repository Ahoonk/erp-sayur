<?php

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $units = [
            ['name' => 'Kilogram', 'symbol' => 'KG'],
            ['name' => 'Gram', 'symbol' => 'G'],
            ['name' => 'Liter', 'symbol' => 'L'],
            ['name' => 'Milliliter', 'symbol' => 'ML'],
            ['name' => 'Pcs', 'symbol' => 'PCS'],
            ['name' => 'Pack', 'symbol' => 'PACK'],
            ['name' => 'Box', 'symbol' => 'BOX'],
            ['name' => 'Tray', 'symbol' => 'TRAY'],
        ];

        foreach ($units as $unit) {
            Unit::firstOrCreate(['name' => $unit['name']], ['symbol' => $unit['symbol']]);
        }
    }
}