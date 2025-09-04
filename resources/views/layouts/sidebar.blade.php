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
                                <li class="nav-item">
                                    <a class="nav-link text-white d-flex align-items-center 
                                        {{ isset($item['route']) && $item['route'] && request()->routeIs($item['route']) ? 'active' : '' }}"
                                       href="{{ 
                                           isset($item['route']) && $item['route'] 
                                               ? route($item['route']) 
                                               : ($item['url'] ?? '#') 
                                       }}"
                                       title="{{ $item['tooltip'] ?? $item['title'] }}">
                                        <i class="{{ $item['icon'] }} me-2"></i>
                                        <span class="nav-text">{{ $item['title'] }}</span>
                                    </a>
                                </li>
                            @endif
                        @endforeach
                    </ul>
                </div>
            @endif
        @endforeach
    </div>
</aside>