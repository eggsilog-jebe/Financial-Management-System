@php
  $currentRoute = request()->route()?->getName() ?? '';

  $isGl = request()->routeIs('gl.*') || request()->routeIs('accounting.general-ledger.*') || request()->routeIs('accounting.period-close.*');
  $isAp = request()->routeIs('ap.*');
  $isAr = request()->routeIs('ar.*');
  $isDisbursement = request()->routeIs('disbursement.*');
  $isCollection = request()->routeIs('collection.*') || request()->routeIs('accounting.cashier.*');
  $isBudget = request()->routeIs('budget.*');
  $isUserSecurity = request()->routeIs('user-security.*') || request()->routeIs('accounting.audit-log');

  $moduleData = null;

  if ($isGl) {
      $moduleData = [
          'title' => 'General Ledger (GL)',
          'subtitle' => 'Financial Accounting Core & GAAP/IFRS Books',
          'icon' => 'ph-book-open-text',
          'categories' => [
              [
                  'name' => 'Journal & Accounts',
                  'icon' => 'ph-book-open',
                  'items' => [
                      [
                          'title' => 'Journal Entries',
                          'route' => 'gl.journal-entries',
                          'icon' => 'ph-pencil-line',
                          'matches' => ['gl.journal-entries', 'accounting.general-ledger.*', 'gl.post-entry'],
                      ],
                      [
                          'title' => 'Chart of Accounts (COA)',
                          'route' => 'gl.chart-of-accounts',
                          'icon' => 'ph-tree-structure',
                          'matches' => ['gl.chart-of-accounts*'],
                      ],
                      [
                          'title' => 'Ledger Books',
                          'route' => 'gl.ledger-books',
                          'icon' => 'ph-books',
                          'matches' => ['gl.ledger-books*'],
                      ],
                  ],
              ],
              [
                  'name' => 'Trial Balance & Closing',
                  'icon' => 'ph-scales',
                  'items' => [
                      [
                          'title' => 'Trial Balance',
                          'route' => 'gl.trial-balance',
                          'icon' => 'ph-chart-bar',
                          'matches' => ['gl.trial-balance*'],
                      ],
                      [
                          'title' => 'Period-End Closing',
                          'route' => 'gl.period-end-closing',
                          'icon' => 'ph-lock-key',
                          'matches' => ['gl.period-end-closing*', 'accounting.period-close.*'],
                      ],
                  ],
              ],
          ],
      ];
  } elseif ($isAp) {
      $moduleData = [
          'title' => 'Accounts Payable (AP)',
          'subtitle' => 'Hospital Procurement, 3-Way Matching & Liabilities',
          'icon' => 'ph-receipt',
          'categories' => [
              [
                  'name' => 'Suppliers & Invoices',
                  'icon' => 'ph-buildings',
                  'items' => [
                      [
                          'title' => 'Vendor Directory',
                          'route' => 'ap.vendors',
                          'icon' => 'ph-address-book',
                          'matches' => ['ap.vendors', 'ap.vendors.index', 'ap.vendors.*'],
                      ],
                      [
                          'title' => 'Vendor Invoices & Tax',
                          'route' => 'ap.invoices',
                          'icon' => 'ph-receipt',
                          'matches' => ['ap.invoices*'],
                      ],
                  ],
              ],
              [
                  'name' => 'Procurement & Matching',
                  'icon' => 'ph-check-square-offset',
                  'items' => [
                      [
                          'title' => '3-Way Verification / Bills',
                          'route' => 'ap.purchase-bills',
                          'icon' => 'ph-file-search',
                          'matches' => ['ap.purchase-bills*'],
                      ],
                      [
                          'title' => 'Payable Aging (5-Tier)',
                          'route' => 'ap.payable-aging',
                          'icon' => 'ph-hourglass',
                          'matches' => ['ap.payable-aging*'],
                      ],
                  ],
              ],
              [
                  'name' => 'Approvals & Payouts',
                  'icon' => 'ph-stamp',
                  'items' => [
                      [
                          'title' => 'AP Payment Approvals',
                          'route' => 'ap.ap-approvals',
                          'icon' => 'ph-check-circle',
                          'matches' => ['ap.ap-approvals*', 'ap.payment-approvals.*'],
                      ],
                  ],
              ],
          ],
      ];
  } elseif ($isAr) {
      $moduleData = [
          'title' => 'Accounts Receivable (AR)',
          'subtitle' => 'Patient Encounters, Claims & Revenue Settlement',
          'icon' => 'ph-currency-circle-dollar',
          'categories' => [
              [
                  'name' => 'Billing & Patients',
                  'icon' => 'ph-receipt',
                  'items' => [
                      [
                          'title' => 'Invoicing & Patient Billing',
                          'route' => 'ar.billing',
                          'icon' => 'ph-file-text',
                          'matches' => ['ar.billing', 'ar.invoices.index', 'ar.invoices.*'],
                      ],
                      [
                          'title' => 'Patient Accounts Directory',
                          'route' => 'ar.customers',
                          'icon' => 'ph-users',
                          'matches' => ['ar.customers', 'ar.patients.index', 'ar.patients.*'],
                      ],
                      [
                          'title' => 'Credit Notes & Adjustments',
                          'route' => 'ar.credit-notes',
                          'icon' => 'ph-arrows-clockwise',
                          'matches' => ['ar.credit-notes*'],
                      ],
                  ],
              ],
              [
                  'name' => 'Subsidies & Aging',
                  'icon' => 'ph-heart-straight',
                  'items' => [
                      [
                          'title' => 'Malasakit Subsidies (RA 11463)',
                          'route' => 'ar.malasakit.index',
                          'icon' => 'ph-first-aid',
                          'matches' => ['ar.malasakit.*'],
                      ],
                      [
                          'title' => 'Receivable Aging Schedule',
                          'route' => 'ar.ar-aging',
                          'icon' => 'ph-clock-countdown',
                          'matches' => ['ar.ar-aging*'],
                      ],
                      [
                          'title' => 'Statements of Account (SOA)',
                          'route' => 'ar.statements',
                          'icon' => 'ph-scroll',
                          'matches' => ['ar.statements*'],
                      ],
                  ],
              ],
          ],
      ];
  } elseif ($isDisbursement) {
      $moduleData = [
          'title' => 'Disbursement Management',
          'subtitle' => 'Disbursement Vouchers, Check Registry & Payment Payouts',
          'icon' => 'ph-arrows-out',
          'categories' => [
              [
                  'name' => 'Vouchers & Approvals',
                  'icon' => 'ph-article',
                  'items' => [
                      [
                          'title' => 'Payment Vouchers (DV)',
                          'route' => 'disbursement.payment-requests',
                          'icon' => 'ph-receipt',
                          'matches' => ['disbursement.payment-requests*'],
                      ],
                      [
                          'title' => 'Voucher Approvals & Release',
                          'route' => 'disbursement.disbursement-approval',
                          'icon' => 'ph-seal-check',
                          'matches' => ['disbursement.disbursement-approval*'],
                      ],
                  ],
              ],
              [
                  'name' => 'Payment Channels',
                  'icon' => 'ph-credit-card',
                  'items' => [
                      [
                          'title' => 'Check Register & Custody',
                          'route' => 'disbursement.check-register',
                          'icon' => 'ph-money',
                          'matches' => ['disbursement.check-register*'],
                      ],
                      [
                          'title' => 'EFT Bank Transfers',
                          'route' => 'disbursement.eft-transfers',
                          'icon' => 'ph-arrows-left-right',
                          'matches' => ['disbursement.eft-transfers*'],
                      ],
                      [
                          'title' => 'Petty Cash Custody Funds',
                          'route' => 'disbursement.petty-cash',
                          'icon' => 'ph-vault',
                          'matches' => ['disbursement.petty-cash*'],
                      ],
                  ],
              ],
          ],
      ];
  } elseif ($isCollection) {
      $moduleData = [
          'title' => 'Collection Management',
          'subtitle' => 'Cashier Shift Desks, Official Receipts & Deposit Batches',
          'icon' => 'ph-hand-coins',
          'categories' => [
              [
                  'name' => 'Cashier Operations',
                  'icon' => 'ph-storefront',
                  'items' => [
                      [
                          'title' => 'Cashier POS Settlement Desk',
                          'route' => 'collection.cashier-desk',
                          'icon' => 'ph-hand-coins',
                          'matches' => ['collection.cashier-desk*', 'accounting.cashier*'],
                      ],
                      [
                          'title' => 'Payment Receipts (OR Hub)',
                          'route' => 'collection.receipts',
                          'icon' => 'ph-receipt',
                          'matches' => ['collection.receipts*'],
                      ],
                  ],
              ],
              [
                  'name' => 'Banking & Settlement',
                  'icon' => 'ph-bank',
                  'items' => [
                      [
                          'title' => 'Deposit Slip Batches',
                          'route' => 'collection.deposit-slips',
                          'icon' => 'ph-newspaper-clipping',
                          'matches' => ['collection.deposit-slips*'],
                      ],
                      [
                          'title' => 'Bank Deposits & Clearing',
                          'route' => 'collection.bank-deposits',
                          'icon' => 'ph-building-bank',
                          'matches' => ['collection.bank-deposits*'],
                      ],
                      [
                          'title' => 'Payment Gateway Logs',
                          'route' => 'collection.payment-gateways',
                          'icon' => 'ph-cpu',
                          'matches' => ['collection.payment-gateways*'],
                      ],
                  ],
              ],
          ],
      ];
  } elseif ($isBudget) {
      $moduleData = [
          'title' => 'Budget Management',
          'subtitle' => 'Fiscal Appropriations, Commitments & Variance Tracking',
          'icon' => 'ph-calculator',
          'categories' => [
              [
                  'name' => 'Planning & Allocations',
                  'icon' => 'ph-calendar-check',
                  'items' => [
                      [
                          'title' => 'Fiscal Year Planning',
                          'route' => 'budget.fiscal-planning',
                          'icon' => 'ph-calendar-check',
                          'matches' => ['budget.fiscal-planning*'],
                      ],
                      [
                          'title' => 'Budget Allocation',
                          'route' => 'budget.budget-allocation',
                          'icon' => 'ph-chart-pie-slice',
                          'matches' => ['budget.budget-allocation*'],
                      ],
                      [
                          'title' => 'Departmental Budgets',
                          'route' => 'budget.departmental-budgets',
                          'icon' => 'ph-users-three',
                          'matches' => ['budget.departmental-budgets*'],
                      ],
                  ],
              ],
              [
                  'name' => 'Controls & Variance',
                  'icon' => 'ph-chart-line-up',
                  'items' => [
                      [
                          'title' => 'Variance Analysis',
                          'route' => 'budget.variance-analysis',
                          'icon' => 'ph-trend-up',
                          'matches' => ['budget.variance-analysis*'],
                      ],
                      [
                          'title' => 'Budget Reallocations',
                          'route' => 'budget.reallocations',
                          'icon' => 'ph-arrows-left-right',
                          'matches' => ['budget.reallocations*'],
                      ],
                  ],
              ],
          ],
      ];
  } elseif ($isUserSecurity) {
      $moduleData = [
          'title' => 'User & Security',
          'subtitle' => 'Identity, Workstation Computer Binding & CAS Audit Logs',
          'icon' => 'ph-shield-check',
          'categories' => [
              [
                  'name' => 'Identity & Workstations',
                  'icon' => 'ph-users',
                  'items' => [
                      [
                          'title' => 'User Accounts Directory',
                          'route' => 'user-security.users',
                          'icon' => 'ph-user-gear',
                          'matches' => ['user-security.users*'],
                      ],
                      [
                          'title' => 'Workstation Binding',
                          'route' => 'user-security.workstations',
                          'icon' => 'ph-desktop',
                          'matches' => ['user-security.workstations*'],
                      ],
                  ],
              ],
              [
                  'name' => 'Audit & Compliance',
                  'icon' => 'ph-lock-key',
                  'items' => [
                      [
                          'title' => 'CAS Tamper-Proof Audit Trail',
                          'route' => 'user-security.audit-trail',
                          'icon' => 'ph-shield-check',
                          'matches' => ['user-security.audit-trail*', 'accounting.audit-log*'],
                      ],
                  ],
              ],
          ],
      ];
  }

  // Pre-calculate active flags for categories and items
  if ($moduleData) {
      foreach ($moduleData['categories'] as &$cat) {
          $cat['hasActive'] = false;
          foreach ($cat['items'] as &$item) {
              $item['active'] = false;
              foreach ($item['matches'] as $matchPattern) {
                  if (request()->routeIs($matchPattern)) {
                      $item['active'] = true;
                      $cat['hasActive'] = true;
                      break;
                  }
              }
          }
      }
      unset($cat, $item);
  }
