<aside class="app-sidebar">
    <div class="sidebar-content p-2">
        @php
            $menuConfig = config('menu');
            $isAdmin = session('is_admin', false);
        @endphp
        
        @foreach($menuConfig as $section)
            @php
                $isAdminSection = ($section['section'] ?? '') === 'ADMINISTRACIÓN';
            @endphp
            @if(!$isAdminSection || ($isAdminSection && $isAdmin))
                <div class="mt-2 mb-3">
                    <div class="text-uppercase text-muted small fw-bold ms-2">{{ $section['section'] }}</div>
                    <ul class="nav flex-column">
                        @foreach($section['items'] as $item)
                            @if(!$item['auth_required'] || ($item['auth_required'] && auth()->check()))
                                @php
                                    $itemRoute = $item['route'] ?? null;
                                    $itemRouteExists = !$itemRoute || \Illuminate\Support\Facades\Route::has($itemRoute);
                                @endphp
                                <li class="nav-item">
                                    @if($itemRouteExists)
                                        <a class="nav-link text-white d-flex align-items-center {{ $itemRoute && request()->routeIs($itemRoute) ? 'active' : '' }}"
                                           href="{{ $itemRoute ? route($itemRoute) : ($item['url'] ?? '#') }}"
                                           title="{{ $item['tooltip'] ?? $item['title'] }}">
                                            <i class="{{ $item['icon'] }} me-2"></i>
                                            <span class="nav-text">{{ $item['title'] }}</span>
                                        </a>
                                    @else
                                        <span class="nav-link text-white-50 d-flex align-items-center" aria-disabled="true" title="Actualiza la caché de rutas de Laravel">
                                            <i class="{{ $item['icon'] }} me-2"></i>
                                            <span class="nav-text">{{ $item['title'] }} · actualizar rutas</span>
                                        </span>
                                    @endif
                                </li>
                            @endif
                        @endforeach
                    </ul>
                </div>
            @endif
        @endforeach
    </div>
</aside>
