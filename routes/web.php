<?php

use App\Http\Controllers\OrdersController;
use App\Http\Controllers\AdminManagementController;
use App\Http\Controllers\AnalystPanelController;
use App\Http\Controllers\ApiBrasilConsultationController;
use App\Http\Controllers\ClientExperienceController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicContactController;
use App\Http\Controllers\SacTicketController;
use App\Models\User;
use App\Models\SacTicket;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

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
        'analista', 'vendedor' => redirect()->route('analyst.dashboard'),
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
            'analista', 'vendedor' => redirect()->intended(route('analyst.dashboard')),
            default => redirect()->intended(route('portal.dashboard')),
        };
    })->name('login.attempt');

    Route::view('/esqueci-senha', 'auth.forgot-password')->name('password.request');

    Route::post('/esqueci-senha', function (Request $request) {
        $request->validate([
            'email' => ['required', 'email'],
        ], [
            'email.required' => 'Informe o e-mail.',
            'email.email' => 'Informe um e-mail válido.',
        ]);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('status', 'Enviamos o link de redefinição para o seu e-mail.');
        }

        if ($status === Password::INVALID_USER) {
            return back()->withErrors(['email' => 'Não encontramos usuário com este e-mail.']);
        }

        if ($status === Password::RESET_THROTTLED) {
            return back()->withErrors(['email' => 'Aguarde alguns instantes antes de tentar novamente.']);
        }

        return back()->withErrors(['email' => 'Não foi possível enviar o link de redefinição agora.']);
    })->name('password.email');

    Route::get('/resetar-senha/{token}', function (Request $request, string $token) {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => (string) $request->query('email', ''),
        ]);
    })->name('password.reset');

    Route::post('/resetar-senha', function (Request $request) {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'email.required' => 'Informe o e-mail.',
            'email.email' => 'Informe um e-mail válido.',
            'password.required' => 'Informe a nova senha.',
            'password.min' => 'A nova senha deve ter no mínimo 8 caracteres.',
            'password.confirmed' => 'A confirmação da senha não confere.',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('status', 'Senha redefinida com sucesso. Faça login com a nova senha.');
        }

        if ($status === Password::INVALID_TOKEN) {
            return back()->withErrors(['email' => ['Link de redefinição inválido ou expirado.']])
                ->withInput($request->only('email'));
        }

        if ($status === Password::INVALID_USER) {
            return back()->withErrors(['email' => ['Não encontramos usuário com este e-mail.']])
                ->withInput($request->only('email'));
        }

        return back()->withErrors(['email' => ['Não foi possível redefinir a senha agora.']])
            ->withInput($request->only('email'));
    })->name('password.update');
});

Route::post('/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('login');
})->middleware('auth')->name('logout');

