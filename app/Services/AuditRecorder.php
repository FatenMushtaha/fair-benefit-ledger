<?php

namespace App\Services;

use App\Models\AuditEvent;
use App\Support\OrganizationContext;
use Illuminate\Support\Str;
use LogicException;

class AuditRecorder
{
    public function __construct(private readonly OrganizationContext $organizationContext) {}

    /**
     * @param  array<string, mixed>|null  $payload
     */
    public function record(
        int $organizationId,
        ?int $actorId,
        string $eventType,
        string $subjectType,
        string|int $subjectId,
        string $reason,
        ?array $payload = null,
        ?string $correlationId = null,
    ): AuditEvent {
        if ($this->organizationContext->id() !== $organizationId) {
            throw new LogicException('Audit events must be recorded inside the matching organization context.');
        }

        return AuditEvent::create([
            'actor_id' => $actorId,
            'event_type' => $eventType,
            'subject_type' => $subjectType,
            'subject_id' => (string) $subjectId,
            'reason' => $reason,
            'payload' => $payload,
            'occurred_at' => now(),
            'correlation_id' => $correlationId ?? (string) Str::uuid(),
        ]);
    }
}
