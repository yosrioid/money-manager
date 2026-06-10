<?php

namespace App\Domain\Ordering;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class MoveOrderedResource
{
    /**
     * @template TModel of Model
     *
     * @param  TModel  $resource
     * @param  Builder<TModel>  $siblings
     */
    public function move(Model $resource, Builder $siblings, string $direction): void
    {
        DB::transaction(function () use ($resource, $siblings, $direction): void {
            $current = (clone $siblings)
                ->whereKey($resource->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $position = (int) $current->getAttribute('position');
            $target = (clone $siblings)
                ->whereKeyNot($current->getKey())
                ->where('position', $direction === 'up' ? '<' : '>', $position)
                ->orderBy('position', $direction === 'up' ? 'desc' : 'asc')
                ->lockForUpdate()
                ->first();

            if ($target === null) {
                return;
            }

            $targetPosition = (int) $target->getAttribute('position');

            $target->forceFill(['position' => $position])->save();
            $current->forceFill(['position' => $targetPosition])->save();
        });
    }
}
