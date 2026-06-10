<template>
  <AppLayout>
    <Breadcrumb :breadcrumbs="['Главная страница', 'Личный кабинет']" />
    <Notification />

    <div class="page-container py-6 md:py-[30px] flex flex-col lg:flex-row gap-8 md:gap-[60px]">
      <div class="w-full max-w-full lg:max-w-[420px]">
        <h2 class="font-['Vetrino'] text-[20px] text-black font-bold mb-[30px]">Личные данные</h2>

        <div class="mb-[33px]">
          <label class="font-['Montserrat'] text-[#868695] text-[16px] block mb-[10px]">Имя</label>
          <input
            v-model="formData.name"
            type="text"
            class="w-full h-[44px] bg-white rounded-[15px] px-[20px] font-['Montserrat'] text-black text-[15px] border border-gray-300 focus:outline-none focus:ring-2 focus:ring-[#2E7D32]"
          />
        </div>

        <div class="mb-[33px]">
          <label class="font-['Montserrat'] text-[#868695] text-[16px] block mb-[10px]">Почта</label>
          <input
            type="email"
            :value="user.email"
            disabled
            class="w-full h-[44px] bg-[#F6F6F9] rounded-[15px] px-[20px] font-['Montserrat'] text-black text-[15px] focus:outline-none"
          />
        </div>

        <div v-if="!isAdmin" class="mb-[20px]">
          <h3 class="font-['Montserrat'] font-bold text-[16px] mb-[10px]">Адрес доставки</h3>
          <YandexAddressMap v-model="formData.delivery_address" :api-key="yandexMapsApiKey" />
        </div>

        <div class="flex justify-center mt-[20px]">
          <button type="button" :disabled="saving" class="bg-[#2E7D32] text-white font-['Montserrat'] text-[15px] px-[30px] py-[10px] rounded-[15px] hover:bg-[#1b5e2b] disabled:opacity-50" @click="saveProfile">
            {{ saving ? 'Сохранение...' : 'Сохранить' }}
          </button>
        </div>

        <div class="flex justify-center mt-[20px]">
          <Link href="/favorites" class="text-[#2E7D32] font-['Montserrat'] text-[15px] hover:underline">
            Перейти в избранное
          </Link>
        </div>

        <div class="flex justify-center mt-[20px]">
          <button type="button" class="flex items-center gap-[10px] text-black font-['Montserrat'] text-[15px] hover:opacity-70" @click="logout">
            <svg class="w-[20px] h-[20px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
            </svg>
            Выход
          </button>
        </div>
      </div>

      <div class="flex-1">
        <h2 class="font-['Montserrat'] text-[20px] text-black font-bold mb-[20px]">История покупок</h2>

        <div v-if="orders.length > 0" class="space-y-[16px]">
          <div v-for="order in orders" :key="order.id" class="bg-white rounded-lg p-[20px] shadow">
            <div class="flex flex-wrap justify-between gap-[12px] mb-[10px]">
              <div>
                <p class="font-['Montserrat'] font-bold text-[16px] text-black">Заказ #{{ order.id }}</p>
                <p class="font-['Montserrat'] text-[14px] text-[#888888]">{{ formatOrderDate(order.created_at) }}</p>
              </div>
              <div class="text-right">
                <p class="font-['Montserrat'] font-bold text-[16px] text-black">{{ order.total_amount }} ₽</p>
                <span
                  class="inline-block mt-[6px] font-['Montserrat'] text-[14px] font-bold px-[14px] py-[6px] rounded-full border"
                  :class="statusBadgeClass(order.status)"
                >
                  {{ order.status }}
                </span>
              </div>
            </div>

            <p class="font-['Montserrat'] text-[13px] text-[#666666] mb-[4px]">Адрес: {{ order.delivery_address }}</p>
            <p class="font-['Montserrat'] text-[13px] text-[#666666] mb-[4px]">
              Оплата: <span class="font-semibold text-[#333333]">{{ order.payment_method || '—' }}</span>
              <template v-if="order.payment_status">
                · статус: <span class="font-semibold text-[#333333]">{{ order.payment_status }}</span>
              </template>
            </p>
            <p v-if="order.promo_code" class="font-['Montserrat'] text-[13px] text-[#2E7D32] mb-[10px]">
              Промокод {{ order.promo_code }}: скидка −{{ Number(order.discount_amount).toFixed(2) }} ₽
            </p>

            <div v-if="order.items && order.items.length" class="mb-[12px]">
              <p class="font-['Montserrat'] font-semibold text-[14px] text-black mb-[8px]">Состав заказа</p>
              <ul class="space-y-[6px] mb-[10px]">
                <li
                  v-for="item in order.items"
                  :key="item.id"
                  class="font-['Montserrat'] text-[13px] text-[#333333] flex flex-wrap justify-between gap-[8px] border-b border-gray-100 pb-[4px]"
                >
                  <Link
                    v-if="item.product?.id"
                    :href="`/product/${item.product.id}`"
                    class="text-[#2E7D32] hover:underline"
                  >
                    {{ item.display_name || item.product_name || item.product.name }}
                  </Link>
                  <span v-else class="text-[#333333]">{{ item.display_name || item.product_name || 'Товар (удалён из каталога)' }}</span>
                  <span class="text-[#555555] whitespace-nowrap">
                    {{ item.quantity }} шт. × {{ item.price }} ₽ = {{ (item.quantity * item.price).toFixed(2) }} ₽
                  </span>
                </li>
              </ul>
              <div class="flex flex-wrap gap-[10px]">
                <Link
                  v-for="item in order.items"
                  :key="'img-' + item.id"
                  :href="item.product?.id ? `/product/${item.product.id}` : '#'"
                  class="w-[72px] h-[72px] rounded bg-cover bg-center border hover:ring-2 hover:ring-[#2E7D32] transition bg-gray-100"
                  :style="item.product?.main_image ? { backgroundImage: `url('${item.product.main_image}')` } : {}"
                  :title="item.display_name || item.product_name || item.product?.name"
                />
              </div>
            </div>
            <p v-else class="font-['Montserrat'] text-[13px] text-[#888888] mb-[10px]">
              В заказе нет позиций
            </p>

            <p
              v-if="order.cancel_blocked"
              class="mt-[8px] font-['Montserrat'] text-[12px] text-amber-800 bg-amber-50 border border-amber-200 rounded px-3 py-2"
            >
              Заказ оплачен картой онлайн — отмена недоступна.
            </p>
            <button
              v-else-if="canCancelOrder(order)"
              type="button"
              class="mt-[8px] px-[14px] py-[8px] rounded border border-red-500 text-red-600 font-['Montserrat'] text-[13px] hover:bg-red-50"
              @click="cancelOrder(order)"
            >
              Отменить заказ
            </button>
          </div>
        </div>
        <div v-else class="text-center py-[40px] text-[#888888] font-['Montserrat'] text-[15px]">
          Заказов пока нет
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed, ref } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import Breadcrumb from '@/Components/Breadcrumb.vue'
import Notification from '@/Components/Notification.vue'
import YandexAddressMap from '@/Components/YandexAddressMap.vue'
import { Link, router, usePage } from '@inertiajs/vue3'

