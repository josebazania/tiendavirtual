<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\PasswordController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

Route::get('/', [CourseController::class, 'index'])->name('home');
Route::get('/cursos/{course}', [CourseController::class, 'show'])->name('courses.show');
Route::post('/pagos/mercadopago/notificacion', [PaymentController::class, 'webhook'])->name('payments.webhook');
Route::get('/imagen-sitio/{type}', [SettingsController::class, 'image'])->whereIn('type', ['logo', 'favicon'])->name('settings.image');

Route::middleware('guest')->group(function (): void {
    Route::get('/ingresar', [AuthController::class, 'loginForm'])->name('login');
    Route::post('/ingresar', [AuthController::class, 'login'])->middleware('throttle:6,1');
    Route::get('/registro', [AuthController::class, 'registerForm'])->name('register');
    Route::post('/registro', [AuthController::class, 'register'])->middleware('throttle:6,1');
    Route::get('/olvide-clave', [PasswordController::class, 'requestForm'])->name('password.request');
    Route::post('/olvide-clave', [PasswordController::class, 'sendLink'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/restablecer-clave/{token}', [PasswordController::class, 'resetForm'])->name('password.reset');
    Route::post('/restablecer-clave', [PasswordController::class, 'reset'])->middleware('throttle:5,1')->name('password.update');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/salir', [AuthController::class, 'logout'])->name('logout');
    Route::get('/mis-cursos', [CourseController::class, 'mine'])->name('courses.mine');
    Route::get('/aula/{course}', [CourseController::class, 'learn'])->name('courses.learn');
    Route::post('/cursos/{course}/comprar', [PaymentController::class, 'checkout'])->name('payments.checkout');
    Route::get('/pagos/resultado', [PaymentController::class, 'result'])->name('payments.result');

    Route::prefix('admin')->name('admin.')->group(function (): void {
        Route::get('/', [AdminController::class, 'index'])->name('index');
        Route::get('/configuracion', [SettingsController::class, 'edit'])->name('settings.edit');
        Route::put('/configuracion', [SettingsController::class, 'update'])->name('settings.update');
        Route::post('/configuracion/probar-correo', [SettingsController::class, 'testMail'])->middleware('throttle:5,1')->name('settings.test-mail');
        Route::post('/cursos', [AdminController::class, 'store'])->name('courses.store');
        Route::get('/cursos/{course}', [AdminController::class, 'edit'])->name('courses.edit');
        Route::put('/cursos/{course}', [AdminController::class, 'update'])->name('courses.update');
        Route::delete('/cursos/{course}', [AdminController::class, 'destroy'])->name('courses.destroy');
        Route::post('/cursos/{course}/lecciones', [AdminController::class, 'storeLesson'])->name('lessons.store');
        Route::put('/cursos/{course}/lecciones/{lesson}', [AdminController::class, 'updateLesson'])->name('lessons.update');
        Route::delete('/cursos/{course}/lecciones/{lesson}', [AdminController::class, 'destroyLesson'])->name('lessons.destroy');
    });
});
