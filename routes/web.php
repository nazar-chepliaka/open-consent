<?php

use App\Http\Controllers\AiConnectionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\VaultController;
use App\Models\ArchiveEntry;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', function () {
        return view('auth.login');
    })->name('login');

    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])->name('register.store');

    Route::post('/login', function (Request $request) {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    })->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function (Request $request) {
        $vaultIds = $request->user()->vaults()->pluck('vaults.id');

        return view('dashboard.index', [
            'vaultCount' => $vaultIds->count(),
            'documentCount' => Document::query()->whereIn('owner_vault_id', $vaultIds)->count(),
            'latestEntries' => ArchiveEntry::query()
                ->whereIn('vault_id', $vaultIds)
                ->with(['vault', 'documentVersion.document'])
                ->latest('added_at')
                ->limit(5)
                ->get(),
        ]);
    })->name('dashboard');

    Route::get('/vaults', [VaultController::class, 'index'])->name('vaults.index');
    Route::post('/vaults', [VaultController::class, 'store'])->name('vaults.store');
    Route::get('/vaults/{vault}', [VaultController::class, 'show'])->name('vaults.show');
    Route::get('/vaults/{vault}/documents/create', [DocumentController::class, 'create'])->name('vaults.documents.create');
    Route::post('/vaults/{vault}/documents', [DocumentController::class, 'store'])->name('vaults.documents.store');
    Route::get('/documents/{document}', [DocumentController::class, 'show'])->name('documents.show');
    Route::post('/documents/{document}/versions', [DocumentController::class, 'addVersion'])->name('documents.versions.store');

    Route::get('/settings/ai', [AiConnectionController::class, 'index'])->name('settings.ai.index');
    Route::put('/settings/ai/default', [AiConnectionController::class, 'updateDefault'])->name('settings.ai.default.update');
    Route::get('/settings/ai/connections/create', [AiConnectionController::class, 'create'])->name('settings.ai.create');
    Route::post('/settings/ai/connections', [AiConnectionController::class, 'store'])->name('settings.ai.store');
    Route::post('/settings/ai/connections/test', [AiConnectionController::class, 'testDraft'])->name('settings.ai.test-draft');
    Route::get('/settings/ai/connections/{ai_connection}/edit', [AiConnectionController::class, 'edit'])->name('settings.ai.edit');
    Route::put('/settings/ai/connections/{ai_connection}', [AiConnectionController::class, 'update'])->name('settings.ai.update');
    Route::delete('/settings/ai/connections/{ai_connection}', [AiConnectionController::class, 'destroy'])->name('settings.ai.destroy');
    Route::post('/settings/ai/connections/{ai_connection}/test', [AiConnectionController::class, 'testSaved'])->name('settings.ai.test');

    Route::post('/logout', function (Request $request) {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    })->name('logout');
});
