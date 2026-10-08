@extends('layouts.app')

@section('title', 'Financial Reports Hub - General Ledger | FMS')
@section('module', 'general-ledger')
@section('page', 'reports-hub')

@section('content')
<div class="space-y-6">
  <!-- Executive Header -->
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-800">
    <div>
      <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
          Financial Reports &amp; Statements Hub
        </h1>
    </div>

    <div class="flex flex-wrap items-center gap-2.5">
      <a 
        href="{{ route('accounting.export.trial-balance-csv') }}" 
        class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 transition-all dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700 cursor-pointer"
      >
        <i class="ph-bold ph-file-csv text-emerald-600"></i>
        <span>Export TB (CSV)</span>
      </a>
      <a 
        href="{{ route('accounting.export.general-ledger-csv') }}" 
        class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 transition-all dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700 cursor-pointer"
      >
        <i class="ph-bold ph-file-csv text-purple-600"></i>
        <span>GL Book (CAS)</span>
      </a>
      <button 
        type="button" 
        onclick="window.print()" 
        class="inline-flex items-center gap-1.5 rounded-xl bg-slate-800 px-3.5 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-slate-700 dark:bg-slate-700 dark:hover:bg-slate-600 transition-all cursor-pointer"
      >
        <i class="ph-bold ph-printer"></i>
        <span>Print</span>
      </button>
    </div>
  </div>


