<?php

namespace App\Traits;

use Illuminate\Support\Facades\Log;

trait HasActivityLog
{
    protected static function bootHasActivityLog(): void
    {
        static::created(function ($model) {
            Log::info("{$model->getTable()} created", [
                'id' => $model->id,
                'attributes' => $model->getAttributes(),
                'user_id' => auth()->id(),
            ]);
        });

        static::updated(function ($model) {
            Log::info("{$model->getTable()} updated", [
                'id' => $model->id,
                'changes' => $model->getChanges(),
                'original' => $model->getOriginal(),
                'user_id' => auth()->id(),
            ]);
        });

        static::deleted(function ($model) {
            Log::info("{$model->getTable()} deleted", [
                'id' => $model->id,
                'attributes' => $model->getAttributes(),
                'user_id' => auth()->id(),
            ]);
        });
    }
}
