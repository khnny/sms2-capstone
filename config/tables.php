<?php
/**
 * Central table-name map for the single-database architecture.
 *
 * Target schema (same physical DB in every environment):
 *   sms2_db / hf_db_*  →  sms2_*  +  crad_*
 *
 * Logical keys stay stable; physical names are prefixed.
 * Use sms2_table() / crad_table() in new or migrated SQL — do not scatter
 * prefixed literals. Until the rename migration runs, live tables may still
 * be unprefixed; cut over PHP and RENAME TABLE together.
 */

declare(strict_types=1);

if (!function_exists('sms2_table_map')) {
    /**
     * @return array{sms2: array<string, string>, crad: array<string, string>}
     */
    function sms2_table_map(): array
    {
        static $map = null;
        if ($map !== null) {
            return $map;
        }

        $map = [
            'sms2' => [
                'activity_logs' => 'sms2_activity_logs',
                'admin_announcements' => 'sms2_admin_announcements',
                'login_throttles' => 'sms2_login_throttles',
                'password_resets' => 'sms2_password_resets',
                'password_reset_requests' => 'sms2_password_reset_requests',
                'roles' => 'sms2_roles',
                'role_permissions' => 'sms2_role_permissions',
                'schema_migrations' => 'sms2_schema_migrations',
                'security_otps' => 'sms2_security_otps',
                'student_profiles' => 'sms2_student_profiles',
                'system_settings' => 'sms2_system_settings',
                'users' => 'sms2_users',
                'user_authenticators' => 'sms2_user_authenticators',
                'user_passkeys' => 'sms2_user_passkeys',
            ],
            'crad' => [
                'chapter_evaluations' => 'crad_chapter_evaluations',
                'chapter_evaluation_notifications' => 'crad_chapter_evaluation_notifications',
                'chapter_submissions' => 'crad_chapter_submissions',
                'chapter_submission_history' => 'crad_chapter_submission_history',
                'final_defense_evaluations' => 'crad_final_defense_evaluations',
                'final_defense_recommendations' => 'crad_final_defense_recommendations',
                'final_manuscript_approvals' => 'crad_final_manuscript_approvals',
                'grant_applications' => 'crad_grant_applications',
                'grant_document_repository' => 'crad_grant_document_repository',
                'grant_document_repository_items' => 'crad_grant_document_repository_items',
                'grant_final_output_submissions' => 'crad_grant_final_output_submissions',
                'grant_funded_progress_evidence' => 'crad_grant_funded_progress_evidence',
                'grant_funded_project_milestones' => 'crad_grant_funded_project_milestones',
                'grant_funding_disbursements' => 'crad_grant_funding_disbursements',
                'grant_opportunities' => 'crad_grant_opportunities',
                'grant_proposal_approval_steps' => 'crad_grant_proposal_approval_steps',
                'grant_proposal_approval_workflows' => 'crad_grant_proposal_approval_workflows',
                'grant_proposal_evaluations' => 'crad_grant_proposal_evaluations',
                'grant_proposal_notifications' => 'crad_grant_proposal_notifications',
                'grant_proposal_versions' => 'crad_grant_proposal_versions',
                'grant_publications_ip_repository' => 'crad_grant_publications_ip_repository',
                'manuscript_evaluations' => 'crad_manuscript_evaluations',
                'manuscript_submissions' => 'crad_manuscript_submissions',
                'panel_assignment_notifications' => 'crad_panel_assignment_notifications',
                'panel_application_documents' => 'crad_panel_application_documents',
                'panel_application_history' => 'crad_panel_application_history',
                'panel_pool_assignments' => 'crad_panel_pool_assignments',
                'panelist_applications' => 'crad_panelist_applications',
                'panel_member_availability' => 'crad_panel_member_availability',
                'preoral_defense_evaluations' => 'crad_preoral_defense_evaluations',
                'proposal_documents' => 'crad_proposal_documents',
                'proposal_drafts' => 'crad_proposal_drafts',
                'proposal_members' => 'crad_proposal_members',
                'proposal_status_logs' => 'crad_proposal_status_logs',
                'publications' => 'crad_publications',
                'research_adviser_assignments' => 'crad_research_adviser_assignments',
                'research_clearance_notifications' => 'crad_research_clearance_notifications',
                'research_clearance_payments' => 'crad_research_clearance_payments',
                'research_coordinator_assignments' => 'crad_research_coordinator_assignments',
                'research_defense_schedules' => 'crad_research_defense_schedules',
                'research_groups' => 'crad_research_groups',
                'research_milestones' => 'crad_research_milestones',
                'research_panel_assignments' => 'crad_research_panel_assignments',
                'research_plans' => 'crad_research_plans',
                'research_progress_activity_logs' => 'crad_research_progress_activity_logs',
                'research_progress_ai_analyses' => 'crad_research_progress_ai_analyses',
                'research_progress_attachments' => 'crad_research_progress_attachments',
                'research_progress_feedback' => 'crad_research_progress_feedback',
                'research_progress_notifications' => 'crad_research_progress_notifications',
                'research_progress_updates' => 'crad_research_progress_updates',
                'research_proposals' => 'crad_research_proposals',
                'research_revision_cycles' => 'crad_research_revision_cycles',
                'research_services_clearances' => 'crad_research_services_clearances',
                'research_venues' => 'crad_research_venues',
                'title_approvals' => 'crad_title_approvals',
            ],
        ];

        return $map;
    }
}

if (!function_exists('sms2_table')) {
    /**
     * Physical SMS2 table name for a logical key (e.g. users → sms2_users).
     */
    function sms2_table(string $logical): string
    {
        $map = sms2_table_map()['sms2'];
        if (!isset($map[$logical])) {
            throw new InvalidArgumentException('Unknown SMS2 table key: ' . $logical);
        }

        return $map[$logical];
    }
}

if (!function_exists('crad_table')) {
    /**
     * Physical CRAD table name for a logical key
     * (e.g. research_proposals → crad_research_proposals).
     */
    function crad_table(string $logical): string
    {
        $map = sms2_table_map()['crad'];
        if (!isset($map[$logical])) {
            throw new InvalidArgumentException('Unknown CRAD table key: ' . $logical);
        }

        return $map[$logical];
    }
}

if (!function_exists('sms2_quote_table')) {
    /**
     * Backtick-quoted physical table for safe SQL interpolation.
     */
    function sms2_quote_table(string $physicalName): string
    {
        return '`' . str_replace('`', '``', $physicalName) . '`';
    }
}

if (!function_exists('sms2_resolve_table')) {
    /**
     * Accept logical or already-prefixed SMS2 table name.
     */
    function sms2_resolve_table(string $name): string
    {
        $name = trim($name);
        if ($name === '') {
            return $name;
        }
        if (str_starts_with($name, 'sms2_')) {
            return $name;
        }
        $map = sms2_table_map()['sms2'];
        return $map[$name] ?? $name;
    }
}

if (!function_exists('crad_resolve_table')) {
    /**
     * Accept logical or already-prefixed CRAD table name.
     */
    function crad_resolve_table(string $name): string
    {
        $name = trim($name);
        if ($name === '') {
            return $name;
        }
        if (str_starts_with($name, 'crad_')) {
            return $name;
        }
        $map = sms2_table_map()['crad'];
        return $map[$name] ?? $name;
    }
}
