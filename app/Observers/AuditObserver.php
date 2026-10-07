<?php

namespace App\Observers;

use App\Models\AdminAuditLog;
use Illuminate\Database\Eloquent\Model;

class AuditObserver
{
    public function created(Model $model): void
    {
        $this->log('created', $model, null, $model->getAttributes());
    }

    public function updated(Model $model): void
    {
        $this->log('updated', $model, $model->getOriginal(), $model->getChanges());
    }

    public function deleted(Model $model): void
    {
        $this->log('deleted', $model, $model->getOriginal(), null);
    }

    private function log(string $action, Model $model, ?array $old, ?array $new): void
    {
        if (! auth()->check()) {
            return; // on n'audite que les actions faites par un utilisateur connecté (pas les seeders)
        }

        AdminAuditLog::create([
            'admin_id' => auth()->id(),
            'action' => $action,
            'entity_type' => class_basename($model),
            'entity_id' => $model->getKey(),
            'old_values' => $old,
            'new_values' => $new,
            'ip_address' => request()?->ip(),
            'created_at' => now(),
        ]);
    }
}