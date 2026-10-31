<?php

// Valores por defecto de un negocio nuevo. (Cada negocio guarda los suyos en la tabla users.)
// Importante: este archivo debe empezar exactamente con "<?php": cualquier espacio antes se
// imprime en todas las respuestas y corrompe descargas como el Excel de métricas.
return [
    'hora_apertura' => '06:00',
    'hora_cierre' => '23:00',
    'max_horas_por_reserva' => 4,
    // Zona horaria en que atienden los negocios: define qué es "hoy" y qué horas ya pasaron
    'zona' => 'America/Bogota',

    // Cuenta maestra que crea el SuperAdminSeeder. En producción define SUPERADMIN_PASSWORD
    // (si no, se genera una clave aleatoria y se muestra una sola vez en el registro del despliegue).
    'superadmin' => [
        'email' => env('SUPERADMIN_EMAIL', 'superadmin@canchas.com'),
        'password' => env('SUPERADMIN_PASSWORD'),
    ],
];
