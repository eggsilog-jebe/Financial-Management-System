<?php

declare(strict_types=1);

namespace App\Services\Accounting;

use App\Models\Account;
use App\Models\DisbursementVoucher;
use App\Models\Invoice;
use App\Models\OfficialReceipt;
use App\Models\PatientAccount;
use App\Models\Payment;
use App\Models\PurchaseBill;
use App\Models\Vendor;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

final class GlobalSearchService
{
    /**
     * Complete list of all FMS/HIMS modules and submodules for instant search and navigation.
     *
     * @var array<int, array{title: string, module: string, category: string, route: string, icon: string, keywords: string}>
     */
    private const NAVIGATION_REGISTRY = [
        // General Ledger
        [
            'title' => 'Journal Entries',
            'module' => 'General Ledger',
            'category' => 'Journal & Accounts',
            'route' => 'gl.journal-entries',
            'icon' => 'ph-pencil-line',
            'keywords' => 'journal entries debit credit general adjusting closing voucher gl',
        ],
        [
            'title' => 'Chart of Accounts (COA)',
            'module' => 'General Ledger',
            'category' => 'Journal & Accounts',
            'route' => 'gl.chart-of-accounts',
            'icon' => 'ph-tree-structure',
            'keywords' => 'chart of accounts coa gl accounts assets liabilities equity revenue expense',
        ],
        [
            'title' => 'Ledger Books',
            'module' => 'General Ledger',
            'category' => 'Journal & Accounts',
            'route' => 'gl.ledger-books',
            'icon' => 'ph-books',
            'keywords' => 'ledger books general ledger subsidiary ledger running balance',
        ],
        [
            'title' => 'Trial Balance',
            'module' => 'General Ledger',
            'category' => 'Trial Balance & Closing',
            'route' => 'gl.trial-balance',
            'icon' => 'ph-chart-bar',
            'keywords' => 'trial balance gaap ifrs debit credit equilibrium balance check',
        ],
        [
            'title' => 'Period-End Closing',
            'module' => 'General Ledger',
            'category' => 'Trial Balance & Closing',
            'route' => 'gl.period-end-closing',
            'icon' => 'ph-lock-key',
            'keywords' => 'period end closing fiscal month lock close hard lock',
        ],
        // Accounts Payable
        [
            'title' => 'Vendor Directory',
            'module' => 'Accounts Payable',
            'category' => 'Suppliers & Invoices',
            'route' => 'ap.vendors',
            'icon' => 'ph-address-book',
            'keywords' => 'vendor directory suppliers legal name tin contact terms ap',
        ],
        [
            'title' => 'Vendor Invoices & Tax',
            'module' => 'Accounts Payable',
            'category' => 'Suppliers & Invoices',
            'route' => 'ap.invoices',
            'icon' => 'ph-receipt',
            'keywords' => 'vendor invoices vouchers tax bir 2307 supplier bill payables',
        ],
        [
            'title' => '3-Way Verification / Purchase Bills',
            'module' => 'Accounts Payable',
            'category' => 'Procurement & Matching',
            'route' => 'ap.purchase-bills',
            'icon' => 'ph-file-search',
            'keywords' => 'purchase bills 3-way matching po grn receiving report verification variance',
        ],
        [
            'title' => 'Payable Aging Schedule (5-Tier)',
            'module' => 'Accounts Payable',
            'category' => 'Procurement & Matching',
            'route' => 'ap.payable-aging',
            'icon' => 'ph-hourglass',
            'keywords' => 'payable aging schedule 30 60 90 days overdue supplier liabilities',
        ],
        [
            'title' => 'AP Payment Approvals',
            'module' => 'Accounts Payable',
            'category' => 'Approvals & Payouts',
            'route' => 'ap.ap-approvals',
            'icon' => 'ph-check-circle',
            'keywords' => 'ap payment approvals release disbursement voucher signoff cfo',
        ],
        // Accounts Receivable
        [
            'title' => 'Invoicing & Patient Billing',
            'module' => 'Accounts Receivable',
            'category' => 'Billing & Patients',
            'route' => 'ar.billing',
            'icon' => 'ph-file-text',
            'keywords' => 'invoicing patient billing hospital encounter mrn charges philhealth hmo',
        ],
        [
            'title' => 'Patient Accounts Directory',
            'module' => 'Accounts Receivable',
            'category' => 'Billing & Patients',
            'route' => 'ar.customers',
            'icon' => 'ph-users',
            'keywords' => 'patient accounts directory guarantor mrn inpatient outpatient debtors',
        ],
        [
            'title' => 'Credit Notes & Adjustments',
            'module' => 'Accounts Receivable',
            'category' => 'Billing & Patients',
            'route' => 'ar.credit-notes',
            'icon' => 'ph-arrows-clockwise',
            'keywords' => 'credit notes adjustments writeoff senior pwd discount reversal ar',
        ],
        [
            'title' => 'Malasakit Subsidies (RA 11463)',
            'module' => 'Accounts Receivable',
            'category' => 'Subsidies & Aging',
            'route' => 'ar.malasakit.index',
            'icon' => 'ph-first-aid',
            'keywords' => 'malasakit assistance guarantee letters pcso dswd doh maip indigent subsidies',
        ],
        [
            'title' => 'Receivable Aging Schedule',
            'module' => 'Accounts Receivable',
            'category' => 'Subsidies & Aging',
            'route' => 'ar.ar-aging',
            'icon' => 'ph-clock-countdown',
            'keywords' => 'receivable aging schedule overdue patient hmo claims 30 60 90 120 days',
        ],
        [
            'title' => 'Statements of Account (SOA)',
            'module' => 'Accounts Receivable',
            'category' => 'Subsidies & Aging',
            'route' => 'ar.statements',
            'icon' => 'ph-scroll',
            'keywords' => 'statements of account soa customer ledger patient balance forward statement',
        ],
        // Disbursement Management
        [
            'title' => 'Payment Vouchers (DV)',
            'module' => 'Disbursement Management',
            'category' => 'Vouchers & Approvals',
            'route' => 'disbursement.payment-requests',
            'icon' => 'ph-receipt',
            'keywords' => 'payment vouchers dv disbursement requests claims checks payouts',
        ],
        [
            'title' => 'Voucher Approvals & Release',
            'module' => 'Disbursement Management',
            'category' => 'Vouchers & Approvals',
            'route' => 'disbursement.disbursement-approval',
            'icon' => 'ph-seal-check',
            'keywords' => 'voucher approvals release cfo check release cashier payout',
        ],
        [
            'title' => 'Check Register & Custody',
            'module' => 'Disbursement Management',
            'category' => 'Payment Channels',
            'route' => 'disbursement.check-register',
            'icon' => 'ph-money',
            'keywords' => 'check register checks custody cleared void manager check printing',
        ],
        [
            'title' => 'EFT Bank Transfers',
            'module' => 'Disbursement Management',
            'category' => 'Payment Channels',
            'route' => 'disbursement.eft-transfers',
            'icon' => 'ph-arrows-left-right',
            'keywords' => 'eft bank transfers pesonet instapay online electronic payout',
        ],
        [
            'title' => 'Petty Cash Custody Funds',
            'module' => 'Disbursement Management',
            'category' => 'Payment Channels',
            'route' => 'disbursement.petty-cash',
            'icon' => 'ph-vault',
            'keywords' => 'petty cash revolving custody drawer replenishment imprest expense slips',
        ],
        // Collection Management
        [
            'title' => 'Cashier POS Settlement Desk',
            'module' => 'Collection Management',
            'category' => 'Cashier Operations',
            'route' => 'collection.cashier-desk',
            'icon' => 'ph-hand-coins',
            'keywords' => 'cashier desk pos collection tender official receipt cash shift drawer settlement collection management',
        ],
        [
            'title' => 'Payment Receipts (OR Hub)',
            'module' => 'Collection Management',
            'category' => 'Cashier Operations',
            'route' => 'collection.receipts',
            'icon' => 'ph-receipt',
            'keywords' => 'payment receipts official receipt or hub collections cash gcash maya card collection management',
        ],
        [
            'title' => 'Deposit Slip Batches',
            'module' => 'Collection Management',
            'category' => 'Banking & Settlement',
            'route' => 'collection.deposit-slips',
            'icon' => 'ph-newspaper-clipping',
            'keywords' => 'deposit slips batch closed shifts bank remittance cash count collection management',
        ],
        [
            'title' => 'Bank Deposits & Clearing',
            'module' => 'Collection Management',
            'category' => 'Banking & Settlement',
            'route' => 'collection.bank-deposits',
            'icon' => 'ph-building-bank',
            'keywords' => 'bank deposits clearing teller validation cash check deposit clearing collection management',
        ],
        [
            'title' => 'Payment Gateway Logs',
            'module' => 'Collection Management',
            'category' => 'Banking & Settlement',
            'route' => 'collection.payment-gateways',
            'icon' => 'ph-cpu',
            'keywords' => 'payment gateway logs digital gcash maya pos transaction audit stream collection management',
        ],
        // Budget Management
        [
            'title' => 'Fiscal Year Planning',
            'module' => 'Budget Management',
            'category' => 'Planning & Allocations',
            'route' => 'budget.fiscal-planning',
            'icon' => 'ph-calendar-check',
            'keywords' => 'fiscal year planning annual budget appropriations revenue targets margin',
        ],
        [
            'title' => 'Budget Allocation',
            'module' => 'Budget Management',
            'category' => 'Planning & Allocations',
            'route' => 'budget.budget-allocation',
            'icon' => 'ph-chart-pie-slice',
            'keywords' => 'budget allocation cost center expenditure encumbrances commitments',
        ],
        [
            'title' => 'Departmental Budgets',
            'module' => 'Budget Management',
            'category' => 'Planning & Allocations',
            'route' => 'budget.departmental-budgets',
            'icon' => 'ph-users-three',
            'keywords' => 'departmental budgets wing burn rate utilization clinical administrative',
        ],
        [
            'title' => 'Variance Analysis',
            'module' => 'Budget Management',
            'category' => 'Controls & Variance',
            'route' => 'budget.variance-analysis',
            'icon' => 'ph-trend-up',
            'keywords' => 'variance analysis favorable unfavorable actual budget comparison',
        ],
        [
            'title' => 'Budget Reallocations',
            'module' => 'Budget Management',
            'category' => 'Controls & Variance',
            'route' => 'budget.reallocations',
            'icon' => 'ph-arrows-left-right',
            'keywords' => 'budget reallocations transfer inter-departmental funds approval review',
        ],
        // User & Security
        [
            'title' => 'User Accounts Directory',
            'module' => 'User & Security',
            'category' => 'Identity & Workstations',
            'route' => 'user-security.users',
            'icon' => 'ph-user-gear',
            'keywords' => 'user accounts directory personnel roles cfo finance cashier staff access',
        ],
        [
            'title' => 'Workstation Binding',
            'module' => 'User & Security',
            'category' => 'Identity & Workstations',
            'route' => 'user-security.workstations',
            'icon' => 'ph-desktop',
            'keywords' => 'workstation binding authorized terminal computer mac fingerprint security',
        ],
        [
            'title' => 'CAS Tamper-Proof Audit Trail',
            'module' => 'User & Security',
            'category' => 'Audit & Compliance',
            'route' => 'user-security.audit-trail',
            'icon' => 'ph-shield-check',
            'keywords' => 'cas audit trail tamper proof system logs security incidents logins',
        ],
    ];

