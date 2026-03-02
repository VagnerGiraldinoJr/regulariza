<?php

use App\Http\Controllers\OrdersController;
use App\Http\Controllers\PublicContactController;
use App\Http\Controllers\SacTicketController;
use App\Models\User;
use App\Models\SacTicket;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::get('/assets/selos-seguranca/siteblindado-dinamico.svg', function () {
    $svgPath = public_path('assets/selos-seguranca/siteblindado.svg');
    abort_unless(File::exists($svgPath), 404);

    $now = now();
    $monthMap = [
        1 => 'JAN',
        2 => 'FEV',
        3 => 'MAR',
        4 => 'ABR',
        5 => 'MAI',
        6 => 'JUN',
        7 => 'JUL',
        8 => 'AGO',
        9 => 'SET',
        10 => 'OUT',
        11 => 'NOV',
        12 => 'DEZ',
    ];
    $auditDate = $now->format('d') . ' ' . $monthMap[(int) $now->format('n')];

    $svg = File::get($svgPath);
    $svg = preg_replace(
        '/(<text[^>]*id="auditing"[^>]*>)([^<]*)(<\/text>)/',
        '$1AUDITADO EM ' . $auditDate . '$3',
        $svg,
        1
    );

    return response($svg, 200, [
        'Content-Type' => 'image/svg+xml',
        'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
    ]);
})->name('assets.siteblindado.svg');

Route::view('/regularizacao', 'regularizacao.index')->name('regularizacao.index');
Route::view('/regularizacao/sucesso', 'regularizacao.index')->name('regularizacao.sucesso');
Route::view('/regularizacao/cancelado', 'regularizacao.index')->name('regularizacao.cancelado');
Route::post('/contato/whatsapp', [PublicContactController::class, 'store'])
    ->middleware('throttle:20,1')
    ->name('public.whatsapp.store');

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

        $email = mb_strtolower(trim((string) $credentials['email']));
        $password = (string) $credentials['password'];

        $attemptOk = Auth::attempt([
            'email' => $email,
            'password' => $password,
        ], remember: true);

        if (! $attemptOk) {
            $user = User::query()->where('email', $email)->first();

            if (! $user || ! Hash::check($password, (string) $user->password)) {
                return back()->withErrors([
                    'email' => 'Credenciais inválidas.',
                ])->onlyInput('email');
            }

            Auth::login($user, remember: true);
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
    Route::post('/orders/{order}/resend-payment-link', [OrdersController::class, 'resendPaymentLink'])
        ->name('orders.resend-payment-link');

    Route::get('/tickets', [SacTicketController::class, 'index'])->name('tickets.index');
    Route::post('/tickets', [SacTicketController::class, 'store'])->name('tickets.store');

    Route::get('/tickets/{ticket}', function (SacTicket $ticket) {
        return view('portal.tickets.chat', ['ticket' => $ticket]);
    })->can('view', 'ticket')->name('tickets.show');
});

Route::middleware(['auth', 'role:admin,atendente'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/orders', [OrdersController::class, 'adminIndex'])->name('orders.index');
    Route::get('/vendedores', [OrdersController::class, 'adminSellers'])
        ->middleware('role:admin')
        ->name('vendors.index');
    Route::get('/financeiro', [OrdersController::class, 'adminFinance'])
        ->middleware('role:admin')
        ->name('finance.dashboard');

    Route::get('/tickets', [SacTicketController::class, 'adminIndex'])->name('tickets.index');
    Route::patch('/tickets/{ticket}/assign', [SacTicketController::class, 'assign'])->name('tickets.assign');

    Route::get('/tickets/{ticket}', function (SacTicket $ticket) {
        return view('admin.tickets.chat', ['ticket' => $ticket]);
    })->can('view', 'ticket')->name('tickets.show');
});
