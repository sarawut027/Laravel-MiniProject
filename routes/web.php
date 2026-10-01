<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TechnicianController;
use App\Http\Controllers\TicketController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes (Single Source of Truth / Route Contract)
|--------------------------------------------------------------------------
*/

// Root redirection
Route::get('/', function () {
    if (Auth::check()) {
        return match (Auth::user()->role) {
            'admin' => redirect()->route('admin.dashboard'),
            'technician' => redirect()->route('tech.tasks'),
            default => redirect()->route('tickets.index'),
        };
    }
    return redirect()->route('login');
})->name('home');

// Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// Authenticated Routes
Route::middleware(['auth'])->group(function () {

    // General /dashboard redirect according to role
    Route::get('/dashboard', function () {
        return match (Auth::user()->role) {
            'admin' => redirect()->route('admin.dashboard'),
            'technician' => redirect()->route('tech.tasks'),
            default => redirect()->route('tickets.index'),
        };
    })->name('dashboard');

    // 1. User / Requester Routes (role:user,admin)
    Route::middleware(['role:user,admin'])->group(function () {
        Route::get('/tickets', [TicketController::class, 'index'])->name('tickets.index');
        Route::get('/tickets/create', [TicketController::class, 'create'])->name('tickets.create');
        Route::post('/tickets', [TicketController::class, 'store'])->name('tickets.store');
    });

    Route::middleware(['role:user'])->group(function () {
        Route::post('/tickets/{ticket}/cancel', [TicketController::class, 'cancel'])->name('tickets.cancel');
        Route::post('/tickets/{ticket}/review', [TicketController::class, 'review'])->name('tickets.review');
    });

    // Ticket Show / Details (Shared between User, Assigned Technician, and Admin)
    Route::get('/tickets/{ticket}', [TicketController::class, 'show'])
        ->whereNumber('ticket')
        ->name('tickets.show');

    // 2. Technician Routes (role:technician)
    Route::middleware(['role:technician'])->prefix('technician')->group(function () {
        Route::get('/tasks', [TechnicianController::class, 'index'])->name('tech.tasks');
        Route::post('/tasks/{ticket}/status', [TechnicianController::class, 'updateStatus'])->name('tech.updateStatus');
    });

    // 3. Admin Routes (role:admin)
    Route::middleware(['role:admin'])->prefix('admin')->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');
        Route::get('/tickets', [AdminController::class, 'tickets'])->name('admin.tickets.index');
        Route::post('/tickets/{ticket}/assign', [AdminController::class, 'assign'])->name('admin.tickets.assign');
    });
});

require __DIR__.'/settings.php';
