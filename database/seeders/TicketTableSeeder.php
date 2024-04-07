<?php

namespace Database\Seeders;

use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class TicketTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('tickets')->insert([
            'id'        => Str::uuid()->toString(),
            'name'      => 'Konser Hutao',
            'price'     => 500000,
            'stock'     => 100,
        ]);
    }
}
