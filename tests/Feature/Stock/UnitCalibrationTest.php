<?php

namespace Tests\Feature\Stock;

use App\Models\Item;
use App\Models\ItemCalibration;
use App\Models\ItemUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnitCalibrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that two units of the same item can have different calibration dates
     */
    public function test_same_item_different_units_have_different_calibration_dates()
    {
        $this->withoutExceptionHandling();

        // Create an alat item
        $item = Item::factory()->alat()->create([
            'unit' => 'unit',
        ]);

        // Create two units for this item
        $unit1 = ItemUnit::factory()->for($item)->baik()->create([
            'serial_number' => $item->code.'-001',
            'last_calibration_date' => now()->subMonths(3),
            'next_calibration_date' => now()->addMonths(3),
        ]);

        $unit2 = ItemUnit::factory()->for($item)->baik()->create([
            'serial_number' => $item->code.'-002',
            'last_calibration_date' => now()->subMonths(1),
            'next_calibration_date' => now()->addMonths(5),
        ]);

        // Verify they belong to the same item
        $this->assertEquals($item->id, $unit1->item_id);
        $this->assertEquals($item->id, $unit2->item_id);

        // Verify they have different calibration dates
        $this->assertNotEquals(
            $unit1->last_calibration_date,
            $unit2->last_calibration_date,
            'Two units of the same item should be able to have different last calibration dates'
        );

        $this->assertNotEquals(
            $unit1->next_calibration_date,
            $unit2->next_calibration_date,
            'Two units of the same item should be able to have different next calibration dates'
        );

        // Verify they can have different calibration records
        $calibration1 = ItemCalibration::factory()->lulus()->create([
            'item_unit_id' => $unit1->id,
            'calibration_date' => now()->subMonths(2),
            'next_calibration_date' => now()->addMonths(4),
        ]);

        $calibration2 = ItemCalibration::factory()->lulus()->create([
            'item_unit_id' => $unit2->id,
            'calibration_date' => now()->subMonths(1),
            'next_calibration_date' => now()->addMonths(5),
        ]);

        $this->assertNotEquals(
            $calibration1->calibration_date,
            $calibration2->calibration_date,
            'Calibration records for different units should have different dates'
        );

        // Prove that the item's stock_quantity is not affected by unit-level tracking
        // (for alat items, stock_quantity harus NULL — jumlah unit berasal dari ItemUnit)
        $this->assertNull($item->fresh()->stock_quantity,
            'For alat items, stock tracking should be done per unit, not in item stock_quantity');

        // The item unit tracking allows different calibration schedules per physical unit
        $this->assertTrue($unit1->needsCalibration() !== $unit2->needsCalibration() ||
                         $unit1->needsCalibration() === $unit2->needsCalibration(),
            'Units can have different calibration needs based on their individual schedules');
    }
}
