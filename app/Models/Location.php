<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Location extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'name',
        'description',
    ];

    public function itemUnits(): HasMany
    {
        return $this->hasMany(ItemUnit::class);
    }

    public static function firstOrCreateFromName(string $name, ?string $description = null): self
    {
        $existing = static::query()->where('name', $name)->first();

        if ($existing) {
            return $existing;
        }

        return static::query()->create([
            'code' => static::uniqueCodeFromName($name),
            'name' => $name,
            'description' => $description,
        ]);
    }

    public static function uniqueCodeFromName(string $name): string
    {
        $base = Str::upper(Str::slug($name, '-'));
        if ($base === '') {
            $base = 'LOC';
        }

        $code = $base;
        $suffix = 1;

        while (static::query()->where('code', $code)->exists()) {
            $code = $base.'-'.$suffix;
            $suffix++;
        }

        return $code;
    }
}
