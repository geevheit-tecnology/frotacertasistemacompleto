<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FinancialTransaction;
use App\Models\Invoice;
use App\Support\Auditor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'status' => ['nullable', 'string', 'max:32'],
            'vehicle_id' => ['nullable', 'uuid'],
        ]);
        $query = Invoice::query()->tenant($request->user()->company_id, $request->user()->branch_id);
        if (isset($data['status'])) {
            $query->where('status', $data['status']);
        }
        if (isset($data['vehicle_id'])) {
            $query->where('vehicle_id', $data['vehicle_id']);
        }

        return response()->json($query->latest('issued_at')->paginate(25));
    }

    public function store(Request $request, Auditor $auditor): JsonResponse
    {
        $data = $request->validate([
            'supplier_id' => ['nullable', 'uuid'],
            'vehicle_id' => ['nullable', 'uuid'],
            'work_order_id' => ['nullable', 'uuid'],
            'cost_center_id' => ['nullable', 'uuid'],
            'number' => ['required', 'string', 'max:80'],
            'series' => ['nullable', 'string', 'max:40'],
            'access_key' => ['nullable', 'string', 'max:80'],
            'issued_at' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0'],
            'category' => ['nullable', 'string', 'max:80'],
            'file_path' => ['nullable', 'string', 'max:500'],
            'status' => ['nullable', 'string', 'max:32'],
        ]);
        $flags = $this->flagsFor($request, $data);

        $invoice = DB::transaction(function () use ($request, $auditor, $data, $flags): Invoice {
            $invoice = Invoice::create($data + [
                'group_id' => $request->user()->group_id,
                'company_id' => $request->user()->company_id,
                'branch_id' => $request->user()->branch_id,
                'flags' => $flags,
            ]);
            FinancialTransaction::create([
                'group_id' => $invoice->group_id,
                'company_id' => $invoice->company_id,
                'branch_id' => $invoice->branch_id,
                'vehicle_id' => $invoice->vehicle_id,
                'work_order_id' => $invoice->work_order_id,
                'supplier_id' => $invoice->supplier_id,
                'cost_center_id' => $invoice->cost_center_id,
                'type' => 'PAYABLE',
                'category' => $invoice->category,
                'description' => 'Nota fiscal '.$invoice->number,
                'amount' => $invoice->amount,
                'due_at' => $invoice->issued_at,
                'status' => 'OPEN',
                'origin' => 'invoice',
            ]);
            $auditor->recordCreated($request, $invoice, 'api.v1.invoices.store');

            return $invoice;
        });

        return response()->json($invoice, 201);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<int, string>
     */
    private function flagsFor(Request $request, array $data): array
    {
        $flags = [];
        if (! isset($data['vehicle_id']) && ! isset($data['work_order_id'])) {
            $flags[] = 'MISSING_OPERATIONAL_LINK';
        }
        if (! isset($data['cost_center_id'])) {
            $flags[] = 'MISSING_COST_CENTER';
        }
        $duplicate = Invoice::query()
            ->tenant($request->user()->company_id, $request->user()->branch_id)
            ->where('number', $data['number'])
            ->where('series', $data['series'] ?? null)
            ->exists();
        if ($duplicate) {
            $flags[] = 'POSSIBLE_DUPLICATE';
        }

        return $flags;
    }
}
