<?php

namespace App\Observers;

use App\Models\AuditTrail;
use Illuminate\Database\Eloquent\Model;

class AuditableObserver
{
    /**
     * Attributes that should never be persisted into audit payloads.
     *
     * @var list<string>
     */
    private const EXCLUDED_ATTRIBUTES = [
        'password',
        'remember_token',
        'updated_at',
    ];

    public function created(Model $model): void
    {
        AuditTrail::create([
            'user_id' => auth()->id(),
            'auditable_type' => get_class($model),
            'auditable_id' => $model->id,
            'action' => 'created',
            'old_values' => null,
            'new_values' => $this->auditableAttributes($model),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    public function updated(Model $model): void
    {
        $changes = collect($model->getChanges())
            ->except(self::EXCLUDED_ATTRIBUTES)
            ->all();

        if ($changes === []) {
            return;
        }

        $oldValues = [];
        foreach (array_keys($changes) as $attribute) {
            $oldValues[$attribute] = $model->getOriginal($attribute);
        }

        AuditTrail::create([
            'user_id' => auth()->id(),
            'auditable_type' => get_class($model),
            'auditable_id' => $model->id,
            'action' => 'updated',
            'old_values' => $this->normalizeValues($oldValues),
            'new_values' => $this->normalizeValues($changes),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Snapshot of model attributes suitable for audit storage (no relations).
     *
     * @return array<string, mixed>
     */
    private function auditableAttributes(Model $model): array
    {
        return $this->normalizeValues(
            collect($model->getAttributes())
                ->except(self::EXCLUDED_ATTRIBUTES)
                ->all()
        );
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private function normalizeValues(array $values): array
    {
        foreach ($values as $key => $value) {
            if ($value instanceof \DateTimeInterface) {
                $values[$key] = $value->format('Y-m-d H:i:s');
            }
        }

        return $values;
    }
}
