<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\PromoCode;
use App\Models\PromoType;
use App\Services\PromoCodeValidator;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class PromoCodeController extends Controller
{
    public function index(Request $request)
    {
        $activeFilter = $request->get('active_filter', 'all');
        $typeFilter = $request->get('type_filter', 'all');

        $query = PromoCode::query()
            ->with(['product:id,name', 'promoTypeRef', 'category:id,name,slug']);

        if ($activeFilter === '1') {
            $query->where('active', true);
        } elseif ($activeFilter === '0') {
            $query->where('active', false);
        }

        if (in_array($typeFilter, [PromoCode::TYPE_BUNDLE_FREE, PromoCode::TYPE_ORDER_PERCENT], true)) {
            $query->whereHas('promoTypeRef', fn ($q) => $q->where('code', $typeFilter));
        }

        $promoCodes = $query
            ->orderByDesc('active')
            ->orderBy('code')
            ->paginate(20)
            ->withQueryString();

        $typeCounts = [
            'all' => PromoCode::count(),
            PromoCode::TYPE_BUNDLE_FREE => PromoCode::whereHas(
                'promoTypeRef',
                fn ($q) => $q->where('code', PromoCode::TYPE_BUNDLE_FREE)
            )->count(),
            PromoCode::TYPE_ORDER_PERCENT => PromoCode::whereHas(
                'promoTypeRef',
                fn ($q) => $q->where('code', PromoCode::TYPE_ORDER_PERCENT)
            )->count(),
        ];

        $activeCounts = [
            'all' => PromoCode::count(),
            '1' => PromoCode::where('active', true)->count(),
            '0' => PromoCode::where('active', false)->count(),
        ];

        return Inertia::render('Admin/PromoCodes', [
            'promoCodes' => $promoCodes,
            'categories' => Category::orderBy('name')->get(['id', 'name', 'slug']),
            'products' => Product::orderBy('name')->get(['id', 'name', 'price']),
            'promoTypes' => [
                ['value' => PromoCode::TYPE_BUNDLE_FREE, 'label' => 'N+1 бесплатно (категория или товар)'],
                ['value' => PromoCode::TYPE_ORDER_PERCENT, 'label' => 'Скидка % от суммы заказа'],
            ],
            'activeFilter' => $activeFilter,
            'typeFilter' => $typeFilter,
            'typeCounts' => $typeCounts,
            'activeCounts' => $activeCounts,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validatePromo($request);

        PromoCode::create($validated);

        return back()->with('success', 'Промокод создан');
    }

    public function update(Request $request, PromoCode $promoCode)
    {
        $validated = $this->validatePromo($request, $promoCode->id);

        $promoCode->update($validated);

        return back()->with('success', 'Промокод обновлён');
    }

    public function destroy(PromoCode $promoCode)
    {
        $promoCode->delete();

        return back()->with('success', 'Промокод удалён');
    }

    private function validatePromo(Request $request, ?int $ignoreId = null): array
    {
        $request->merge([
            'code' => PromoCodeValidator::normalizeCode((string) $request->input('code')),
            'product_id' => $request->input('product_id') ?: null,
            'category_id' => $request->input('category_id') ?: null,
            'active' => $request->boolean('active'),
        ]);

        PromoCodeValidator::validateCodeFormat((string) $request->input('code'));

        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:64',
                Rule::unique('promo_codes', 'code')->ignore($ignoreId),
            ],
            'type' => ['required', Rule::in([PromoCode::TYPE_BUNDLE_FREE, PromoCode::TYPE_ORDER_PERCENT])],
            'description' => 'required|string|min:3|max:500',
            'active' => 'boolean',
            'category_id' => 'nullable|exists:categories,id',
            'product_id' => 'nullable|exists:products,id',
            'buy_quantity' => 'nullable|integer|min:1|max:99',
            'free_quantity' => 'nullable|integer|min:1|max:99',
            'min_order_amount' => 'nullable|numeric|min:1',
            'discount_percent' => 'nullable|numeric|min:1|max:100',
        ]);

        $typeCode = $validated['type'];
        $promoTypeId = PromoType::idFor($typeCode);
        if (! $promoTypeId) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'type' => 'Неизвестный тип промокода',
            ]);
        }

        $validated['promo_type_id'] = $promoTypeId;
        unset($validated['type']);

        if ($typeCode === PromoCode::TYPE_BUNDLE_FREE) {
            if (empty($validated['category_id']) && empty($validated['product_id'])) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'category_id' => 'Укажите категорию или товар для акции',
                ]);
            }

            $validated['buy_quantity'] = max(1, (int) ($validated['buy_quantity'] ?? 3));
            $validated['free_quantity'] = max(1, (int) ($validated['free_quantity'] ?? 1));
            $validated['min_order_amount'] = null;
            $validated['discount_percent'] = null;

            if ($validated['product_id']) {
                $validated['category_id'] = null;
            }
        } else {
            if (empty($validated['min_order_amount']) || empty($validated['discount_percent'])) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'min_order_amount' => 'Укажите минимальную сумму заказа (от 1 ₽) и процент скидки (от 1%)',
                ]);
            }

            $validated['min_order_amount'] = max(1, (float) $validated['min_order_amount']);
            $validated['discount_percent'] = max(1, min(100, (float) $validated['discount_percent']));
            $validated['category_id'] = null;
            $validated['product_id'] = null;
            $validated['buy_quantity'] = 1;
            $validated['free_quantity'] = 1;
        }

        $conflictData = array_merge($validated, ['type' => $typeCode]);
        PromoCodeValidator::assertNoActiveTargetConflict($conflictData, $ignoreId);

        return $validated;
    }
}
