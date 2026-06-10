<template>
  <AppLayout>
    <Breadcrumb :breadcrumbs="['Главная страница', 'Корзина']" />
    <Notification />

    <div class="page-container py-6 md:py-[43px]">
      <div class="flex flex-col lg:flex-row gap-8">
        <div class="flex-1 min-w-0">
          <h2 class="font-['Montserrat'] text-[24px] md:text-[32px] text-black font-bold mb-[20px]">Корзина</h2>

          <div v-if="cartItems.length > 0" class="space-y-[16px] md:space-y-[20px] mb-8 md:mb-[48px]">
            <div v-for="item in cartItems" :key="item.id" class="flex flex-col sm:flex-row sm:items-center gap-4 sm:gap-[20px] bg-white rounded-lg shadow p-4 md:p-[20px]">
              <Link :href="`/product/${item.product.id}`" class="w-full sm:w-[120px] md:w-[159px] h-[120px] sm:h-[159px] relative bg-cover bg-center rounded-[12px] flex-shrink-0 block mx-auto sm:mx-0" :style="{ backgroundImage: `url('${item.product.main_image}')` }" />

              <div class="flex-1">
                <Link :href="`/product/${item.product.id}`" class="font-['Montserrat'] text-[18px] font-bold text-black mb-[10px] hover:text-[#2E7D32]">
                  {{ item.product.name }}
                </Link>
                <p class="font-['Montserrat'] text-[16px] text-[#666666] mb-[10px]">{{ item.product.price }} ₽</p>

                <div class="flex flex-col gap-[6px]">
                  <div class="flex items-center gap-[15px]">
                    <div class="flex items-center gap-[10px]">
                      <button
                        type="button"
                        :disabled="item.quantity <= 1"
                        class="w-[30px] h-[30px] rounded border border-gray-300 text-[#666666] hover:text-[#2E7D32] hover:border-[#2E7D32] flex items-center justify-center disabled:opacity-40"
                        @click="updateQuantity(item, item.quantity - 1)"
                      >
                        −
                      </button>
                      <span class="font-['Montserrat'] text-[16px] text-black w-[40px] text-center">{{ item.quantity }}</span>
                      <button
                        type="button"
                        :disabled="!canIncrease(item)"
                        class="w-[30px] h-[30px] rounded border border-gray-300 text-[#666666] hover:text-[#2E7D32] hover:border-[#2E7D32] flex items-center justify-center disabled:opacity-40 disabled:cursor-not-allowed"
                        @click="updateQuantity(item, item.quantity + 1)"
                      >
                        +
                      </button>
                    </div>
                    <button type="button" class="text-red-500 hover:opacity-70 font-['Montserrat'] text-[14px]" @click="removeFromCart(item.id)">
                      Удалить
                    </button>
                  </div>
                  <p class="font-['Montserrat'] text-[12px] text-[#888888]">
                    На складе: {{ maxQuantity(item) }} шт.
                  </p>
                  <p v-if="itemStockError === item.id" class="font-['Montserrat'] text-[12px] text-red-500">
                    Товар на складе закончился
                  </p>
                </div>
              </div>

              <div class="text-left sm:text-right w-full sm:w-auto">
                <p class="font-['Montserrat'] font-bold text-[18px] md:text-[20px] text-black">{{ formatMoney(lineTotal(item)) }} ₽</p>
              </div>
            </div>
          </div>

          <div v-else class="text-center py-[50px]">
            <p class="font-['Montserrat'] text-[#888888] text-[16px]">Корзина пуста</p>
          </div>
        </div>
      </div>
    </div>

    <div v-if="cartItems.length > 0" class="page-container py-6 md:py-[48px]">
      <div class="w-full max-w-[420px] mx-auto lg:mx-0 bg-white rounded-[12px] shadow p-5 md:p-[33px] space-y-[20px]">
        <h3 class="font-['Vetrino'] text-[28px] text-black font-bold">Итого:</h3>

        <div class="space-y-2">
          <p class="font-['Montserrat'] text-[14px] text-amber-800 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2.5 leading-snug">
            За один заказ можно использовать только один промокод.
          </p>
          <div class="flex gap-2">
            <input
              v-model="promoCodeInput"
              type="text"
              placeholder="Промокод"
              :class="promoInputClass"
              @input="promoCodeInput = promoCodeInput.toUpperCase().replace(/[^A-Z0-9]/g, '')"
            />
            <button
              type="button"
              class="px-4 py-2 border border-[#2E7D32] text-[#2E7D32] font-['Montserrat'] text-[13px] font-semibold rounded-lg hover:bg-[#F1F8E9] disabled:opacity-50"
              :disabled="applyingPromo || !promoCodeInput.trim() || (appliedPromo && promoCodeInput.trim().toUpperCase() !== appliedPromo.code.toUpperCase())"
              @click="applyPromo"
            >
              {{ applyingPromo ? '...' : (appliedPromo ? 'Обновить' : 'Применить') }}
            </button>
          </div>
          <p
            v-if="promoError"
            class="font-['Montserrat'] text-[13px] text-red-700 bg-red-50 border border-red-200 rounded-lg px-3 py-2 leading-snug"
            role="alert"
          >
            {{ promoError }}
          </p>
          <p v-if="appliedPromo" class="font-['Montserrat'] text-[12px] text-[#2E7D32]">
            {{ appliedPromo.code }}: −{{ formatMoney(appliedPromo.discount) }} ₽
            <template v-if="appliedPromo.free_units"> ({{ appliedPromo.free_units }} шт. бесплатно)</template>
            <button type="button" class="ml-2 text-red-500 underline" @click="clearPromo">убрать</button>
          </p>

          <div v-if="orderPercentOffers.length > 0" class="space-y-2 pt-1">
            <p class="font-['Montserrat'] text-[13px] font-semibold text-black">
              Скидки от суммы заказа
            </p>
            <p class="font-['Montserrat'] text-[11px] text-[#888888] leading-snug">
              Каждый промокод можно использовать один раз. Доступно не более {{ orderPercentOffers.length }} акций.
            </p>
            <ul class="space-y-2">
              <li
                v-for="offer in orderPercentOffers"
                :key="offer.code"
                class="rounded-lg border px-3 py-2.5 transition"
                :class="offerCardClass(offer)"
              >
                <div class="flex items-start justify-between gap-2">
                  <div class="min-w-0 flex-1">
                    <p class="font-['Montserrat'] text-[14px] font-semibold text-black">
                      {{ offer.code }}
                      <span class="font-normal text-[#666666]">−{{ offer.discount_percent }}%</span>
                    </p>
                    <p v-if="offer.description" class="font-['Montserrat'] text-[12px] text-[#666666] mt-0.5">
                      {{ offer.description }}
                    </p>
                    <p class="font-['Montserrat'] text-[12px] text-[#888888] mt-1">
                      от {{ formatMoney(offer.min_order_amount) }} ₽
                      <template v-if="offer.eligible && offer.potential_discount != null">
                        · экономия {{ formatMoney(offer.potential_discount) }} ₽
                      </template>
                      <template v-else-if="!offer.eligible">
                        · не хватает {{ formatMoney(offer.shortfall) }} ₽
                      </template>
                    </p>
                  </div>
                  <button
                    type="button"
                    class="shrink-0 px-3 py-1.5 font-['Montserrat'] text-[12px] font-semibold rounded-lg border transition disabled:opacity-50 disabled:cursor-not-allowed"
                    :class="offerButtonClass(offer)"
                    :disabled="!canApplyOffer(offer)"
                    @click="applyOffer(offer)"
                  >
                    {{ offerButtonLabel(offer) }}
                  </button>
                </div>
              </li>
            </ul>
          </div>
        </div>

        <div class="space-y-1 text-center">
          <p v-if="appliedPromo" class="font-['Montserrat'] text-[14px] text-[#888888] line-through">
            {{ formatMoney(appliedPromo.subtotal ?? cartSubtotalRubles()) }} ₽
          </p>
          <div class="flex items-center justify-center gap-[10px]">
            <svg class="w-[30px] h-[30px] text-[#2E7D32]" fill="currentColor" viewBox="0 0 20 20">
              <path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1V5a1 1 0 00-1-1H3zM14 7a1 1 0 00-1 1v6.05A2.5 2.5 0 0115.95 16H17a1 1 0 001-1v-5a1 1 0 00-.293-.707l-2-2A1 1 0 0015 7h-1z" />
            </svg>
            <span class="font-['Vetrino'] text-[28px] text-[#2E7D32] font-bold">{{ displayTotal() }} ₽</span>
          </div>
        </div>

        <button type="button" class="w-full bg-[#2E7D32] text-white font-['Montserrat'] font-bold text-[19px] py-[15px] rounded-[20px] hover:bg-[#1b5e2b]" @click="openCheckout = true">
          Оформить заказ
        </button>
      </div>
    </div>

    <Modal :show="openCheckout" max-width="lg" @close="openCheckout = false">
      <div class="p-6 space-y-5">
        <div>
          <h3 class="font-['Montserrat'] text-[20px] font-bold text-black">Оформление заказа</h3>
          <p class="font-['Montserrat'] text-[13px] text-[#666666] mt-1">
            Укажите адрес доставки и способ оплаты.
          </p>
        </div>

        <div>
          <p class="font-['Montserrat'] text-[14px] font-semibold text-black mb-2">Адрес доставки</p>
          <YandexAddressMap v-model="checkoutAddress" :api-key="yandexMapsApiKey" />
        </div>

        <div>
          <p class="font-['Montserrat'] text-[14px] font-semibold text-black mb-3">Способ оплаты</p>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <label
              v-for="method in paymentMethods"
              :key="method.code"
              class="relative flex rounded-xl border-2 p-4 transition"
              :class="[
                !method.available ? 'cursor-not-allowed opacity-60 border-gray-200 bg-gray-50' : 'cursor-pointer',
                method.available && paymentMethod === method.code
                  ? 'border-[#2E7D32] bg-[#F1F8E9] shadow-sm'
                  : method.available ? 'border-gray-200 bg-white hover:border-[#A5D6A7]' : '',
              ]"
            >
              <input
                v-model="paymentMethod"
                type="radio"
                name="payment_method"
                :value="method.code"
                :disabled="!method.available"
                class="sr-only"
              />
              <div class="flex flex-col gap-1">
                <span class="font-['Montserrat'] text-[15px] font-semibold text-black">{{ method.label }}</span>
                <span class="font-['Montserrat'] text-[12px] text-[#666666]">{{ method.description }}</span>
                <span
                  v-if="!method.available"
                  class="font-['Montserrat'] text-[11px] text-amber-800 mt-1"
                >
                  Укажите YOOKASSA_SHOP_ID и YOOKASSA_SECRET_KEY в .env и перезапустите сервер
                </span>
              </div>
              <span
                v-if="paymentMethod === method.code"
                class="absolute top-3 right-3 w-5 h-5 rounded-full bg-[#2E7D32] flex items-center justify-center"
              >
                <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                </svg>
              </span>
            </label>
          </div>

          <div
            v-if="paymentMethod === 'yookassa'"
            class="mt-3 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3"
            role="alert"
          >
            <p class="font-['Montserrat'] text-[13px] text-amber-900 leading-snug">
              <span class="font-semibold">Важно:</span>
              после оплаты картой или СБП на сайте отменить заказ будет нельзя.
            </p>
          </div>
        </div>

        <div class="flex justify-end gap-3 pt-2 border-t border-gray-100">
          <button type="button" class="px-4 py-2 font-['Montserrat'] text-[14px] text-gray-600 hover:text-gray-800" @click="openCheckout = false">
            Отмена
          </button>
          <button
            type="button"
            class="px-5 py-2 bg-[#2E7D32] text-white font-['Montserrat'] text-[14px] rounded-lg hover:bg-[#1b5e2b] disabled:opacity-50"
            :disabled="checkingOut || !checkoutAddress.trim() || !paymentMethod"
            @click="submitCheckout"
          >
            {{ checkingOut ? 'Отправка...' : (paymentMethod === 'yookassa' ? 'Перейти к оплате' : 'Подтвердить заказ') }}
          </button>
        </div>
      </div>
    </Modal>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import Breadcrumb from '@/Components/Breadcrumb.vue'
