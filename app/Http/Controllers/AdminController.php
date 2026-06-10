<?php

namespace App\Http\Controllers;

use App\Exports\ExportReportMeta;
use App\Exports\OrdersExport;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Services\OrderCancellationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class AdminController extends Controller
{
    public function __construct(
        private OrderCancellationService $orderCancellation
    ) {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            if (! Auth::user()->isAdmin()) {
                abort(403);
            }

            return $next($request);
        });
    }

    public function dashboard()
    {
        $stock = Product::query()
            ->orderByDesc('quantity')
            ->take(12)
            ->get(['name', 'quantity'])
            ->map(fn ($p) => [
                'label' => Str::limit($p->name, 18),
                'value' => (int) $p->quantity,
            ])
            ->values()
            ->all();

        $monthStart = Carbon::now()->subMonth();
        $cancelledId = Order::cancelledStatusId();
        $popular = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.created_at', '>=', $monthStart)
            ->when($cancelledId, fn ($q) => $q->where('orders.order_status_id', '!=', $cancelledId))
            ->selectRaw('order_items.product_id, SUM(order_items.quantity) as cnt')
            ->groupBy('order_items.product_id')
            ->orderByDesc('cnt')
            ->take(10)
            ->get();

        $productIds = $popular->pluck('product_id')->all();
        $names = Product::whereIn('id', $productIds)->pluck('name', 'id');
        $popularChart = $popular->map(fn ($row) => [
            'label' => Str::limit($names[$row->product_id] ?? ('#'.$row->product_id), 18),
            'value' => (int) $row->cnt,
        ])->values()->all();

        $today = Carbon::today();
        $yesterday = Carbon::yesterday();
        $ordersToday = Order::excludingCancelled()->whereDate('created_at', $today)->count();
        $ordersYesterday = Order::excludingCancelled()->whereDate('created_at', $yesterday)->count();

        $weekLabels = [];
        $weekValues = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = Carbon::today()->subDays($i);
            $weekLabels[] = $day->format('d.m');
            $weekValues[] = (int) Order::excludingCancelled()->whereDate('created_at', $day)->count();
        }

        $lowStock = Product::query()
            ->where('quantity', '<=', 5)
            ->orderBy('quantity')
            ->get(['id', 'name', 'quantity', 'price']);

        return Inertia::render('Admin/Dashboard', [
            'chartStock' => $stock,
            'chartPopular' => $popularChart,
            'ordersToday' => $ordersToday,
            'ordersYesterday' => $ordersYesterday,
            'chartWeekLabels' => $weekLabels,
            'chartWeekValues' => $weekValues,
            'lowStockProducts' => $lowStock,
        ]);
    }

    public function products(Request $request)
    {
        $sort = $request->get('admin_sort', 'name');
        $dir = strtolower((string) $request->get('dir', 'asc')) === 'desc' ? 'desc' : 'asc';
        $allowed = ['name', 'price', 'quantity'];
        $column = in_array($sort, $allowed, true) ? $sort : 'name';

        $products = Product::with(['categories', 'stockStatusRef', 'careDifficultyRef', 'productSizeRef', 'ageGroupRef', 'images'])
            ->orderBy($column, $dir)
            ->paginate(15)
            ->withQueryString();

        $categories = Category::orderBy('name')->get();

        return Inertia::render('Admin/Products', [
            'products' => $products,
            'categories' => $categories,
            'adminSort' => $column,
            'adminSortDir' => $dir,
        ]);
    }

    public function reviews()
    {
        $reviews = Review::with(['product', 'user'])->paginate(10);

        return Inertia::render('Admin/Reviews', ['reviews' => $reviews]);
    }

    public function orders(Request $request)
    {
        $sort = $request->get('order_sort', 'created_at');
        $dir = strtolower((string) $request->get('dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $allowed = ['created_at', 'id'];
        $column = in_array($sort, $allowed, true) ? $sort : 'created_at';

        $statusFilter = $request->get('status_filter', 'all');
        $statusOptions = ['в обработке', 'отправлен', 'выполнен', 'отменен'];

        $query = Order::with(['user', 'items.product', 'orderStatusRef', 'paymentMethodRef', 'paymentStatusRef'])
            ->orderBy($column, $dir);

        if ($statusFilter !== 'all' && in_array($statusFilter, $statusOptions, true)) {
            $query->whereHas('orderStatusRef', fn ($q) => $q->where('label', $statusFilter));
        }

        $orders = $query->paginate(10)->withQueryString();

        $statusCounts = ['all' => Order::count()];
        foreach ($statusOptions as $label) {
            $statusCounts[$label] = Order::whereHas('orderStatusRef', fn ($q) => $q->where('label', $label))->count();
        }

        return Inertia::render('Admin/Orders', [
            'orders' => $orders,
            'orderSort' => $column,
            'orderSortDir' => $dir,
            'statusFilter' => $statusFilter,
            'statusCounts' => $statusCounts,
            'statusOptions' => $statusOptions,
        ]);
    }

    public function exportOrders(Request $request)
    {
        $status = $request->get('status');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $allowedStatuses = ['в обработке', 'отправлен', 'выполнен', 'отменен'];
        if ($status && ! in_array($status, $allowedStatuses, true)) {
            $status = null;
        }

        $user = Auth::user();
        $title = $status
            ? "Отчёт по заказам (статус: {$status})"
            : 'Отчёт по заказам';

        $meta = new ExportReportMeta(
            title: $title,
            periodFrom: $dateFrom,
            periodTo: $dateTo,
            authorName: $user->name.' ('.$user->email.')',
            generatedAt: Carbon::now(ExportReportMeta::REPORT_TIMEZONE),
        );

        $export = new OrdersExport($meta, $status, $dateFrom, $dateTo);

        if ($export->isEmpty()) {
            $message = $status
                ? "Пустой отчёт. Нет заказов со статусом «{$status}» за выбранный период."
                : 'Пустой отчёт. Нет заказов за выбранный период.';

            return response()->json(['message' => $message], 422);
        }

        $periodSuffix = ($dateFrom || $dateTo)
            ? '-'.($dateFrom ?? 'start').'_'.($dateTo ?? 'end')
            : '';
        $filename = $status
            ? 'orders-'.str_replace(' ', '-', $status).$periodSuffix.'.xlsx'
            : 'orders'.$periodSuffix.'.xlsx';

        return Excel::download($export, $filename);
    }

    public function lowStockPdf(Request $request)
    {
        $products = Product::query()
            ->where('quantity', '<=', 5)
            ->orderBy('quantity')
            ->get(['name', 'quantity', 'price']);

        if ($products->isEmpty()) {
            return response()->json([
                'message' => 'Пустой отчёт. Нет товаров с низким остатком.',
            ], 422);
        }

        $user = Auth::user();
        $generatedAt = Carbon::now(ExportReportMeta::REPORT_TIMEZONE);

        $pdf = Pdf::loadView('pdf.low-stock', [
            'products' => $products,
            'reportTitle' => 'Отчёт: товары с низким остатком (≤ 5 шт.)',
            'periodLabel' => 'на дату '.$generatedAt->format('d.m.Y'),
            'authorName' => $user->name.' ('.$user->email.')',
            'generatedAt' => $generatedAt->format('d.m.Y H:i'),
        ]);

        return $pdf->download('low-stock-'.$generatedAt->format('Y-m-d_H-i').'.pdf');
    }

    public function storeProduct(\App\Http\Requests\Admin\StoreProductRequest $request)
    {
        $validated = $request->validated();

        $categoryIds = collect($validated['category_ids'])->unique()->values()->all();
        $validated['slug'] = Str::slug(Str::ascii($validated['name'].'-'.$validated['article']));
        unset($validated['category_ids']);

        $product = Product::create(collect($validated)->only([
            'name', 'slug', 'article', 'price', 'description', 'quantity',
        ])->all());
        $product->care_difficulty = $validated['care_difficulty'] ?? null;
        $product->size = $validated['size'] ?? null;
        $product->age_group = $validated['age_group'] ?? null;
        $product->save();
        $product->main_image = $validated['main_image'];
        $product->save();

        Product::syncCategoryPivot($product, $categoryIds);

        return back();
    }

    public function updateProduct(\App\Http\Requests\Admin\UpdateProductRequest $request, $id)
    {
        $product = Product::findOrFail($id);
        $validated = $request->validated();

        $categoryIds = isset($validated['category_ids'])
            ? collect($validated['category_ids'])->unique()->values()->all()
            : null;

        if ($categoryIds) {
            unset($validated['category_ids']);
        }

        $product->update(collect($validated)->only([
            'name', 'price', 'quantity', 'description',
        ])->all());

        if (array_key_exists('care_difficulty', $validated)) {
            $product->care_difficulty = $validated['care_difficulty'];
        }
        if (array_key_exists('size', $validated)) {
            $product->size = $validated['size'];
        }
        if (array_key_exists('age_group', $validated)) {
            $product->age_group = $validated['age_group'];
        }
        $product->main_image = $validated['main_image'];
        $product->save();

        if ($categoryIds) {
            Product::syncCategoryPivot($product, $categoryIds);
        }

        return back();
    }

    public function deleteProduct($id)
    {
        $product = Product::findOrFail($id);

        if ($product->hasActiveOrders()) {
            return back()->withErrors([
                'product' => 'Нельзя удалить товар: он есть в активных заказах (в обработке, отправлен, выполнен или ожидает оплаты). Сначала завершите или отмените эти заказы.',
            ]);
        }

        $product->delete();

        return back();
    }

    public function approveReview($id)
    {
        Review::findOrFail($id)->update(['approved' => true]);

        return back();
    }

    public function rejectReview($id)
    {
        Review::findOrFail($id)->delete();

        return back();
    }

    public function updateOrderStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|in:в обработке,отправлен,выполнен',
        ]);

        $order = Order::findOrFail($id);

        if ($order->isCancelled()) {
            return back()->withErrors([
                'status' => 'Нельзя изменить статус отменённого заказа',
            ]);
        }

        if ($order->isCompleted()) {
            return back()->withErrors([
                'status' => 'Нельзя изменить статус выполненного заказа',
            ]);
        }

        if ($order->isAwaitingPayment()) {
            return back()->withErrors([
                'status' => 'Заказ ожидает оплаты. Сначала дождитесь оплаты или отмените заказ.',
            ]);
        }

        if ($order->usesYooKassa() && ! $order->isPaid()) {
            return back()->withErrors([
                'status' => 'Нельзя менять статус заказа без подтверждённой онлайн-оплаты',
            ]);
        }

        $order->status = $validated['status'];

        if ($validated['status'] === 'выполнен' && $order->usesOfflinePayment()) {
            $order->markOfflinePaymentReceived();
        }

        $order->save();

        return back();
    }

    public function cancelOrder(Request $request, $id)
    {
        $order = Order::findOrFail($id);

        if ($order->isCancelled()) {
            return back()->withErrors(['status' => 'Заказ уже отменён']);
        }

        if (! in_array($order->status, ['в обработке', 'ожидает оплаты'], true)) {
            return back()->withErrors([
                'status' => 'Отменить можно только заказ «в обработке» или «ожидает оплаты»',
            ]);
        }

        $this->orderCancellation->cancel($order);

        return back();
    }
}
