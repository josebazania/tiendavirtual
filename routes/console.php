<?php

use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('store:make-admin {email : Correo de una cuenta existente}', function (): int {
    $user = User::where('email', $this->argument('email'))->first();
    if (! $user) {
        $this->error('No existe una cuenta con ese correo. Regístrala primero.');

        return 1;
    }
    $user->is_admin = true;
    $user->save();
    $this->info('La cuenta ahora es administradora.');

    return 0;
})->purpose('Dar acceso de administrador a una cuenta existente');