    /**
     * Execute comprehensive multi-entity search across hospital system.
     *
     * @return array{
     *   query: string,
     *   context: array{route: string, module: string, submodule: string, label: string}|null,
     *   contextual_results: array<int, array{type: string, title: string, subtitle: string, meta: string, url: string, icon: string, badge: string}>,
     *   categories: array<string, array{label: string, icon: string, items: array<int, array{type: string, title: string, subtitle: string, meta: string, url: string, icon: string, badge: string}>}>,
     *   navigation: array<int, array{title: string, module: string, category: string, url: string, icon: string}>,
     *   total_matches: int
     * }
     */
    public function search(string $query, ?string $currentRoute = null): array
    {
        $cleanQuery = trim($query);
        $contextInfo = $this->resolveRouteContext($currentRoute);

        if (mb_strlen($cleanQuery) < 1) {
            return [
                'query'              => '',
                'predictions'        => [],
                'context'            => $contextInfo,
                'contextual_results' => [],
                'categories'         => [],
                'navigation'         => [],
                'total_matches'      => 0,
            ];
        }

        $term = mb_strtolower($cleanQuery);
        $totalMatches = 0;

        // 1. Search Submodule Navigation registry
        $navigationMatches = $this->searchNavigation($term);
        $totalMatches += count($navigationMatches);

        // 2. Query Entity Categories in Parallel / Pipeline
        $accounts = $this->searchAccounts($cleanQuery);
        $totalMatches += count($accounts);

        $patients = $this->searchPatients($cleanQuery);
        $totalMatches += count($patients);

        $invoices = $this->searchInvoices($cleanQuery);
        $totalMatches += count($invoices);

        $vendors = $this->searchVendors($cleanQuery);
        $totalMatches += count($vendors);

        $bills = $this->searchPurchaseBills($cleanQuery);
        $totalMatches += count($bills);

        $vouchers = $this->searchDisbursementVouchers($cleanQuery);
        $totalMatches += count($vouchers);

        $receipts = $this->searchOfficialReceipts($cleanQuery);
        $totalMatches += count($receipts);

        // 3. Assemble Categories
        $categories = [];

        if (! empty($accounts)) {
            $categories['accounts'] = [
                'label' => 'GL Accounts',
                'icon'  => 'ph-tree-structure',
                'items' => $accounts,
            ];
        }

        if (! empty($patients)) {
            $categories['patients'] = [
                'label' => 'Patient Accounts (AR)',
                'icon'  => 'ph-users',
                'items' => $patients,
            ];
        }

        if (! empty($invoices)) {
            $categories['invoices'] = [
                'label' => 'Patient Invoices / Billing',
                'icon'  => 'ph-file-text',
                'items' => $invoices,
            ];
        }

        if (! empty($vendors)) {
            $categories['vendors'] = [
                'label' => 'Suppliers & Vendors (AP)',
                'icon'  => 'ph-buildings',
                'items' => $vendors,
            ];
        }

        if (! empty($bills)) {
            $categories['bills'] = [
                'label' => 'Purchase Bills / Matching',
                'icon'  => 'ph-file-search',
                'items' => $bills,
            ];
        }

        if (! empty($vouchers)) {
            $categories['vouchers'] = [
                'label' => 'Disbursement Vouchers',
                'icon'  => 'ph-receipt',
                'items' => $vouchers,
            ];
        }

        if (! empty($receipts)) {
            $categories['receipts'] = [
                'label' => 'Payment Receipts (OR)',
                'icon'  => 'ph-hand-coins',
                'items' => $receipts,
            ];
        }

        // 4. Extract Contextual Results (Matching active submodule/module)
        $contextualResults = [];
        if ($contextInfo) {
            $currentModule = $contextInfo['module'];
            $currentSubmodule = $contextInfo['submodule'];

            if ($currentModule === 'Accounts Payable') {
                if (str_contains($currentSubmodule, 'Vendor') && ! empty($vendors)) {
                    $contextualResults = array_merge($contextualResults, $vendors);
                } elseif (str_contains($currentSubmodule, 'Bills') && ! empty($bills)) {
                    $contextualResults = array_merge($contextualResults, $bills);
                } else {
                    $contextualResults = array_merge($contextualResults, $vendors, $bills);
                }
            } elseif ($currentModule === 'Accounts Receivable') {
                if (str_contains($currentSubmodule, 'Patient') && ! empty($patients)) {
                    $contextualResults = array_merge($contextualResults, $patients);
                } elseif (str_contains($currentSubmodule, 'Billing') && ! empty($invoices)) {
                    $contextualResults = array_merge($contextualResults, $invoices);
                } else {
                    $contextualResults = array_merge($contextualResults, $invoices, $patients);
                }
            } elseif ($currentModule === 'General Ledger') {
                if (! empty($accounts)) {
                    $contextualResults = array_merge($contextualResults, $accounts);
                }
            } elseif ($currentModule === 'Disbursement Management') {
                if (! empty($vouchers)) {
                    $contextualResults = array_merge($contextualResults, $vouchers);
                }
            } elseif ($currentModule === 'Collection Management' || $currentModule === 'Collection & Treasury') {
                if (! empty($receipts)) {
                    $contextualResults = array_merge($contextualResults, $receipts);
                }
                if (! empty($invoices)) {
                    $contextualResults = array_merge($contextualResults, array_slice($invoices, 0, 2));
                }
            }
        }

        // 5. Assemble Unified Predictive Suggestions
        $predictions = [];

        // Pages / Submodules
        foreach ($navigationMatches as $nav) {
            $predictions[] = [
                'title'    => $nav['title'],
                'subtitle' => $nav['module'] . ' • ' . $nav['category'],
                'type'     => 'Page',
                'badge'    => $nav['module'],
                'icon'     => $nav['icon'],
                'url'      => $nav['url'],
            ];
        }

        // GL Accounts
        foreach ($accounts as $acc) {
            $predictions[] = [
                'title'    => $acc['title'],
                'subtitle' => $acc['subtitle'],
                'type'     => 'GL Account',
                'badge'    => $acc['badge'],
                'icon'     => $acc['icon'],
                'url'      => $acc['url'],
            ];
        }

        // AP Vendors
        foreach ($vendors as $v) {
            $predictions[] = [
                'title'    => $v['title'],
                'subtitle' => $v['subtitle'],
                'type'     => 'Vendor',
                'badge'    => 'AP Vendor',
                'icon'     => $v['icon'],
                'url'      => $v['url'],
            ];
        }

        // AR Patients
        foreach ($patients as $p) {
            $predictions[] = [
                'title'    => $p['title'],
                'subtitle' => $p['subtitle'] . ' • ' . $p['meta'],
                'type'     => 'Patient',
                'badge'    => 'AR Patient',
                'icon'     => $p['icon'],
                'url'      => $p['url'],
            ];
        }

        // Invoices
        foreach ($invoices as $inv) {
            $predictions[] = [
                'title'    => $inv['title'],
                'subtitle' => $inv['subtitle'] . ' • ' . $inv['meta'],
                'type'     => 'Patient Bill',
                'badge'    => $inv['badge'],
                'icon'     => $inv['icon'],
                'url'      => $inv['url'],
            ];
        }

        // Purchase Bills
        foreach ($bills as $b) {
            $predictions[] = [
                'title'    => $b['title'],
                'subtitle' => $b['subtitle'] . ' • ' . $b['meta'],
                'type'     => 'Purchase Bill',
                'badge'    => $b['badge'],
                'icon'     => $b['icon'],
                'url'      => $b['url'],
            ];
        }

        // Disbursement Vouchers
        foreach ($vouchers as $vc) {
            $predictions[] = [
                'title'    => $vc['title'],
                'subtitle' => $vc['subtitle'] . ' • ' . $vc['meta'],
                'type'     => 'Voucher',
                'badge'    => $vc['badge'],
                'icon'     => $vc['icon'],
                'url'      => $vc['url'],
            ];
        }

        // Official Receipts
        foreach ($receipts as $rc) {
            $predictions[] = [
                'title'    => $rc['title'],
                'subtitle' => $rc['subtitle'] . ' • ' . $rc['meta'],
                'type'     => 'Receipt',
                'badge'    => $rc['badge'],
                'icon'     => $rc['icon'],
                'url'      => $rc['url'],
            ];
        }

        return [
            'query'              => $cleanQuery,
            'predictions'        => array_slice($predictions, 0, 8),
            'context'            => $contextInfo,
            'contextual_results' => array_slice($contextualResults, 0, 5),
            'categories'         => $categories,
            'navigation'         => array_slice($navigationMatches, 0, 4),
            'total_matches'      => $totalMatches,
        ];
    }

