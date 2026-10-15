<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $outOfStock = Product::where('status', 'published')->where(function (Builder $q): void {
            $q->where(fn (Builder $simple) => $simple->where('product_type', 'simple')->whereNotNull('stock')->where('stock', '<=', 0))
                ->orWhere(fn (Builder $variable) => $variable->where('product_type', 'variable')->whereHas('variants', fn (Builder $v) => $v->where('active', true))->whereDoesntHave('variants', fn (Builder $v) => $v->where('active', true)->where(fn (Builder $stock) => $stock->whereNull('stock')->orWhere('stock', '>', 0))));
        })->count();

        return view('admin.home', [
            'productStats' => ['published' => Product::where('status', 'published')->count(), 'out_of_stock' => $outOfStock, 'draft' => Product::where('status', 'draft')->count(), 'without_image' => Product::where('status', 'published')->whereDoesntHave('images')->count()],
            'orderStats' => Order::selectRaw('status, COUNT(*) AS total')->groupBy('status')->pluck('total', 'status'),
            'recentOrders' => Order::latest('id')->limit(8)->get(),
        ]);
    }
}