@endphp

@if($moduleData)
<nav 
  aria-label="Sub-module category navigation"
  class="flex items-center gap-2 sm:gap-2.5 flex-wrap relative z-20"
  x-data="{
    openCategory: null,
    toggle(catKey) {
      this.openCategory = (this.openCategory === catKey) ? null : catKey;
    },
    close() {
      this.openCategory = null;
    }
  }"
  @click.outside="close()"
  @keydown.escape.window="close()"
>
  @foreach($moduleData['categories'] as $catIdx => $category)
    <div class="relative inline-block text-left">
      
      <!-- Category Pill Button (Active vs Inactive matching Photo 2) -->
      <button 
        type="button" 
        @click="toggle('cat_{{ $catIdx }}')"
        :aria-expanded="openCategory === 'cat_{{ $catIdx }}'"
        class="group inline-flex items-center gap-2 rounded-2xl px-3.5 py-2 text-xs sm:text-sm font-semibold transition-all select-none {{ $category['hasActive'] ? 'border border-emerald-500/70 bg-emerald-50/70 text-emerald-700 shadow-sm dark:border-emerald-500/60 dark:bg-emerald-950/50 dark:text-emerald-300' : 'border border-slate-200/90 bg-white text-slate-700 shadow-sm hover:border-slate-300 hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800/80' }}"
        :class="openCategory === 'cat_{{ $catIdx }}' ? 'ring-2 ring-emerald-500/30 border-emerald-500' : ''"
      >
        <i class="ph-bold {{ $category['icon'] }} text-sm shrink-0 {{ $category['hasActive'] ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-500 dark:text-slate-400 group-hover:text-slate-700 dark:group-hover:text-slate-200' }}"></i>
        <span class="truncate">{{ $category['name'] }}</span>
        <i 
          class="ph-bold text-xs shrink-0 transition-transform duration-200"
          :class="openCategory === 'cat_{{ $catIdx }}' ? 'ph-caret-up text-emerald-600 dark:text-emerald-400' : 'ph-caret-down text-slate-400'"
        ></i>
      </button>

      <!-- Dropdown Popover (Floating Card matching Photo 2, aligned to left) -->
      <div 
        x-show="openCategory === 'cat_{{ $catIdx }}'" 
        x-cloak
        x-transition:enter="ease-out duration-150"
        x-transition:enter-start="opacity-0 translate-y-1 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="ease-in duration-100"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 translate-y-1 scale-95"
        class="absolute left-0 mt-2 z-50 w-64 sm:w-72 rounded-2xl bg-white p-1.5 shadow-2xl ring-1 ring-slate-200/90 dark:bg-slate-900 dark:ring-slate-800 focus:outline-none"
        role="menu"
        aria-orientation="vertical"
      >
        <div class="px-3 py-1.5 text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 border-b border-slate-100 dark:border-slate-800/80 mb-1 flex items-center justify-between">
          <span>{{ $category['name'] }}</span>
          <span class="font-mono text-[9px] text-slate-400">{{ count($category['items']) }} views</span>
        </div>

        <div class="space-y-0.5">
          @foreach($category['items'] as $subItem)
            <a 
              href="{{ route($subItem['route']) }}" 
              class="group flex items-center justify-between px-3 py-2 rounded-xl text-xs sm:text-sm transition-all {{ $subItem['active'] ? 'bg-emerald-50/80 text-emerald-800 font-semibold dark:bg-emerald-950/50 dark:text-emerald-300' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/80 dark:text-slate-400 dark:hover:text-white dark:hover:bg-slate-800/70 font-medium' }}"
              role="menuitem"
            >
              <div class="flex items-center gap-2.5 min-w-0">
                <i class="ph-bold {{ $subItem['icon'] }} text-base shrink-0 {{ $subItem['active'] ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400 group-hover:text-slate-600 dark:text-slate-500 dark:group-hover:text-slate-300' }}"></i>
                <span class="truncate">{{ $subItem['title'] }}</span>
              </div>

              <!-- Active Blue/Emerald Dot Indicator (Exact match of Reference Photo 2) -->
              @if($subItem['active'])
                <span class="h-2 w-2 rounded-full bg-emerald-600 dark:bg-emerald-400 shadow-sm shrink-0" title="Current Active View"></span>
              @endif
            </a>
          @endforeach
        </div>
      </div>

    </div>
  @endforeach
</nav>
@endif
