<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAvenueRequest;
use App\Models\Avenue;
use App\Models\Neighborhood;
use App\Services\ReferenceGenerator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class AvenueController extends Controller
{
    public function index(): View
    {
        return view('land.avenues.index', ['avenues' => Avenue::query()->with('neighborhood')->withCount('plots')->orderBy('name')->paginate(10)]);
    }

    public function create(): View
    {
        return $this->form(new Avenue);
    }

    public function store(StoreAvenueRequest $request, ReferenceGenerator $generator): RedirectResponse
    {
        $data = $request->validated();
        if (empty($data['code'])) {
            $data['code'] = $generator->generate(Avenue::class, 'code', 'avenues', 'AVE');
        }

        Avenue::query()->create($data);

        return redirect()->route('avenues.index')->with('status', __('Avenue créée.'));
    }

    public function edit(Avenue $avenue): View
    {
        return $this->form($avenue);
    }

    public function update(StoreAvenueRequest $request, Avenue $avenue): RedirectResponse
    {
        $data = $request->validated();
        if (empty($data['code'])) {
            unset($data['code']);
        }

        $avenue->update($data);

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