const props = defineProps({
  user: Object,
  orders: Array,
})

const page = usePage()
const yandexMapsApiKey = computed(() => page.props.yandexMapsApiKey || '')

const isAdmin = computed(() => {
  const role = page.props.auth?.user?.role
  return role === 'admin' || role === 'administrator'
})

const formData = ref({
  name: props.user.name,
  delivery_address: props.user.delivery_address || '',
})

const saving = ref(false)

const formatOrderDate = (iso) => {
  if (!iso) return '—'
  const d = new Date(iso)
  if (Number.isNaN(d.getTime())) return iso
  const pad = (n) => String(n).padStart(2, '0')
  return `${pad(d.getDate())}.${pad(d.getMonth() + 1)}.${d.getFullYear()} ${pad(d.getHours())}:${pad(d.getMinutes())}`
}

const statusBadgeClass = (status) => {
  const map = {
    'в обработке': 'bg-amber-50 text-amber-800 border-amber-300',
    'ожидает оплаты': 'bg-purple-50 text-purple-800 border-purple-300',
    отправлен: 'bg-blue-50 text-blue-800 border-blue-300',
    выполнен: 'bg-green-50 text-green-800 border-green-300',
    отменен: 'bg-red-50 text-red-800 border-red-300',
  }
  return map[status] || 'bg-gray-50 text-gray-700 border-gray-300'
}

const saveProfile = () => {
  saving.value = true
  router.patch('/profile', formData.value, {
    preserveScroll: true,
    onFinish: () => {
      saving.value = false
    },
  })
}

const logout = () => {
  router.post('/logout')
}

const canCancelOrder = (order) => {
  if (order.cancel_blocked) return false
  return order.status === 'в обработке' || order.status === 'ожидает оплаты'
}

const cancelOrder = (order) => {
  const msg = order.status === 'ожидает оплаты'
    ? 'Отменить заказ? Оплата не будет проведена.'
    : 'Отменить заказ? Товары вернутся на склад.'
  if (!confirm(msg)) return
  router.post(`/orders/${order.id}/cancel`, {}, { preserveScroll: true })
}
</script>
