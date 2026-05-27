<?php

namespace Database\Seeders;

use App\Support\PowerGrid\PowerGridDataBootstrapper;
use Illuminate\Database\Seeder;

class PowerGridSeeder extends Seeder
{
    public function run(): void
    {
        app(PowerGridDataBootstrapper::class)->seed();
    }
}
