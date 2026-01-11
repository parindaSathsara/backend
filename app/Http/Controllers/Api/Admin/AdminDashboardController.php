<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Inventory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Admin Dashboard Controller
 * Provides dashboard statistics and insights
 */
class AdminDashboardController extends Controller
{
    /**
     * Get dashboard overview
     */
    public function index()
    {
        $stats = $this->stats();
        $recentOrders = $this->getRecentOrders();
        $lowStock = $this->getLowStockProducts();

        return response()->json([
            'stats' => $stats->original,
            'recent_orders' => $recentOrders->original['orders'],
            'low_stock' => $lowStock->original['products']
        ]);
    }

    /**
     * Get dashboard statistics
     */
    public function stats()
    {
        $today = now()->startOfDay();
        $thisMonth = now()->startOfMonth();
        $lastMonth = now()->subMonth()->startOfMonth();

        // Sales statistics
        $totalSales = Order::where('payment_status', 'paid')->sum('total');
        $todaySales = Order::where('payment_status', 'paid')
            ->whereDate('created_at', $today)
            ->sum('total');
        $monthSales = Order::where('payment_status', 'paid')
            ->whereDate('created_at', '>=', $thisMonth)
            ->sum('total');
        $lastMonthSales = Order::where('payment_status', 'paid')
            ->whereDate('created_at', '>=', $lastMonth)
            ->whereDate('created_at', '<', $thisMonth)
            ->sum('total');

        // Order statistics
        $totalOrders = Order::count();
        $pendingOrders = Order::where('status', 'pending')->count();
        $processingOrders = Order::where('status', 'processing')->count();
        $shippedOrders = Order::where('status', 'shipped')->count();

        // Customer statistics
        $totalCustomers = User::whereHas('role', function ($q) {
            $q->where('name', 'customer');
        })->count();
        $newCustomersThisMonth = User::whereHas('role', function ($q) {
            $q->where('name', 'customer');
        })->whereDate('created_at', '>=', $thisMonth)->count();

        // Product statistics
        $totalProducts = Product::count();
        $activeProducts = Product::where('is_active', true)->count();
        $lowStockCount = Inventory::lowStock()->count();
        $outOfStockCount = Inventory::outOfStock()->count();

        return response()->json([
            'sales' => [
                'total' => $totalSales,
                'today' => $todaySales,
                'this_month' => $monthSales,
                'last_month' => $lastMonthSales,
                'growth_percentage' => $lastMonthSales > 0 
                    ? round((($monthSales - $lastMonthSales) / $lastMonthSales) * 100, 2)
                    : 0
            ],
            'orders' => [
                'total' => $totalOrders,
                'pending' => $pendingOrders,
                'processing' => $processingOrders,
                'shipped' => $shippedOrders,
            ],
            'customers' => [
                'total' => $totalCustomers,
                'new_this_month' => $newCustomersThisMonth,
            ],
            'products' => [
                'total' => $totalProducts,
                'active' => $activeProducts,
                'low_stock' => $lowStockCount,
                'out_of_stock' => $outOfStockCount,
            ]
        ]);
    }

    /**
     * Get recent orders
     */
    public function recentOrders()
    {
        $orders = Order::with(['user', 'items'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'orders' => $orders
        ]);
    }

    /**
     * Get low stock products
     */
    public function lowStock()
    {
        $products = Inventory::lowStock()
            ->with(['product', 'variant'])
            ->limit(20)
            ->get()
            ->map(function ($inventory) {
                return [
                    'id' => $inventory->id,
                    'product' => $inventory->product,
                    'variant' => $inventory->variant,
                    'available_quantity' => $inventory->available_quantity,
                    'threshold' => $inventory->low_stock_threshold,
                ];
            });

        return response()->json([
            'products' => $products
        ]);
    }

    /**
     * Helper method to get recent orders
     */
    private function getRecentOrders()
    {
        return $this->recentOrders();
    }

    /**
     * Helper method to get low stock products
     */
    private function getLowStockProducts()
    {
        return $this->lowStock();
    }
}
