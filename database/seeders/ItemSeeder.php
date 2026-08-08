<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Item;
use Illuminate\Database\Seeder;

class ItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get categories
        $alatCategories = Category::where('type', 'alat')->pluck('id')->toArray();
        $bahanCategories = Category::where('type', 'bahan')->pluck('id')->toArray();

        // Alat items (no stock tracking, only units)
        $alatItems = [
            ['category_id' => $alatCategories[0], 'code' => 'MCS-001', 'name' => 'Mikroskop Binocular Olympus CX23', 'unit' => 'unit', 'location' => 'Ruang Mikroskopi'],
            ['category_id' => $alatCategories[0], 'code' => 'MCS-002', 'name' => 'Mikroskop Trinocular Nikon Eclipse Ei', 'unit' => 'unit', 'location' => 'Ruang Mikroskopi'],
            ['category_id' => $alatCategories[1], 'code' => 'SNF-001', 'name' => 'Sentrifus Mikro Eppendorf 5424R', 'unit' => 'unit', 'location' => 'Ruang Sentrifugasi'],
            ['category_id' => $alatCategories[1], 'code' => 'SNF-002', 'name' => 'Sentrifus Universal Hettich Rotina 380', 'unit' => 'unit', 'location' => 'Ruang Sentrifugasi'],
            ['category_id' => $alatCategories[2], 'code' => 'SPF-001', 'name' => 'Spektrofotometer UV-Vis Shimadzu UV-1800', 'unit' => 'unit', 'location' => 'Ruang Analisis'],
            ['category_id' => $alatCategories[2], 'code' => 'SPF-002', 'name' => 'Spektrofotometer Thermo Scientific Genesys 10S', 'unit' => 'unit', 'location' => 'Ruang Analisis'],
            ['category_id' => $alatCategories[3], 'code' => 'PHM-001', 'name' => 'pH Meter Portable Hanna Instruments HI98107', 'unit' => 'unit', 'location' => 'Ruang Analisis'],
            ['category_id' => $alatCategories[3], 'code' => 'PHM-002', 'name' => 'pH Meter Benchtop Mettler Toledo SevenExcellence', 'unit' => 'unit', 'location' => 'Ruang Analisis'],
            ['category_id' => $alatCategories[4], 'code' => 'TNG-001', 'name' => 'Timbangan Analitik Mettler Toledo XPR205DR', 'unit' => 'unit', 'location' => 'Ruang Pembobotan'],
            ['category_id' => $alatCategories[4], 'code' => 'TNG-002', 'name' => 'Timbangan Analitik Shimadzu AUW220D', 'unit' => 'unit', 'location' => 'Ruang Pembobotan'],
            ['category_id' => $alatCategories[5], 'code' => 'OVN-001', 'name' => 'Oven Memmert UF55 Plus', 'unit' => 'unit', 'location' => 'Ruang Inkubasi'],
            ['category_id' => $alatCategories[5], 'code' => 'OVN-002', 'name' => 'Inkubator Memmert IPS550', 'unit' => 'unit', 'location' => 'Ruang Inkubasi'],
            ['category_id' => $alatCategories[6], 'code' => 'BLK-001', 'name' => 'Blok Kalibrasi Temperatur Fluke 9142', 'unit' => 'unit', 'location' => 'Ruang Kalibrasi'],
            ['category_id' => $alatCategories[6], 'code' => 'TRM-001', 'name' => 'Termometer Standar PT100 Klasse A', 'unit' => 'unit', 'location' => 'Ruang Kalibrasi'],
            ['category_id' => $alatCategories[7], 'code' => 'EWS-001', 'name' => 'Emergency Eye Wash Station Hughes Safety', 'unit' => 'unit', 'location' => 'Area Darurat'],
            ['category_id' => $alatCategories[7], 'code' => 'SWR-001', 'name' => 'Safety Shower Hughes Safety', 'unit' => 'unit', 'location' => 'Area Darurat'],
            ['category_id' => $alatCategories[8], 'code' => 'LBU-001', 'name' => 'Labu Sungkai Pyrex 1000ml', 'unit' => 'set', 'location' => 'Gelas Peralatan'],
            ['category_id' => $alatCategories[8], 'code' => 'BKR-001', 'name' => 'Beaker Kimial Pyrex 500ml', 'unit' => 'set', 'location' => 'Gelas Peralatan'],
            ['category_id' => $alatCategories[9], 'code' => 'MLT-001', 'name' => 'Multimeter Digital Fluke 87V', 'unit' => 'unit', 'location' => 'Ruang Elektronika'],
            ['category_id' => $alatCategories[9], 'code' => 'OSC-001', 'name' => 'Osiloskop Rigol DS1054Z', 'unit' => 'unit', 'location' => 'Ruang Elektronika'],
        ];

        foreach ($alatItems as $item) {
            Item::create($item);
        }

        // Bahan items (with stock tracking)
        $bahanItems = [
            ['category_id' => $bahanCategories[0], 'code' => 'HCL-001', 'name' => 'Asam Klorida 37%', 'unit' => 'liter', 'stock_quantity' => 50, 'minimum_stock' => 5, 'location' => 'Gudang Kimia A'],
            ['category_id' => $bahanCategories[0], 'code' => 'NAOH-001', 'name' => 'Natrium Hidroksida Pellets', 'unit' => 'kg', 'stock_quantity' => 25, 'minimum_stock' => 3, 'location' => 'Gudang Kimia A'],
            ['category_id' => $bahanCategories[0], 'code' => 'H2SO4-001', 'name' => 'Asam Sulfat 98%', 'unit' => 'liter', 'stock_quantity' => 30, 'minimum_stock' => 3, 'location' => 'Gudang Kimia A'],
            ['category_id' => $bahanCategories[1], 'code' => 'AGR-001', 'name' => 'Agar-Agar Bacteriological', 'unit' => 'kg', 'stock_quantity' => 10, 'minimum_stock' => 2, 'location' => 'Gudang Bahan Kimia'],
            ['category_id' => $bahanCategories[1], 'code' => 'BRO-001', 'name' => 'Media Broth Nutrisi', 'unit' => 'liter', 'stock_quantity' => 20, 'minimum_stock' => 5, 'location' => 'Gudang Bahan Kimia'],
            ['category_id' => $bahanCategories[2], 'code' => 'TIP-001', 'name' => 'Tip Pipet Steril 0,1-10µl', 'unit' => 'box', 'stock_quantity' => 100, 'minimum_stock' => 20, 'location' => 'Gudang Konsumabel'],
            ['category_id' => $bahanCategories[2], 'code' => 'TUB-001', 'name' => 'Microtube 1,5ml Steril', 'unit' => 'box', 'stock_quantity' => 50, 'minimum_stock' => 10, 'location' => 'Gudang Konsumabel'],
            ['category_id' => $bahanCategories[2], 'code' => 'GLV-001', 'name' => 'Glove Nitril Non-Puter Large', 'unit' => 'box', 'stock_quantity' => 200, 'minimum_stock' => 50, 'location' => 'Gudang Konsumabel'],
            ['category_id' => $bahanCategories[3], 'code' => 'STD-001', 'name' => 'Standar Khlorida 1000ppm', 'unit' => 'liter', 'stock_quantity' => 15, 'minimum_stock' => 2, 'location' => 'Gudang Standar'],
            ['category_id' => $bahanCategories[3], 'code' => 'CRM-001', 'name' => 'Referensi Merkurium Air 5ppb', 'unit' => 'liter', 'stock_quantity' => 5, 'minimum_stock' => 1, 'location' => 'Gudang Standar'],
            ['category_id' => $bahanCategories[4], 'code' => 'N2-001', 'name' => 'Gas Nitrogen Ultra Pure 99,999%', 'unit' => 'meter3', 'stock_quantity' => 10, 'minimum_stock' => 2, 'location' => 'Tangki Gas'],
            ['category_id' => $bahanCategories[4], 'code' => 'H2O-001', 'name' => 'Air Ultrapure Type I 18,2 MOhm-cm', 'unit' => 'liter', 'stock_quantity' => 100, 'minimum_stock' => 20, 'location' => 'Sistem Air Lab'],
            ['category_id' => $bahanCategories[5], 'code' => 'STP-001', 'name' => 'Strip Test Glukosa Serum', 'unit' => 'box', 'stock_quantity' => 30, 'minimum_stock' => 5, 'location' => 'Gudang Kit Diagnostik'],
            ['category_id' => $bahanCategories[5], 'code' => 'ELS-001', 'name' => 'Kit ELISA Deteksi IgG', 'unit' => 'kit', 'stock_quantity' => 10, 'minimum_stock' => 2, 'location' => 'Gudang Kit Diagnostik'],
            ['category_id' => $bahanCategories[6], 'code' => 'BHK-001', 'name' => 'Bahan Baku untuk Reagen Protein', 'unit' => 'kg', 'stock_quantity' => 5, 'minimum_stock' => 1, 'location' => 'Gudang Produksi'],
            ['category_id' => $bahanCategories[7], 'code' => 'BOT-001', 'name' => 'Botol HDPE 100ml dengan Tutup', 'unit' => 'box', 'stock_quantity' => 500, 'minimum_stock' => 100, 'location' => 'Gudang Kemasan'],
        ];

        foreach ($bahanItems as $item) {
            Item::create($item);
        }

        $this->command->info('Created '.Item::count().' items');
    }
}
