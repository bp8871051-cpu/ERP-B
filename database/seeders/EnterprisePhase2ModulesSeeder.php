<?php

namespace Database\Seeders;

use App\Models\ApiKey;
use App\Models\Company;
use App\Models\Customer;
use App\Models\DeleteRequest;
use App\Models\Department;
use App\Models\KnowledgeBaseArticle;
use App\Models\KnowledgeBaseCategory;
use App\Models\Membership;
use App\Models\MembershipAddon;
use App\Models\MembershipAddonItem;
use App\Models\MembershipPlan;
use App\Models\MembershipRenewal;
use App\Models\MembershipTransaction;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SupportContactMessage;
use App\Models\SupportSlaLog;
use App\Models\SupportSlaPolicy;
use App\Models\SystemAuditLog;
use App\Models\SystemSetting;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketMessage;
use App\Models\User;
use App\Models\Webhook;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class EnterprisePhase2ModulesSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::first() ?? Company::create([
            'name' => 'Falcon Global Enterprises Ltd.',
            'email' => 'admin@falconerp.com',
            'phone' => '+1 (555) 019-2834',
        ]);
        $companyId = $company->id;

        $adminUser = User::first() ?? User::create([
            'company_id' => $companyId,
            'name' => 'Alexander Wright',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role' => 'Super Admin',
            'is_active' => true,
        ]);

        $customers = Customer::where('company_id', $companyId)->take(5)->get();
        if ($customers->isEmpty()) {
            $customers = collect([
                Customer::create([
                    'company_id' => $companyId,
                    'name' => 'Apex Global Logistics',
                    'email' => 'contact@apexlogistics.com',
                    'phone' => '+1 (555) 234-5678',
                    'status' => 'active',
                ]),
                Customer::create([
                    'company_id' => $companyId,
                    'name' => 'Nexis Healthcare Systems',
                    'email' => 'it-ops@nexishealth.org',
                    'phone' => '+1 (555) 876-5432',
                    'status' => 'active',
                ]),
            ]);
        }

        // ==========================================
        // 1. SUPPORT MODULE SEEDING
        // ==========================================
        // 1.1 SLA Policies
        $slaUrgent = SupportSlaPolicy::firstOrCreate(
            ['company_id' => $companyId, 'name' => 'Critical & Outage SLA (P1)'],
            [
                'description' => '30 min initial response, 4 hours full incident resolution',
                'priority' => 'urgent',
                'first_response_target_minutes' => 30,
                'resolution_target_minutes' => 240,
                'business_hours' => false,
                'status' => 'active',
            ]
        );

        $slaHigh = SupportSlaPolicy::firstOrCreate(
            ['company_id' => $companyId, 'name' => 'High Priority SLA (P2)'],
            [
                'description' => '1 hour initial response, 8 hours resolution target',
                'priority' => 'high',
                'first_response_target_minutes' => 60,
                'resolution_target_minutes' => 480,
                'business_hours' => true,
                'status' => 'active',
            ]
        );

        $slaMedium = SupportSlaPolicy::firstOrCreate(
            ['company_id' => $companyId, 'name' => 'Standard Business SLA (P3)'],
            [
                'description' => '4 hours initial response, 24 hours resolution target',
                'priority' => 'medium',
                'first_response_target_minutes' => 240,
                'resolution_target_minutes' => 1440,
                'business_hours' => true,
                'status' => 'active',
            ]
        );

        $slaLow = SupportSlaPolicy::firstOrCreate(
            ['company_id' => $companyId, 'name' => 'General Inquiry SLA (P4)'],
            [
                'description' => '8 hours initial response, 72 hours resolution target',
                'priority' => 'low',
                'first_response_target_minutes' => 480,
                'resolution_target_minutes' => 4320,
                'business_hours' => true,
                'status' => 'active',
            ]
        );

        // 1.2 Ticket Categories
        $categories = [
            'Technical & Cloud Infrastructure',
            'Billing, Invoicing & Subscriptions',
            'Software & API Integration',
            'Hardware & Workstation Support',
            'Account Access & Security',
        ];
        $catModels = [];
        foreach ($categories as $catName) {
            $catModels[] = TicketCategory::firstOrCreate(['name' => $catName]);
        }

        // 1.3 Tickets
        $ticket1 = Ticket::updateOrCreate(
            ['company_id' => $companyId, 'ticket_number' => 'TCK-2026-0891'],
            [
                'customer_id' => $customers->first()->id,
                'assigned_agent_id' => $adminUser->id,
                'category_id' => $catModels[0]->id,
                'sla_policy_id' => $slaUrgent->id,
                'subject' => 'Production Database Latency & Connection Spike',
                'description' => 'We observed query latency spikes > 2200ms on the main enterprise sales cluster after the latest migration.',
                'priority' => 'urgent',
                'status' => 'in_progress',
                'source' => 'web',
                'first_response_due_at' => Carbon::now()->addMinutes(15),
                'resolution_due_at' => Carbon::now()->addHours(3),
                'first_responded_at' => Carbon::now()->subMinutes(10),
                'sla_status' => 'within_sla',
                'tags' => ['database', 'performance', 'p1-escalation'],
            ]
        );

        $ticket2 = Ticket::updateOrCreate(
            ['company_id' => $companyId, 'ticket_number' => 'TCK-2026-0892'],
            [
                'customer_id' => $customers->last()->id,
                'assigned_agent_id' => $adminUser->id,
                'category_id' => $catModels[1]->id,
                'sla_policy_id' => $slaMedium->id,
                'subject' => 'VAT / GST Invoice Recalculation Request',
                'description' => 'Please generate an amended tax invoice for the quarterly subscription reflecting our new corporate GSTIN number.',
                'priority' => 'medium',
                'status' => 'open',
                'source' => 'email',
                'first_response_due_at' => Carbon::now()->addHours(2),
                'resolution_due_at' => Carbon::now()->addHours(20),
                'sla_status' => 'within_sla',
                'tags' => ['billing', 'tax', 'invoice'],
            ]
        );

        $ticket3 = Ticket::updateOrCreate(
            ['company_id' => $companyId, 'ticket_number' => 'TCK-2026-0890'],
            [
                'customer_id' => $customers->first()->id,
                'assigned_agent_id' => $adminUser->id,
                'category_id' => $catModels[2]->id,
                'sla_policy_id' => $slaHigh->id,
                'subject' => 'REST API Webhook Signature Verification Failure',
                'description' => 'The HMAC-SHA256 signature header on incoming order notifications is mismatching our local verification endpoint.',
                'priority' => 'high',
                'status' => 'resolved',
                'source' => 'crm',
                'first_response_due_at' => Carbon::now()->subHours(6),
                'resolution_due_at' => Carbon::now()->subHours(2),
                'first_responded_at' => Carbon::now()->subHours(5),
                'resolved_at' => Carbon::now()->subHours(1),
                'sla_status' => 'within_sla',
                'tags' => ['api', 'webhooks', 'security'],
            ]
        );

        // 1.4 Ticket Messages
        TicketMessage::updateOrCreate(
            ['ticket_id' => $ticket1->id, 'company_id' => $companyId, 'message' => 'Initial inquiry submitted by client monitoring bot.'],
            [
                'user_id' => $adminUser->id,
                'sender_type' => 'customer',
                'message_type' => 'reply',
                'is_internal' => false,
            ]
        );
        TicketMessage::updateOrCreate(
            ['ticket_id' => $ticket1->id, 'company_id' => $companyId, 'message' => 'Internal investigation: Verified Redis cache hit ratio dropped from 94% to 68%. Purged stale keys.'],
            [
                'user_id' => $adminUser->id,
                'sender_type' => 'agent',
                'message_type' => 'internal_note',
                'is_internal' => true,
            ]
        );
        TicketMessage::updateOrCreate(
            ['ticket_id' => $ticket1->id, 'company_id' => $companyId, 'message' => 'Hello team, we have identified the root cause in the query cache and deployed an index tuning patch. Please recheck.'],
            [
                'user_id' => $adminUser->id,
                'sender_type' => 'agent',
                'message_type' => 'reply',
                'is_internal' => false,
            ]
        );

        // 1.5 Contact Messages
        SupportContactMessage::updateOrCreate(
            ['company_id' => $companyId, 'email' => 'partner@cloudscale.io', 'subject' => 'Enterprise Custom ERP Integration & Onboarding'],
            [
                'name' => 'Marcus Vance',
                'phone' => '+1 (555) 432-8976',
                'message' => 'We are looking to onboard 450 concurrent warehouse staff across 4 locations onto Falcon ERP POS and inventory. Can your team coordinate a tailored trial setup?',
                'source' => 'website',
                'status' => 'new',
            ]
        );
        SupportContactMessage::updateOrCreate(
            ['company_id' => $companyId, 'email' => 'support@horizonpharma.com', 'subject' => 'Compliance Document Expiry Notification inquiry'],
            [
                'name' => 'Dr. Elena Rostova',
                'phone' => '+1 (555) 789-0123',
                'message' => 'Our clinical audit is next month. We need to verify that automated ISO compliance renewal alerts are sending to both the department head and legal council.',
                'source' => 'support_form',
                'status' => 'read',
                'assigned_to_user_id' => $adminUser->id,
            ]
        );

        // 1.6 Knowledge Base Categories & Articles
        $kbCategories = [
            ['name' => 'Getting Started', 'slug' => 'getting-started', 'icon' => 'Sparkles', 'desc' => 'Core onboarding, first login and workspace configuration.'],
            ['name' => 'Billing & Plans', 'slug' => 'billing-plans', 'icon' => 'CreditCard', 'desc' => 'Invoicing, membership renewal, addons and tax receipts.'],
            ['name' => 'Point of Sale (POS)', 'slug' => 'pos', 'icon' => 'Store', 'desc' => 'Cash registers, barcode scanning, thermal printers and offline sync.'],
            ['name' => 'Fixed Assets', 'slug' => 'fixed-assets', 'icon' => 'Boxes', 'desc' => 'Asset tracking, automated depreciation, QR labels and maintenance logs.'],
            ['name' => 'Security & RBAC', 'slug' => 'security-rbac', 'icon' => 'ShieldCheck', 'desc' => 'Role permissions, 2FA enforcement, API keys and audit logs.'],
        ];

        foreach ($kbCategories as $kbCat) {
            $cat = KnowledgeBaseCategory::updateOrCreate(
                ['company_id' => $companyId, 'slug' => $kbCat['slug']],
                [
                    'name' => $kbCat['name'],
                    'description' => $kbCat['desc'],
                    'icon' => $kbCat['icon'],
                    'status' => 'active',
                ]
            );

            // Create sample article
            KnowledgeBaseArticle::updateOrCreate(
                ['company_id' => $companyId, 'category_id' => $cat->id, 'slug' => 'how-to-configure-' . $kbCat['slug']],
                [
                    'title' => 'Comprehensive Guide: Setting up ' . $kbCat['name'] . ' in Falcon ERP',
                    'author_id' => $adminUser->id,
                    'description' => 'Step-by-step instructions on best enterprise workflows and configuration checklist.',
                    'content' => "## Overview\nThis guide covers the necessary parameters for enterprise deployment of " . $kbCat['name'] . ".\n\n### Step 1: Verification\nEnsure your user account has administrative privileges.\n\n### Step 2: Implementation\nNavigate to the appropriate submodule in the sidebar to review parameters.\n\n### Step 3: Audit\nEvery change is recorded in the immutable System Audit Log.",
                    'status' => 'published',
                    'visibility' => 'public',
                    'tags' => ['setup', 'best-practices', 'enterprise'],
                    'view_count' => rand(150, 850),
                    'helpful_count' => rand(25, 95),
                    'not_helpful_count' => rand(0, 3),
                    'published_at' => Carbon::now()->subMonths(1),
                ]
            );
        }

        // ==========================================
        // 2. MEMBERSHIP MODULE SEEDING
        // ==========================================
        // 2.1 Membership Plans
        $planStarter = MembershipPlan::updateOrCreate(
            ['company_id' => $companyId, 'code' => 'PLAN-STARTER'],
            [
                'name' => 'Starter Business Plan',
                'description' => 'Ideal for small retail businesses and single store operations.',
                'price' => 49.00,
                'billing_cycle' => 'monthly',
                'trial_period_days' => 14,
                'setup_fee' => 0.00,
                'discount' => 0.00,
                'tax_rate' => 18.00,
                'max_users' => 5,
                'storage_limit_gb' => 15,
                'features' => ['pos', 'inventory', 'sales', 'reports'],
                'status' => 'active',
            ]
        );

        $planPro = MembershipPlan::updateOrCreate(
            ['company_id' => $companyId, 'code' => 'PLAN-PROFESSIONAL'],
            [
                'name' => 'Professional Growth Plan',
                'description' => 'Complete enterprise ERP suite for growing multi-department businesses.',
                'price' => 149.00,
                'billing_cycle' => 'monthly',
                'trial_period_days' => 30,
                'setup_fee' => 50.00,
                'discount' => 10.00,
                'tax_rate' => 18.00,
                'max_users' => 25,
                'storage_limit_gb' => 100,
                'features' => ['crm', 'inventory', 'pos', 'hrm', 'finance', 'assets', 'documents', 'reports', 'support'],
                'status' => 'active',
            ]
        );

        $planEnterprise = MembershipPlan::updateOrCreate(
            ['company_id' => $companyId, 'code' => 'PLAN-ENTERPRISE-ANNUAL'],
            [
                'name' => 'Enterprise Global Unlimited',
                'description' => 'Unrestricted users, dedicated cloud storage, 24/7 Priority SLA and API access.',
                'price' => 1490.00,
                'billing_cycle' => 'yearly',
                'trial_period_days' => 30,
                'setup_fee' => 0.00,
                'discount' => 150.00,
                'tax_rate' => 18.00,
                'max_users' => 200,
                'storage_limit_gb' => 1000,
                'features' => ['crm', 'inventory', 'pos', 'hrm', 'finance', 'assets', 'documents', 'reports', 'support', 'api_access', 'custom_workflows', 'sla_guarantee'],
                'status' => 'active',
            ]
        );

        // 2.2 Membership Addons
        $addonUsers = MembershipAddon::updateOrCreate(
            ['company_id' => $companyId, 'code' => 'ADDON-EXTRA-USER-5'],
            [
                'name' => 'Pack of 5 Additional User Seats',
                'description' => 'Expand concurrency for sales and warehouse staff.',
                'price' => 25.00,
                'billing_cycle' => 'monthly',
                'quantity_limit' => 20,
                'feature_key' => 'extra_users',
                'status' => 'active',
            ]
        );

        $addonStorage = MembershipAddon::updateOrCreate(
            ['company_id' => $companyId, 'code' => 'ADDON-STORAGE-100GB'],
            [
                'name' => '100 GB High-Speed Cloud Storage',
                'description' => 'For document versioning, PDF invoice archives and media files.',
                'price' => 15.00,
                'billing_cycle' => 'monthly',
                'quantity_limit' => 50,
                'feature_key' => 'extra_storage',
                'status' => 'active',
            ]
        );

        $addonWhatsapp = MembershipAddon::updateOrCreate(
            ['company_id' => $companyId, 'code' => 'ADDON-WHATSAPP-DISPATCH'],
            [
                'name' => 'WhatsApp Enterprise Business Messaging',
                'description' => 'Automated receipt, payment link and invoice delivery via official Meta WhatsApp API.',
                'price' => 45.00,
                'billing_cycle' => 'monthly',
                'quantity_limit' => 5,
                'feature_key' => 'whatsapp_api',
                'status' => 'active',
            ]
        );

        // 2.3 Customer Memberships
        $sub1 = Membership::updateOrCreate(
            ['company_id' => $companyId, 'membership_number' => 'MEM-2026-0041'],
            [
                'customer_id' => $customers->first()->id,
                'plan_id' => $planPro->id,
                'status' => 'active',
                'start_date' => Carbon::now()->subMonths(3)->toDateString(),
                'expiry_date' => Carbon::now()->addMonths(9)->toDateString(),
                'billing_cycle' => 'monthly',
                'auto_renew' => true,
                'total_amount' => 164.02,
                'payment_method' => 'card',
                'notes' => 'Corporate account with automated renewal enabled.',
            ]
        );

        $sub2 = Membership::updateOrCreate(
            ['company_id' => $companyId, 'membership_number' => 'MEM-2026-0042'],
            [
                'customer_id' => $customers->last()->id,
                'plan_id' => $planEnterprise->id,
                'status' => 'active',
                'start_date' => Carbon::now()->subMonths(6)->toDateString(),
                'expiry_date' => Carbon::now()->addDays(12)->toDateString(), // Expiring in 12 days to test alert!
                'billing_cycle' => 'yearly',
                'auto_renew' => true,
                'total_amount' => 1581.20,
                'payment_method' => 'bank_transfer',
                'notes' => 'Annual enterprise license. Renewal notice triggered.',
            ]
        );

        // 2.4 Transactions
        MembershipTransaction::updateOrCreate(
            ['company_id' => $companyId, 'transaction_number' => 'TXN-MEM-99210'],
            [
                'customer_id' => $customers->first()->id,
                'membership_id' => $sub1->id,
                'amount' => 149.00,
                'tax' => 26.82,
                'discount' => 10.00,
                'total' => 165.82,
                'payment_method' => 'card',
                'status' => 'paid',
                'payment_reference' => 'STRIPE_CH_9281748291',
                'transaction_date' => Carbon::now()->subMonths(1),
                'notes' => 'Monthly subscription renewal',
            ]
        );

        MembershipTransaction::updateOrCreate(
            ['company_id' => $companyId, 'transaction_number' => 'TXN-MEM-99211'],
            [
                'customer_id' => $customers->last()->id,
                'membership_id' => $sub2->id,
                'amount' => 1490.00,
                'tax' => 268.20,
                'discount' => 150.00,
                'total' => 1608.20,
                'payment_method' => 'bank_transfer',
                'status' => 'paid',
                'payment_reference' => 'WIRE_REF_48102948',
                'transaction_date' => Carbon::now()->subMonths(6),
                'notes' => 'Annual enterprise upfront settlement',
            ]
        );

        // ==========================================
        // 3. SYSTEM & SETTINGS SEEDING
        // ==========================================
        // 3.1 Roles & Permissions Matrix
        $rolesList = [
            'Super Admin' => 'Full unrestricted system-wide administrative control',
            'Admin' => 'Operational enterprise administrator',
            'Manager' => 'Departmental manager with approval authority',
            'Support Agent' => 'Customer support desk and ticket resolution specialist',
            'Finance Manager' => 'Accounts, ledger, invoices and payment processing',
            'Sales Representative' => 'CRM leads, sales orders and quotations',
            'Inventory Manager' => 'Stock adjustments, transfers, warehouses and barcode scanning',
            'Viewer' => 'Read-only access across business dashboards',
        ];

        foreach ($rolesList as $rName => $rDesc) {
            Role::firstOrCreate(
                ['name' => $rName],
                ['display_name' => $rName, 'description' => $rDesc]
            );
        }

        // 3.2 Delete Requests
        DeleteRequest::updateOrCreate(
            ['company_id' => $companyId, 'module' => 'products', 'record_id' => 99],
            [
                'user_id' => $adminUser->id,
                'record_title' => 'Discontinued Hardware Dongle V1 (SKU: HW-DNG-01)',
                'reason' => 'Product line phased out in 2025; no remaining warehouse stock or active warranties.',
                'status' => 'pending',
            ]
        );

        DeleteRequest::updateOrCreate(
            ['company_id' => $companyId, 'module' => 'customers', 'record_id' => 14],
            [
                'user_id' => $adminUser->id,
                'record_title' => 'Test Account Sandbox Inc.',
                'reason' => 'Created during testing and verification phase.',
                'status' => 'approved',
                'reviewed_by' => $adminUser->id,
                'reviewed_at' => Carbon::now()->subDays(2),
                'review_notes' => 'Safe to purge - no financial transactions linked.',
            ]
        );

        // 3.3 System Settings Defaults
        $settings = [
            // General
            ['key' => 'company_name', 'value' => 'Falcon Global Enterprises Ltd.', 'type' => 'string', 'group' => 'general'],
            ['key' => 'company_legal_name', 'value' => 'Falcon Technologies Global Inc.', 'type' => 'string', 'group' => 'general'],
            ['key' => 'company_email', 'value' => 'support@falconerp.com', 'type' => 'string', 'group' => 'general'],
            ['key' => 'company_phone', 'value' => '+1 (555) 019-2834', 'type' => 'string', 'group' => 'general'],
            ['key' => 'company_website', 'value' => 'https://falconerp.com', 'type' => 'string', 'group' => 'general'],
            ['key' => 'company_address', 'value' => '100 Enterprise Way, Suite 400', 'type' => 'string', 'group' => 'general'],
            ['key' => 'company_city', 'value' => 'San Francisco', 'type' => 'string', 'group' => 'general'],
            ['key' => 'company_state', 'value' => 'California', 'type' => 'string', 'group' => 'general'],
            ['key' => 'company_country', 'value' => 'United States', 'type' => 'string', 'group' => 'general'],
            ['key' => 'company_pincode', 'value' => '94107', 'type' => 'string', 'group' => 'general'],
            ['key' => 'currency', 'value' => 'USD', 'type' => 'string', 'group' => 'general'],
            ['key' => 'currency_symbol', 'value' => '$', 'type' => 'string', 'group' => 'general'],
            ['key' => 'timezone', 'value' => 'America/Los_Angeles', 'type' => 'string', 'group' => 'general'],
            ['key' => 'date_format', 'value' => 'YYYY-MM-DD', 'type' => 'string', 'group' => 'general'],
            ['key' => 'time_format', 'value' => '12h', 'type' => 'string', 'group' => 'general'],

            // ERP Prefixes
            ['key' => 'prefix_invoice', 'value' => 'INV-', 'type' => 'string', 'group' => 'general'],
            ['key' => 'prefix_order', 'value' => 'SO-', 'type' => 'string', 'group' => 'general'],
            ['key' => 'prefix_ticket', 'value' => 'TCK-', 'type' => 'string', 'group' => 'general'],
            ['key' => 'prefix_asset', 'value' => 'AST-', 'type' => 'string', 'group' => 'general'],
            ['key' => 'prefix_document', 'value' => 'DOC-', 'type' => 'string', 'group' => 'general'],
            ['key' => 'prefix_membership', 'value' => 'MEM-', 'type' => 'string', 'group' => 'general'],

            // Security
            ['key' => 'password_min_length', 'value' => 8, 'type' => 'integer', 'group' => 'security'],
            ['key' => 'password_complexity', 'value' => true, 'type' => 'boolean', 'group' => 'security'],
            ['key' => 'password_expiry_days', 'value' => 90, 'type' => 'integer', 'group' => 'security'],
            ['key' => 'max_login_attempts', 'value' => 5, 'type' => 'integer', 'group' => 'security'],
            ['key' => 'lockout_duration_minutes', 'value' => 15, 'type' => 'integer', 'group' => 'security'],
            ['key' => 'session_timeout_minutes', 'value' => 60, 'type' => 'integer', 'group' => 'security'],
            ['key' => 'two_factor_auth', 'value' => false, 'type' => 'boolean', 'group' => 'security'],
            ['key' => 'force_2fa_for_admins', 'value' => true, 'type' => 'boolean', 'group' => 'security'],

            // Notifications
            ['key' => 'notif_channel_email', 'value' => true, 'type' => 'boolean', 'group' => 'notifications'],
            ['key' => 'notif_channel_sms', 'value' => false, 'type' => 'boolean', 'group' => 'notifications'],
            ['key' => 'notif_channel_whatsapp', 'value' => true, 'type' => 'boolean', 'group' => 'notifications'],
            ['key' => 'notif_channel_inapp', 'value' => true, 'type' => 'boolean', 'group' => 'notifications'],
            ['key' => 'event_ticket_created', 'value' => true, 'type' => 'boolean', 'group' => 'notifications'],
            ['key' => 'event_ticket_assigned', 'value' => true, 'type' => 'boolean', 'group' => 'notifications'],
            ['key' => 'event_membership_renewal', 'value' => true, 'type' => 'boolean', 'group' => 'notifications'],
            ['key' => 'event_payment_success', 'value' => true, 'type' => 'boolean', 'group' => 'notifications'],
            ['key' => 'event_low_stock', 'value' => true, 'type' => 'boolean', 'group' => 'notifications'],

            // SMTP
            ['key' => 'smtp_host', 'value' => 'smtp.mailgun.org', 'type' => 'string', 'group' => 'notifications'],
            ['key' => 'smtp_port', 'value' => 587, 'type' => 'integer', 'group' => 'notifications'],
            ['key' => 'smtp_username', 'value' => 'postmaster@falconerp.com', 'type' => 'string', 'group' => 'notifications'],
            ['key' => 'smtp_encryption', 'value' => 'tls', 'type' => 'string', 'group' => 'notifications'],
            ['key' => 'smtp_from_name', 'value' => 'Falcon ERP Notifications', 'type' => 'string', 'group' => 'notifications'],
            ['key' => 'smtp_from_email', 'value' => 'noreply@falconerp.com', 'type' => 'string', 'group' => 'notifications'],
        ];

        foreach ($settings as $s) {
            SystemSetting::updateOrCreate(
                ['key' => $s['key']],
                ['value' => $s['value'], 'type' => $s['type'], 'group' => $s['group']]
            );
        }

        // 3.4 API Keys
        ApiKey::updateOrCreate(
            ['company_id' => $companyId, 'key' => 'falc_live_9a8b7c6d5e4f3a2b1c0d'],
            [
                'user_id' => $adminUser->id,
                'name' => 'Falcon Mobile POS Sync API Key',
                'secret_hash' => Hash::make('secret_live_key_pos_99812'),
                'scopes' => ['pos:read', 'pos:write', 'inventory:read', 'orders:write'],
                'last_used_at' => Carbon::now()->subMinutes(12),
                'expires_at' => Carbon::now()->addYear(),
                'is_revoked' => false,
            ]
        );

        // 3.5 Webhooks
        Webhook::updateOrCreate(
            ['company_id' => $companyId, 'name' => 'Slack Operations Incident Channel'],
            [
                'url' => 'https://api.falconerp.local/webhooks/incident-alerts',
                'event' => 'ticket.created',
                'secret' => 'whsec_9812481928419284',
                'status' => 'active',
                'retry_policy' => '3_retries',
                'last_status_code' => 200,
                'last_triggered_at' => Carbon::now()->subHours(2),
            ]
        );

        // 3.6 Initial Audit Log
        SystemAuditLog::log('system', 'initialize_enterprise_modules', null, null, ['status' => 'initialized'], $companyId, $adminUser->id);
    }
}