    /**
     * Map current route name to user-friendly Module and Submodule names.
     *
     * @return array{route: string, module: string, submodule: string, label: string}|null
     */
    public function resolveRouteContext(?string $currentRoute): ?array
    {
        if (empty($currentRoute)) {
            return null;
        }

        return match (true) {
            str_starts_with($currentRoute, 'ap.vendors') => [
                'route'     => $currentRoute,
                'module'    => 'Accounts Payable',
                'submodule' => 'Vendor Directory',
                'label'     => 'Vendors',
            ],
            str_starts_with($currentRoute, 'ap.invoices') => [
                'route'     => $currentRoute,
                'module'    => 'Accounts Payable',
                'submodule' => 'Vendor Invoices & Tax',
                'label'     => 'Invoices & Vouchers',
            ],
            str_starts_with($currentRoute, 'ap.purchase-bills') => [
                'route'     => $currentRoute,
                'module'    => 'Accounts Payable',
                'submodule' => 'Purchase Bills / 3-Way',
                'label'     => 'Purchase Bills',
            ],
            str_starts_with($currentRoute, 'ap.payable-aging') => [
                'route'     => $currentRoute,
                'module'    => 'Accounts Payable',
                'submodule' => 'Payable Aging',
                'label'     => 'AP Aging',
            ],
            str_starts_with($currentRoute, 'ap.ap-approvals') || str_starts_with($currentRoute, 'ap.payment-approvals') => [
                'route'     => $currentRoute,
                'module'    => 'Accounts Payable',
                'submodule' => 'Payment Approvals',
                'label'     => 'AP Approvals',
            ],
            str_starts_with($currentRoute, 'ar.billing') || str_starts_with($currentRoute, 'ar.invoices') => [
                'route'     => $currentRoute,
                'module'    => 'Accounts Receivable',
                'submodule' => 'Invoicing & Patient Billing',
                'label'     => 'Patient Billing',
            ],
            str_starts_with($currentRoute, 'ar.customers') || str_starts_with($currentRoute, 'ar.patients') => [
                'route'     => $currentRoute,
                'module'    => 'Accounts Receivable',
                'submodule' => 'Patient Accounts Directory',
                'label'     => 'Patient Accounts',
            ],
            str_starts_with($currentRoute, 'ar.credit-notes') => [
                'route'     => $currentRoute,
                'module'    => 'Accounts Receivable',
                'submodule' => 'Credit Notes & Adjustments',
                'label'     => 'Credit Notes',
            ],
            str_starts_with($currentRoute, 'ar.malasakit') => [
                'route'     => $currentRoute,
                'module'    => 'Accounts Receivable',
                'submodule' => 'Malasakit Assistance',
                'label'     => 'Malasakit Subsidies',
            ],
            str_starts_with($currentRoute, 'ar.ar-aging') => [
                'route'     => $currentRoute,
                'module'    => 'Accounts Receivable',
                'submodule' => 'Receivable Aging',
                'label'     => 'AR Aging',
            ],
            str_starts_with($currentRoute, 'ar.statements') => [
                'route'     => $currentRoute,
                'module'    => 'Accounts Receivable',
                'submodule' => 'Customer Statements (SOA)',
                'label'     => 'Statements of Account',
            ],
            str_starts_with($currentRoute, 'gl.journal-entries') || str_starts_with($currentRoute, 'accounting.general-ledger') => [
                'route'     => $currentRoute,
                'module'    => 'General Ledger',
                'submodule' => 'Journal Entries',
                'label'     => 'Journal Entries',
            ],
            str_starts_with($currentRoute, 'gl.chart-of-accounts') => [
                'route'     => $currentRoute,
                'module'    => 'General Ledger',
                'submodule' => 'Chart of Accounts (COA)',
                'label'     => 'Chart of Accounts',
            ],
            str_starts_with($currentRoute, 'gl.ledger-books') => [
                'route'     => $currentRoute,
                'module'    => 'General Ledger',
                'submodule' => 'Ledger Books',
                'label'     => 'Ledger Books',
            ],
            str_starts_with($currentRoute, 'gl.trial-balance') => [
                'route'     => $currentRoute,
                'module'    => 'General Ledger',
                'submodule' => 'Trial Balance',
                'label'     => 'Trial Balance',
            ],
            str_starts_with($currentRoute, 'gl.period-end-closing') => [
                'route'     => $currentRoute,
                'module'    => 'General Ledger',
                'submodule' => 'Period-End Closing',
                'label'     => 'Period Closing',
            ],
            str_starts_with($currentRoute, 'collection.cashier') || str_starts_with($currentRoute, 'accounting.cashier') => [
                'route'     => $currentRoute,
                'module'    => 'Collection Management',
                'submodule' => 'Cashier POS Settlement',
                'label'     => 'Cashier Desk',
            ],
            str_starts_with($currentRoute, 'collection.receipts') => [
                'route'     => $currentRoute,
                'module'    => 'Collection Management',
                'submodule' => 'Payment Receipts (OR Hub)',
                'label'     => 'Payment Receipts',
            ],
            str_starts_with($currentRoute, 'collection.deposit-slips') => [
                'route'     => $currentRoute,
                'module'    => 'Collection Management',
                'submodule' => 'Deposit Slip Batches',
                'label'     => 'Deposit Slips',
            ],
            str_starts_with($currentRoute, 'collection.bank-deposits') => [
                'route'     => $currentRoute,
                'module'    => 'Collection Management',
                'submodule' => 'Bank Deposits & Clearing',
                'label'     => 'Bank Deposits',
            ],
            str_starts_with($currentRoute, 'collection.payment-gateways') => [
                'route'     => $currentRoute,
                'module'    => 'Collection Management',
                'submodule' => 'Payment Gateway Logs',
                'label'     => 'Gateway Logs',
            ],
            str_starts_with($currentRoute, 'disbursement.payment-requests') => [
                'route'     => $currentRoute,
                'module'    => 'Disbursement Management',
                'submodule' => 'Payment Vouchers (DV)',
                'label'     => 'Payment Vouchers',
            ],
            str_starts_with($currentRoute, 'disbursement.disbursement-approval') => [
                'route'     => $currentRoute,
                'module'    => 'Disbursement Management',
                'submodule' => 'Voucher Approvals & Release',
                'label'     => 'Disbursement Approvals',
            ],
            str_starts_with($currentRoute, 'disbursement.check-register') => [
                'route'     => $currentRoute,
                'module'    => 'Disbursement Management',
                'submodule' => 'Check Register & Custody',
                'label'     => 'Check Register',
            ],
            str_starts_with($currentRoute, 'disbursement.eft-transfers') => [
                'route'     => $currentRoute,
                'module'    => 'Disbursement Management',
                'submodule' => 'EFT Bank Transfers',
                'label'     => 'EFT Transfers',
            ],
            str_starts_with($currentRoute, 'disbursement.petty-cash') => [
                'route'     => $currentRoute,
                'module'    => 'Disbursement Management',
                'submodule' => 'Petty Cash Custody Funds',
                'label'     => 'Petty Cash',
            ],
            str_starts_with($currentRoute, 'budget.fiscal-planning') => [
                'route'     => $currentRoute,
                'module'    => 'Budget Management',
                'submodule' => 'Fiscal Year Planning',
                'label'     => 'Fiscal Planning',
            ],
            str_starts_with($currentRoute, 'budget.budget-allocation') => [
                'route'     => $currentRoute,
                'module'    => 'Budget Management',
                'submodule' => 'Budget Allocation',
                'label'     => 'Budget Allocation',
            ],
            str_starts_with($currentRoute, 'budget.departmental-budgets') => [
                'route'     => $currentRoute,
                'module'    => 'Budget Management',
                'submodule' => 'Departmental Budgets',
                'label'     => 'Department Budgets',
            ],
            str_starts_with($currentRoute, 'budget.variance-analysis') => [
                'route'     => $currentRoute,
                'module'    => 'Budget Management',
                'submodule' => 'Variance Analysis',
                'label'     => 'Variance Analysis',
            ],
            str_starts_with($currentRoute, 'budget.reallocations') => [
                'route'     => $currentRoute,
                'module'    => 'Budget Management',
                'submodule' => 'Budget Reallocations',
                'label'     => 'Budget Reallocations',
            ],
            str_starts_with($currentRoute, 'user-security.users') => [
                'route'     => $currentRoute,
                'module'    => 'User & Security',
                'submodule' => 'User Accounts Directory',
                'label'     => 'User Accounts',
            ],
            str_starts_with($currentRoute, 'user-security.workstations') => [
                'route'     => $currentRoute,
                'module'    => 'User & Security',
                'submodule' => 'Workstation Binding',
                'label'     => 'Workstations',
            ],
            str_starts_with($currentRoute, 'user-security.audit-trail') || str_starts_with($currentRoute, 'accounting.audit-log') => [
                'route'     => $currentRoute,
                'module'    => 'User & Security',
                'submodule' => 'CAS Tamper-Proof Audit Trail',
                'label'     => 'Audit Trail',
            ],
            default => null,
        };
    }

