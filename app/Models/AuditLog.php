<?php

namespace App\Models;

use App\Enums\AuditAction;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

#[Fillable([
    'workspace_id',
    'actor_id',
    'action',
    'subject_type',
    'subject_id',
    'metadata',
])]
class AuditLog extends Model
{
    public const ?string UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'action' => AuditAction::class,
            'metadata' => 'array',
            'created_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return BelongsTo<Workspace, $this>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function save(array $options = []): bool
    {
        if ($this->exists) {
            throw new LogicException('Audit log entries are immutable.');
        }

        return parent::save($options);
    }

    public function delete(): ?bool
    {
        throw new LogicException('Audit log entries are immutable.');
    }
}
