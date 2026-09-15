<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreNeighborhoodRequest;
use App\Models\Neighborhood;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class NeighborhoodController extends Controller
{
    public function index(): View
    {
        return view('land.neighborhoods.index', ['neighborhoods' => Neighborhood::query()->withCount(['avenues', 'avenues as plots_count' => fn ($query) => $query->join('plots', 'plots.avenue_id', '=', 'avenues.id')])->orderBy('name')->paginate(20)]);
    }

    public function create(): View
    {
        return view('land.neighborhoods.form', ['neighborhood' => new Neighborhood]);
    }

    public function store(StoreNeighborhoodRequest $request): RedirectResponse
    {
        $neighborhood = Neighborhood::query()->create($request->validated());

        return redirect()->route('neighborhoods.index')->with('status', __('Quartier créé.'));
    }

    public function edit(Neighborhood $neighborhood): View
    {
        return view('land.neighborhoods.form', compact('neighborhood'));
    }

    public function update(StoreNeighborhoodRequest $request, Neighborhood $neighborhood): RedirectResponse
    {
        $neighborhood->update($request->validated());

        return redirect()->route('neighborhoods.index')->with('status', __('Quartier mis à jour.'));
    }

    public function destroy(Neighborhood $neighborhood): RedirectResponse
    {
        abort_if($neighborhood->avenues()->exists(), 409, __('Ce quartier contient des avenues.'));
        $neighborhood->delete();

        return redirect()->route('neighborhoods.index')->with('status', __('Quartier supprimé.'));
    }
}
