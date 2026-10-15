<nav class="product-sections" aria-label="Secciones del producto">
<a @if(request()->routeIs('admin.products.edit')) aria-current="page" @endif href="{{ route('admin.products.edit',$product) }}">Información</a>
<a @if(request()->routeIs('admin.products.variants.*')) aria-current="page" @endif href="{{ route('admin.products.variants.index',$product) }}">Variantes</a>
<a @if(request()->routeIs('admin.products.categories.*')) aria-current="page" @endif href="{{ route('admin.products.categories.edit',$product) }}">Categorías</a>
<a @if(request()->routeIs('admin.products.images.*')) aria-current="page" @endif href="{{ route('admin.products.images.index',$product) }}">Imágenes</a>
</nav>
