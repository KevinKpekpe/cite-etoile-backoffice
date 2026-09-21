<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePlotRequest;
use App\Models\AuditLog;
use App\Models\Avenue;
use App\Models\Neighborhood;
use App\Models\Plot;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PlotController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'], 'neighborhood_id' => ['nullable', 'integer', 'exists:neighborhoods,id'],
            'avenue_id' => ['nullable', 'integer', 'exists:avenues,id'], 'commercial_status' => ['nullable', 'in:available,reserved,subscribed,blocked,unavailable'],
        ]);
        $plots = Plot::query()->with('avenue.neighborhood')
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(fn ($query) => $query->where('reference', 'like', "%{$search}%")->orWhere('plot_number', 'like', "%{$search}%")))
            ->when($filters['neighborhood_id'] ?? null, fn ($query, $id) => $query->whereHas('avenue', fn ($query) => $query->where('neighborhood_id', $id)))
            ->when($filters['avenue_id'] ?? null, fn ($query, $id) => $query->where('avenue_id', $id))
            ->when($filters['commercial_status'] ?? null, fn ($query, $status) => $query->where('commercial_status', $status))
            ->latest()->paginate(24)->withQueryString();

        return view('land.plots.index', ['plots' => $plots, 'filters' => $filters, 'neighborhoods' => Neighborhood::query()->orderBy('name')->get(), 'avenues' => Avenue::query()->orderBy('name')->get()]);
    }

    public function create(): View
    {
        return $this->form(new Plot);
    }

    public function store(StorePlotRequest $request): RedirectResponse
    {
        $plot = Plot::query()->create($request->validated());
        $this->audit($request, 'plot.created', $plot, null, $plot->getAttributes());

        return redirect()->route('plots.show', $plot)->with('status', __('Parcelle créée.'));
    }

    public function show(Plot $plot): View
    {
        $plot->load(['avenue.neighborhood', 'subscriptions.customer', 'subscriptions.paymentPlan']);
        $history = AuditLog::query()->where('entity_type', Plot::class)->where('entity_id', $plot->id)->latest()->get();

        return view('land.plots.show', compact('plot', 'history'));
    }

    public function edit(Plot $plot): View
    {
        return $this->form($plot);
    }

    public function update(StorePlotRequest $request, Plot $plot): RedirectResponse
    {
        $oldValues = $plot->only(array_keys($request->validated()));
        $plot->update($request->validated());
        $this->audit($request, 'plot.updated', $plot, $oldValues, $plot->only(array_keys($request->validated())));

        return redirect()->route('plots.show', $plot)->with('status', __('Parcelle mise à jour.'));
    }

    public function destroy(Request $request, Plot $plot): RedirectResponse
    {
        abort_unless($request->user()?->can('plots.manage'), 403);

        $plot->delete();
        $this->audit($request, 'plot.deleted', $plot, $plot->only(['reference', 'plot_number']), null);

        return redirect()->route('plots.index')->with('status', __('Parcelle placée en corbeille.'));
    }

    public function trashed(Request $request): View
    {
        abort_unless($request->user()?->can('plots.manage'), 403);

        $plots = Plot::onlyTrashed()
            ->with('avenue.neighborhood')
            ->latest('deleted_at')
            ->paginate(24);

        return view('land.plots.trashed', compact('plots'));
    }

    public function restore(Request $request, Plot $plot): RedirectResponse
    {
        abort_unless($request->user()?->can('plots.restore'), 403);

        $plot->restore();
        $this->audit($request, 'plot.restored', $plot, null, $plot->only(['reference', 'plot_number']));

        return redirect()->route('plots.show', $plot)->with('status', __('Parcelle restaurée avec succès.'));
    }

    public function forceDelete(Request $request, Plot $plot): RedirectResponse
    {
        abort_unless($request->user()?->can('plots.force_delete'), 403);
        abort_if($plot->subscriptions()->exists(), 409, __('Cette parcelle possède un historique de souscriptions et ne peut pas être supprimée définitivement.'));

        $this->audit($request, 'plot.force_deleted', $plot, $plot->only(['reference', 'plot_number']), null);
        $plot->forceDelete();

        return redirect()->route('plots.trashed')->with('status', __('Parcelle supprimée définitivement.'));
    }

    private function form(Plot $plot): View
    {
        return view('land.plots.form', ['plot' => $plot, 'avenues' => Avenue::query()->with('neighborhood')->orderBy('name')->get()]);
    }

    /**
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     */
    private function audit(Request $request, string $action, Plot $plot, ?array $oldValues, ?array $newValues): void
    {
        AuditLog::query()->create(['user_id' => $request->user()?->id, 'action' => $action, 'entity_type' => Plot::class, 'entity_id' => $plot->id, 'old_values' => $oldValues, 'new_values' => $newValues, 'ip_address' => $request->ip(), 'user_agent' => $request->userAgent()]);
    }
}
