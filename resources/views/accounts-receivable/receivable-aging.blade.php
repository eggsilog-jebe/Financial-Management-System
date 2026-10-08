@extends('layouts.app')

@section('title', 'Receivable Aging Schedule - Accounts Receivable | FMS')
@section('module', 'ar')
@section('page', 'ar-aging')

@section('content')
<div class="space-y-6" x-data="{
  activeDrawer: null,
  init() {
    this.$watch('activeDrawer', val => {
      document.body.classList.toggle('overflow-hidden', val !== null);
      document.body.classList.toggle('modal-open', val !== null);
    });
  }
}">

  <!-- Page Header -->
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-800">
    <div>
      <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
          Patient &amp; HMO Receivable Aging
        </h1>
    </div>

    <div class="flex items-center gap-2.5 flex-wrap">
      <a 
        href="{{ route('ar.ar-aging.export', ['as_of_date' => $asOfDate ?? date('Y-m-d')]) }}" 
        class="inline-flex items-center gap-1.5 rounded-xl bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:ring-slate-700 transition-all"
      >
        <i class="ph-bold ph-download-simple"></i>
        <span>Export Aging CSV</span>
      </a>
    </div>
  </div>

  <!-- 5 Aging Buckets + Grand Total AR -->
  <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3.5">
    <!-- Current <30d -->
    <x-stat-card 
      title="Current (<30d)" 
      :value="$totalCurrent ?? 0" 
      icon="ph-clock" 
      color="emerald" 
      subtitle="Within payment terms"
    />

    <!-- 31 - 60 Days -->
    <x-stat-card 
      title="31 - 60 Days" 
      :value="$total31To60 ?? 0" 
      icon="ph-warning-circle" 
      color="blue" 
      subtitle="Initial follow-up"
    />

    <!-- 61 - 90 Days -->
    <x-stat-card 
      title="61 - 90 Days" 
      :value="$total61To90 ?? 0" 
      icon="ph-hourglass-high" 
      color="amber" 
      subtitle="Overdue notice active"
    />

    <!-- 91 - 120 Days -->
    <x-stat-card 
      title="91 - 120 Days" 
      :value="$total91To120 ?? 0" 
      icon="ph-shield-warning" 
      color="rose" 
      subtitle="Final demand review"
    />

    <!-- 120+ Days -->
    <x-stat-card 
      title="> 120 Days" 
      :value="$total120Plus ?? 0" 
      icon="ph-skull" 
      color="rose" 
      badge="Bad Debt"
      subtitle="Candidate for write-off"
    />

    <!-- Grand Total AR -->
    <x-stat-card 
      title="Grand Total AR" 
      :value="$grandTotalAR ?? 0" 
      icon="ph-coins" 
      color="rose" 
      subtitle="Gross outstanding claims"
    />
  </div>

  <!-- Segment Filter Tabs -->
  <div class="inline-flex p-1.5 rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-800 w-full sm:w-auto overflow-x-auto">
    <div class="flex items-center gap-1 min-w-max">
      <a 
        href="{{ route('ar.ar-aging', array_merge(request()->query(), ['payor_type' => 'ALL'])) }}" 
        class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-semibold transition-all {{ ($payorType ?? 'ALL') === 'ALL' ? 'bg-emerald-600 text-white shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800' }}"
      >
        <i class="ph-bold ph-squares-four"></i> All Receivables
      </a>
      <a 
        href="{{ route('ar.ar-aging', array_merge(request()->query(), ['payor_type' => 'PATIENT'])) }}" 
        class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-semibold transition-all {{ ($payorType ?? '') === 'PATIENT' ? 'bg-emerald-600 text-white shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800' }}"
      >
        <i class="ph-bold ph-user"></i> Patient Copays
      </a>
      <a 
        href="{{ route('ar.ar-aging', array_merge(request()->query(), ['payor_type' => 'HMO'])) }}" 
        class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-semibold transition-all {{ ($payorType ?? '') === 'HMO' ? 'bg-emerald-600 text-white shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800' }}"
      >
        <i class="ph-bold ph-shield"></i> HMO Guarantees
      </a>
      <a 
        href="{{ route('ar.ar-aging', array_merge(request()->query(), ['payor_type' => 'PHILHEALTH'])) }}" 
        class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-semibold transition-all {{ ($payorType ?? '') === 'PHILHEALTH' ? 'bg-emerald-600 text-white shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800' }}"
      >
        <i class="ph-bold ph-crosshair"></i> PhilHealth Claims
      </a>
    </div>
  </div>

  <!-- Aging Schedule Table Card -->
  

  <div class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/80 overflow-hidden dark:bg-slate-900 dark:ring-slate-800">
    <!-- Filter Toolbar -->
    <div class="border-b border-slate-200 p-4 dark:border-slate-800">
      <form method="GET" action="{{ route('ar.ar-aging') }}" class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
        <input type="hidden" name="payor_type" value="{{ $payorType ?? 'ALL' }}">
        
        <div class="flex flex-wrap items-center gap-2.5 flex-1">
          <div class="relative w-full sm:w-64">
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
              <i class="ph ph-magnifying-glass text-sm"></i>
            </div>
            <input 
              type="search" 
              name="search" 
              value="{{ request('search') }}" 
              placeholder="Search MRN, Patient, or HMO..." 
              class="w-full rounded-xl border-0 bg-slate-50 py-1.5 pl-9 pr-3 text-xs text-slate-900 ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-700"
            >
          </div>

          <div class="w-full sm:w-44">
            <select 
              name="admission_type" 
              onchange="this.form.submit()" 
              class="w-full rounded-xl border-0 bg-slate-50 py-1.5 px-3 text-xs font-semibold text-slate-800 ring-1 ring-inset ring-slate-300 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-700"
            >
              <option value="ALL" {{ request('admission_type', 'ALL') === 'ALL' ? 'selected' : '' }}>All Admissions</option>
              <option value="INPATIENT" {{ request('admission_type') === 'INPATIENT' ? 'selected' : '' }}>Inpatient</option>
              <option value="OUTPATIENT" {{ request('admission_type') === 'OUTPATIENT' ? 'selected' : '' }}>Outpatient</option>
              <option value="EMERGENCY" {{ request('admission_type') === 'EMERGENCY' ? 'selected' : '' }}>Emergency</option>
            </select>
          </div>

          <div class="flex items-center gap-2">
            <span class="text-xs text-slate-500 font-medium">As-Of:</span>
            <input 
              type="date" 
              name="as_of_date" 
              value="{{ $asOfDate ?? date('Y-m-d') }}" 
              onchange="this.form.submit()" 
              class="rounded-xl border-0 bg-slate-50 py-1.5 px-3 text-xs text-slate-900 ring-1 ring-inset ring-slate-300 dark:bg-slate-800 dark:text-white dark:ring-slate-700 font-mono"
            >
            <button 
              type="submit" 
              class="inline-flex items-center gap-1.5 rounded-xl bg-slate-100 px-3.5 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 transition-all"
            >
              <i class="ph-bold ph-funnel text-xs"></i>
              <span>Filter</span>
            </button>
          </div>
        </div>

        <div class="text-xs font-medium text-slate-500 dark:text-slate-400">
          Showing <span class="font-bold text-slate-900 dark:text-white">{{ $totalDebtors ?? count($debtors ?? []) }}</span> Debtor Accounts
        </div>
      </form>
    </div>

    <!-- Table -->
    <div class="overflow-x-auto custom-scrollbar">
      <table class="w-full text-left text-xs text-slate-700 dark:text-slate-300">
        <thead class="border-b border-slate-200 bg-slate-50/75 text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:border-slate-800 dark:bg-slate-800/50 dark:text-slate-400">
          <tr>
            <th class="py-3 px-4">Patient MRN &amp; Name</th>
            <th class="py-3 px-4">Admission</th>
            <th class="py-3 px-4">HMO Provider</th>
            <th class="py-3 px-4 text-right font-mono">Current (&lt;30d)</th>
            <th class="py-3 px-4 text-right font-mono">31 - 60 Days</th>
            <th class="py-3 px-4 text-right font-mono">61 - 90 Days</th>
            <th class="py-3 px-4 text-right font-mono">91 - 120 Days</th>
            <th class="py-3 px-4 text-right font-mono">120+ Days</th>
            <th class="py-3 px-4 text-right font-mono font-bold">Total Due (₱)</th>
            <th class="py-3 px-4 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
          @forelse($debtors as $d)
            <tr class="transition-colors hover:bg-slate-50/50 dark:hover:bg-slate-800/40">
              <td class="py-3.5 px-4">
                <div class="font-bold text-slate-900 dark:text-white">{{ $d['debtor_name'] }}</div>
                <div class="font-mono text-[11px] text-slate-400">{{ $d['debtor_code'] }} &bull; <span class="font-semibold text-emerald-600 dark:text-emerald-400">{{ $d['debtor_type'] }}</span></div>
              </td>
              <td class="py-3.5 px-4">
                <span class="rounded bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-700 dark:bg-slate-800 dark:text-slate-300">{{ $d['admission'] }}</span>
                @if(isset($d['statutory_category']))
                  @if($d['statutory_category'] === 'SENIOR_CITIZEN')
                    <span class="inline-flex items-center gap-1 rounded bg-purple-50 px-2 py-0.5 text-[10px] font-semibold text-purple-700 ring-1 ring-purple-600/20 ms-1">
                      <i class="ph-bold ph-identification-card"></i> Senior 20%
                    </span>
                  @elseif($d['statutory_category'] === 'PWD')
                    <span class="inline-flex items-center gap-1 rounded bg-teal-50 px-2 py-0.5 text-[10px] font-semibold text-teal-700 ring-1 ring-teal-600/20 ms-1">
                      <i class="ph-bold ph-wheelchair"></i> PWD 20%
                    </span>
                  @endif
                @endif
              </td>
              <td class="py-3.5 px-4">
                @if($d['hmo'] !== 'Self-Pay' && $d['hmo'] !== 'None')
                  <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-semibold bg-sky-50 text-sky-700 ring-1 ring-sky-600/20 dark:bg-sky-950/40 dark:text-sky-300">{{ $d['hmo'] }}</span>
                @else
                  <span class="text-slate-400 text-xs">Self-Pay</span>
                @endif
              </td>
              <td class="py-3.5 px-4 text-right font-mono tabular-nums text-slate-700 dark:text-slate-300">
                {{ (float)$d['current'] > 0 ? '₱' . number_format((float)$d['current'], 2) : '—' }}
              </td>
              <td class="py-3.5 px-4 text-right font-mono tabular-nums text-blue-600 dark:text-blue-400">
                {{ (float)$d['days_31_60'] > 0 ? '₱' . number_format((float)$d['days_31_60'], 2) : '—' }}
              </td>
              <td class="py-3.5 px-4 text-right font-mono tabular-nums text-amber-600 dark:text-amber-400">
                {{ (float)$d['days_61_90'] > 0 ? '₱' . number_format((float)$d['days_61_90'], 2) : '—' }}
              </td>
              <td class="py-3.5 px-4 text-right font-mono tabular-nums text-orange-600 dark:text-orange-400">
                {{ (float)$d['days_91_120'] > 0 ? '₱' . number_format((float)$d['days_91_120'], 2) : '—' }}
              </td>
              <td class="py-3.5 px-4 text-right font-mono tabular-nums font-bold text-rose-600 dark:text-rose-400">
                {{ (float)$d['days_120_plus'] > 0 ? '₱' . number_format((float)$d['days_120_plus'], 2) : '—' }}
              </td>
              <td class="py-3.5 px-4 text-right font-mono tabular-nums font-bold text-slate-900 dark:text-white">
                ₱{{ number_format((float)$d['total_due'], 2) }}
              </td>
              <td class="py-3.5 px-4 text-right">
                <button 
                  type="button" 
                  @click="activeDrawer = {{ $loop->index }}" 
                  class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1 text-xs font-semibold text-emerald-700 bg-emerald-50 ring-1 ring-inset ring-emerald-600/20 hover:bg-emerald-100 transition-all dark:bg-emerald-950/40 dark:text-emerald-300"
                >
                  <i class="ph-bold ph-eye"></i> Breakdown
                </button>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="10" class="py-12 text-center text-sm text-slate-400">No outstanding accounts receivable found for this cutoff date.</td>
            </tr>
          @endforelse
        </tbody>
        <tfoot class="border-t-2 border-slate-200 bg-slate-50/75 text-xs font-bold uppercase font-mono tabular-nums dark:border-slate-800 dark:bg-slate-800/50">
          <tr>
            <td colspan="3" class="py-3.5 px-4 text-slate-900 dark:text-white">TOTALS:</td>
            <td class="px-4 py-3.5 text-right text-emerald-600 dark:text-emerald-400">₱{{ number_format((float) ($totalCurrent ?? 0), 2) }}</td>
            <td class="px-4 py-3.5 text-right text-blue-600 dark:text-blue-400">₱{{ number_format((float) ($total31To60 ?? 0), 2) }}</td>
            <td class="px-4 py-3.5 text-right text-amber-600 dark:text-amber-400">₱{{ number_format((float) ($total61To90 ?? 0), 2) }}</td>
            <td class="px-4 py-3.5 text-right text-orange-600 dark:text-orange-400">₱{{ number_format((float) ($total91To120 ?? 0), 2) }}</td>
            <td class="px-4 py-3.5 text-right text-rose-600 dark:text-rose-400">₱{{ number_format((float) ($total120Plus ?? 0), 2) }}</td>
            <td class="px-4 py-3.5 text-right text-rose-600 dark:text-rose-400 text-sm">₱{{ number_format((float) ($grandTotalAR ?? 0), 2) }}</td>
            <td class="py-3.5 px-4"></td>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>

  <!-- Itemized Invoices Slide-Over Drawers (Alpine.js) -->
  @foreach($debtors as $d)
    <template x-teleport="body">
    <div 
      x-show="activeDrawer === {{ $loop->index }}" 
      x-cloak 
      class="fixed inset-0 z-50 overflow-hidden" 
      aria-labelledby="slide-over-title" 
      role="dialog" 
      aria-modal="true"
    >
      <div 
        x-show="activeDrawer === {{ $loop->index }}" 
        x-transition.opacity.duration.300ms 
        class="fixed inset-0 bg-slate-950/60 backdrop-blur-md transition-opacity"
        @click="activeDrawer = null"
      ></div>

      <div class="fixed inset-y-0 right-0 max-w-full flex pl-10">
        <div 
          x-show="activeDrawer === {{ $loop->index }}" 
          x-transition:enter="transform transition ease-in-out duration-300"
          x-transition:enter-start="translate-x-full"
          x-transition:enter-end="translate-x-0"
          x-transition:leave="transform transition ease-in-out duration-300"
          x-transition:leave-start="translate-x-0"
          x-transition:leave-end="translate-x-full"
          @click.outside="activeDrawer = null" 
          class="w-screen max-w-xl bg-white shadow-2xl ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800 flex flex-col justify-between"
        >
          <!-- Drawer Header -->
          <div class="p-6 border-b border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 flex items-center justify-between">
            <div>
              <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <i class="ph-bold ph-receipt text-emerald-600"></i>
                <span>{{ $d['debtor_name'] }}</span>
              </h3>
              <p class="text-xs text-slate-500 font-mono mt-0.5">{{ $d['debtor_code'] }} &bull; {{ $d['debtor_type'] }}</p>
            </div>
            <button 
              type="button" 
              @click="activeDrawer = null" 
              class="rounded-xl p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-slate-800"
            >
              <i class="ph-bold ph-x text-lg"></i>
            </button>
          </div>

          <!-- Drawer Body -->
          <div class="p-6 overflow-y-auto custom-scrollbar flex-1 space-y-5">
            <div class="rounded-xl bg-slate-50 p-4 ring-1 ring-slate-200 dark:bg-slate-800/60 dark:ring-slate-700 flex justify-between items-center">
              <div>
                <span class="text-xs text-slate-500 block">Debtor Category:</span>
                <strong class="text-xs font-semibold text-slate-900 dark:text-white">{{ $d['debtor_type'] }}</strong>
              </div>
              <div class="text-right">
                <span class="text-xs text-slate-500 block">Total Due:</span>
                <strong class="font-mono text-xl font-bold text-rose-600 dark:text-rose-400 tabular-nums">₱{{ number_format((float)$d['total_due'], 2) }}</strong>
              </div>
            </div>

            <div>
              <div class="flex justify-between items-center mb-2">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">
                  Itemized Unpaid Invoices ({{ count($d['invoices'] ?? []) }})
                </span>
                <span class="text-[11px] text-slate-400 font-mono">Cutoff: {{ $asOfDate }}</span>
              </div>

              <div class="rounded-xl ring-1 ring-slate-200 overflow-hidden dark:ring-slate-800">
                <table class="w-full text-left text-xs">
                  <thead class="border-b border-slate-200 bg-slate-50 text-[11px] font-semibold uppercase text-slate-500 dark:border-slate-800 dark:bg-slate-800/50 dark:text-slate-400">
                    <tr>
                      <th class="py-2.5 px-3">Invoice #</th>
                      <th class="py-2.5 px-3">Date</th>
                      <th class="py-2.5 px-3">Overdue</th>
                      <th class="py-2.5 px-3 text-right font-mono">Amount (₱)</th>
                      <th class="py-2.5 px-3 text-right">Action</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($d['invoices'] ?? [] as $inv)
                      <tr>
                        <td class="py-2.5 px-3">
                          <div class="font-mono font-bold text-emerald-700 dark:text-emerald-400">{{ $inv['invoice_number'] }}</div>
                          <div class="text-[10px] text-slate-400">{{ $inv['claim_type'] }}</div>
                        </td>
                        <td class="py-2.5 px-3 text-slate-500">{{ $inv['invoice_date'] }}</td>
                        <td class="py-2.5 px-3">
                          @if($inv['days_overdue'] <= 30)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20">{{ $inv['days_overdue'] }}d</span>
                          @elseif($inv['days_overdue'] <= 60)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-blue-50 text-blue-700 ring-1 ring-blue-600/20">{{ $inv['days_overdue'] }}d</span>
                          @elseif($inv['days_overdue'] <= 90)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-50 text-amber-700 ring-1 ring-amber-600/20">{{ $inv['days_overdue'] }}d</span>
                          @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-rose-50 text-rose-700 ring-1 ring-rose-600/20">{{ $inv['days_overdue'] }}d</span>
                          @endif
                        </td>
                        <td class="py-2.5 px-3 text-right font-mono font-bold text-slate-900 dark:text-white tabular-nums">
                          ₱{{ number_format((float) $inv['amount_due'], 2) }}
                        </td>
                        <td class="py-2.5 px-3 text-right">
                          <a 
                            href="{{ route('collection.cashier-desk') }}" 
                            class="inline-flex items-center gap-1 rounded-lg bg-emerald-50 px-2 py-1 text-[11px] font-semibold text-emerald-700 ring-1 ring-emerald-600/20 hover:bg-emerald-100"
                          >
                            <i class="ph-bold ph-coins"></i> Settle
                          </a>
                        </td>
                      </tr>
                    @empty
                      <tr>
                        <td colspan="5" class="py-4 text-center text-slate-400">No itemized invoices found.</td>
                      </tr>
                    @endforelse
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <!-- Drawer Footer -->
          <div class="p-6 border-t border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 grid grid-cols-2 gap-3">
            <a 
              href="{{ route('ar.credit-notes') }}" 
              class="inline-flex items-center justify-center gap-1.5 rounded-xl bg-white px-3.5 py-2.5 text-xs font-semibold text-rose-700 shadow-sm ring-1 ring-inset ring-rose-300 hover:bg-rose-50 dark:bg-slate-800 dark:text-rose-400 dark:ring-rose-800 transition-all text-center"
            >
              <i class="ph-bold ph-minus-circle"></i> Issue Credit Note
            </a>
            <a 
              href="{{ route('collection.cashier-desk') }}" 
              class="inline-flex items-center justify-center gap-1.5 rounded-xl bg-emerald-600 px-3.5 py-2.5 text-xs font-semibold text-white shadow-sm hover:bg-emerald-700 ring-1 ring-emerald-600/20 transition-all text-center"
            >
              <i class="ph-bold ph-check-circle"></i> Cashier Settlement Desk
            </a>
          </div>
        </div>
      </div>
    </div>
    </template>
  @endforeach

</div>
@endsection