    /**
     * Search navigation registry by term.
     *
     * @return array<int, array{title: string, module: string, category: string, url: string, icon: string}>
     */
    private function searchNavigation(string $term): array
    {
        $titleMatches = [];
        $otherMatches = [];

        foreach (self::NAVIGATION_REGISTRY as $nav) {
            $title = mb_strtolower($nav['title']);
            $module = mb_strtolower($nav['module']);
            $category = mb_strtolower($nav['category']);
            $keywords = mb_strtolower($nav['keywords']);

            if (str_contains($title, $term)) {
                $titleMatches[] = [
                    'title'    => $nav['title'],
                    'module'   => $nav['module'],
                    'category' => $nav['category'],
                    'url'      => route($nav['route']),
                    'icon'     => $nav['icon'],
                ];
            } elseif (str_contains($module, $term) || str_contains($category, $term) || str_contains($keywords, $term)) {
                $otherMatches[] = [
                    'title'    => $nav['title'],
                    'module'   => $nav['module'],
                    'category' => $nav['category'],
                    'url'      => route($nav['route']),
                    'icon'     => $nav['icon'],
                ];
            }
        }

        return array_merge($titleMatches, $otherMatches);
    }

    /**
     * Provide quick navigation recommendations when search query is empty.
     *
     * @param array{route: string, module: string, submodule: string, label: string}|null $contextInfo
     * @return array<int, array{title: string, module: string, category: string, url: string, icon: string}>
     */
    private function getTopNavigationShortcuts(?array $contextInfo): array
    {
        $topKeys = [
            'gl.journal-entries',
            'gl.chart-of-accounts',
            'ap.vendors',
            'ap.purchase-bills',
            'ar.billing',
            'collection.cashier-desk',
            'disbursement.payment-requests',
            'budget.fiscal-planning',
        ];

        $shortcuts = [];
        foreach (self::NAVIGATION_REGISTRY as $nav) {
            if (in_array($nav['route'], $topKeys, true)) {
                $shortcuts[] = [
                    'title'    => $nav['title'],
                    'module'   => $nav['module'],
                    'category' => $nav['category'],
                    'url'      => route($nav['route']),
                    'icon'     => $nav['icon'],
                ];
            }
        }

        return array_slice($shortcuts, 0, 4);
    }

