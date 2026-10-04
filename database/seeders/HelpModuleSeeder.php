<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\HelpDocumentCategory;
use App\Models\HelpDocument;
use App\Models\HelpChangelog;

class HelpModuleSeeder extends Seeder
{
    public function run(): void
    {
        // 1. CATEGORIES & DOCUMENTS
        $categoriesData = [
            [
                'name' => 'Getting Started',
                'slug' => 'getting-started',
                'icon' => 'Compass',
                'description' => 'Introduction to the Falcon & Dreams ERP platform, system concepts, and initial walkthrough.',
                'sort_order' => 1,
                'docs' => [
                    [
                        'title' => 'System Architecture & Overview',
                        'slug' => 'system-overview',
                        'excerpt' => 'Understand the core modules, role permissions, multi-company hierarchy, and high-performance design of Dreams ERP.',
                        'read_time' => '4 min',
                        'content' => "## Overview\nDreams ERP is a complete, scalable enterprise resource planning suite built on top of high-concurrency Laravel 11 REST APIs and a responsive React 19 single-page application.\n\n### Core Tenets\n- **Multi-tenant & Multi-Company Isolation**: Complete data segregation for corporations and subsidiaries.\n- **Real-Time Synchronisation**: Automated inventory allocation, order dispatching, and cash-flow recalculations.\n- **Extensible Architecture**: Modular service providers with fine-grained RBAC permissions.\n\n```bash\n# Check system environment status\nphp artisan about\n```\n\n### Navigation Fundamentals\nThe application features a modern collapsible left navigation pane, unified global search (Ctrl + K), notification center, and quick-action toolbars on every workspace page."
                    ],
                    [
                        'title' => 'Quick Start Guide for New Teams',
                        'slug' => 'quick-start',
                        'excerpt' => 'Step-by-step instructions to initialize your company profile, invite team members, and configure tax rules.',
                        'read_time' => '6 min',
                        'content' => "## Quick Setup Walkthrough\nWelcome to your new enterprise workspace! Follow these three simple setup phases:\n\n### Step 1: Configure Legal Entity & Company Profile\nNavigate to **Settings > General Settings** to configure your primary operating currency, tax identification number (EIN / GSTIN), financial year start date, and company address.\n\n### Step 2: Set Up Roles & Team Access\nUnder **System > Roles & Permissions**, establish your operational tiers:\n1. Super Admin\n2. Finance Director\n3. Warehouse Manager\n4. Sales Executive\n5. HR Specialist\n\n### Step 3: Populate Inventory Master Catalog\nImport existing SKU catalogs via the Bulk CSV uploader in **Inventory > Products**."
                    ]
                ]
            ],
            [
                'name' => 'Installation & Setup',
                'slug' => 'installation-setup',
                'icon' => 'Server',
                'description' => 'Server provisioning, environment setup, database optimization, and deployment procedures.',
                'sort_order' => 2,
                'docs' => [
                    [
                        'title' => 'Server Prerequisites & Stack Configuration',
                        'slug' => 'server-prerequisites',
                        'excerpt' => 'Hardware sizing, PHP extensions, MySQL 8 settings, and Node.js requirements for production servers.',
                        'read_time' => '5 min',
                        'content' => "## Infrastructure Requirements\nEnsure your target hosting environment satisfies these requirements:\n\n- **PHP Version**: 8.2 or 8.3 with extensions: `pdo_mysql`, `mbstring`, `bcmath`, `curl`, `xml`, `intl`\n- **Database**: MySQL 8.0+ or MariaDB 10.11+\n- **Frontend Toolchain**: Node.js 20+ LTS, npm 10+\n- **Cache & Queues**: Redis 7+ recommended for background queues and high-volume sessions.\n\n```bash\n# Verify PHP extensions\nphp -m | grep -E 'pdo_mysql|mbstring|bcmath'\n```"
                    ],
                    [
                        'title' => 'Environment Variables & Sanctum Setup',
                        'slug' => 'environment-sanctum',
                        'excerpt' => 'Configure .env parameters, CORS policies, JWT Sanctum lifetime, and database connection pools.',
                        'read_time' => '7 min',
                        'content' => "## Configuring .env\nSet up your backend `.env` file with accurate application credentials:\n\n```env\nAPP_NAME=\"Falcon ERP Enterprise\"\nAPP_ENV=production\nAPP_KEY=base64:your-secret-key\nAPP_URL=http://your-domain.com\n\nDB_CONNECTION=mysql\nDB_HOST=127.0.0.1\nDB_PORT=3306\nDB_DATABASE=erp_system\nDB_USERNAME=root\nDB_PASSWORD=secret\n\nSANCTUM_STATEFUL_DOMAINS=localhost:5173,your-domain.com\nSESSION_DRIVER=database\n```"
                    ]
                ]
            ],
            [
                'name' => 'Modules Overview',
                'slug' => 'modules-overview',
                'icon' => 'Boxes',
                'description' => 'Detailed technical documentation covering HRM, Sales, Inventory, Finance, and POS.',
                'sort_order' => 3,
                'docs' => [
                    [
                        'title' => 'Inventory & Stock Management',
                        'slug' => 'inventory-management',
                        'excerpt' => 'Multi-warehouse stock tracking, batch numbers, automated reorder thresholds, and stock transfers.',
                        'read_time' => '8 min',
                        'content' => "## Inventory System Architecture\nThe Inventory module powers unified stock lifecycle tracking across physical and virtual warehouses:\n\n### Key Capabilities\n- **Live Balance Tracking**: Continuous valuation calculation using FIFO and Weighted Average algorithms.\n- **Multi-Warehouse Transfers**: Two-phase transfer dispatch and receiving with discrepancy auditing.\n- **Low Stock Alerts**: Configurable automated notifications triggered when safety margins are breached.\n- **Barcode & QR Generation**: High-density 2D and 1D barcode rendering for thermal label printers."
                    ],
                    [
                        'title' => 'Point of Sale (POS) Engine',
                        'slug' => 'pos-engine',
                        'excerpt' => 'Touch-optimized cashier terminal with split tenders, barcode scanner integration, and offline-first resiliency.',
                        'read_time' => '6 min',
                        'content' => "## Point of Sale Architecture\nThe POS sub-system delivers instantaneous order checkout designed for retail shops, cafes, and wholesale counters.\n\n### Features\n1. Quick keyboard accelerators (F2 for Search, F8 for Tender, F12 for Print).\n2. Multi-tender split transactions (Cash, Visa/Mastercard, Store Credit, UPI).\n3. Hardware receipt printer integration via direct raw thermal ESC/POS commands.\n4. Cash register open/close drawer audits with discrepancy tracking."
                    ]
                ]
            ],
            [
                'name' => 'REST API Reference',
                'slug' => 'api-reference',
                'icon' => 'Code',
                'description' => 'Complete specification of all REST endpoints, Sanctum Bearer tokens, request payloads, and rate limits.',
                'sort_order' => 4,
                'docs' => [
                    [
                        'title' => 'Authentication & Bearer Token Headers',
                        'slug' => 'api-authentication',
                        'excerpt' => 'How to authenticate REST API requests using Laravel Sanctum personal access tokens.',
                        'read_time' => '4 min',
                        'content' => "## API Authentication\nAll private ERP endpoints require a valid Sanctum bearer token in the `Authorization` header.\n\n### Request Header\n```http\nAuthorization: Bearer 1|u98sdjfs72834jh238947sdfjh\nAccept: application/json\nContent-Type: application/json\n```\n\n### Standard Error Codes\n- `401 Unauthorized`: Token is expired or missing.\n- `403 Forbidden`: Authenticated user lacks permission.\n- `422 Unprocessable Entity`: Validation payload failed."
                    ],
                    [
                        'title' => 'Webhooks & External System Integration',
                        'slug' => 'api-webhooks',
                        'excerpt' => 'Receive instant HTTP webhooks on sales orders, payment confirmations, and employee onboarding.',
                        'read_time' => '5 min',
                        'content' => "## Webhook Dispatcher\nERP events trigger signed HMAC-SHA256 HTTP POST payloads to your designated endpoints.\n\n### Event Types\n- `order.created`\n- `payment.received`\n- `inventory.low_stock`\n- `employee.created`\n\nEach payload includes `X-Falcon-Signature` header for cryptographic authenticity verification."
                    ]
                ]
            ],
            [
                'name' => 'FAQ & Troubleshooting',
                'slug' => 'faq-troubleshooting',
                'icon' => 'HelpCircle',
                'description' => 'Frequently asked questions, common resolution steps, error handling, and support contacts.',
                'sort_order' => 5,
                'docs' => [
                    [
                        'title' => 'Frequently Asked Questions (FAQ)',
                        'slug' => 'faq-general',
                        'excerpt' => 'Answers to common queries regarding multi-currency conversions, user licenses, and data backups.',
                        'read_time' => '5 min',
                        'content' => "## Frequently Asked Questions\n\n#### Q: How often does the system backup database records?\nA: Automated database dumps occur nightly at 02:00 UTC with 30-day encrypted retention policies.\n\n#### Q: Can I export financial ledger reports to Excel?\nA: Yes, all data grids support streaming export to standard CSV and Microsoft Excel formats.\n\n#### Q: What browsers are supported?\nA: Chrome 95+, Edge 95+, Firefox 90+, Safari 15+ across desktop, laptop, and tablet viewports."
                    ]
                ]
            ]
        ];

        foreach ($categoriesData as $catData) {
            $docs = $catData['docs'];
            unset($catData['docs']);

            $category = HelpDocumentCategory::updateOrCreate(
                ['slug' => $catData['slug']],
                $catData
            );

            foreach ($docs as $index => $docData) {
                HelpDocument::updateOrCreate(
                    ['slug' => $docData['slug']],
                    array_merge($docData, [
                        'category_id' => $category->id,
                        'sort_order' => $index + 1,
                        'status' => 'published'
                    ])
                );
            }
        }

        // 2. CHANGELOGS
        $changelogs = [
            [
                'version' => 'v2.4.0',
                'title' => 'Enterprise UI Interface & Multi-Level Help System',
                'release_date' => '2026-10-02',
                'tag' => 'Latest Release',
                'description' => 'Comprehensive UI showcase module with 36 dedicated pages, Recharts analytics, drag-and-drop Kanban persistence, and multi-level nested navigation.',
                'changes' => [
                    'features' => [
                        'Added complete UI Interface module with Base UI (20 pages), Advanced UI, Forms, Tables, Charts, and Icons.',
                        'Implemented deep 4-level nested Help navigation system with independent expand/collapse states.',
                        'Introduced dynamic Documentation knowledge base with category filtering and instant article search.',
                        'Added real-time Apex & Chart.js responsive dashboards with multi-metric toggles.'
                    ],
                    'improvements' => [
                        'Upgraded sidebar multi-parent accordion to maintain simultaneous independent expansion.',
                        'Streamlined REST API table pagination with server-side column sorting and CSV streaming.',
                        'Enhanced dark mode contrast across all form inputs and button groups.'
                    ],
                    'bug_fixes' => [
                        'Fixed chevron state synchronization when navigating between nested route levels.',
                        'Resolved z-index clipping on modal backdrops in low-height mobile viewports.'
                    ]
                ]
            ],
            [
                'version' => 'v2.3.0',
                'title' => 'Enhanced HRM & Employee Training Suite',
                'release_date' => '2026-09-18',
                'tag' => 'Stable',
                'description' => 'Expanded human resources management with automated payroll structures, performance review cycles, and training program tracking.',
                'changes' => [
                    'features' => [
                        'Full Employee Salary Structure designer with automated tax bracket deductions.',
                        'Performance appraisal 360-degree review cycles with custom KPI weights.',
                        'Digital employee document archive with expiration notifications.'
                    ],
                    'improvements' => [
                        'Optimized payroll calculation batch job performance by 45%.',
                        'Added multi-currency salary compensation support.'
                    ],
                    'bug_fixes' => [
                        'Fixed attendance timestamp shift calculation for overnight employee rotas.'
                    ]
                ]
            ],
            [
                'version' => 'v2.2.0',
                'title' => 'Advanced Point of Sale & Thermal Print Engine',
                'release_date' => '2026-08-30',
                'tag' => 'Stable',
                'description' => 'High-throughput retail POS terminal with offline transaction queue, split payments, and customized barcode receipt printing.',
                'changes' => [
                    'features' => [
                        'Touch-friendly POS checkout interface with fast category filtering.',
                        'Raw ESC/POS thermal printer driver with customizable logo and footer messages.',
                        'Barcode and QR code generator for shelf labels and product packaging.'
                    ],
                    'improvements' => [
                        'Accelerated product search indexing for catalogs with over 100,000 SKUs.',
                        'Added cash register open/close drawer audit reconciliations.'
                    ],
                    'bug_fixes' => [
                        'Resolved receipt print font alignment for multi-byte character sets.'
                    ]
                ]
            ],
            [
                'version' => 'v2.0.0',
                'title' => 'Major 2.0 Enterprise Architecture Upgrade',
                'release_date' => '2026-07-15',
                'tag' => 'Major Release',
                'description' => 'Complete architectural evolution migrating to React 19 single-page client, Laravel 11 high-speed REST framework, and FilamentPHP v5 administration.',
                'changes' => [
                    'features' => [
                        'Full rewrite with modular modern React architecture and Tailwind CSS design tokens.',
                        'Integrated Filament v5 administration portal with fine-grained access control.',
                        'Universal multi-company tenant segregation with audit trail logging.'
                    ],
                    'improvements' => [
                        'Reduced initial page payload by 60% through code splitting and tree shaking.',
                        'Migrated to modern Lucide icon design system.'
                    ],
                    'breaking_changes' => [
                        'Legacy v1 REST endpoints deprecated; update API clients to use /api/v1/ route prefix.',
                        'Updated token header specification to standard Bearer token format.'
                    ]
                ]
            ],
            [
                'version' => 'v1.0.0',
                'title' => 'Initial Commercial Release of Dreams ERP',
                'release_date' => '2026-01-10',
                'tag' => 'Foundational',
                'description' => 'The premier commercial launch of Dreams ERP featuring core Inventory, Sales, CRM, Finance, and User Management modules.',
                'changes' => [
                    'features' => [
                        'Core Inventory tracking with multi-warehouse support.',
                        'Sales Order, Quotations, and Invoice generation engine.',
                        'Double-entry General Ledger bookkeeping and financial cashflow reports.'
                    ]
                ]
            ]
        ];

        foreach ($changelogs as $item) {
            HelpChangelog::updateOrCreate(
                ['version' => $item['version']],
                $item
            );
        }
    }
}
