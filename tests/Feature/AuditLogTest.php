<?php

use App\Domain\Accounts\CreateAccountWithOpeningBalance;
use App\Domain\Audit\RecordAuditLog;
use App\Domain\Workspaces\CreatePersonalWorkspace;
use App\Enums\AccountType;
use App\Enums\AuditAction;
use App\Models\User;
use Database\Seeders\CurrencySeeder;

beforeEach(function () {
    $this->seed(CurrencySeeder::class);

    $this->user = User::factory()->create();
    $this->workspace = app(CreatePersonalWorkspace::class)->create($this->user);

    $this->account = app(CreateAccountWithOpeningBalance::class)->create($this->workspace, [
        'name' => 'Audit account',
        'type' => AccountType::Cash,
        'currency_code' => 'IDR',
        'opening_balance' => 1000,
    ], $this->user);

    $this->transaction = $this->account->ledgerEntries()->sole()->transaction;
});

test('recording an audit log creates an entry scoped to the workspace and subject', function () {
    $auditLog = app(RecordAuditLog::class)->record(
        $this->workspace,
        AuditAction::TransactionPosted,
        $this->transaction,
        $this->user,
        ['amount' => 1000],
    );

    expect($auditLog->workspace_id)->toBe($this->workspace->id)
        ->and($auditLog->actor_id)->toBe($this->user->id)
        ->and($auditLog->action)->toBe(AuditAction::TransactionPosted)
        ->and($auditLog->subject_type)->toBe($this->transaction->getMorphClass())
        ->and($auditLog->subject_id)->toBe($this->transaction->id)
        ->and($auditLog->metadata)->toBe(['amount' => 1000])
        ->and($auditLog->created_at)->not->toBeNull();

    $this->assertDatabaseHas('audit_logs', [
        'id' => $auditLog->id,
        'workspace_id' => $this->workspace->id,
    ]);
});

test('audit logs are accessible from the workspace relation', function () {
    app(RecordAuditLog::class)->record(
        $this->workspace,
        AuditAction::TransactionPosted,
        $this->transaction,
        $this->user,
    );

    expect($this->workspace->auditLogs()->count())->toBe(1);
});

test('audit log entries are immutable', function () {
    $auditLog = app(RecordAuditLog::class)->record(
        $this->workspace,
        AuditAction::TransactionPosted,
        $this->transaction,
        $this->user,
    );

    expect(fn () => $auditLog->update(['action' => AuditAction::TransactionVoided]))
        ->toThrow(LogicException::class)
        ->and(fn () => $auditLog->delete())
        ->toThrow(LogicException::class);
});

test('audit logs can be recorded without an actor', function () {
    $auditLog = app(RecordAuditLog::class)->record(
        $this->workspace,
        AuditAction::TransactionPosted,
        $this->transaction,
        null,
    );

    expect($auditLog->actor_id)->toBeNull();
});
