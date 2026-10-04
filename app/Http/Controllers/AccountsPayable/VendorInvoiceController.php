<?php

declare(strict_types=1);

namespace App\Http\Controllers\AccountsPayable;

use App\DTOs\Accounting\DisbursementVoucherData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Accounting\PrepareDisbursementVoucherRequest;
use App\Models\BankAccount;
use App\Models\Bir2307Certificate;
use App\Models\DisbursementVoucher;
use App\Models\PurchaseBill;
use App\Models\Vendor;
use App\Services\Accounting\DisbursementExecutionService;
use App\Services\Accounting\ThreeWayMatchingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class VendorInvoiceController extends Controller
{
    public function __construct(
        private readonly DisbursementExecutionService $disbursementService,
        private readonly ThreeWayMatchingService $matchingService,
    ) {}

    public function index(Request $request): View
    {
        $status = $request->query('status');
        $search = $request->query('search');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $vendorId = $request->query('vendor_id');

        $query = PurchaseBill::with([
            'vendor',
            'items',
            'threeWayMatch.approver',
            'birCertificate',
            'disbursementVouchers.bankAccount',
        ])->latest('bill_date');

        if ($status) {
            $query->where('status', $status);
        }

        $matchStatus = $request->query('match_status');
        if ($matchStatus) {
            $query->whereHas('threeWayMatch', fn ($tq) => $tq->where('match_status', $matchStatus));
        }

        if ($vendorId) {
            $query->where('vendor_id', (int) $vendorId);
        }

        if ($startDate) {
            $query->whereDate('bill_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('bill_date', '<=', $endDate);
        }

        if ($search) {
            $query->where(function ($q) use ($search): void {
                $q->where('bill_number', 'LIKE', "%{$search}%")
                  ->orWhereHas('vendor', fn ($vq) => $vq->where('name', 'LIKE', "%{$search}%")
                      ->orWhere('code', 'LIKE', "%{$search}%")
                      ->orWhere('tin', 'LIKE', "%{$search}%"))
                  ->orWhereHas('threeWayMatch', fn ($tq) => $tq->where('vendor_invoice_number', 'LIKE', "%{$search}%")
                      ->orWhere('po_number', 'LIKE', "%{$search}%")
                      ->orWhere('grn_number', 'LIKE', "%{$search}%"));
            });
        }

        $invoices = $query->paginate(15)->withQueryString();

        $totalOpenBills = PurchaseBill::whereIn('status', ['UNPAID', 'PARTIAL', 'OVERDUE', 'APPROVED'])->count();
        $totalPayableAmount = (float) (PurchaseBill::whereIn('status', ['UNPAID', 'PARTIAL', 'OVERDUE', 'APPROVED'])
            ->selectRaw('COALESCE(SUM(total_amount - paid_amount), 0) as aggregate')
            ->value('aggregate') ?? 0);
        $totalEwtWithheld = (float) (\App\Models\Bir2307Certificate::sum('tax_withheld') ?? 0);
        $totalBilled = PurchaseBill::sum('total_amount');
        $totalPending = $totalPayableAmount;
        $totalVouchers = DisbursementVoucher::count();
        $bankAccounts = BankAccount::where('status', 'Active')->get();
        $vendors = Vendor::where('status', 'Active')->orderBy('name')->get();

        return view('accounts-payable.invoices-vouchers', [
            'invoices'           => $invoices,
            'totalOpenBills'     => $totalOpenBills,
            'totalPayableAmount' => $totalPayableAmount,
            'totalEwtWithheld'   => $totalEwtWithheld,
            'totalBilled'        => $totalBilled,
            'totalPending'       => $totalPending,
            'totalVouchers'      => $totalVouchers,
            'bankAccounts'       => $bankAccounts,
            'vendors'            => $vendors,
            'status'             => $status,
            'matchStatus'        => $matchStatus,
            'search'             => $search,
            'startDate'          => $startDate,
            'endDate'            => $endDate,
            'vendorId'           => $vendorId,
        ]);
    }

    public function prepareVoucher(PrepareDisbursementVoucherRequest $request): RedirectResponse
    {
        try {
            $dto = DisbursementVoucherData::fromArray($request->validated());
            $voucher = $this->disbursementService->prepareDisbursementVoucher($dto, auth()->id() ?? 1);

            return redirect()->back()->with('success', "Disbursement Voucher [{$voucher->voucher_number}] prepared for ₱" . number_format((float) $voucher->net_disbursed_amount, 2) . ". Routed to AP Payment Approvals.");
        } catch (\DomainException|\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function quickApprove(int|string $id): RedirectResponse
    {
        $bill = $this->matchingService->approveMatch((int) $id, auth()->id() ?? 1);

        return redirect()->back()->with('success', "Purchase Bill [{$bill->bill_number}] 3-Way Match successfully approved and routed for payment disbursement.");
    }

    public function exportApRegister(Request $request): StreamedResponse
    {
        $status = $request->query('status');
        $search = $request->query('search');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $vendorId = $request->query('vendor_id');

        $query = PurchaseBill::with(['vendor', 'items', 'threeWayMatch', 'birCertificate', 'disbursementVouchers'])
            ->latest('bill_date');

        if ($status) {
            $query->where('status', $status);
        }
        if ($vendorId) {
            $query->where('vendor_id', (int) $vendorId);
        }
        if ($startDate) {
            $query->whereDate('bill_date', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('bill_date', '<=', $endDate);
        }
        if ($search) {
            $query->where(function ($q) use ($search): void {
                $q->where('bill_number', 'LIKE', "%{$search}%")
                  ->orWhereHas('vendor', fn ($vq) => $vq->where('name', 'LIKE', "%{$search}%")->orWhere('tin', 'LIKE', "%{$search}%"))
                  ->orWhereHas('threeWayMatch', fn ($tq) => $tq->where('vendor_invoice_number', 'LIKE', "%{$search}%"));
            });
        }

        $filename = 'AP_Register_' . date('Ymd_His') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        $callback = function () use ($query): void {
            $handle = fopen('php://output', 'w');
            fputs($handle, "\xEF\xBB\xBF"); // UTF-8 BOM

            fputcsv($handle, ['Hospital Accounts Payable Voucher & Invoice Register']);
            fputcsv($handle, ['Export Date: ' . date('Y-m-d H:i:s')]);
            fputcsv($handle, ['BIR CAS & Audit Trail Record']);
            fputcsv($handle, []);

            fputcsv($handle, [
                'APV / Bill #',
                'Supplier Invoice #',
                'PO #',
                'GRN #',
                'Vendor Legal Name',
                'Vendor TIN',
                'Tax Type',
                'Bill Date',
                'Due Date',
                'Gross Amount (PHP)',
                'Withheld Tax EWT (PHP)',
                'ATC Code',
                'Net Payable (PHP)',
                'Paid Amount (PHP)',
                'Balance Due (PHP)',
                'Status',
            ]);

            $query->lazy(200)->each(function (PurchaseBill $bill) use ($handle): void {
                $taxType = $bill->vendor?->tax_type ?? 'VAT_REGISTERED';
                $atcCode = $bill->birCertificate?->atc_code ?? $bill->vendor?->default_atc_code ?? 'WC158';

                fputcsv($handle, [
                    $bill->bill_number,
                    $bill->vendor_invoice_number,
                    $bill->threeWayMatch?->po_number ?? '—',
                    $bill->threeWayMatch?->grn_number ?? '—',
                    $bill->vendor?->name ?? 'Unknown Vendor',
                    $bill->vendor?->tin ?? 'N/A',
                    $taxType,
                    $bill->bill_date?->format('Y-m-d') ?? '',
                    $bill->due_date?->format('Y-m-d') ?? '',
                    number_format((float) $bill->total_amount, 2, '.', ''),
                    number_format((float) $bill->withholding_tax_amount, 2, '.', ''),
                    $atcCode,
                    number_format((float) $bill->net_payable_amount, 2, '.', ''),
                    number_format((float) $bill->paid_amount, 2, '.', ''),
                    number_format((float) $bill->balance_due, 2, '.', ''),
                    $bill->status,
                ]);
            });

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function batchBir2307(Request $request): StreamedResponse
    {
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $vendorId = $request->query('vendor_id');

        $query = Bir2307Certificate::with(['vendor', 'purchaseBill.threeWayMatch'])
            ->latest('created_at');

        if ($vendorId) {
            $query->where('vendor_id', (int) $vendorId);
        }
        if ($startDate) {
            $query->whereDate('period_from', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('period_to', '<=', $endDate);
        }

        $filename = 'BIR_2307_Batch_Export_' . date('Ymd_His') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        $callback = function () use ($query): void {
            $handle = fopen('php://output', 'w');
            fputs($handle, "\xEF\xBB\xBF");

            fputcsv($handle, ['BIR Form 2307 Batch Certificate Register']);
            fputcsv($handle, ['Export Date: ' . date('Y-m-d H:i:s')]);
            fputcsv($handle, []);

            fputcsv($handle, [
                'Certificate #',
                'Payee Name',
                'Payee TIN',
                'ATC Code',
                'Period From',
                'Period To',
                'Tax Base (PHP)',
                'Tax Rate (%)',
                'Tax Withheld (PHP)',
                'Related Bill #',
                'Related Invoice #',
                'Status',
            ]);

            $query->lazy(200)->each(function (Bir2307Certificate $cert) use ($handle): void {
                fputcsv($handle, [
                    $cert->certificate_number,
                    $cert->payee_name,
                    $cert->payee_tin,
                    $cert->atc_code,
                    $cert->period_from?->format('Y-m-d') ?? '',
                    $cert->period_to?->format('Y-m-d') ?? '',
                    number_format((float) $cert->tax_base_amount, 2, '.', ''),
                    number_format((float) $cert->tax_rate * 100, 2, '.', '') . '%',
                    number_format((float) $cert->tax_withheld, 2, '.', ''),
                    $cert->purchaseBill?->bill_number ?? '—',
                    $cert->purchaseBill?->vendor_invoice_number ?? '—',
                    $cert->form_status,
                ]);
            });

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}

