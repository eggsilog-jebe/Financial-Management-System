<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Billing Statement - {{ $invoice->invoice_number }}</title>
  <x-favicon />
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/@phosphor-icons/web"></script>
  @vite(['resources/css/app.css'])
  <style>
    @media print {
      .no-print { display: none !important; }
      body { background-color: #fff !important; font-size: 11px; }
      .invoice-card { box-shadow: none !important; border: 1px solid #cbd5e1 !important; }
    }
  </style>
</head>
<body class="bg-slate-50 text-slate-800 p-4 sm:p-8 font-sans antialiased">
  <div class="max-w-4xl mx-auto">
    <!-- Print Button Toolbar -->
    <div class="flex items-center justify-between mb-6 no-print">
      <button 
        type="button" 
        onclick="window.history.back()" 
        class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 transition-all cursor-pointer"
      >
        <i class="ph-bold ph-arrow-left"></i>
        <span>Back to Invoices</span>
      </button>
      <button 
        type="button" 
        onclick="window.print()" 
        class="inline-flex items-center gap-1.5 rounded-xl bg-slate-900 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-slate-800 transition-all cursor-pointer"
      >
        <i class="ph-bold ph-printer"></i>
        <span>Print / Save as PDF</span>
      </button>
    </div>

    <!-- Official Billing Statement Card -->
    <div class="invoice-card rounded-2xl bg-white p-6 sm:p-10 shadow-sm ring-1 ring-slate-200/80">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-200 pb-6 mb-6">
        <div>
          <h1 class="text-xl font-extrabold tracking-tight text-slate-900 uppercase">
            Financial Management System
          </h1>
          <p class="text-xs text-slate-600 mt-0.5 font-medium">Novaliches, Quezon City, Metro Manila, Philippines</p>
        </div>
        <div class="text-left sm:text-right">
          <span class="inline-flex items-center px-3 py-1 rounded-lg text-xs font-bold tracking-wider uppercase bg-slate-900 text-white ring-1 ring-slate-900 mb-2">
            Billing Statement
          </span>
          <div class="font-mono font-bold text-sm text-slate-900">{{ $invoice->invoice_number }}</div>
          <div class="text-xs text-slate-500 mt-0.5 font-medium">Date: {{ $invoice->invoice_date->format('M d, Y') }}</div>
        </div>
      </div>

      <!-- Patient Information -->
      <div class="grid grid-cols-1 sm:grid-cols-12 gap-4 rounded-xl bg-slate-50 p-4 ring-1 ring-slate-300 mb-6 text-xs">
        <div class="sm:col-span-6">
          <span class="text-[11px] font-bold uppercase tracking-wider text-slate-600 block mb-1">Patient Full Name</span>
          <div class="font-bold text-slate-900 text-sm">{{ $invoice->patientAccount?->full_name ?? 'Walk-In Patient' }}</div>
          <div class="text-xs text-slate-600 font-mono mt-0.5 font-semibold">MRN: {{ $invoice->patientAccount?->patient_id_number ?? 'N/A' }}</div>
        </div>
        <div class="sm:col-span-3">
          <span class="text-[11px] font-bold uppercase tracking-wider text-slate-600 block mb-1">Admission Type</span>
          <div class="font-bold text-slate-900">{{ $invoice->patientAccount?->admission_type ?? 'Inpatient' }}</div>
          <div class="text-slate-600 mt-0.5 font-medium">Due Date: {{ $invoice->due_date ? $invoice->due_date->format('M d, Y') : 'Immediate' }}</div>
        </div>
        <div class="sm:col-span-3">
          <span class="text-[11px] font-bold uppercase tracking-wider text-slate-600 block mb-1">HMO / Policy Provider</span>
          <div class="font-bold text-slate-900">{{ $invoice->patientAccount?->hmo_provider ?? 'Self-Pay' }}</div>
          <div class="text-slate-600 mt-0.5 font-medium">Status: <span class="font-bold text-slate-900">{{ $invoice->status }}</span></div>
        </div>
      </div>

      <!-- Itemized Hospital Charges -->
      <h2 class="text-xs font-bold uppercase tracking-wider text-slate-900 mb-2">Itemized Departmental Charges</h2>
      <div class="overflow-x-auto rounded-xl border border-slate-300 mb-6">
        <table class="w-full text-left text-xs text-slate-800">
          <thead class="bg-slate-100 border-b border-slate-300 text-xs font-bold uppercase tracking-wider text-slate-900">
            <tr>
              <th class="px-3.5 py-2.5 w-12">#</th>
              <th class="px-3.5 py-2.5">Department</th>
              <th class="px-3.5 py-2.5">Procedure / Service Particulars</th>
              <th class="px-3.5 py-2.5 text-center">Qty</th>
              <th class="px-3.5 py-2.5 text-right">Unit Price</th>
              <th class="px-3.5 py-2.5 text-right">Gross (₱)</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200">
            @forelse($invoice->items as $idx => $item)
            <tr>
              <td class="px-3.5 py-2 text-slate-400 font-mono">{{ $idx + 1 }}</td>
              <td class="px-3.5 py-2">
                <span class="inline-flex px-1.5 py-0.5 rounded text-[10px] font-mono font-medium bg-slate-100 text-slate-700">{{ $item->department }}</span>
              </td>
              <td class="px-3.5 py-2 font-medium text-slate-900">{{ $item->description }}</td>
              <td class="px-3.5 py-2 text-center font-mono">{{ number_format((float) $item->quantity, 0) }}</td>
              <td class="px-3.5 py-2 text-right font-mono text-slate-600 tabular-nums">₱{{ number_format((float) $item->unit_price, 2) }}</td>
              <td class="px-3.5 py-2 text-right font-mono font-semibold text-slate-900 tabular-nums">₱{{ number_format((float) $item->gross_amount, 2) }}</td>
            </tr>
            @empty
            <tr>
              <td colspan="6" class="px-3.5 py-6 text-center text-slate-400">No itemized charges listed.</td>
            </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      @php
        $gross = (float) $invoice->total_amount;
        $vatRelief = (float) ($invoice->vat_amount ?? 0);
        $netAfterVat = $gross - $vatRelief;
        $discount20 = (float) bcsub((string) ($invoice->discount_amount ?? '0'), (string) ($invoice->vat_amount ?? '0'), 2);
        $statutoryTotal = (float) ($invoice->discount_amount ?? 0);
        $balanceAfterDiscount = $gross - $statutoryTotal;
        $philhealth = (float) ($invoice->philhealthClaim?->total_case_rate_amount ?? 0);
        $balanceAfterPhilhealth = $balanceAfterDiscount - $philhealth;
        $hmo = (float) ($invoice->hmoClaims->sum('claimed_amount') ?? 0);
        $balanceAfterHmo = $balanceAfterPhilhealth - $hmo;
        $finalPayable = (float) $invoice->balance_due;
        $isSeniorOrPwd = $statutoryTotal > 0 || in_array(strtoupper((string) ($invoice->patientAccount?->discount_category ?? '')), ['SENIOR_CITIZEN', 'SENIOR', 'PWD'], true);
      @endphp

      @if($isSeniorOrPwd || $philhealth > 0 || $hmo > 0)
      <!-- Statutory & Multi-Payer Adjudication Matrix -->
      <div class="mb-8">
        <h2 class="text-xs font-bold uppercase tracking-wider text-slate-900 mb-2 flex items-center gap-1.5">
          <i class="ph-bold ph-scales text-slate-800"></i>
          <span>Statutory &amp; Multi-Payer Adjudication Breakdown (RA 9994 / RA 10754 / PhilHealth)</span>
        </h2>
        <div class="overflow-x-auto rounded-xl border border-slate-300">
          <table class="w-full text-left text-xs">
            <thead class="bg-slate-100 border-b border-slate-300 font-bold uppercase tracking-wider text-slate-900">
              <tr>
                <th class="px-3.5 py-2.5">Calculation Step</th>
                <th class="px-3.5 py-2.5">Legal &amp; Accounting Basis</th>
                <th class="px-3.5 py-2.5 text-right font-mono">Amount</th>
                <th class="px-3.5 py-2.5 text-right font-mono">Running Balance</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
              <tr class="font-semibold bg-white">
                <td class="px-3.5 py-2.5 text-slate-900">Gross Clinical Charges</td>
                <td class="px-3.5 py-2.5 text-slate-800 font-normal">Sum of Inpatient Room, Lab, X-Ray, Pharmacy, PF</td>
                <td class="px-3.5 py-2.5 text-right font-mono text-slate-400">—</td>
                <td class="px-3.5 py-2.5 text-right font-mono text-slate-900 font-bold">₱{{ number_format($gross, 2) }}</td>
              </tr>
              @if($vatRelief > 0)
              <tr class="bg-slate-50/50">
                <td class="px-3.5 py-2.5 text-slate-900 font-semibold">1. 12% VAT Exemption</td>
                <td class="px-3.5 py-2.5 text-slate-800">
                  Under RA 9994 / RA 10754, exempt from 12% VAT:<br>
                  <span class="font-mono text-[11px] text-slate-900 font-semibold">Net of VAT = ₱{{ number_format($gross, 2) }} / 1.12 = ₱{{ number_format($netAfterVat, 2) }}</span><br>
                  <span class="font-mono text-[11px] text-slate-900 font-semibold">VAT Relief = ₱{{ number_format($gross, 2) }} - ₱{{ number_format($netAfterVat, 2) }}</span>
                </td>
                <td class="px-3.5 py-2.5 text-right font-mono text-slate-900 font-bold">-₱{{ number_format($vatRelief, 2) }}</td>
                <td class="px-3.5 py-2.5 text-right font-mono text-slate-900 font-medium">₱{{ number_format($netAfterVat, 2) }}</td>
              </tr>
              @endif
              @if($discount20 > 0)
              <tr class="bg-white">
                <td class="px-3.5 py-2.5 text-slate-900 font-semibold">2. 20% Statutory Discount</td>
                <td class="px-3.5 py-2.5 text-slate-800">
                  By law, the 20% discount applies to the Net of VAT amount:<br>
                  <span class="font-mono text-[11px] text-slate-900 font-semibold">₱{{ number_format($netAfterVat, 2) }} × 20%</span>
                </td>
                <td class="px-3.5 py-2.5 text-right font-mono text-slate-900 font-bold">-₱{{ number_format($discount20, 2) }}</td>
                <td class="px-3.5 py-2.5 text-right font-mono text-slate-900 font-medium">₱{{ number_format($balanceAfterDiscount, 2) }}</td>
              </tr>
              <tr class="bg-slate-100 font-semibold border-y border-slate-300">
                <td class="px-3.5 py-2 text-slate-900 font-bold">Total Statutory Relief</td>
                <td class="px-3.5 py-2 text-slate-800 font-medium">VAT Relief (₱{{ number_format($vatRelief, 2) }}) + 20% Discount (₱{{ number_format($discount20, 2) }})</td>
                <td class="px-3.5 py-2 text-right font-mono text-slate-900 font-bold">-₱{{ number_format($statutoryTotal, 2) }}</td>
                <td class="px-3.5 py-2 text-right font-mono text-slate-900 font-bold">₱{{ number_format($balanceAfterDiscount, 2) }}</td>
              </tr>
              @endif
              @if($philhealth > 0)
              <tr class="bg-slate-50/50">
                <td class="px-3.5 py-2.5 text-slate-900 font-semibold">3. PhilHealth ACR Deduction</td>
                <td class="px-3.5 py-2.5 text-slate-800">Transmitted Inpatient Case Rate Claim (Coverage/Deductions)</td>
                <td class="px-3.5 py-2.5 text-right font-mono text-slate-900 font-bold">-₱{{ number_format($philhealth, 2) }}</td>
                <td class="px-3.5 py-2.5 text-right font-mono text-slate-900 font-medium">₱{{ number_format($balanceAfterPhilhealth, 2) }}</td>
              </tr>
              @endif
              @if($hmo > 0)
              <tr class="bg-white">
                <td class="px-3.5 py-2.5 text-slate-900 font-semibold">4. Private HMO Coverage</td>
                <td class="px-3.5 py-2.5 text-slate-800">Approved Letter of Authorization ({{ $invoice->hmoClaims->first()?->hmo_provider ?? 'HMO' }})</td>
                <td class="px-3.5 py-2.5 text-right font-mono text-slate-900 font-bold">-₱{{ number_format($hmo, 2) }}</td>
                <td class="px-3.5 py-2.5 text-right font-mono text-slate-900 font-medium">₱{{ number_format($balanceAfterHmo, 2) }}</td>
              </tr>
              @endif
              <tr class="bg-slate-100 font-bold border-t-2 border-slate-900">
                <td class="px-3.5 py-2.5 text-slate-900 uppercase">Final Patient Payable</td>
                <td class="px-3.5 py-2.5 text-slate-800 font-medium">Out-of-Pocket Balance Due at Cashier</td>
                <td class="px-3.5 py-2.5 text-right font-mono text-slate-400">—</td>
                <td class="px-3.5 py-2.5 text-right font-mono text-slate-900 font-extrabold text-sm">₱{{ number_format($finalPayable, 2) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      @endif

      <!-- Financial Calculation Breakdown -->
      <div class="flex justify-end mb-8">
        <div class="w-full sm:w-96 space-y-2 text-xs">
          <div class="flex justify-between py-1.5 border-b border-slate-200">
            <span class="text-slate-800 font-semibold">Gross Hospital Charges:</span>
            <span class="font-mono font-bold text-slate-900 tabular-nums">₱{{ number_format((float) $invoice->total_amount, 2) }}</span>
          </div>
          
          @if((float) $invoice->vat_amount > 0)
          <div class="flex justify-between py-1 border-b border-slate-100 text-slate-900">
            <span class="text-slate-800 font-semibold">Less: 12% VAT Exemption (RA 9994/10754):</span>
            <span class="font-mono font-bold text-slate-900 tabular-nums">-₱{{ number_format((float) $invoice->vat_amount, 2) }}</span>
          </div>
          @endif

          @php
            $netDiscount = bcsub((string) $invoice->discount_amount, (string) ($invoice->vat_amount ?? '0'), 2);
          @endphp
          @if((float) $netDiscount > 0)
          <div class="flex justify-between py-1 border-b border-slate-100 text-slate-900">
            <span class="text-slate-800 font-semibold">Less: 20% Statutory Discount:</span>
            <span class="font-mono font-bold text-slate-900 tabular-nums">-₱{{ number_format((float) $netDiscount, 2) }}</span>
          </div>
          <div class="flex justify-between py-1.5 border-b border-slate-200 text-slate-900 font-bold bg-slate-50 px-2 rounded">
            <span>Total Statutory Deductions:</span>
            <span class="font-mono font-bold tabular-nums">-₱{{ number_format((float) $invoice->discount_amount, 2) }}</span>
          </div>
          @endif

          @if($invoice->philhealthClaim && (float) $invoice->philhealthClaim->total_case_rate_amount > 0)
          <div class="flex justify-between py-1 border-b border-slate-100 text-slate-900">
            <span class="text-slate-800 font-semibold">Less: PhilHealth ACR Claim:</span>
            <span class="font-mono font-bold text-slate-900 tabular-nums">-₱{{ number_format((float) $invoice->philhealthClaim->total_case_rate_amount, 2) }}</span>
          </div>
          @endif

          @if($invoice->hmoClaims && $invoice->hmoClaims->sum('claimed_amount') > 0)
          <div class="flex justify-between py-1 border-b border-slate-100 text-slate-900">
            <span class="text-slate-800 font-semibold">Less: HMO Approved Coverage ({{ $invoice->hmoClaims->first()?->hmo_provider }}):</span>
            <span class="font-mono font-bold text-slate-900 tabular-nums">-₱{{ number_format((float) $invoice->hmoClaims->sum('claimed_amount'), 2) }}</span>
          </div>
          @endif

          @if((float) $invoice->paid_amount > 0)
          <div class="flex justify-between py-1 border-b border-slate-100 text-slate-900">
            <span class="text-slate-800 font-semibold">Less: Cashier Payments Settled:</span>
            <span class="font-mono font-bold text-slate-900 tabular-nums">-₱{{ number_format((float) $invoice->paid_amount, 2) }}</span>
          </div>
          @endif

          <div class="flex justify-between py-2.5 border-t-2 border-slate-900 text-sm bg-slate-50 px-2 rounded-lg mt-2">
            <span class="font-bold text-slate-900">Net Patient Out-Of-Pocket Due:</span>
            <span class="font-mono font-extrabold text-slate-900 tabular-nums text-base">₱{{ number_format((float) $invoice->balance_due, 2) }}</span>
          </div>
        </div>
      </div>

      <!-- Footer & Signatures -->
      <div class="grid grid-cols-3 gap-6 text-center text-xs text-slate-500 pt-6 border-t border-slate-200">
        <div>
          <div class="border-t border-slate-300 pt-2 font-medium">Prepared by: Billing Clerk</div>
        </div>
        <div>
          <div class="border-t border-slate-300 pt-2 font-medium">Verified by: Patient / Representative</div>
        </div>
        <div>
          <div class="border-t border-slate-300 pt-2 font-medium">Authorized by: Hospital Cashier</div>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