Route::middleware('auth')->group(function (): void {
    Route::get('/perfil', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/perfil', [ProfileController::class, 'update'])->name('profile.update');
});

Route::middleware(['auth', 'role:cliente', 'client.portal'])->prefix('portal')->name('portal.')->group(function (): void {
    Route::get('/dashboard', [OrdersController::class, 'dashboard'])->name('dashboard');
    Route::get('/contracts', [ContractController::class, 'clientIndex'])->name('contracts');
    Route::get('/timeline', [ClientExperienceController::class, 'timeline'])->name('timeline');
    Route::get('/perfil', fn () => redirect()->route('profile.edit'))->name('profile');
    Route::patch('/perfil', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/analista/chat', [ClientExperienceController::class, 'analystChat'])->name('analyst-chat');
    Route::post('/analista/chat', [ClientExperienceController::class, 'openAnalystChat'])->name('analyst-chat.open');
    Route::post('/orders/{order}/resend-payment-link', [OrdersController::class, 'resendPaymentLink'])
        ->name('orders.resend-payment-link');

    Route::get('/tickets', [SacTicketController::class, 'index'])->name('tickets.index');
    Route::post('/tickets', [SacTicketController::class, 'store'])->name('tickets.store');

    Route::get('/tickets/{ticket}', function (SacTicket $ticket) {
        return view('portal.tickets.chat', ['ticket' => $ticket]);
    })->can('view', 'ticket')->name('tickets.show');
});

Route::middleware(['auth', 'role:admin,atendente,analista,vendedor'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/orders', [OrdersController::class, 'adminIndex'])->name('orders.index');
    Route::get('/contracts', [ContractController::class, 'adminIndex'])
        ->middleware('role:admin')
        ->name('contracts.index');
    Route::post('/contracts', [ContractController::class, 'store'])
        ->middleware('role:admin')
        ->name('contracts.store');
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

Route::middleware(['auth', 'role:admin'])->prefix('admin/management')->name('admin.management.')->group(function (): void {
    Route::get('/dashboard', [AdminManagementController::class, 'dashboard'])->name('dashboard');
    Route::get('/contract-payments', [AdminManagementController::class, 'contractPayments'])->name('contract-payments');
    Route::get('/commissions', [AdminManagementController::class, 'commissions'])->name('commissions');
    Route::get('/payout-requests', [AdminManagementController::class, 'payoutRequests'])->name('payout-requests');
    Route::get('/integrations', [AdminManagementController::class, 'integrations'])->name('integrations');
    Route::post('/integrations', [AdminManagementController::class, 'updateIntegrations'])->name('integrations.update');
    Route::get('/apibrasil-consultations', [ApiBrasilConsultationController::class, 'index'])->name('apibrasil-consultations');
    Route::post('/apibrasil-consultations', [ApiBrasilConsultationController::class, 'store'])->name('apibrasil-consultations.store');
    Route::get('/apibrasil-consultations/{consultation}/pdf', [ApiBrasilConsultationController::class, 'downloadPdf'])->name('apibrasil-consultations.pdf');
    Route::post('/apibrasil-consultations/{consultation}/forward', [ApiBrasilConsultationController::class, 'forward'])->name('apibrasil-consultations.forward');
    Route::get('/messages', [AdminManagementController::class, 'messages'])->name('messages');
    Route::get('/orphan-leads', [AdminManagementController::class, 'orphanLeads'])->name('orphan-leads');
    Route::post('/orphan-leads/{lead}/assign', [AdminManagementController::class, 'assignLead'])->name('orphan-leads.assign');
    Route::get('/users', [AdminManagementController::class, 'users'])->name('users');
    Route::post('/users', [AdminManagementController::class, 'storeUser'])->name('users.store');
    Route::post('/users/{user}/send-reset-link', [AdminManagementController::class, 'sendResetLink'])->name('users.send-reset-link');
    Route::get('/vendors', [AdminManagementController::class, 'vendors'])->name('vendors');
    Route::post('/vendors', [AdminManagementController::class, 'storeVendor'])->name('vendors.store');
    Route::get('/clients', [AdminManagementController::class, 'clients'])->name('clients');
    Route::post('/clients', [AdminManagementController::class, 'storeClient'])->name('clients.store');
    Route::get('/clients/{user}/history', [AdminManagementController::class, 'clientHistory'])->name('clients.history');
    Route::post('/fake-data/generate', [AdminManagementController::class, 'generateFakeData'])->name('fake-data.generate');
    Route::delete('/fake-data/clear', [AdminManagementController::class, 'clearFakeData'])->name('fake-data.clear');
});

Route::middleware(['auth', 'role:analista,vendedor'])->prefix('analyst')->name('analyst.')->group(function (): void {
    Route::get('/dashboard', [AnalystPanelController::class, 'dashboard'])->name('dashboard');
    Route::get('/contracts', [ContractController::class, 'analystIndex'])->name('contracts');
    Route::get('/commissions', [AnalystPanelController::class, 'commissions'])->name('commissions');
    Route::post('/commissions/{commission}/request-payout', [AnalystPanelController::class, 'requestPayout'])->name('commissions.request-payout');
    Route::get('/clients', [AnalystPanelController::class, 'clients'])->name('clients');
});
