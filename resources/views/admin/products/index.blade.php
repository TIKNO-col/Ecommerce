@extends('layouts.app')

@section('title', 'Gestión de Productos - Panel de Administración')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0">Gestión de Productos</h1>
                    <p class="text-muted mb-0">Administra tu catálogo de productos</p>
                </div>
                <div>
                    <a href="{{ route('admin.products.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Agregar Producto
                    </a>
                </div>
            </div>

            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <form id="filterForm" class="row g-3">
                        <div class="col-md-3">
                            <label for="search" class="form-label">Buscar</label>
                            <input type="text" class="form-control" id="search" name="search" placeholder="Nombre, SKU..." value="{{ request('search') }}">
                        </div>
                        <div class="col-md-2">
                            <label for="category" class="form-label">Categoría</label>
                            <select class="form-select" id="category" name="category">
                                <option value="">Todas</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="brand" class="form-label">Marca</label>
                            <select class="form-select" id="brand" name="brand">
                                <option value="">Todas</option>
                                @foreach($brands as $brand)
                                    <option value="{{ $brand->id }}" {{ request('brand') == $brand->id ? 'selected' : '' }}>
                                        {{ $brand->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="status" class="form-label">Estado</label>
                            <select class="form-select" id="status" name="status">
                                <option value="">Todos</option>
                                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Activos</option>
                                <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactivos</option>
                                <option value="featured" {{ request('status') == 'featured' ? 'selected' : '' }}>Destacados</option>
                                <option value="low_stock" {{ request('status') == 'low_stock' ? 'selected' : '' }}>Stock Bajo</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="sort" class="form-label">Ordenar por</label>
                            <select class="form-select" id="sort" name="sort">
                                <option value="created_desc" {{ request('sort') == 'created_desc' ? 'selected' : '' }}>Más recientes</option>
                                <option value="created_asc" {{ request('sort') == 'created_asc' ? 'selected' : '' }}>Más antiguos</option>
                                <option value="name_asc" {{ request('sort') == 'name_asc' ? 'selected' : '' }}>Nombre A-Z</option>
                                <option value="name_desc" {{ request('sort') == 'name_desc' ? 'selected' : '' }}>Nombre Z-A</option>
                                <option value="price_asc" {{ request('sort') == 'price_asc' ? 'selected' : '' }}>Precio menor</option>
                                <option value="price_desc" {{ request('sort') == 'price_desc' ? 'selected' : '' }}>Precio mayor</option>
                                <option value="stock_asc" {{ request('sort') == 'stock_asc' ? 'selected' : '' }}>Stock menor</option>
                                <option value="stock_desc" {{ request('sort') == 'stock_desc' ? 'selected' : '' }}>Stock mayor</option>
                            </select>
                        </div>
                        <div class="col-md-1">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-outline-primary">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Bulk Actions -->
            <div class="card mb-4" id="bulkActions" style="display: none;">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3">
                        <span class="text-muted">Acciones masivas:</span>
                        <button type="button" class="btn btn-sm btn-success" onclick="bulkAction('activate')">
                            <i class="fas fa-check me-1"></i>Activar
                        </button>
                        <button type="button" class="btn btn-sm btn-warning" onclick="bulkAction('deactivate')">
                            <i class="fas fa-times me-1"></i>Desactivar
                        </button>
                        <button type="button" class="btn btn-sm btn-info" onclick="bulkAction('feature')">
                            <i class="fas fa-star me-1"></i>Destacar
                        </button>
                        <button type="button" class="btn btn-sm btn-secondary" onclick="bulkAction('unfeature')">
                            <i class="fas fa-star-o me-1"></i>No destacar
                        </button>
                        <button type="button" class="btn btn-sm btn-danger" onclick="bulkAction('delete')">
                            <i class="fas fa-trash me-1"></i>Eliminar
                        </button>
                    </div>
                </div>
            </div>

            <!-- Products Table -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th width="40">
                                        <input type="checkbox" id="selectAll" class="form-check-input">
                                    </th>
                                    <th width="80">Imagen</th>
                                    <th>Producto</th>
                                    <th>SKU</th>
                                    <th>Categorías</th>
                                    <th>Precio</th>
                                    <th>Stock</th>
                                    <th>Estado</th>
                                    <th>Fecha</th>
                                    <th width="120">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($products as $product)
                                    <tr>
                                        <td>
                                            <input type="checkbox" class="form-check-input product-checkbox" value="{{ $product->id }}">
                                        </td>
                                        <td>
                                            @if($product->images->count() > 0)
                                                <img src="{{ asset('storage/' . $product->images->first()->image_path) }}" 
                                                     alt="{{ $product->name }}" 
                                                     class="img-thumbnail" 
                                                     style="width: 50px; height: 50px; object-fit: cover;">
                                            @else
                                                <div class="bg-light d-flex align-items-center justify-content-center" 
                                                     style="width: 50px; height: 50px; border-radius: 4px;">
                                                    <i class="fas fa-image text-muted"></i>
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            <div>
                                                <strong>{{ $product->name }}</strong>
                                                @if($product->is_featured)
                                                    <span class="badge bg-warning text-dark ms-1">Destacado</span>
                                                @endif
                                            </div>
                                            @if($product->brand)
                                                <small class="text-muted">{{ $product->brand->name }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            <code>{{ $product->sku }}</code>
                                        </td>
                                        <td>
                                            @foreach($product->categories->take(2) as $category)
                                                <span class="badge bg-secondary me-1">{{ $category->name }}</span>
                                            @endforeach
                                            @if($product->categories->count() > 2)
                                                <span class="text-muted">+{{ $product->categories->count() - 2 }} más</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($product->sale_price && $product->sale_price < $product->price)
                                                <div>
                                                    <span class="text-decoration-line-through text-muted">${{ number_format($product->price, 2) }}</span>
                                                </div>
                                                <div class="text-success fw-bold">${{ number_format($product->sale_price, 2) }}</div>
                                            @else
                                                <div class="fw-bold">${{ number_format($product->price, 2) }}</div>
                                            @endif
                                        </td>
                                        <td>
                                            @if($product->stock_quantity <= 5)
                                                <span class="badge bg-danger">{{ $product->stock_quantity }}</span>
                                            @elseif($product->stock_quantity <= 20)
                                                <span class="badge bg-warning text-dark">{{ $product->stock_quantity }}</span>
                                            @else
                                                <span class="badge bg-success">{{ $product->stock_quantity }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($product->is_active)
                                                <span class="badge bg-success">Activo</span>
                                            @else
                                                <span class="badge bg-secondary">Inactivo</span>
                                            @endif
                                        </td>
                                        <td>
                                            <small class="text-muted">{{ $product->created_at->format('d/m/Y') }}</small>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="{{ route('admin.products.show', $product) }}" 
                                                   class="btn btn-outline-info" 
                                                   title="Ver">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="{{ route('admin.products.edit', $product) }}" 
                                                   class="btn btn-outline-primary" 
                                                   title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button type="button" 
                                                        class="btn btn-outline-danger" 
                                                        onclick="deleteProduct({{ $product->id }})" 
                                                        title="Eliminar">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="text-center py-4">
                                            <div class="text-muted">
                                                <i class="fas fa-box-open fa-3x mb-3"></i>
                                                <h5>No hay productos</h5>
                                                <p>Comienza agregando tu primer producto</p>
                                                <a href="{{ route('admin.products.create') }}" class="btn btn-primary">
                                                    <i class="fas fa-plus me-2"></i>Agregar Producto
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    @if($products->hasPages())
                        <div class="d-flex justify-content-between align-items-center mt-4">
                            <div class="text-muted">
                                Mostrando {{ $products->firstItem() }} a {{ $products->lastItem() }} de {{ $products->total() }} productos
                            </div>
                            {{ $products->appends(request()->query())->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Auto-submit filter form on change
    $('#filterForm select, #filterForm input').on('change input', function() {
        if ($(this).attr('id') === 'search') {
            // Debounce search input
            clearTimeout(window.searchTimeout);
            window.searchTimeout = setTimeout(function() {
                $('#filterForm').submit();
            }, 500);
        } else {
            $('#filterForm').submit();
        }
    });
    
    // Select all checkbox
    $('#selectAll').on('change', function() {
        $('.product-checkbox').prop('checked', this.checked);
        toggleBulkActions();
    });
    
    // Individual checkboxes
    $('.product-checkbox').on('change', function() {
        const totalCheckboxes = $('.product-checkbox').length;
        const checkedCheckboxes = $('.product-checkbox:checked').length;
        
        $('#selectAll').prop('checked', totalCheckboxes === checkedCheckboxes);
        toggleBulkActions();
    });
    
    function toggleBulkActions() {
        const checkedCount = $('.product-checkbox:checked').length;
        if (checkedCount > 0) {
            $('#bulkActions').show();
        } else {
            $('#bulkActions').hide();
        }
    }
});

function deleteProduct(productId) {
    if (confirm('¿Estás seguro de que quieres eliminar este producto?')) {
        $.ajax({
            url: `/admin/products/${productId}`,
            method: 'DELETE',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Error al eliminar el producto');
                }
            },
            error: function() {
                alert('Error al eliminar el producto');
            }
        });
    }
}

function bulkAction(action) {
    const selectedIds = $('.product-checkbox:checked').map(function() {
        return this.value;
    }).get();
    
    if (selectedIds.length === 0) {
        alert('Selecciona al menos un producto');
        return;
    }
    
    let confirmMessage = '';
    switch (action) {
        case 'activate':
            confirmMessage = '¿Activar los productos seleccionados?';
            break;
        case 'deactivate':
            confirmMessage = '¿Desactivar los productos seleccionados?';
            break;
        case 'feature':
            confirmMessage = '¿Destacar los productos seleccionados?';
            break;
        case 'unfeature':
            confirmMessage = '¿Quitar de destacados los productos seleccionados?';
            break;
        case 'delete':
            confirmMessage = '¿Eliminar permanentemente los productos seleccionados?';
            break;
    }
    
    if (confirm(confirmMessage)) {
        $.ajax({
            url: '{{ route("admin.products.bulk-action") }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                action: action,
                product_ids: selectedIds
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function() {
                alert('Error al realizar la acción');
            }
        });
    }
}
</script>
@endpush