<!-- Navigation Tabs -->
  <div class="rounded-2xl bg-white p-2 shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-800">
    <div class="flex flex-wrap items-center gap-1.5">
      <a 
        href="{{ route('accounting.reports.index', ['tab' => 'trial-balance']) }}"
        class="inline-flex items-center gap-2 rounded-xl px-4 py-2 text-xs font-semibold transition-all {{ $tab === 'trial-balance' ? 'bg-emerald-600 text-white shadow-sm ring-1 ring-emerald-600/20' : 'text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-800' }}"
      >
        <i class="ph-bold ph-scales"></i>
        <span>Trial Balance</span>
      </a>
      <a 
        href="{{ route('accounting.reports.index', ['tab' => 'pnl']) }}"
        class="inline-flex items-center gap-2 rounded-xl px-4 py-2 text-xs font-semibold transition-all {{ $tab === 'pnl' ? 'bg-emerald-600 text-white shadow-sm ring-1 ring-emerald-600/20' : 'text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-800' }}"
      >
        <i class="ph-bold ph-chart-line-up"></i>
        <span>Income Statement (P&amp;L)</span>
      </a>
      <a 
        href="{{ route('accounting.reports.index', ['tab' => 'balance-sheet']) }}"
        class="inline-flex items-center gap-2 rounded-xl px-4 py-2 text-xs font-semibold transition-all {{ $tab === 'balance-sheet' ? 'bg-emerald-600 text-white shadow-sm ring-1 ring-emerald-600/20' : 'text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-800' }}"
      >
        <i class="ph-bold ph-shield-check"></i>
        <span>Balance Sheet</span>
      </a>
      <a 
        href="{{ route('accounting.reports.index', ['tab' => 'bir-schedules']) }}"
        class="inline-flex items-center gap-2 rounded-xl px-4 py-2 text-xs font-semibold transition-all {{ $tab === 'bir-schedules' ? 'bg-emerald-600 text-white shadow-sm ring-1 ring-emerald-600/20' : 'text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-800' }}"
      >
        <i class="ph-bold ph-file-text"></i>
        <span>BIR Tax Returns</span>
      </a>
    </div>
  </div>

  <!-- TAB 1: TRIAL BALANCE -->
  @if($tab === 'trial-balance')
    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-800">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-slate-200 p-5 dark:border-slate-800">
        <div>
          <h5 class="text-base font-bold text-slate-900 dark:text-white">General Ledger Trial Balance</h5>
          <p class="text-xs text-slate-500 dark:text-slate-400">Real-time balances across all Chart of Accounts (COA)</p>
        </div>
        <div>
          @if($trialBalance['is_balanced'])
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
              <i class="ph-bold ph-check-circle text-base"></i>
              <span>DOUBLE-ENTRY BALANCED</span>
            </span>
          @else
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-300 border border-rose-200 dark:border-rose-800">
              <i class="ph-bold ph-warning text-base"></i>
              <span>OUT OF BALANCE</span>
            </span>
          @endif
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
          <thead class="border-b border-slate-200 bg-slate-50/75 dark:border-slate-800 dark:bg-slate-800/60 sticky top-0">
            <tr>
              <th class="py-3 px-4 font-semibold text-slate-700 dark:text-slate-300 w-28">Code</th>
              <th class="py-3 px-4 font-semibold text-slate-700 dark:text-slate-300">Account Title</th>
              <th class="py-3 px-4 font-semibold text-slate-700 dark:text-slate-300">Classification</th>
              <th class="py-3 px-4 font-semibold text-slate-700 dark:text-slate-300">Normal Balance</th>
              <th class="py-3 px-4 font-semibold text-slate-700 dark:text-slate-300 text-right w-44">Debit (DR)</th>
              <th class="py-3 px-4 font-semibold text-slate-700 dark:text-slate-300 text-right w-44">Credit (CR)</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
            @foreach($trialBalance['accounts'] as $acc)
              <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors">
                <td class="py-3 px-4">
                  <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-mono font-semibold bg-slate-100 text-slate-700 border border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700">
                    {{ $acc['code'] }}
                  </span>
                </td>
                <td class="py-3 px-4 font-semibold text-slate-900 dark:text-white">
                  {{ $acc['name'] }}
                </td>
                <td class="py-3 px-4">
                  <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-slate-100 text-slate-700 border border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700">
                    {{ $acc['category'] }}
                  </span>
                </td>
                <td class="py-3 px-4 font-mono text-slate-400 text-xs">
                  {{ $acc['normal_balance'] }}
                </td>
                <td class="py-3 px-4 text-right font-mono tabular-nums {{ (float) $acc['debit'] > 0 ? 'font-bold text-slate-900 dark:text-white' : 'text-slate-400' }}">
                  {{ (float) $acc['debit'] > 0 ? '₱' . number_format((float) $acc['debit'], 2) : '-' }}
                </td>
                <td class="py-3 px-4 text-right font-mono tabular-nums {{ (float) $acc['credit'] > 0 ? 'font-bold text-slate-900 dark:text-white' : 'text-slate-400' }}">
                  {{ (float) $acc['credit'] > 0 ? '₱' . number_format((float) $acc['credit'], 2) : '-' }}
                </td>
              </tr>
            @endforeach
          </tbody>
          <tfoot class="border-t-2 border-slate-300 bg-slate-100 dark:border-slate-700 dark:bg-slate-800/80 font-mono font-bold text-xs">
            <tr>
              <td colspan="4" class="py-3 px-4 uppercase text-right tracking-wider text-slate-700 dark:text-slate-300">
                Grand Total Balances:
              </td>
              <td class="py-3 px-4 text-right tabular-nums text-emerald-600 dark:text-emerald-400 text-sm">
                ₱{{ number_format((float) $trialBalance['total_debit'], 2) }}
              </td>
              <td class="py-3 px-4 text-right tabular-nums text-emerald-600 dark:text-emerald-400 text-sm">
                ₱{{ number_format((float) $trialBalance['total_credit'], 2) }}
              </td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
  @endif

  <!-- TAB 2: INCOME STATEMENT (P&L) -->
  @if($tab === 'pnl')
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Revenues Column -->
      <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-800 flex flex-col">
        <div class="flex items-center gap-2 border-b border-slate-200 p-4 dark:border-slate-800">
          <i class="ph-bold ph-trend-up text-emerald-600"></i>
          <h5 class="text-sm font-bold uppercase text-slate-800 dark:text-slate-200">
            Operating Revenues
          </h5>
        </div>
        <div class="overflow-x-auto flex-1">
          <table class="w-full text-left text-xs">
            <thead class="border-b border-slate-200 bg-slate-50/75 dark:border-slate-800 dark:bg-slate-800/60">
              <tr>
                <th class="py-2.5 px-4 font-semibold text-slate-700 dark:text-slate-300">Code</th>
                <th class="py-2.5 px-4 font-semibold text-slate-700 dark:text-slate-300">Revenue Account</th>
                <th class="py-2.5 px-4 font-semibold text-slate-700 dark:text-slate-300 text-right">Amount</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
              @forelse($pnl['revenues'] as $rev)
                <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors">
                  <td class="py-3 px-4">
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-mono font-semibold bg-slate-100 text-slate-700 border border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700">
                      {{ $rev['code'] }}
                    </span>
                  </td>
                  <td class="py-3 px-4 font-medium text-slate-800 dark:text-slate-200">{{ $rev['name'] }}</td>
                  <td class="py-3 px-4 text-right font-mono tabular-nums font-semibold text-emerald-600 dark:text-emerald-400">
                    ₱{{ number_format((float) $rev['balance'], 2) }}
                  </td>
                </tr>
              @empty
                <tr><td colspan="3" class="py-8 text-center text-slate-400">No revenues recorded.</td></tr>
              @endforelse
            </tbody>
            <tfoot class="border-t border-slate-200 bg-slate-50 dark:border-slate-800 dark:bg-slate-800/60 font-semibold">
              <tr>
                <td colspan="2" class="py-3 px-4 text-slate-700 dark:text-slate-300">Total Gross Revenues:</td>
                <td class="py-3 px-4 text-right font-mono tabular-nums font-bold text-emerald-600 dark:text-emerald-400 text-sm">
                  ₱{{ number_format((float) $pnl['total_revenue'], 2) }}
                </td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>

      <!-- Expenses Column -->
      <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-800 flex flex-col">
        <div class="flex items-center gap-2 border-b border-slate-200 p-4 dark:border-slate-800">
          <i class="ph-bold ph-trend-down text-rose-600"></i>
          <h5 class="text-sm font-bold uppercase text-slate-800 dark:text-slate-200">
            Operating &amp; Direct Expenses
          </h5>
        </div>
        <div class="overflow-x-auto flex-1">
          <table class="w-full text-left text-xs">
            <thead class="border-b border-slate-200 bg-slate-50/75 dark:border-slate-800 dark:bg-slate-800/60">
              <tr>
                <th class="py-2.5 px-4 font-semibold text-slate-700 dark:text-slate-300">Code</th>
                <th class="py-2.5 px-4 font-semibold text-slate-700 dark:text-slate-300">Expense Account</th>
                <th class="py-2.5 px-4 font-semibold text-slate-700 dark:text-slate-300 text-right">Amount</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
              @forelse($pnl['expenses'] as $exp)
                <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors">
                  <td class="py-3 px-4">
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-mono font-semibold bg-slate-100 text-slate-700 border border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700">
                      {{ $exp['code'] }}
                    </span>
                  </td>
                  <td class="py-3 px-4 font-medium text-slate-800 dark:text-slate-200">{{ $exp['name'] }}</td>
                  <td class="py-3 px-4 text-right font-mono tabular-nums font-semibold text-rose-600 dark:text-rose-400">
                    ₱{{ number_format((float) $exp['balance'], 2) }}
                  </td>
                </tr>
              @empty
                <tr><td colspan="3" class="py-8 text-center text-slate-400">No expenses recorded.</td></tr>
              @endforelse
            </tbody>
            <tfoot class="border-t border-slate-200 bg-slate-50 dark:border-slate-800 dark:bg-slate-800/60 font-semibold">
              <tr>
                <td colspan="2" class="py-3 px-4 text-slate-700 dark:text-slate-300">Total Operating Expenses:</td>
                <td class="py-3 px-4 text-right font-mono tabular-nums font-bold text-rose-600 dark:text-rose-400 text-sm">
                  ₱{{ number_format((float) $pnl['total_expense'], 2) }}
                </td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>

      <!-- Net Income Banner -->
      <div class="lg:col-span-2">
        <div class="rounded-2xl bg-gradient-to-r from-blue-600 to-indigo-700 p-6 text-white shadow-lg flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <div>
            <span class="text-xs font-semibold uppercase tracking-wider text-blue-200">Summary Bottomline</span>
            <h4 class="text-xl sm:text-2xl font-bold mt-1">Net Hospital Margin / Net Income</h4>
            <p class="text-xs text-blue-100 mt-0.5">Gross Clinical Revenue less Operating &amp; Direct Medical Expenses</p>
          </div>
          <div class="sm:text-right">
            <span class="text-xs text-blue-200 font-semibold uppercase">Net Operating Profit</span>
            <h2 class="text-3xl font-extrabold font-mono tracking-tight text-white mt-1">
              ₱{{ number_format((float) $pnl['net_income'], 2) }}
            </h2>
          </div>
        </div>
      </div>
    </div>
  @endif

  <!-- TAB 3: BALANCE SHEET -->
  @if($tab === 'balance-sheet')
    <div class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-800 overflow-hidden">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-slate-200 p-5 dark:border-slate-800">
        <div>
          <h5 class="text-base font-bold text-slate-900 dark:text-white">Statement of Financial Position (Balance Sheet)</h5>
          <p class="text-xs text-slate-500 dark:text-slate-400">Assets = Liabilities + Owner's Equity + Current Net Income</p>
        </div>
        <div>
          @if($balanceSheet['is_balanced'])
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
              <i class="ph-bold ph-check-circle text-base"></i>
              <span>A = L + E (BALANCED)</span>
            </span>
          @else
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-300 border border-rose-200 dark:border-rose-800">
              <i class="ph-bold ph-warning text-base"></i>
              <span>EQUATION DISCREPANCY</span>
            </span>
          @endif
        </div>
      </div>

      <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
          <!-- Assets Column -->
          <div class="space-y-4">
            <div class="flex items-center gap-2 pb-2 border-b border-slate-100 dark:border-slate-800">
              <i class="ph-bold ph-vault text-blue-600"></i>
              <h6 class="text-xs font-bold uppercase tracking-wider text-blue-600 dark:text-blue-400">Assets</h6>
            </div>
            <ul class="divide-y divide-slate-100 dark:divide-slate-800 text-xs">
              @foreach($balanceSheet['assets'] as $asset)
                <li class="flex items-center justify-between py-2.5">
                  <div class="flex items-center gap-2">
                    <span class="font-mono text-xs text-slate-500 bg-slate-100 dark:bg-slate-800 px-1.5 py-0.5 rounded border border-slate-200 dark:border-slate-700">{{ $asset['code'] }}</span>
                    <span class="font-medium text-slate-800 dark:text-slate-200">{{ $asset['name'] }}</span>
                  </div>
                  <span class="font-mono tabular-nums font-semibold text-slate-900 dark:text-white">
                    ₱{{ number_format((float) $asset['balance'], 2) }}
                  </span>
                </li>
              @endforeach
            </ul>
            <div class="rounded-xl bg-blue-50/60 p-4 dark:bg-blue-950/30 border border-blue-100 dark:border-blue-900/40 flex items-center justify-between">
              <span class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase">Total Assets:</span>
              <span class="font-mono font-bold text-blue-600 dark:text-blue-400 text-base">
                ₱{{ number_format((float) $balanceSheet['total_assets'], 2) }}
              </span>
            </div>
          </div>

          <!-- Liabilities & Equity Column -->
          <div class="space-y-6">
            <!-- Liabilities -->
            <div class="space-y-4">
              <div class="flex items-center gap-2 pb-2 border-b border-slate-100 dark:border-slate-800">
                <i class="ph-bold ph-bank text-rose-600"></i>
                <h6 class="text-xs font-bold uppercase tracking-wider text-rose-600 dark:text-rose-400">Liabilities</h6>
              </div>
              <ul class="divide-y divide-slate-100 dark:divide-slate-800 text-xs">
                @foreach($balanceSheet['liabilities'] as $liab)
                  <li class="flex items-center justify-between py-2.5">
                    <div class="flex items-center gap-2">
                      <span class="font-mono text-xs text-slate-500 bg-slate-100 dark:bg-slate-800 px-1.5 py-0.5 rounded border border-slate-200 dark:border-slate-700">{{ $liab['code'] }}</span>
                      <span class="font-medium text-slate-800 dark:text-slate-200">{{ $liab['name'] }}</span>
                    </div>
                    <span class="font-mono tabular-nums font-semibold text-rose-600 dark:text-rose-400">
                      ₱{{ number_format((float) $liab['balance'], 2) }}
                    </span>
                  </li>
                @endforeach
              </ul>
              <div class="rounded-xl bg-slate-50 p-3.5 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-600 dark:text-slate-400">Total Liabilities:</span>
                <span class="font-mono font-bold text-rose-600 dark:text-rose-400">
                  ₱{{ number_format((float) $balanceSheet['total_liabilities'], 2) }}
                </span>
              </div>
            </div>

            <!-- Equity -->
            <div class="space-y-4">
              <div class="flex items-center gap-2 pb-2 border-b border-slate-100 dark:border-slate-800">
                <i class="ph-bold ph-buildings text-emerald-600"></i>
                <h6 class="text-xs font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Equity &amp; Earnings</h6>
              </div>
              <ul class="divide-y divide-slate-100 dark:divide-slate-800 text-xs">
                @foreach($balanceSheet['equity'] as $eq)
                  <li class="flex items-center justify-between py-2.5">
                    <div class="flex items-center gap-2">
                      <span class="font-mono text-xs text-slate-500 bg-slate-100 dark:bg-slate-800 px-1.5 py-0.5 rounded border border-slate-200 dark:border-slate-700">{{ $eq['code'] }}</span>
                      <span class="font-medium text-slate-800 dark:text-slate-200">{{ $eq['name'] }}</span>
                    </div>
                    <span class="font-mono tabular-nums font-semibold text-slate-900 dark:text-white">
                      ₱{{ number_format((float) $eq['balance'], 2) }}
                    </span>
                  </li>
                @endforeach
                <li class="flex items-center justify-between py-2.5">
                  <div class="flex items-center gap-2">
                    <span class="font-mono text-xs text-emerald-700 bg-emerald-50 dark:bg-emerald-950/40 px-1.5 py-0.5 rounded border border-emerald-200 dark:border-emerald-800">NET-INC</span>
                    <span class="font-medium text-slate-800 dark:text-slate-200">Current Period Net Income (Retained)</span>
                  </div>
                  <span class="font-mono tabular-nums font-bold text-emerald-600 dark:text-emerald-400">
                    ₱{{ number_format((float) $balanceSheet['current_period_net_income'], 2) }}
                  </span>
                </li>
              </ul>
              <div class="rounded-xl bg-emerald-50/60 p-4 dark:bg-emerald-950/30 border border-emerald-100 dark:border-emerald-900/40 flex items-center justify-between">
                <span class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase">Total Liabilities &amp; Equity:</span>
                <span class="font-mono font-bold text-emerald-600 dark:text-emerald-400 text-base">
                  ₱{{ number_format((float) $balanceSheet['total_liabilities_and_equity'], 2) }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  @endif

  <!-- TAB 4: BIR TAX RETURNS -->
  @if($tab === 'bir-schedules')
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-800 flex flex-col justify-between">
        <div>
          <div class="flex items-center justify-between mb-3">
            <h5 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
              <i class="ph-bold ph-file-text text-blue-600"></i>
              BIR Form 1601-EQ
            </h5>
            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold bg-blue-50 text-blue-700 dark:bg-blue-950/40 dark:text-blue-300 border border-blue-200 dark:border-blue-800">
              Expanded Withholding Tax
            </span>
          </div>
          <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">
            Quarterly creditable income taxes withheld from medical suppliers and doctor professional fees.
          </p>
          <div class="rounded-xl bg-slate-50 p-4 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 space-y-2 text-xs">
            <div class="flex items-center justify-between">
              <span class="text-slate-500 dark:text-slate-400">Total Tax Base:</span>
              <strong class="font-mono tabular-nums text-slate-800 dark:text-slate-200">₱{{ number_format((float) $bir1601eq['total_tax_base'], 2) }}</strong>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-slate-500 dark:text-slate-400">Form 2307 Certificates:</span>
              <strong class="font-mono text-slate-800 dark:text-slate-200">{{ $bir1601eq['total_forms'] }} Issued</strong>
            </div>
            <div class="border-t border-slate-200 dark:border-slate-700 pt-2 flex items-center justify-between font-bold">
              <span class="text-slate-700 dark:text-slate-300">Total Creditable Tax Withheld:</span>
              <span class="font-mono text-blue-600 dark:text-blue-400 text-sm">₱{{ number_format((float) $bir1601eq['total_withheld'], 2) }}</span>
            </div>
          </div>
        </div>
      </div>

      <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-800 flex flex-col justify-between">
        <div>
          <div class="flex items-center justify-between mb-3">
            <h5 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
              <i class="ph-bold ph-percent text-emerald-600"></i>
              BIR Form 2550M / 2550Q
            </h5>
            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
              Value-Added Tax (VAT)
            </span>
          </div>
          <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">
            Monthly and quarterly VAT declaration summaries on hospital gross receipts and exemptions.
          </p>
          <div class="rounded-xl bg-slate-50 p-4 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 space-y-2 text-xs">
            <div class="flex items-center justify-between">
              <span class="text-slate-500 dark:text-slate-400">Total Official Receipts Count:</span>
              <strong class="font-mono text-slate-800 dark:text-slate-200">{{ $birVat['total_receipts_count'] ?? $birVat['receipts_count'] ?? 0 }} Receipts</strong>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-slate-500 dark:text-slate-400">Total Gross Cashier Collections:</span>
              <strong class="font-mono tabular-nums text-slate-800 dark:text-slate-200">₱{{ number_format((float) ($birVat['total_collections'] ?? 0), 2) }}</strong>
            </div>
            <div class="border-t border-slate-200 dark:border-slate-700 pt-2 flex items-center justify-between font-bold">
              <span class="text-slate-700 dark:text-slate-300">Output VAT Relief Status:</span>
              <span class="font-mono text-emerald-600 dark:text-emerald-400 text-xs">RA 9994 / RA 10754 Applied</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  @endif
</div>
@endsection
