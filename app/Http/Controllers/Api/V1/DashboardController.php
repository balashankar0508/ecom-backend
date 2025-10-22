<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function kpis()
    {
        $salesToday = DB::scalar("SELECT IFNULL(SUM(total),0) FROM orders WHERE status IN ('paid','shipped','delivered') AND DATE(placed_at)=CURDATE()");
        $ordersToday = DB::scalar("SELECT COUNT(*) FROM orders WHERE DATE(placed_at)=CURDATE()");
        $salesMonth = DB::scalar("SELECT IFNULL(SUM(total),0) FROM orders WHERE status IN ('paid','shipped','delivered') AND placed_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')");
        $aov30 = DB::scalar("SELECT ROUND(IFNULL(SUM(total)/NULLIF(COUNT(*),0),0),2) FROM orders WHERE placed_at >= (NOW() - INTERVAL 30 DAY) AND status IN ('paid','shipped','delivered')");
        $newCustomers = DB::scalar("SELECT COUNT(*) FROM users WHERE created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')");

        return [
            'sales_today' => $salesToday,
            'orders_today' => $ordersToday,
            'sales_this_month' => $salesMonth,
            'aov_30d' => $aov30,
            'new_customers_this_month' => $newCustomers,
        ];
    }

    public function salesByDay()
    {
        return DB::select("
            SELECT DATE(placed_at) AS day, ROUND(SUM(total),2) AS revenue, COUNT(*) AS orders
            FROM orders
            WHERE placed_at >= (NOW() - INTERVAL 30 DAY) AND status IN ('paid','shipped','delivered')
            GROUP BY DATE(placed_at) ORDER BY day ASC
        ");
    }

    public function topProducts()
    {
        return DB::select("
            SELECT oi.variant_id, MAX(oi.title_snapshot) AS title, SUM(oi.qty) AS units_sold, ROUND(SUM(oi.subtotal),2) AS revenue
            FROM order_items oi JOIN orders o ON o.id=oi.order_id
            WHERE o.placed_at >= (NOW()-INTERVAL 30 DAY) AND o.status IN ('paid','shipped','delivered')
            GROUP BY oi.variant_id ORDER BY revenue DESC LIMIT 10
        ");
    }

    public function lowStock()
    {
        return DB::select("
            SELECT pv.id AS variant_id, pv.sku, p.title, inventory.stock, inventory.low_stock_threshold
            FROM product_variants pv
            JOIN products p ON p.id = pv.product_id
            JOIN inventory ON inventory.variant_id = pv.id
            WHERE inventory.stock <= inventory.low_stock_threshold
            ORDER BY inventory.stock ASC, pv.sku
        ");
    }
}