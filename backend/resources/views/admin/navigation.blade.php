<nav class="admin-nav" aria-label="Administración">
@foreach([
    ['Dashboard','admin.home','admin.home'],
    ['Productos','admin.products.index','admin.products.*'],
    ['Categorías','admin.categories.index','admin.categories.*'],
    ['Pedidos','admin.orders.index','admin.orders.*'],
    ['Página de inicio','admin.sections.index','admin.sections.*'],
    ['Banners','admin.banners.index','admin.banners.*'],
] as [$label,$route,$pattern])
<a href="{{ route($route) }}" @if(request()->routeIs($pattern)) aria-current="page" @endif>{{ $label }}</a>
@endforeach
@can('manage-settings')<a href="{{ route('admin.settings.edit') }}" @if(request()->routeIs('admin.settings.*')) aria-current="page" @endif>Configuración</a>@endcan
@can('view-activity')<a href="{{ route('admin.activity.index') }}" @if(request()->routeIs('admin.activity.*')) aria-current="page" @endif>Historial de actividad</a>@endcan
@can('manage-commercial-sync')<a href="{{ route('admin.sync.index') }}" @if(request()->routeIs('admin.sync.*')) aria-current="page" @endif>Sincronización comercial</a>@endcan
</nav>
