<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVaultRequest;
use App\Models\Vault;
use App\Services\VaultService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class VaultController extends Controller
{
    public function index(Request $request): View
    {
        return view('vaults.index', [
            'vaults' => $request->user()->vaults()->withCount('archiveEntries')->orderBy('name')->get(),
        ]);
    }

    public function store(StoreVaultRequest $request, VaultService $vaults): RedirectResponse
    {
        $vault = $vaults->createForUser($request->user(), $request->validated('name'));

        return redirect()->route('vaults.show', $vault)->with('status', 'Vault created.');
    }

    public function show(Vault $vault): View
    {
        $this->authorize('view', $vault);

        return view('vaults.show', [
            'vault' => $vault,
            'entries' => $vault->archiveEntries()
                ->with('documentVersion.document')
                ->latest('added_at')
                ->get(),
        ]);
    }
}
