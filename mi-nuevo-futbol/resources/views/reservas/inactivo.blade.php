@extends('layouts.app')

@section('title', $negocio->name)

@section('header')
<style>:root { {!! $negocio->variablesMarca() !!} }</style>
<header class="topbar">
    <div class="topbar-inner">
        <span class="marca">
            @include('partials.logo-negocio', ['negocio' => $negocio])
            <span class="marca-texto">
                <span class="marca-nombre">{{ $negocio->name }}</span>
                <span class="marca-tag">{{ $negocio->eslogan ?: 'Reservas en línea' }}</span>
            </span>
        </span>
        <div class="topbar-acciones">@include('partials.tema')</div>
    </div>
</header>
@endsection

@section('content')
    <div class="vacio" style="max-width: 560px; margin: clamp(30px, 8vw, 90px) auto;">
        <div class="ico-caja"><x-icon name="calendar" size="26" /></div>
        <strong>Las reservas están en pausa</strong>
        {{ $negocio->name }} no está recibiendo reservas en línea por ahora.
        @if ($negocio->telefono_contacto)
            <br>Puedes comunicarte directamente al <b>{{ $negocio->telefono_contacto }}</b>.
        @else
            <br>Vuelve a intentarlo más tarde.
        @endif
    </div>
@endsection
