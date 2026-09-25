<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreNeighborhoodRequest;
use App\Models\Neighborhood;
use App\Services\ReferenceGenerator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class NeighborhoodController extends Controller
{
    public function index(): View
    {
        return view('land.neighborhoods.index', ['neighborhoods' => Neighborhood::query()->withCount(['avenues', 'avenues as plots_count' => fn ($query) => $query->join('plots', 'plots.avenue_id', '=', 'avenues.id')])->orderBy('name')->paginate(10)]);
    }

    public function create(): View
    {
        return view('land.neighborhoods.form', ['neighborhood' => new Neighborhood]);
    }

    public function store(StoreNeighborhoodRequest $request, ReferenceGenerator $generator): RedirectResponse
    {
        $data = $request->validated();
        if (empty($data['code'])) {
            $data['code'] = $generator->generate(Neighborhood::class, 'code', 'neighborhoods', 'NBR');
        }

        $neighborhood = Neighborhood::query()->create($data);

        return redirect()->route('neighborhoods.index')->with('status', __('Quartier créé.'));
    }

    public function edit(Neighborhood $neighborhood): View
    {
        return view('land.neighborhoods.form', compact('neighborhood'));
    }

    public function update(StoreNeighborhoodRequest $request, Neighborhood $neighborhood): RedirectResponse
    {
        $data = $request->validated();
        if (empty($data['code'])) {
            unset($data['code']);
        }

        $neighborhood->update($data);

        return redirect()->route('neighborhoods.index')->with('status', __('Quartier mis à jour.'));
    }

    public function destroy(Neighborhood $neighborhood): RedirectResponse
    {
        abort_if($neighborhood->avenues()->exists(), 409, __('Ce quartier contient des avenues.'));
        $neighborhood->delete();

        return redirect()->route('neighborhoods.index')->with('status', __('Quartier supprimé.'));
    }
}