import Notification from '@/Components/Notification.vue'
import Modal from '@/Components/Modal.vue'
import YandexAddressMap from '@/Components/YandexAddressMap.vue'
import { checkoutPaymentMethods } from '@/utils/paymentMethods'
import { cartSubtotalKopecks, formatMoney, fromKopecks, lineTotalKopecks } from '@/utils/money'
import { Link, router, usePage } from '@inertiajs/vue3'

const props = defineProps({
  cartItems: Array,
  favorites: Array,
  user: Object,
  yookassaEnabled: { type: Boolean, default: false },
  promo: { type: Object, default: null },
  orderPercentOffers: { type: Array, default: () => [] },
})

const page = usePage()
const yandexMapsApiKey = computed(() => page.props.yandexMapsApiKey || '')
const paymentMethods = computed(() => checkoutPaymentMethods(props.yookassaEnabled))

const openCheckout = ref(false)
const checkoutAddress = ref(props.user?.delivery_address || '')
const paymentMethod = ref('cash')
const checkingOut = ref(false)
const applyingPromo = ref(false)
const itemStockError = ref(null)
const appliedPromo = ref(props.promo)
const promoCodeInput = ref(props.promo?.code || '')

const promoError = computed(() => {
  const err = page.props.errors?.promo_code
  if (!err) return ''
  return Array.isArray(err) ? err[0] : err
})

