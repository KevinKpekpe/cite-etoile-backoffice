<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\User;
use App\Services\ReferenceGenerator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $filters = $request->validate(['search' => ['nullable', 'string', 'max:100'], 'status' => ['nullable', 'in:prospect,active,settled,suspended,archived']]);
        $search = trim((string) ($filters['search'] ?? ''));

        $customers = Customer::query()
            ->with('assignedAgent:id,first_name,last_name')
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search): void {
                $query->where('customer_number', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            }))
            ->when(isset($filters['status']), fn ($query) => $query->where('status', $filters['status']))
            ->latest()->paginate(20)->withQueryString();

        return view('customers.index', compact('customers', 'filters'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('customers.create', ['agents' => $this->agents()]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCustomerRequest $request, ReferenceGenerator $references): RedirectResponse
    {
        $customer = DB::transaction(function () use ($request, $references): Customer {
            $customer = Customer::query()->create([
                ...$request->validated(),
                'customer_number' => $references->generate(Customer::class, 'customer_number', 'customer', 'CLI'),
                'created_by' => $request->user()->id,
            ]);
            $this->audit($request, 'customer.created', $customer, null, $customer->getAttributes());

            return $customer;
        });

        return redirect()->route('customers.show', $customer)->with('status', __('Client créé.'));
    }

    /**
     * Display the specified resource.
     */
    public function show(Customer $customer): View
    {
        $customer->load([
            'assignedAgent:id,first_name,last_name', 'documents',
            'subscriptions' => fn ($query) => $query->with(['plot', 'paymentPlan'])->latest(),
            'payments' => fn ($query) => $query->latest('payment_date'),
            'receipts' => fn ($query) => $query->latest('issued_at'),
        ]);

        return view('customers.show', compact('customer'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Customer $customer): View
    {
        return view('customers.edit', ['customer' => $customer, 'agents' => $this->agents()]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCustomerRequest $request, Customer $customer): RedirectResponse
    {
        DB::transaction(function () use ($request, $customer): void {
            $oldValues = $customer->only(array_keys($request->validated()));
            $customer->update($request->validated());
            $this->audit($request, 'customer.updated', $customer, $oldValues, $customer->only(array_keys($request->validated())));
        });

        return redirect()->route('customers.show', $customer)->with('status', __('Client mis à jour.'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function archive(Request $request, Customer $customer): RedirectResponse
    {
        abort_unless($request->user()?->can('customers.delete'), 403);
        $oldStatus = $customer->status;
        $customer->update(['status' => 'archived']);
        $this->audit($request, 'customer.archived', $customer, ['status' => $oldStatus], ['status' => 'archived']);

        return redirect()->route('customers.show', $customer)->with('status', __('Client archivé.'));
    }

    /** @return Collection<int, User> */
    private function agents(): Collection
    {
        return User::query()->whereHas('roles', fn ($query) => $query->whereIn('name', ['super_admin', 'admin', 'commercial']))
            ->orderBy('first_name')->get(['id', 'first_name', 'last_name']);
    }

    /**
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     */
    private function audit(Request $request, string $action, Customer $customer, ?array $oldValues, ?array $newValues): void
    {
        AuditLog::query()->create([
            'user_id' => $request->user()?->id, 'action' => $action, 'entity_type' => Customer::class,
            'entity_id' => $customer->id, 'old_values' => $oldValues, 'new_values' => $newValues,
            'ip_address' => $request->ip(), 'user_agent' => $request->userAgent(),
        ]);
    }
}
