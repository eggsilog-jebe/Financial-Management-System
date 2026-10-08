@extends('layouts.app')

@section('title', 'General Ledger & Journal Browser')
@section('module', 'general-ledger')
@section('page', 'journals')

@section('content')
<div class="space-y-6" x-data="{
  init() {
    this.$watch('reverseModalOpen', val => {
      document.body.classList.toggle('overflow-hidden', val);
      document.body.classList.toggle('modal-open', val);
    });
  },
  reverseModalOpen: false,
  reversalEntry: null,
  openReverse(entry) {
    this.reversalEntry = entry;
    this.reverseModalOpen = true;
  },
  closeReverse() {
    this.reverseModalOpen = false;
    this.reversalEntry = null;
  }
}" @keydown.escape.window="closeReverse()">

  {{-- Header --}}
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
      <div class="flex items-center gap-2 text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">
        <a href="{{ route('accounting.dashboard') }}" class="hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">Overview</a>
        <i class="ph ph-caret-right text-[10px]"></i>
        <span class="text-slate-900 dark:text-slate-200 font-semibold">General Ledger</span>
      </div>
      <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
        General Ledger &amp; Journal Browser
      </h1>
    </div>
    <div class="flex flex-wrap items-center gap-2.5">
      <a href="{{ route('accounting.reports.index') }}" class="inline-flex items-center gap-1.5 rounded-xl border border-emerald-200 bg-white px-3.5 py-2 text-xs font-semibold text-emerald-700 shadow-sm hover:bg-emerald-50 dark:border-emerald-800 dark:bg-slate-900 dark:text-emerald-400 dark:hover:bg-slate-800 transition-all">
        <i class="ph ph-chart-line-up"></i>
        Trial Balance
      </a>
      <a href="{{ route('accounting.dashboard') }}" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800 transition-all">
        <i class="ph ph-arrow-left"></i>
        Dashboard
      </a>
    </div>
  </div>

  @if(session('success'))
    <div x-data="{ show: true }" x-show="show" x-transition class="flex items-center justify-between gap-3 rounded-2xl border border-emerald-200/80 bg-emerald-50/80 p-4 text-xs text-emerald-800 shadow-sm dark:border-emerald-800/40 dark:bg-emerald-950/40 dark:text-emerald-300">
      <div class="flex items-center gap-2.5">
        <i class="ph-fill ph-check-circle text-lg text-emerald-600 dark:text-emerald-400 shrink-0"></i>
        <span>{{ session('success') }}</span>
      </div>
      <button @click="show = false" type="button" class="text-emerald-600 hover:text-emerald-800 dark:text-emerald-400 dark:hover:text-emerald-200 transition-colors">
        <i class="ph ph-x text-sm"></i>
      </button>
    </div>
  @endif


  {{-- Journal Entries Table Card --}}
  <div class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-800 overflow-hidden">
    {{-- Filter Toolbar --}}
    <div class="border-b border-slate-200 p-4 dark:border-slate-800">
      <form method="GET" action="{{ route('accounting.general-ledger.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-center">
        <div class="lg:col-span-4">
          <div class="relative">
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
              <i class="ph ph-magnifying-glass"></i>
            </div>
            <input type="search" name="q" value="{{ $search }}" class="block w-full rounded-xl border-0 bg-slate-50 py-1.5 pl-9 pr-3 text-xs text-slate-900 ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-700" placeholder="Search reference # or description...">
          </div>
        </div>
        <div class="lg:col-span-2">
          <select name="status" class="block w-full rounded-xl border-0 bg-slate-50 py-1.5 px-3 text-xs font-semibold text-slate-800 ring-1 ring-inset ring-slate-300 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-700">
            <option value="">All Statuses</option>
            <option value="POSTED" {{ $status === 'POSTED' ? 'selected' : '' }}>POSTED</option>
            <option value="REVERSED" {{ $status === 'REVERSED' ? 'selected' : '' }}>REVERSED</option>
            <option value="DRAFT" {{ $status === 'DRAFT' ? 'selected' : '' }}>DRAFT</option>
          </select>
        </div>
        <div class="lg:col-span-2">
          <input type="date" name="date_from" value="{{ $dateFrom }}" class="block w-full rounded-xl border-0 bg-slate-50 py-1.5 px-3 text-xs font-semibold text-slate-800 ring-1 ring-inset ring-slate-300 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-700" title="Posting Date From">
        </div>
        <div class="lg:col-span-2">
          <input type="date" name="date_to" value="{{ $dateTo }}" class="block w-full rounded-xl border-0 bg-slate-50 py-1.5 px-3 text-xs font-semibold text-slate-800 ring-1 ring-inset ring-slate-300 focus:bg-white focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-white dark:ring-slate-700" title="Posting Date To">
        </div>
        <div class="lg:col-span-2 flex items-center gap-2">
          <button type="submit" class="inline-flex w-full items-center justify-center gap-1.5 rounded-xl bg-emerald-600 px-3.5 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-emerald-700 transition-all cursor-pointer">
            <i class="ph ph-funnel"></i>
            Filter
          </button>
          <a href="{{ route('accounting.general-ledger.index') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white p-1.5 text-xs font-semibold text-slate-600 shadow-sm hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-400 dark:hover:bg-slate-800 transition-all" title="Reset filters">
            <i class="ph ph-arrow-counter-clockwise"></i>
          </a>
        </div>
      </form>
    </div>

    <div class="overflow-x-auto custom-scrollbar">
      <table class="w-full text-left border-collapse text-xs">
        <thead>
          <tr class="border-b border-slate-200/80 bg-slate-50/75 dark:border-slate-800 dark:bg-slate-950/50">
            <th class="py-3 px-3 w-10"></th>
            <th class="py-3 px-4 font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider text-[11px]">Reference #</th>
            <th class="py-3 px-4 font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider text-[11px]">Posting Date</th>
            <th class="py-3 px-4 font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider text-[11px]">Description</th>
            <th class="py-3 px-4 font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider text-[11px]">Type</th>
            <th class="py-3 px-4 font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider text-[11px]">Status</th>
            <th class="py-3 px-4 font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider text-[11px] text-right">Total Debit</th>
            <th class="py-3 px-4 font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider text-[11px] text-right">Total Credit</th>
            <th class="py-3 px-4 font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider text-[11px] text-center">Action</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
          @forelse($entries as $entry)
            <tbody x-data="{ expanded: false }" class="border-b border-slate-100 dark:border-slate-800/60">
              <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors">
                <td class="py-3 px-3 text-center">
                  <button @click="expanded = !expanded" class="inline-flex h-6 w-6 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-400 transition-transform duration-200" :class="{ 'rotate-180': expanded }" type="button">
                    <i class="ph ph-caret-down text-xs"></i>
                  </button>
                </td>
                <td class="py-3 px-4 font-mono font-semibold text-slate-900 dark:text-white">
                  {{ $entry->reference_number }}
                </td>
                <td class="py-3 px-4 whitespace-nowrap text-slate-600 dark:text-slate-300">
                  {{ $entry->entry_date->format('M d, Y') }}
                </td>
                <td class="py-3 px-4">
                  <span class="font-medium text-slate-900 dark:text-white">{{ $entry->description }}</span>
                  @if($entry->reversed_by_entry_id)
                    <span class="inline-flex items-center gap-1 rounded bg-rose-50 px-1.5 py-0.5 text-[10px] font-semibold text-rose-700 ring-1 ring-inset ring-rose-700/10 dark:bg-rose-950/60 dark:text-rose-400 ml-1">
                      Reversed by #{{ $entry->reversed_by_entry_id }}
                    </span>
                  @endif
                </td>
                <td class="py-3 px-4">
                  <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                    {{ $entry->type }}
                  </span>
                </td>
                <td class="py-3 px-4">
                  @php
                    $statusClass = match($entry->status) {
                      'POSTED' => 'bg-emerald-50 text-emerald-700 ring-emerald-700/10 dark:bg-emerald-950/60 dark:text-emerald-400 dark:ring-emerald-500/20',
                      'REVERSED' => 'bg-rose-50 text-rose-700 ring-rose-700/10 dark:bg-rose-950/60 dark:text-rose-400 dark:ring-rose-500/20',
                      default => 'bg-amber-50 text-amber-700 ring-amber-700/10 dark:bg-amber-950/60 dark:text-amber-400 dark:ring-amber-500/20'
                    };
                  @endphp
                  <span class="inline-flex items-center rounded-md px-2 py-0.5 text-[11px] font-semibold ring-1 ring-inset {{ $statusClass }}">
                    {{ $entry->status }}
                  </span>
                </td>
                <td class="py-3 px-4 text-right font-mono tabular-nums font-semibold text-slate-900 dark:text-white">
                  ₱{{ number_format((float) $entry->lines->sum('debit'), 2) }}
                </td>
                <td class="py-3 px-4 text-right font-mono tabular-nums font-semibold text-slate-900 dark:text-white">
                  ₱{{ number_format((float) $entry->lines->sum('credit'), 2) }}
                </td>
                <td class="py-3 px-4 text-center">
                  @if($entry->status === 'POSTED')
                    @can('reverse-journal-entries')
                      <button type="button" @click="openReverse({ id: {{ $entry->id }}, ref: '{{ $entry->reference_number }}' })" class="inline-flex items-center gap-1 rounded-lg border border-rose-200 bg-white px-2 py-1 text-xs font-semibold text-rose-600 shadow-sm hover:bg-rose-50 dark:border-rose-900/50 dark:bg-slate-900 dark:text-rose-400 dark:hover:bg-rose-950/30 transition-all">
                        <i class="ph ph-arrow-u-down-left"></i>
                        Reverse
                      </button>
                    @else
                      <span class="inline-flex items-center gap-1 text-[11px] text-slate-400">
                        <i class="ph ph-eye"></i> Read-Only
                      </span>
                    @endcan
                  @else
                    <span class="text-[11px] font-mono text-slate-400">Immutable</span>
                  @endif
                </td>
              </tr>

              {{-- Expandable Double-Entry Journal Lines --}}
              <tr x-show="expanded" x-cloak class="bg-slate-50/75 dark:bg-slate-950/40">
                <td colspan="9" class="p-4">
                  <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex items-center justify-between pb-3 mb-3 border-b border-slate-100 dark:border-slate-800">
                      <h4 class="text-xs font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <i class="ph ph-check-square-offset text-emerald-600 dark:text-emerald-400"></i>
                        Balanced Double-Entry Journal Lines
                      </h4>
                      <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-700 dark:text-emerald-400">
                        <i class="ph ph-check"></i> Balanced &sum;DR == &sum;CR
                      </span>
                    </div>

                    <table class="w-full text-left border-collapse text-xs">
                      <thead>
                        <tr class="border-b border-slate-200/80 bg-slate-50/75 dark:border-slate-800 dark:bg-slate-950/50 text-[11px] uppercase tracking-wider text-slate-500">
                          <th class="py-2 px-3">Account Code &amp; Title</th>
                          <th class="py-2 px-3">Department</th>
                          <th class="py-2 px-3">Memo / Description</th>
                          <th class="py-2 px-3 text-right w-36">Debit (DR)</th>
                          <th class="py-2 px-3 text-right w-36">Credit (CR)</th>
                        </tr>
                      </thead>
                      <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                        @foreach($entry->lines as $line)
                          <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40">
                            <td class="py-2 px-3">
                              <span class="font-mono font-bold text-emerald-600 dark:text-emerald-400">{{ $line->account->code }}</span>
                              <span class="text-slate-800 dark:text-slate-200 font-medium ml-1.5">{{ $line->account->name }}</span>
                            </td>
                            <td class="py-2 px-3">
                              <span class="inline-flex items-center rounded bg-slate-100 px-1.5 py-0.5 text-[10px] text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                {{ $line->account->department ?? 'FINANCE' }}
                              </span>
                            </td>
                            <td class="py-2 px-3 text-slate-500 dark:text-slate-400 text-[11px]">
                              {{ $line->memo ?? '-' }}
                            </td>
                            <td class="py-2 px-3 text-right font-mono tabular-nums font-semibold {{ (float) $line->debit > 0 ? 'text-slate-900 dark:text-white' : 'text-slate-400' }}">
                              {{ (float) $line->debit > 0 ? '₱' . number_format((float) $line->debit, 2) : '-' }}
                            </td>
                            <td class="py-2 px-3 text-right font-mono tabular-nums font-semibold {{ (float) $line->credit > 0 ? 'text-slate-900 dark:text-white' : 'text-slate-400' }}">
                              {{ (float) $line->credit > 0 ? '₱' . number_format((float) $line->credit, 2) : '-' }}
                            </td>
                          </tr>
                        @endforeach
                      </tbody>
                    </table>
                  </div>
                </td>
              </tr>
            </tbody>
          @empty
            <tr>
              <td colspan="9" class="text-center py-8 text-slate-500 dark:text-slate-400">
                No journal transactions match your search criteria.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if($entries->hasPages())
      <div class="p-4 border-t border-slate-100 dark:border-slate-800/80">
        {{ $entries->links() }}
      </div>
    @endif
  </div>

  {{-- Reversal Alpine Modal --}}
  <template x-teleport="body">
  <div x-show="reverseModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div x-show="reverseModalOpen" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-slate-950/60 backdrop-blur-md transition-opacity" @click="closeReverse()"></div>

    <div class="fixed inset-0 z-10 w-screen overflow-y-auto p-4 sm:p-6 md:p-20 flex min-h-full items-center justify-center pointer-events-none">
      <div x-show="reverseModalOpen" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" @click.outside="closeReverse()" class="pointer-events-auto relative mx-auto w-full max-w-lg transform rounded-2xl bg-white p-6 shadow-2xl ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-800 transition-all text-left">

        <form method="POST" :action="'/accounting/general-ledger/' + reversalEntry?.id + '/reverse'">
          @csrf
          <div class="flex items-center gap-3 pb-4 border-b border-slate-100 dark:border-slate-800">
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-rose-50 text-rose-600 dark:bg-rose-950/60 dark:text-rose-400">
              <i class="ph-duotone ph-warning-circle text-xl"></i>
            </div>
            <div>
              <h3 class="text-sm font-bold text-slate-900 dark:text-white" id="modal-title">
                Confirm Transaction Reversal
              </h3>
              <p class="text-[11px] text-slate-500 dark:text-slate-400">Reference: <span class="font-mono font-semibold text-slate-700 dark:text-slate-300" x-text="reversalEntry?.ref"></span></p>
            </div>
          </div>

          <div class="py-4 space-y-3">
            <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
              Under GAAP/IFRS and BIR CAS rules, the original record will remain immutable and a mirroring reversal entry will be generated.
            </p>
            <div>
              <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                Reason for Reversal <span class="text-rose-500">*</span>
              </label>
              <textarea name="reason" rows="3" class="block w-full rounded-xl border border-slate-200 bg-white py-2 px-3 text-xs text-slate-900 placeholder:text-slate-400 focus:border-rose-500 focus:outline-none focus:ring-2 focus:ring-rose-500/20 dark:border-slate-800 dark:bg-slate-950 dark:text-white" placeholder="Specify error correction reason..." required></textarea>
            </div>
          </div>

          <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
            <button @click="closeReverse()" type="button" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800 transition-all">
              Cancel
            </button>
            <button type="submit" class="inline-flex items-center gap-1.5 rounded-xl bg-rose-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-rose-700 transition-all">
              <i class="ph ph-arrow-u-down-left"></i>
              Execute Reversal
            </button>
          </div>
        </form>

      </div>
    </div>
  </div>
  </template>

</div>
@endsection
