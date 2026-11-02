<?php

namespace App\Console\Commands;

use App\Enums\MembershipRole;
use App\Enums\MembershipStatus;
use App\Enums\UserStatus;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\User;
use App\Services\AuditRecorder;
use App\Support\OrganizationContext;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ProvisionOrganization extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:provision-organization';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create an organization and its first administrator';

    /**
     * Execute the console command.
     */
    public function handle(AuditRecorder $auditRecorder, OrganizationContext $organizationContext): int
    {
        $organizationName = trim((string) $this->ask('Organization name'));
        $administratorName = trim((string) $this->ask('Administrator name'));
        $email = mb_strtolower(trim((string) $this->ask('Administrator email')));
        $password = (string) $this->secret('Administrator password');
        $confirmation = (string) $this->secret('Confirm administrator password');

        if ($organizationName === '' || $administratorName === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)
            || strlen($password) < 12 || ! hash_equals($password, $confirmation)) {
            $this->error('The organization, administrator details, or password are invalid.');

            return self::FAILURE;
        }

        if (User::where('email', $email)->exists()) {
            $this->error('An account with that email already exists.');

            return self::FAILURE;
        }

        DB::transaction(function () use ($organizationName, $administratorName, $email, $password, $auditRecorder, $organizationContext): void {
            $organization = Organization::create(['name' => $organizationName]);
            $administrator = User::create([
                'name' => $administratorName,
                'email' => $email,
                'password' => $password,
                'locale' => 'en',
                'status' => UserStatus::Active,
            ]);

            $organizationContext->run($organization, function () use ($organization, $administrator, $auditRecorder): void {
                $membership = OrganizationMembership::create([
                    'user_id' => $administrator->id,
                    'role_key' => MembershipRole::Admin,
                    'status' => MembershipStatus::Active,
                ]);

                $auditRecorder->record(
                    (int) $organization->id,
                    (int) $administrator->id,
                    'organization.provisioned',
                    Organization::class,
                    $organization->id,
                    'Initial organization provisioning.',
                    ['membership_id' => $membership->id],
                );
            });
        });

        $this->info('Organization and administrator provisioned.');

        return self::SUCCESS;
    }
}
