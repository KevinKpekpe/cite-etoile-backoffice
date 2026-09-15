<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAvenueRequest;
use App\Models\Avenue;
use App\Models\Neighborhood;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class AvenueController extends Controller
{
    public function index(): View
    {
        return view('land.avenues.index', ['avenues' => Avenue::query()->with('neighborhood')->withCount('plots')->orderBy('name')->paginate(20)]);
    }

    public function create(): View
    {
        return $this->form(new Avenue);
    }

    public function store(StoreAvenueRequest $request): RedirectResponse
    {
        Avenue::query()->create($request->validated());

        return redirect()->route('avenues.index')->with('status', __('Avenue créée.'));
    }

    public function edit(Avenue $avenue): View
    {
        return $this->form($avenue);
    }

    public function update(StoreAvenueRequest $request, Avenue $avenue): RedirectResponse
    {
        $avenue->update($request->validated());

        return redirect()->route('avenues.index')->with('status', __('Avenue mise à jour.'));
    }

    public function destroy(Avenue $avenue): RedirectResponse
    {
        abort_if($avenue->plots()->exists(), 409, __('Cette avenue contient des parcelles.'));
        $avenue->delete();

        return redirect()->route('avenues.index')->with('status', __('Avenue supprimée.'));
    }

    private function form(Avenue $avenue): View
    {
        return view('land.avenues.form', ['avenue' => $avenue, 'neighborhoods' => Neighborhood::query()->orderBy('name')->get()]);
    }
}
