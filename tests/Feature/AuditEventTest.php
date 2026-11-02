<?php

namespace Tests\Feature;

use App\Enums\MembershipRole;
use App\Models\AuditEvent;
use App\Services\AuditRecorder;
use App\Services\OrganizationMembershipService;
use App\Support\OrganizationContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\Concerns\BuildsOrganizationContext;
use Tests\TestCase;

class AuditEventTest extends TestCase
{
    use BuildsOrganizationContext, RefreshDatabase;

    public function test_audit_event_cannot_be_updated(): void
    {
        $organization = $this->makeOrganization();
        $event = $this->inOrganization($organization, fn () => AuditEvent::factory()->create());

        $this->expectException(LogicException::class);
        $event->update(['reason' => 'Changed reason.']);
    }

    public function test_audit_event_cannot_be_deleted(): void
    {
        $organization = $this->makeOrganization();
        $event = $this->inOrganization($organization, fn () => AuditEvent::factory()->create());

        $this->expectException(LogicException::class);
        $event->delete();
    }

    public function test_audit_event_creation_requires_a_validated_context(): void
    {
        app(OrganizationContext::class)->clear();

        $this->expectException(LogicException::class);

        AuditEvent::factory()->create();
    }

    public function test_audit_recorder_rejects_a_mismatched_organization_context(): void
    {
        $alpha = $this->makeOrganization('Alpha Works');
        $beta = $this->makeOrganization('Beta Works');
        $admin = $this->makeAdmin($alpha);

        $this->expectException(LogicException::class);

        $this->inOrganization($alpha, fn () => app(AuditRecorder::class)->record(
            (int) $beta->id,
            (int) $admin->id,
            'test.event',
            'TestSubject',
            1,
            'Mismatched tenant.',
        ));
    }

    public function test_audit_recorder_stores_actor_subject_time_and_reason(): void
    {
        $organization = $this->makeOrganization();
        $admin = $this->makeAdmin($organization);

        $event = $this->inOrganization($organization, fn () => app(AuditRecorder::class)->record(
            (int) $organization->id,
            (int) $admin->id,
            'organization.test',
            'TestSubject',
            '42',
            'A recorded reason.',
            ['key' => 'value'],
        ));

        $this->assertSame($organization->id, (int) $event->organization_id);
        $this->assertSame($admin->id, (int) $event->actor_id);
        $this->assertSame('organization.test', $event->event_type);
        $this->assertSame('A recorded reason.', $event->reason);
        $this->assertSame(['key' => 'value'], $event->payload);
        $this->assertNotNull($event->occurred_at);
        $this->assertNotEmpty($event->correlation_id);
    }

    public function test_audit_events_are_tenant_scoped(): void
    {
        $alpha = $this->makeOrganization('Alpha Works');
        $beta = $this->makeOrganization('Beta Works');
        $this->inOrganization($alpha, fn () => AuditEvent::factory()->create());

        app(OrganizationContext::class)->clear();
        $this->assertSame(0, AuditEvent::count());

        $this->inOrganization($beta, fn () => $this->assertSame(0, AuditEvent::count()));
        $this->inOrganization($alpha, fn () => $this->assertSame(1, AuditEvent::count()));
    }

    public function test_organization_id_cannot_be_mass_assigned_on_an_audit_event(): void
    {
        $alpha = $this->makeOrganization('Alpha Works');
        $beta = $this->makeOrganization('Beta Works');

        $event = $this->inOrganization($alpha, fn () => AuditEvent::factory()->create([
            'organization_id' => $beta->id,
        ]));

        $this->assertSame($alpha->id, (int) $event->organization_id);
    }

    public function test_role_changes_and_deactivation_append_audit_events(): void
    {
        $organization = $this->makeOrganization();
        $admin = $this->makeAdmin($organization);
        $target = $this->makeUser();
        $membership = $this->makeMembership($organization, $target, MembershipRole::Viewer);

        $this->inOrganization($organization, fn () => app(OrganizationMembershipService::class)->changeRole(
            $admin,
            $organization,
            (int) $membership->id,
            MembershipRole::Reviewer,
            'Promote reviewer.',
        ));

        $this->inOrganization($organization, fn () => app(OrganizationMembershipService::class)->deactivate(
            $admin,
            $organization,
            (int) $membership->id,
            'Remove reviewer.',
        ));

        $this->assertDatabaseHas('audit_events', [
            'organization_id' => $organization->id,
            'event_type' => 'organization.membership.role_changed',
            'reason' => 'Promote reviewer.',
        ]);
        $this->assertDatabaseHas('audit_events', [
            'organization_id' => $organization->id,
            'event_type' => 'organization.membership.deactivated',
            'reason' => 'Remove reviewer.',
        ]);
        $this->assertSame(2, AuditEvent::withoutGlobalScopes()->count());
    }
}
