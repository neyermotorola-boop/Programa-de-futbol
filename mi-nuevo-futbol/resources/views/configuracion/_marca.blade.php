{{-- Tarjeta de marca (logo, nombre comercial, colores) con vista previa en vivo. Recibe $negocio, $accionMarca, $iconoMarca, $vpTitulo y $vpSub; opcionales: $vpEslogan, $vpChips, $vpBoton. --}}
@php
    $paletas = [
        ['Estadio', '#2F6B3C', '#F2A93B'],
        ['Océano', '#1E4E8C', '#FF8A3D'],
        ['Élite', '#1F1F24', '#D4AF37'],
        ['Rosa', '#8E3B5F', '#F4B6C2'],
        ['Lavanda', '#4B3B8F', '#7FE0C2'],
        ['Coral', '#0F5257', '#FF6F59'],
    ];
@endphp
        <div class="marca-grid">
            <form method="POST" action="{{ $accionMarca }}" enctype="multipart/form-data" class="tarjeta">
                @csrf
                <div class="grid-3">
                    <div class="campo">
                        <span class="label">Logo de la empresa</span>
                        <div class="logo-fila">
                            <div class="logo-preview" id="logoPreview">
                                @if ($negocio->urlLogo())<img src="{{ $negocio->urlLogo() }}" alt="Logo actual">@else<x-icon name="building" size="26" />@endif
                            </div>
                            <div>
                                <label class="btn btn-secondary btn-sm" style="margin:0"><x-icon name="plus" size="15" /> Subir
                                    <input type="file" name="logo" id="inputLogo" accept="image/png,image/jpeg,image/webp">
                                </label>
                                @if ($negocio->logo_path)
                                    <label class="switch" style="font-size:12px;margin-top:6px"><input type="checkbox" name="quitar_logo" value="1"><span class="riel" style="transform:scale(.8);transform-origin:left"></span> Quitar</label>
                                @else
                                    <div class="ayuda">PNG, JPG o WEBP · 2 MB</div>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="campo">
                        <label for="name">Nombre comercial</label>
                        <div class="control"><x-icon name="building" /><input type="text" id="name" name="name" value="{{ old('name', $negocio->name) }}" required maxlength="100"></div>
                    </div>
                    <div class="campo">
                        <label for="razon_social">Razón social</label>
                        <div class="control"><x-icon name="shield" /><input type="text" id="razon_social" name="razon_social" value="{{ old('razon_social', $negocio->razon_social) }}" placeholder="Ej: Inversiones Golazo S.A.S." maxlength="150"></div>
                    </div>
                </div>
                <div class="grid-3">
                    <div class="campo">
                        <label for="eslogan">Eslogan</label>
                        <div class="control"><x-icon name="sparkles" /><input type="text" id="eslogan" name="eslogan" value="{{ old('eslogan', $negocio->eslogan) }}" placeholder="Ej: La mejor cancha del barrio" maxlength="150"></div>
                    </div>
                    <div class="campo">
                        <label for="telefono_contacto">Teléfono de contacto</label>
                        <div class="control"><x-icon name="phone" /><input type="tel" id="telefono_contacto" name="telefono_contacto" value="{{ old('telefono_contacto', $negocio->telefono_contacto) }}" maxlength="30"></div>
                    </div>
                    <div class="campo">
                        <label for="direccion">Dirección</label>
                        <div class="control"><x-icon name="building" /><input type="text" id="direccion" name="direccion" value="{{ old('direccion', $negocio->direccion) }}" maxlength="200"></div>
                    </div>
                </div>

                <span class="label">Colores de tu marca</span>
                <div class="colores">
                    <label class="color-campo">
                        <input type="color" name="color_primario" id="colorPrimario" value="{{ old('color_primario', $negocio->color_primario ?: $negocio->coloresPorDefecto()[0]) }}">
                        <span><b>Principal</b><code id="codPrimario"></code></span>
                    </label>
                    <label class="color-campo">
                        <input type="color" name="color_acento" id="colorAcento" value="{{ old('color_acento', $negocio->color_acento ?: $negocio->coloresPorDefecto()[1]) }}">
                        <span><b>Acento (botones)</b><code id="codAcento"></code></span>
                    </label>
                </div>
                <div class="paletas">
                    <span>Combinaciones:</span>
                    @foreach ($paletas as [$nombre, $p, $a])
                        <button type="button" class="paleta" data-primario="{{ $p }}" data-acento="{{ $a }}"><i style="background:linear-gradient(135deg, {{ $p }} 50%, {{ $a }} 50%)"></i> {{ $nombre }}</button>
                    @endforeach
                </div>
                <button type="submit" class="btn btn-primary btn-block" style="min-height:44px"><x-icon name="check" /> Guardar marca</button>
            </form>

            <div class="vista-previa" id="vistaPrevia">
                <div class="vp-barra">
                    <span class="vp-logo" id="vpLogo">@if ($negocio->urlLogo())<img src="{{ $negocio->urlLogo() }}" alt="">@else<x-icon :name="$iconoMarca" size="18" />@endif</span>
                    <div><div class="vp-nombre" id="vpNombre">{{ $negocio->name }}</div><div class="vp-eslogan" id="vpEslogan" data-vacio="{{ $vpEslogan ?? 'Reservas en línea' }}">{{ $negocio->eslogan ?: ($vpEslogan ?? 'Reservas en línea') }}</div></div>
                </div>
                <div class="vp-cuerpo">
                    <h3>{{ $vpTitulo }}</h3>
                    <p>{{ $vpSub }}</p>
                    <div class="vp-horas">@foreach ($vpChips ?? ['4:00', '5:00', '6:00', '7:00'] as $i => $chip)<span @class(['sel' => $i === 2])>{{ $chip }}</span>@endforeach</div>
                    <div class="vp-boton">{{ $vpBoton ?? 'Confirmar reserva' }}</div>
                </div>
                <div class="vp-etiqueta"><x-icon name="eye" size="14" /> Vista previa en vivo</div>
            </div>
        </div>