const promoInputClass = computed(() => {
  const base = "flex-1 border rounded-lg px-3 py-2 font-['Montserrat'] text-[14px] uppercase"
  return promoError.value
    ? `${base} border-red-500 bg-red-50 ring-1 ring-red-200`
    : `${base} border-gray-300`
})

const maxQuantity = (item) => {
  return Math.max(0, Number(item.product?.quantity) || 0)
}

const canIncrease = (item) => item.quantity < maxQuantity(item)

const lineTotal = (item) => fromKopecks(lineTotalKopecks(item.product.price, item.quantity))

const cartSubtotalRubles = () => fromKopecks(cartSubtotalKopecks(props.cartItems))

const displayTotal = () => {
  if (appliedPromo.value?.total != null) {
    return formatMoney(appliedPromo.value.total)
  }

  return formatMoney(cartSubtotalRubles())
}

const isOrderPercentApplied = (code) => {
  if (!appliedPromo.value || appliedPromo.value.type !== 'order_percent') return false
  return appliedPromo.value.code?.toUpperCase() === code.toUpperCase()
}

const canApplyOffer = (offer) => {
  if (applyingPromo.value || !offer.eligible) return false
  if (appliedPromo.value) {
    if (appliedPromo.value.type === 'order_percent') {
      return isOrderPercentApplied(offer.code)
    }
    return false
  }
  return true
}

