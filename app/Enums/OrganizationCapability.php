<?php

namespace App\Enums;

/**
 * Capabilities taken verbatim from the owner-approved least-privilege matrix
 * in docs/specs/SPEC-01-foundation.md. Do not add capabilities by inference.
 */
enum OrganizationCapability: string
{
    case UseOrganization = 'use_organization';
    case ManageOrganization = 'manage_organization';
    case ManageProgramsCycles = 'manage_programs_cycles';
    case ManageImports = 'manage_imports';
    case ReviewCases = 'review_cases';
    case FinalApproveAcceptance = 'final_approve_acceptance';
    case ManagePersonalDataExports = 'manage_personal_data_exports';
    case ReadAuditLogs = 'read_audit_logs';
    case ReadApprovedReports = 'read_approved_reports';
    case ReadReviewCaseEvidence = 'read_review_case_evidence';
    case ReadOperationalSourceRecords = 'read_operational_source_records';

    public function label(): string
    {
        return __('ui.capabilities.'.$this->value);
    }
}