    /**
     * Search General Ledger Accounts.
     *
     * @return array<int, array{type: string, title: string, subtitle: string, meta: string, url: string, icon: string, badge: string}>
     */
    private function searchAccounts(string $query): array
    {
        return Account::select('id', 'code', 'name', 'category', 'normal_balance', 'department')
            ->where(function ($q) use ($query): void {
                $q->where('code', 'LIKE', "%{$query}%")
                  ->orWhere('name', 'LIKE', "%{$query}%");
            })
            ->limit(5)
            ->get()
            ->map(fn (Account $acc): array => [
                'type'     => 'Account',
                'title'    => "{$acc->code} — {$acc->name}",
                'subtitle' => "Category: {$acc->category} • Normal: {$acc->normal_balance}",
                'meta'     => $acc->department ? "Dept: {$acc->department}" : $acc->normal_balance,
                'url'      => route('gl.chart-of-accounts', ['q' => $acc->code]),
                'icon'     => 'ph-tree-structure',
                'badge'    => $acc->category,
            ])
            ->all();
    }

    /**
     * Search Patient Accounts (AR).
     *
     * @return array<int, array{type: string, title: string, subtitle: string, meta: string, url: string, icon: string, badge: string}>
     */
    private function searchPatients(string $query): array
    {
        return PatientAccount::select('id', 'patient_id_number', 'full_name', 'current_balance', 'status', 'admission_type')
            ->where(function ($q) use ($query): void {
                $q->where('full_name', 'LIKE', "%{$query}%")
                  ->orWhere('patient_id_number', 'LIKE', "%{$query}%");
            })
            ->limit(5)
            ->get()
            ->map(fn (PatientAccount $p): array => [
                'type'     => 'Patient',
                'title'    => $p->full_name,
                'subtitle' => 'MRN: ' . ($p->patient_id_number ?: 'N/A') . ' • ' . ($p->admission_type ?: 'General'),
                'meta'     => 'Bal: ₱' . number_format((float) $p->current_balance, 2),
                'url'      => route('ar.customers', ['search' => $p->patient_id_number ?: $p->full_name]),
                'icon'     => 'ph-user',
                'badge'    => $p->status ?: 'Active',
            ])
            ->all();
    }

