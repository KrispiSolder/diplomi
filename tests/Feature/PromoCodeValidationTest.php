<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\PromoCode;
use App\Models\User;
use App\Services\PromoCodeValidator;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PromoCodeValidationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_promo_code_requires_latin_uppercase_and_two_digits(): void
    {
        $this->expectException(ValidationException::class);
        PromoCodeValidator::validateCodeFormat('тест12');
    }

    public function test_promo_code_rejects_less_than_two_digits(): void
    {
        try {
            PromoCodeValidator::validateCodeFormat('SALE');
            $this->fail('Expected validation exception');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('code', $e->errors());
        }
    }

    public function test_promo_code_accepts_valid_format(): void
    {
        PromoCodeValidator::validateCodeFormat('Grunt4100');
        $this->assertTrue(true);
    }

    public function test_cannot_create_duplicate_active_bundle_for_same_category(): void
    {
        $categoryId = Category::query()->where('slug', 'soils')->value('id')
            ?? Category::factory()->create(['slug' => 'soils', 'name' => 'Грунты'])->id;

        PromoCode::query()->create([
            'code' => 'SOILS99',
            'type' => PromoCode::TYPE_BUNDLE_FREE,
            'category_id' => $categoryId,
            'buy_quantity' => 3,
            'free_quantity' => 1,
            'description' => 'Первая акция на грунты',
            'active' => true,
        ]);

        $this->expectException(ValidationException::class);

        PromoCodeValidator::assertNoActiveTargetConflict([
            'type' => PromoCode::TYPE_BUNDLE_FREE,
            'category_id' => $categoryId,
            'product_id' => null,
            'active' => true,
        ]);
    }

    public function test_allows_up_to_three_active_order_percent_promos(): void
    {
        PromoCode::query()
            ->whereHas('promoTypeRef', fn ($q) => $q->where('code', PromoCode::TYPE_ORDER_PERCENT))
            ->update(['active' => false]);

        for ($i = 1; $i <= PromoCode::MAX_ACTIVE_ORDER_PERCENT - 1; $i++) {
            PromoCode::query()->create([
                'code' => 'ORDR'.$i.'00',
                'type' => PromoCode::TYPE_ORDER_PERCENT,
                'min_order_amount' => 1000 * $i,
                'discount_percent' => 5 * $i,
                'description' => "Акция {$i}",
                'active' => true,
            ]);
        }

        PromoCodeValidator::assertNoActiveTargetConflict([
            'type' => PromoCode::TYPE_ORDER_PERCENT,
            'active' => true,
        ]);

        PromoCode::query()->create([
            'code' => 'ORDR300',
            'type' => PromoCode::TYPE_ORDER_PERCENT,
            'min_order_amount' => 3000,
            'discount_percent' => 15,
            'active' => true,
        ]);

        $this->expectException(ValidationException::class);

        PromoCodeValidator::assertNoActiveTargetConflict([
            'type' => PromoCode::TYPE_ORDER_PERCENT,
            'active' => true,
        ]);
    }

    public function test_cart_rejects_second_promo_while_first_applied(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)
            ->withSession(['promo' => ['code' => 'Grunt4100', 'discount' => 100, 'total' => 900]])
            ->from('/cart')
            ->post('/cart/promo', ['promo_code' => 'OTHER88'])
            ->assertSessionHasErrors('promo_code');
    }
}
