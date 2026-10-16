<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddServiceLaborRequest;
use App\Http\Requests\AddServicePartRequest;
use App\Http\Requests\CancelServiceRequest;
use App\Http\Requests\ChangeServiceStatusRequest;
use App\Http\Requests\ServicePaymentRequest;
use App\Http\Requests\StoreServiceRequest;
use App\Http\Requests\UpdateServiceEstimateRequest;
use App\Models\Product;
use App\Models\Service;
use App\Services\ServiceWorkflowService;
use App\Support\WhatsAppLink;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServiceController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'string', 'max:32'],
        ]);

        $services = Service::query()
            ->with(['receivedBy:id,name'])
            ->when(! empty($validated['status']), fn ($query) => $query->where('status', $validated['status']))
            ->when(! empty($validated['q']), function ($query) use ($validated): void {
                $term = trim($validated['q']);
                $query->where(function ($subQuery) use ($term): void {
                    $subQuery->where('number', 'like', '%'.$term.'%')
                        ->orWhere('customer_name', 'like', '%'.$term.'%')
                        ->orWhere('customer_phone', 'like', '%'.$term.'%')
                        ->orWhere('brand_model', 'like', '%'.$term.'%');
                });
            })
            ->orderByDesc('received_at')
            ->paginate(15)
            ->withQueryString();

        return view('services.index', compact('services'));
    }

    public function create(): View
    {
        return view('services.create');
    }

    public function store(StoreServiceRequest $request, ServiceWorkflowService $workflow): RedirectResponse
    {
        $service = $workflow->register($request->validated(), $request->user());

        return redirect()->route('services.show', $service)->with('success', "Service {$service->number} berhasil diterima.");
    }

    public function show(Service $service): View
    {
        $service->load([
            'customer', 'receivedBy', 'cancelledBy', 'items.product',
            'payments.user', 'statusLogs.user',
        ]);
        $trackingCode = $service->tracking_code_for_display;
        $message = "Halo {$service->customer_name}, pembaruan service {$service->number}: status saat ini {$service->status}. Cek di ".route('public.services.index')." dengan kode {$trackingCode}.";
        $whatsappUrl = $trackingCode ? WhatsAppLink::url($service->customer_phone, $message) : null;

        return view('services.show', [
            'service' => $service,
            'trackingCode' => $trackingCode,
            'whatsappUrl' => $whatsappUrl,
            'products' => Product::query()
                ->available()
                ->goods()
                ->orderBy('name')
                ->get(['id', 'name', 'barcode', 'selling_price', 'stock', 'unit']),
        ]);
    }

    public function addPart(AddServicePartRequest $request, Service $service, ServiceWorkflowService $workflow): RedirectResponse
    {
        $workflow->addPart($service, $request->validated(), $request->user());

        return redirect()->route('services.show', $service)->with('success', 'Sparepart ditambahkan dan stok diperbarui.');
    }

    public function addLabor(AddServiceLaborRequest $request, Service $service, ServiceWorkflowService $workflow): RedirectResponse
    {
        $workflow->addLabor($service, $request->validated(), $request->user());

        return redirect()->route('services.show', $service)->with('success', 'Biaya jasa ditambahkan.');
    }

    public function updateStatus(ChangeServiceStatusRequest $request, Service $service, ServiceWorkflowService $workflow): RedirectResponse
    {
        $workflow->changeStatus($service, $request->validated(), $request->user());

        return redirect()->route('services.show', $service)->with('success', 'Status service berhasil diperbarui.');
    }

    public function estimate(UpdateServiceEstimateRequest $request, Service $service, ServiceWorkflowService $workflow): RedirectResponse
    {
        $workflow->updateEstimate($service, $request->validated(), $request->user());

        return redirect()->route('services.show', $service)->with('success', 'Estimasi biaya berhasil diperbarui.');
    }

    public function payment(ServicePaymentRequest $request, Service $service, ServiceWorkflowService $workflow): RedirectResponse
    {
        $workflow->recordPayment($service, $request->validated(), $request->user());

        return redirect()->route('services.show', $service)->with('success', 'Pembayaran service berhasil dicatat.');
    }

    public function cancel(CancelServiceRequest $request, Service $service, ServiceWorkflowService $workflow): RedirectResponse
    {
        $workflow->cancel($service, $request->validated('reason'), $request->user());

        return redirect()->route('services.show', $service)->with('success', 'Service dibatalkan. Pembayaran yang sudah diterima tetap tercatat.');
    }

    public function intakeReceipt(Service $service): View
    {
        return $this->receipt($service, 'terima');
    }

    public function releaseReceipt(Service $service): View
    {
        abort_unless($service->status === 'Diambil', 404);

        return $this->receipt($service, 'ambil');
    }

    private function receipt(Service $service, string $mode): View
    {
        $service->load(['customer', 'receivedBy', 'items', 'payments.user', 'statusLogs.user']);

        return view('services.receipt', compact('service', 'mode'));
    }
}