    /**
     * Search Patient Billing Invoices (AR).
     *
     * @return array<int, array{type: string, title: string, subtitle: string, meta: string, url: string, icon: string, badge: string}>
     */
    private function searchInvoices(string $query): array
    {
        return Invoice::with('patientAccount:id,full_name,patient_id_number')
            ->select('id', 'invoice_number', 'patient_account_id', 'total_amount', 'patient_payable', 'status')
            ->where(function ($q) use ($query): void {
                $q->where('invoice_number', 'LIKE', "%{$query}%")
                  ->orWhereHas('patientAccount', fn ($pq) => $pq->where('full_name', 'LIKE', "%{$query}%")->orWhere('patient_id_number', 'LIKE', "%{$query}%"));
            })
            ->limit(5)
            ->get()
            ->map(fn (Invoice $inv): array => [
                'type'     => 'Patient Bill',
                'title'    => "Invoice #{$inv->invoice_number}",
                'subtitle' => 'Patient: ' . ($inv->patientAccount?->full_name ?? 'N/A'),
                'meta'     => 'Due: ₱' . number_format((float) $inv->patient_payable, 2),
                'url'      => route('ar.billing', ['search' => $inv->invoice_number]),
                'icon'     => 'ph-file-text',
                'badge'    => $inv->status,
            ])
            ->all();
    }

