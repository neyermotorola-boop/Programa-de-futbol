{{-- Logo del negocio si lo subió; si no, el ícono de su tipo de negocio. --}}
@if ($negocio->urlLogo())
    <span class="badge badge-logo"><img src="{{ $negocio->urlLogo() }}" alt="Logo de {{ $negocio->name }}"></span>
@else
    <span class="badge"><x-icon :name="match ($negocio->tipo_negocio) { 'salon' => 'scissors', 'parqueadero' => 'car', default => 'pitch' }" size="20" /></span>
@endif
