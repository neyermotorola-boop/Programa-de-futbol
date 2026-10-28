<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Collection;

/**
 * Aviso al dueño de un parqueadero: mensualidades que se cumplen pronto, hoy o que acaban de vencer.
 */
class AvisoMensualidades extends Mailable
{
    /** @param Collection<int, \App\Models\Mensualidad> $mensualidades */
    public function __construct(public User $dueno, public Collection $mensualidades)
    {
    }

    public function envelope(): Envelope
    {
        $n = $this->mensualidades->count();

        return new Envelope(
            subject: ($n === 1 ? '1 mensualidad' : "{$n} mensualidades") . " por vencer en {$this->dueno->name}",
            from: new Address(config('mail.from.address'), $this->dueno->name),
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.mensualidades', with: [
            'dueno' => $this->dueno,
            'mensualidades' => $this->mensualidades,
            'paleta' => $this->dueno->paletaCorreo(),
            'url' => route('parqueadero.index') . '#mensualidades',
        ]);
    }
}
