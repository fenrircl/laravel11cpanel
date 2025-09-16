<?php

use Illuminate\Support\Carbon;

if (! function_exists('amount_int')) {
    /**
     * Convierte un valor monetario CLP con separadores a entero (sin decimales)
     * Ej: "140.500" => 140500
     */
    function amount_int(int|string|null $value): int
    {
        if ($value === null || $value === '') {
            return 0;
        }
        if (is_int($value)) {
            return $value;
        }
        $digits = preg_replace('/[^0-9]/', '', (string) $value);
        return $digits !== '' ? (int) $digits : 0;
    }
}

if (! function_exists('format_clp')) {
    /**
     * Formatea un monto entero como CLP: $ 140.500
     */
    function format_clp(int|string|null $amount): string
    {
        $n = amount_int($amount);
        return '$ ' . number_format($n, 0, ',', '.');
    }
}

if (! function_exists('dt_tz')) {
    /**
     * Retorna una instancia Carbon en la zona horaria dada (por defecto America/Santiago)
     */
    function dt_tz(null|string|\DateTimeInterface $date, string $tz = 'America/Santiago'): ?Carbon
    {
        if (empty($date)) return null;
        return Carbon::parse($date)->timezone($tz);
    }
}

if (! function_exists('route_active')) {
    /**
     * Devuelve la clase activa si el nombre de la ruta actual coincide
     */
    function route_active(array|string $names, string $class = 'active'): string
    {
        foreach ((array) $names as $name) {
            if (request()->routeIs($name)) return $class;
        }
        return '';
    }
}

if (! function_exists('user_id')) {
    /** Obtiene el ID del usuario autenticado o null */
    function user_id(): ?int
    {
        return auth()->id();
    }
}

if (! function_exists('bool_badge')) {
    /** Renderiza un badge bootstrap para booleanos */
    function bool_badge($value, string $trueText = 'Sí', string $falseText = 'No'): string
    {
        return $value
            ? '<span class="badge bg-success">' . e($trueText) . '</span>'
            : '<span class="badge bg-secondary">' . e($falseText) . '</span>';
    }
}

if (! function_exists('assets_version')) {
    /**
     * Retorna la versión de assets para cache busting de JS/CSS.
     * Prioriza la variable de entorno ASSETS_VERSION.
     * Fallback: si es producción usa '1', en otros entornos usa timestamp corto.
     */
    function assets_version(): string
    {
        $envVersion = env('ASSETS_VERSION');
        if (!empty($envVersion)) {
            return (string) $envVersion;
        }
        // Fallback por entorno
        $isProd = config('app.env') === 'production';
        return $isProd ? '1' : Carbon::now()->format('YmdHi');
    }
}
