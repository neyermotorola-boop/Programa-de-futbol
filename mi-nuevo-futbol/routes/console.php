<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Todos los días a las 7 a. m. (hora de Colombia): avisa de las mensualidades de parqueadero que se cumplen
Schedule::command('parqueadero:avisar-mensualidades')->dailyAt('07:00')->timezone('America/Bogota');
