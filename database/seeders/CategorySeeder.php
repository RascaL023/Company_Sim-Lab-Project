<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Alat categories
        $alatCategories = [
            ['name' => 'Mikroskop', 'type' => 'alat', 'description' => 'Mikroskop optik dan elektron untuk pengamatan sampel'],
            ['name' => 'Sentrifus', 'type' => 'alat', 'description' => 'Sentrifus untuk pemisahan komponen sampel'],
            ['name' => 'Spektrofotometer', 'type' => 'alat', 'description' => 'Spektrofotometer UV-Vis untuk analisis'],
            ['name' => 'pH Meter', 'type' => 'alat', 'description' => 'pH meter portable dan benchtop'],
            ['name' => 'Timbangan Analitik', 'type' => 'alat', 'description' => 'Timbangan presisi tinggi untuk pembobotan'],
            ['name' => 'Oven & Inkubator', 'type' => 'alat', 'description' => 'Oven pengering dan inkubator kultur'],
            ['name' => 'Alat Ukur Kalibrasi', 'type' => 'alat', 'description' => 'Blok kalibrasi, termometer standar, dll'],
            ['name' => 'Safety Equipment', 'type' => 'alat', 'description' => 'Eye wash, shower, extinguisher, PPE'],
            ['name' => 'Glassware', 'type' => 'alat', 'description' => 'Labu, beaker, pipet, buret, dll'],
            ['name' => 'Peralatan Elektronik', 'type' => 'alat', 'description' => 'Multimeter, osiloskop, power supply'],
        ];

        foreach ($alatCategories as $cat) {
            Category::create($cat);
        }

        // Bahan categories
        $bahanCategories = [
            ['name' => 'Reagen Kimia', 'type' => 'bahan', 'description' => 'Asam, basa, pelarut organik, indikator'],
            ['name' => 'Media Kultur', 'type' => 'bahan', 'description' => 'Agar, broth, media selektif dan diferensial'],
            ['name' => 'Konsumabel Lab', 'type' => 'bahan', 'description' => 'Tip pipet, microtube, petri dish, glove'],
            ['name' => 'Standar & Referensi', 'type' => 'bahan', 'description' => 'Standar kalibrasi, CRM, bahan referensi'],
            ['name' => 'Gas & Cairan Teknis', 'type' => 'bahan', 'description' => 'Nitrogen, helium, air ultrapure, argon'],
            ['name' => 'Kit Uji Cepat', 'type' => 'bahan', 'description' => 'Test strip, rapid test kit, ELISA kit'],
            ['name' => 'Bahan Baku Produksi', 'type' => 'bahan', 'description' => 'Bahan baku untuk produksi reagen/kit'],
            ['name' => 'Kemasan & Label', 'type' => 'bahan', 'description' => 'Botol, vakum, label, seal, kotak'],
        ];

        foreach ($bahanCategories as $cat) {
            Category::create($cat);
        }

        $this->command->info('Created '.Category::count().' categories');
    }
}
