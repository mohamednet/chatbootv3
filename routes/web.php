<?php

use App\Http\Controllers\ChatController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\FacebookWebhookController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SSEController;
use App\Http\Controllers\TrialController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Mail;

// Facebook Webhook Routes
Route::get('/webhook', [FacebookWebhookController::class, 'verify'])->withoutMiddleware(['web']);
Route::post('/webhook', [FacebookWebhookController::class, 'handleWebhook'])->withoutMiddleware(['web']);

// Web routes (with CSRF)
Route::middleware(['web'])->group(function () {
    Route::get('/', function () {
        return redirect()->route('dashboard');
    })->middleware(['auth']);

    Route::get('/dashboard', function () {
        return redirect()->route('chat.index');
    })->middleware(['auth'])->name('dashboard');

    // Admin Dashboard Routes
    Route::middleware(['auth'])->group(function () {
        // Chat Routes
        Route::get('/conversations', [ChatController::class, 'index'])->name('chat.index');
        Route::get('/conversations/updates', [ChatController::class, 'getUpdates'])->name('chat.updates');
        Route::get('/conversations/{conversation}', [ChatController::class, 'show'])->name('chat.show');
        Route::get('/conversations/{conversation}/messages', [ChatController::class, 'getNewMessages'])->name('chat.get-messages');
        Route::post('/conversations/{conversation}/messages', [ChatController::class, 'sendMessage'])->name('chat.send-message');
        Route::post('/conversations/{conversation}/toggle-mode', [ChatController::class, 'toggleResponseMode'])->name('chat.toggle-mode');
        
        // Customer Routes
        Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
        Route::get('/customers/updates', [CustomerController::class, 'getUpdates'])->name('customers.updates');
        Route::post('/customers/{customer}/send-trial', [CustomerController::class, 'sendTrial'])->name('customers.send-trial');
        
        // Trial Routes
        Route::resource('trials', TrialController::class);
        
        Route::get('/stream', [SSEController::class, 'stream'])->name('stream');
    });

    // Profile Routes
    Route::middleware('auth')->group(function () {
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    });

    // Test email route
    Route::get('/test-email', function () {
        try {
            Mail::raw('Test email content', function ($message) {
                $message->to('test@example.com')
                       ->subject('Test Email');
            });
            return 'Email sent successfully';
        } catch (\Exception $e) {
            return 'Email error: ' . $e->getMessage();
        }
    });
});

require __DIR__.'/auth.php';