const offerCardClass = (offer) => {
  if (isOrderPercentApplied(offer.code)) {
    return 'border-[#2E7D32] bg-[#F1F8E9]'
  }
  if (!offer.eligible) {
    return 'border-gray-200 bg-gray-50 opacity-80'
  }
  return 'border-gray-200 bg-white hover:border-[#A5D6A7]'
}

const offerButtonClass = (offer) => {
  if (isOrderPercentApplied(offer.code)) {
    return 'border-[#2E7D32] text-[#2E7D32] bg-white'
  }
  return 'border-[#2E7D32] text-[#2E7D32] hover:bg-[#F1F8E9]'
}

const offerButtonLabel = (offer) => {
  if (isOrderPercentApplied(offer.code)) return 'Применён'
  if (appliedPromo.value) return 'Недоступно'
  if (!offer.eligible) return 'Не подходит'
  return 'Применить'
}

const applyOffer = (offer) => {
  if (!canApplyOffer(offer) || isOrderPercentApplied(offer.code)) return
  promoCodeInput.value = offer.code
  applyPromo()
}

const applyPromo = () => {
  applyingPromo.value = true
  router.post('/cart/promo', { promo_code: promoCodeInput.value.trim() }, {
    preserveScroll: true,
    onSuccess: (page) => {
      appliedPromo.value = page.props.promo
      if (appliedPromo.value?.code) {
        promoCodeInput.value = appliedPromo.value.code
      }
    },
    onFinish: () => {
      applyingPromo.value = false
    },
  })
}

const clearPromo = () => {
  router.delete('/cart/promo', {
    preserveScroll: true,
    onSuccess: () => {
      appliedPromo.value = null
      promoCodeInput.value = ''
    },
  })
}

const updateQuantity = (item, quantity) => {
  if (quantity < 1) return

  if (quantity > maxQuantity(item)) {
    itemStockError.value = item.id
    return
  }

  itemStockError.value = null

  router.patch(`/cart/${item.id}/quantity`, { quantity }, {
    preserveScroll: true,
    onError: () => {
      itemStockError.value = item.id
    },
    onSuccess: () => {
      itemStockError.value = null
    },
  })
}

const removeFromCart = (id) => {
  router.delete(`/cart/${id}`, {
    preserveScroll: true,
  })
}

const submitCheckout = () => {
  checkingOut.value = true
  router.post('/cart/checkout', {
    delivery_address: checkoutAddress.value.trim(),
    payment_method: paymentMethod.value,
    promo_code: appliedPromo.value?.code || '',
  }, {
    preserveScroll: true,
    onFinish: () => {
      checkingOut.value = false
      openCheckout.value = false
    },
  })
}
</script>
