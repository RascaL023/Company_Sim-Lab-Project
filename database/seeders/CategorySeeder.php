<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Beberapa kategori representatif: 2 alat + 2 bahan.
     */
    public function run(): void
    {
        $categories = [
            ['name' => 'Mikroskop', 'type' => 'alat', 'description' => 'Mikroskop untuk pengamatan sampel'],
            ['name' => 'Sentrifus', 'type' => 'alat', 'description' => 'Sentrifus untuk pemisahan komponen sampel'],
            ['name' => 'Reagen Kimia', 'type' => 'bahan', 'description' => 'Asam, basa, dan pelarut kimia'],
            ['name' => 'Konsumabel Lab', 'type' => 'bahan', 'description' => 'Tip pipet, microtube, dan barang habis pakai'],
        ];

        foreach ($categories as $cat) {
            Category::create($cat);
        }

        $this->command->info('Created '.Category::count().' categories');
    }
}
