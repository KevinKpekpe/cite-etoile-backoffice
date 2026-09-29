<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAncillaryFeeTypeRequest;
use App\Models\AncillaryFeeType;
use App\Models\AuditLog;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AncillaryFeeTypeController extends Controller
{
    public function index(): View
    {
        $feeTypes = AncillaryFeeType::query()->withCount('fees')->orderBy('name')->paginate(20);

        return view('ancillary-fee-types.index', compact('feeTypes'));
    }

    public function create(): View
    {
        return view('ancillary-fee-types.form', ['feeType' => new AncillaryFeeType]);
    }

    public function store(StoreAncillaryFeeTypeRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            $feeType = AncillaryFeeType::query()->create([
                ...$request->validated(),
                'is_system' => false,
            ]);
            $this->audit($request, 'ancillary_fee_type.created', $feeType, null, $feeType->getAttributes());
        });

        return redirect()->route('ancillary-fee-types.index')->with('status', __('Type de frais connexe créé.'));
    }

    public function edit(AncillaryFeeType $ancillaryFeeType): View
    {
        return view('ancillary-fee-types.form', ['feeType' => $ancillaryFeeType]);
    }

    public function update(StoreAncillaryFeeTypeRequest $request, AncillaryFeeType $ancillaryFeeType): RedirectResponse
    {
        DB::transaction(function () use ($request, $ancillaryFeeType): void {
            $data = $request->validated();
            $oldValues = $ancillaryFeeType->only(array_keys($data));
            $ancillaryFeeType->update($data);
            $this->audit($request, 'ancillary_fee_type.updated', $ancillaryFeeType, $oldValues, $ancillaryFeeType->only(array_keys($data)));
        });

        return redirect()->route('ancillary-fee-types.index')->with('status', __('Type de frais connexe mis à jour.'));
    }

    public function destroy(Request $request, AncillaryFeeType $ancillaryFeeType): RedirectResponse
    {
        abort_unless($request->user()?->can('payments.create'), 403);

        if ($ancillaryFeeType->is_system) {
            throw ValidationException::withMessages(['fee_type' => __('Les types métier requis par les contrats ne peuvent pas être supprimés.')]);
        }

        if ($ancillaryFeeType->fees()->exists()) {
            throw ValidationException::withMessages(['fee_type' => __('Ce type est utilisé par des frais existants et ne peut pas être supprimé.')]);
        }

        DB::transaction(function () use ($request, $ancillaryFeeType): void {
            $oldValues = $ancillaryFeeType->getAttributes();
            $this->audit($request, 'ancillary_fee_type.deleted', $ancillaryFeeType, $oldValues, null);
            $ancillaryFeeType->delete();
        });

        return redirect()->route('ancillary-fee-types.index')->with('status', __('Type de frais connexe supprimé.'));
    }

    /**
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     */
    private function audit(Request $request, string $action, AncillaryFeeType $feeType, ?array $oldValues, ?array $newValues): void
    {
        AuditLog::query()->create([
            'user_id' => $request->user()?->id,
            'action' => $action,
            'entity_type' => AncillaryFeeType::class,
            'entity_id' => $feeType->id,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}
