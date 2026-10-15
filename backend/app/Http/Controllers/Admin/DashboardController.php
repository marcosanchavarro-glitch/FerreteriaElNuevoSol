<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $outOfStock = Product::where('status', 'published')->where(function (Builder $q): void {
            $q->where(fn (Builder $simple) => $simple->where('product_type', 'simple')->whereNotNull('stock')->where('stock', '<=', 0))
                ->orWhere(fn (Builder $variable) => $variable->where('product_type', 'variable')->whereHas('variants', fn (Builder $v) => $v->where('active', true))->whereDoesntHave('variants', fn (Builder $v) => $v->where('active', true)->where(fn (Builder $stock) => $stock->whereNull('stock')->orWhere('stock', '>', 0))));
        });

        $productStats = DB::query()
            ->selectSub(Product::where('status', 'published')->selectRaw('COUNT(*)'), 'published')
            ->selectSub($outOfStock->selectRaw('COUNT(*)'), 'out_of_stock')
            ->selectSub(Product::where('status', 'draft')->selectRaw('COUNT(*)'), 'draft')
            ->selectSub(Product::where('status', 'published')->whereDoesntHave('images')->selectRaw('COUNT(*)'), 'without_image')
            ->first();

        return view('admin.home', [
            'productStats' => array_map(intval(...), (array) $productStats),
            'orderStats' => Order::selectRaw('status, COUNT(*) AS total')->groupBy('status')->pluck('total', 'status'),
            'recentOrders' => Order::latest('id')->limit(8)->get(),
        ]);
    }
}
