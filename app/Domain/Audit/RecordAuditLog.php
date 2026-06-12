<?php

namespace App\Domain\Audit;

use App\Enums\AuditAction;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Model;

class RecordAuditLog
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function record(Workspace $workspace, AuditAction $action, Model $subject, ?User $actor = null, array $metadata = []): AuditLog
    {
        return AuditLog::query()->create([
            'workspace_id' => $workspace->id,
            'actor_id' => $actor?->id,
            'action' => $action,
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->getKey(),
            'metadata' => $metadata,
        ]);
    }
}
