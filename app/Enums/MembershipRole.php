<?php

namespace App\Enums;

enum MembershipRole: string
{
    case Admin = 'admin';
    case Reviewer = 'reviewer';
    case DataEntry = 'data_entry';
    case Viewer = 'viewer';

    public function label(): string
    {
        return __('ui.roles.'.$this->value);
    }

    /**
     * The single source of truth for role capabilities. Every grant and every
     * denial maps to a row or cell of the owner-approved matrix.
     */
    public function allows(OrganizationCapability $capability): bool
    {
        return in_array($capability, $this->capabilities(), true);
    }

    /** @return array<int, OrganizationCapability> */
    public function capabilities(): array
    {
        return match ($this) {
            self::Admin => OrganizationCapability::cases(),
            self::Reviewer => [
                OrganizationCapability::UseOrganization,
                OrganizationCapability::ReviewCases,
                OrganizationCapability::ReadReviewCaseEvidence,
            ],
            self::DataEntry => [
                OrganizationCapability::UseOrganization,
                OrganizationCapability::ManageImports,
                OrganizationCapability::ReadOperationalSourceRecords,
            ],
            self::Viewer => [
                OrganizationCapability::UseOrganization,
                OrganizationCapability::ReadApprovedReports,
            ],
        };
    }
}