    /**
     * Search Suppliers & Vendors (AP).
     *
     * @return array<int, array{type: string, title: string, subtitle: string, meta: string, url: string, icon: string, badge: string}>
     */
    private function searchVendors(string $query): array
    {
        return Vendor::select('id', 'code', 'name', 'tin', 'status')
            ->where(function ($q) use ($query): void {
                $q->where('name', 'LIKE', "%{$query}%")
                  ->orWhere('code', 'LIKE', "%{$query}%")
                  ->orWhere('tin', 'LIKE', "%{$query}%");
            })
            ->limit(5)
            ->get()
            ->map(fn (Vendor $v): array => [
                'type'     => 'Vendor',
                'title'    => "{$v->code} — {$v->name}",
                'subtitle' => 'TIN: ' . ($v->tin ?: 'N/A'),
                'meta'     => $v->status,
                'url'      => route('ap.vendors', ['search' => $v->code ?: $v->name]),
                'icon'     => 'ph-buildings',
                'badge'    => $v->status,
            ])
            ->all();
    }

    /**
     * Search Purchase Bills (AP).
     *
     * @return array<int, array{type: string, title: string, subtitle: string, meta: string, url: string, icon: string, badge: string}>
     */
    private function searchPurchaseBills(string $query): array
    {
        return PurchaseBill::with('vendor:id,name,code')
            ->select('id', 'bill_number', 'vendor_id', 'total_amount', 'paid_amount', 'status')
            ->where(function ($q) use ($query): void {
                $q->where('bill_number', 'LIKE', "%{$query}%")
                  ->orWhereHas('vendor', fn ($vq) => $vq->where('name', 'LIKE', "%{$query}%")->orWhere('code', 'LIKE', "%{$query}%"));
            })
            ->limit(5)
            ->get()
            ->map(fn (PurchaseBill $b): array => [
                'type'     => 'Purchase Bill',
                'title'    => "Bill #{$b->bill_number}",
                'subtitle' => 'Supplier: ' . ($b->vendor?->name ?? 'N/A'),
                'meta'     => '₱' . number_format((float) $b->total_amount, 2),
                'url'      => route('ap.purchase-bills', ['search' => $b->bill_number]),
                'icon'     => 'ph-file-search',
                'badge'    => $b->status,
            ])
            ->all();
    }

