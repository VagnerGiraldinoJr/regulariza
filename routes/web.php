<?php

use App\Http\Controllers\OrdersController;
use App\Http\Controllers\SacTicketController;
use App\Models\SacTicket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::view('/regularizacao', 'regularizacao.index')->name('regularizacao.index');
Route::view('/regularizacao/sucesso', 'regularizacao.index')->name('regularizacao.sucesso');
Route::view('/regularizacao/cancelado', 'regularizacao.index')->name('regularizacao.cancelado');

Route::get('/dashboard', function (Request $request) {
    return match ($request->user()?->role) {
        'admin', 'atendente' => redirect()->route('admin.orders.index'),
        default => redirect()->route('portal.dashboard'),
    };
})->middleware('auth')->name('dashboard');

Route::middleware('guest')->group(function (): void {
    Route::view('/login', 'auth.login')->name('login');

    Route::post('/login', function (Request $request) {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, remember: true)) {
            return back()->withErrors([
                'email' => 'Credenciais inválidas.',
            ])->onlyInput('email');
        }

        $request->session()->regenerate();

        return match ($request->user()?->role) {
            'admin', 'atendente' => redirect()->intended(route('admin.orders.index')),
            default => redirect()->intended(route('portal.dashboard')),
        };
    })->name('login.attempt');
});

Route::post('/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('login');
})->middleware('auth')->name('logout');

Route::middleware(['auth', 'role:cliente'])->prefix('portal')->name('portal.')->group(function (): void {
    Route::get('/dashboard', [OrdersController::class, 'dashboard'])->name('dashboard');

    Route::get('/tickets', [SacTicketController::class, 'index'])->name('tickets.index');
    Route::post('/tickets', [SacTicketController::class, 'store'])->name('tickets.store');

    Route::get('/tickets/{ticket}', function (SacTicket $ticket) {
        return view('portal.tickets.chat', ['ticket' => $ticket]);
    })->can('view', 'ticket')->name('tickets.show');
});

Route::middleware(['auth', 'role:admin,atendente'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/orders', [OrdersController::class, 'adminIndex'])->name('orders.index');
    Route::get('/financeiro', [OrdersController::class, 'adminFinance'])
        ->middleware('role:admin')
        ->name('finance.dashboard');

    Route::get('/tickets', [SacTicketController::class, 'adminIndex'])->name('tickets.index');
    Route::patch('/tickets/{ticket}/assign', [SacTicketController::class, 'assign'])->name('tickets.assign');

    Route::get('/tickets/{ticket}', function (SacTicket $ticket) {
        return view('admin.tickets.chat', ['ticket' => $ticket]);
    })->can('view', 'ticket')->name('tickets.show');
});
