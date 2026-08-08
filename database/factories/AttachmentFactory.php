<?php

namespace Database\Factories;

use App\Models\Attachment;
use App\Models\Item;
use App\Models\ItemUnit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attachment>
 */
class AttachmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $models = [
            Item::class => Item::factory(),
            ItemUnit::class => ItemUnit::factory(),
        ];
        $modelClass = fake()->randomElement(array_keys($models));
        $model = $models[$modelClass]->create();

        return [
            'attachable_type' => $modelClass,
            'attachable_id' => $model->id,
            'type' => fake()->randomElement([
                'condition_before',
                'condition_after',
                'damage_evidence',
                'calibration_certificate',
                'maintenance_photo',
                'borrow_receipt',
                'return_receipt',
                'usage_record',
            ]),
            'file_path' => 'attachments/'.fake()->uuid().'.jpg',
            'original_filename' => fake()->word().'.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => fake()->numberBetween(10000, 5000000),
            'disk' => 'public',
            'description' => fake()->optional(0.5)->sentence(),
            'uploaded_by' => User::factory(),
        ];
    }
}