    /**
     * Search Disbursement Vouchers.
     *
     * @return array<int, array{type: string, title: string, subtitle: string, meta: string, url: string, icon: string, badge: string}>
     */
    private function searchDisbursementVouchers(string $query): array
    {
        return DisbursementVoucher::select('id', 'voucher_number', 'payee_name', 'net_disbursed_amount', 'status')
            ->where(function ($q) use ($query): void {
                $q->where('voucher_number', 'LIKE', "%{$query}%")
                  ->orWhere('payee_name', 'LIKE', "%{$query}%")
                  ->orWhere('check_or_eft_ref', 'LIKE', "%{$query}%");
            })
            ->limit(5)
            ->get()
            ->map(fn (DisbursementVoucher $v): array => [
                'type'     => 'Voucher',
                'title'    => "Voucher #{$v->voucher_number}",
                'subtitle' => 'Payee: ' . ($v->payee_name ?: 'N/A'),
                'meta'     => '₱' . number_format((float) $v->net_disbursed_amount, 2),
                'url'      => route('disbursement.payment-requests', ['search' => $v->voucher_number]),
                'icon'     => 'ph-receipt',
                'badge'    => $v->status,
            ])
            ->all();
    }

    /**
     * Search Payment Receipts & Official Receipts (OR).
     *
     * @return array<int, array{type: string, title: string, subtitle: string, meta: string, url: string, icon: string, badge: string}>
     */
    private function searchOfficialReceipts(string $query): array
    {
        return Payment::with(['officialReceipt:id,payment_id,or_number,payor_name', 'patientAccount:id,full_name'])
            ->select('id', 'payment_reference', 'amount', 'payment_method', 'patient_account_id')
            ->where(function ($q) use ($query): void {
                $q->where('payment_reference', 'LIKE', "%{$query}%")
                  ->orWhereHas('officialReceipt', fn ($oq) => $oq->where('or_number', 'LIKE', "%{$query}%")->orWhere('payor_name', 'LIKE', "%{$query}%"))
                  ->orWhereHas('patientAccount', fn ($pq) => $pq->where('full_name', 'LIKE', "%{$query}%"));
            })
            ->limit(5)
            ->get()
            ->map(function (Payment $p): array {
                $orNo = $p->officialReceipt?->or_number ?: $p->payment_reference;
                $payor = $p->officialReceipt?->payor_name ?: ($p->patientAccount?->full_name ?? 'Payor');

                return [
                    'type'     => 'Official Receipt',
                    'title'    => "OR #{$orNo}",
                    'subtitle' => "Payor: {$payor} • {$p->payment_method}",
                    'meta'     => '₱' . number_format((float) $p->amount, 2),
                    'url'      => route('collection.receipts', ['search' => $orNo]),
                    'icon'     => 'ph-hand-coins',
                    'badge'    => $p->payment_method,
                ];
            })
            ->all();
    }
}
