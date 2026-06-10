<template>
  <AppLayout>
    <div class="page-container pt-3 md:pt-[12px] pb-6 md:pb-[30px]">
      <div class="flex flex-wrap items-center justify-between gap-[12px] mb-[20px]">
        <Breadcrumb admin :breadcrumbs="['Главная страница', 'Личный кабинет', 'Админ панель', 'Заказы']" />
        <Link href="/admin/dashboard" class="font-['Montserrat'] text-[14px] text-[#2E7D32] hover:underline">
          ← Назад в аналитику
        </Link>
      </div>

      <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-[16px] mb-[16px]">
        <h1 class="font-['Montserrat'] text-[20px] text-black font-normal">Управление заказами</h1>

        <div class="flex flex-wrap gap-[10px] items-center font-['Montserrat'] text-[13px]">
          <label class="flex items-center gap-2">
            Дата от
            <input v-model="dateFrom" type="date" class="border rounded px-2 py-1" />
          </label>
          <label class="flex items-center gap-2">
            Дата до
            <input v-model="dateTo" type="date" class="border rounded px-2 py-1" />
          </label>
          <button type="button" class="bg-[#2E7D32] text-white px-[16px] py-[8px] rounded hover:bg-[#1b5e2b]" @click="exportCurrentFilter">
            Скачать Excel{{ statusFilter !== 'all' ? ` (${statusFilter})` : '' }}
          </button>
        </div>
      </div>

      <div class="mb-[16px] rounded-lg border-2 border-[#E8F5E9] bg-[#F1F8E9] p-[12px]">
        <p class="font-['Montserrat'] text-[13px] font-bold text-[#1B5E20] mb-[10px]">Фильтр по статусу</p>
        <div class="flex flex-wrap gap-[8px]">
          <div class="flex items-stretch rounded-lg overflow-hidden border border-[#A5D6A7]">
            <Link
              :href="statusFilterHref('all')"
              class="px-[14px] py-[8px] text-[13px] font-['Montserrat'] transition"
              :class="statusFilter === 'all' ? 'bg-[#2E7D32] text-white' : 'bg-white text-[#2E7D32] hover:bg-[#E8F5E9]'"
            >
              Все ({{ statusCounts.all ?? 0 }})
            </Link>
            <button
              type="button"
              class="px-[10px] text-[12px] font-['Montserrat'] border-l border-[#A5D6A7] bg-white text-[#2E7D32] hover:bg-[#E8F5E9]"
              title="Скачать Excel: все заказы"
              @click="exportOrdersByStatus('all')"
            >
              Excel
            </button>
          </div>
          <div
            v-for="label in statusOptions"
            :key="label"
            class="flex items-stretch rounded-lg overflow-hidden border border-[#A5D6A7]"
          >
            <Link
              :href="statusFilterHref(label)"
              class="px-[14px] py-[8px] text-[13px] font-['Montserrat'] transition"
              :class="statusFilter === label ? 'bg-[#2E7D32] text-white' : 'bg-white text-[#2E7D32] hover:bg-[#E8F5E9]'"
            >
              {{ label }} ({{ statusCounts[label] ?? 0 }})
            </Link>
            <button
              type="button"
              class="px-[10px] text-[12px] font-['Montserrat'] border-l border-[#A5D6A7] bg-white text-[#2E7D32] hover:bg-[#E8F5E9]"
              :title="`Скачать Excel: ${label}`"
              @click="exportOrdersByStatus(label)"
            >
              Excel
            </button>
          </div>
        </div>
      </div>

      <div class="mb-[16px] rounded-lg border-2 border-[#E8F5E9] bg-[#F1F8E9] p-[12px] flex flex-wrap gap-[8px] items-center">
        <span class="font-['Montserrat'] text-[13px] font-bold text-[#1B5E20]">Сортировка по дате:</span>
        <Link
          :href="dateSortHref('desc')"
          class="inline-flex items-center gap-1 px-[12px] py-[6px] rounded-lg border text-[13px] font-['Montserrat']"
          :class="dateSortBtnClass('desc')"
        >
          Сначала новые ↓
        </Link>
        <Link
          :href="dateSortHref('asc')"
          class="inline-flex items-center gap-1 px-[12px] py-[6px] rounded-lg border text-[13px] font-['Montserrat']"
          :class="dateSortBtnClass('asc')"
        >
          Сначала старые ↑
        </Link>
      </div>

      <div class="space-y-[20px]">
        <div v-for="order in orders.data" :key="order.id" class="bg-white rounded-lg p-[20px] shadow">
          <div class="flex flex-wrap justify-between items-start gap-[15px] mb-[15px]">
            <div>
              <p class="font-['Montserrat'] font-bold text-[16px] text-black">Заказ #{{ order.id }}</p>
              <p class="font-['Montserrat'] text-[14px] text-[#888888]">Клиент: {{ order.user.name }}</p>
              <p class="font-['Montserrat'] text-[13px] text-[#666666] mt-[4px]">
                Создан: {{ formatOrderDate(order.created_at) }}
              </p>
            </div>
            <div class="flex flex-wrap gap-[15px] items-center">
              <div class="text-right">
                <span class="font-['Montserrat'] font-bold text-[16px] text-black block">{{ order.total_amount }} ₽</span>
                <span
                  v-if="order.promo_code"
                  class="font-['Montserrat'] text-[11px] text-[#E65100] font-semibold"
                >
                  с промокодом
                </span>
              </div>
              <span
                class="font-['Montserrat'] text-[13px] font-semibold px-[12px] py-[6px] rounded-full border"
                :class="statusBadgeClass(order.status)"
              >
                {{ order.status }}
              </span>
              <template v-if="order.status === 'отменен'">
                <p class="font-['Montserrat'] text-[12px] text-[#888888] max-w-[220px]">
                  Нельзя изменить статус отменённого заказа
                </p>
              </template>
              <template v-else-if="order.status === 'выполнен'">
                <p class="font-['Montserrat'] text-[12px] text-[#888888] max-w-[220px]">
                  Статус выполненного заказа изменить нельзя
                </p>
              </template>
              <template v-else>
                <select
                  :value="order.status"
                  class="font-['Montserrat'] text-[14px] px-[10px] py-[6px] rounded border border-gray-300 focus:outline-none focus:ring-2 focus:ring-[#2E7D32]"
                  @change="(e) => updateOrderStatus(order.id, e.target.value)"
                >
                  <option value="в обработке">в обработке</option>
                  <option value="отправлен">отправлен</option>
                  <option value="выполнен">выполнен</option>
                </select>
                <button
                  v-if="order.status === 'в обработке'"
                  type="button"
                  class="bg-red-500 text-white font-['Montserrat'] text-[14px] px-[16px] py-[8px] rounded hover:bg-red-600 transition"
                  @click="cancelOrder(order.id)"
                >
                  Отменить заказ
                </button>
              </template>
            </div>
          </div>

          <div class="bg-gray-50 rounded p-[10px] text-[12px] space-y-[6px]">
            <p class="font-['Montserrat'] text-[#555555]">
              <span class="text-[#888888]">Адрес:</span> {{ order.delivery_address }}
            </p>
            <p class="font-['Montserrat'] text-[#555555]">
              <span class="text-[#888888]">Оплата:</span>
              <span class="inline-block ml-1 px-[10px] py-[4px] rounded-full bg-[#E8F5E9] text-[#1B5E20] font-semibold">
                {{ order.payment_method || '—' }}
              </span>
              <span v-if="order.payment_status" class="text-[#666666] ml-2">({{ order.payment_status }})</span>
            </p>
            <p v-if="order.promo_code" class="font-['Montserrat'] text-[#555555]">
              <span class="text-[#888888]">Промокод:</span>
              <span class="inline-block ml-1 px-[10px] py-[4px] rounded-full bg-[#FFF8E1] border border-[#FFE082] text-[#E65100] font-semibold tracking-wide">
                {{ order.promo_code }}
              </span>
              <span v-if="Number(order.discount_amount) > 0" class="text-[#2E7D32] font-semibold ml-2">
                −{{ Number(order.discount_amount).toFixed(2) }} ₽
              </span>
            </p>
            <p v-if="order.promo_code && orderItemsSubtotal(order) > Number(order.total_amount)" class="font-['Montserrat'] text-[#777777]">
              <span class="text-[#888888]">Сумма товаров:</span>
              {{ orderItemsSubtotal(order).toFixed(2) }} ₽
              <span class="mx-1">→</span>
              <span class="text-[#1B5E20] font-semibold">к оплате {{ order.total_amount }} ₽</span>
            </p>
          </div>

          <div class="mt-[15px]">
            <p class="font-['Montserrat'] font-semibold text-[14px] text-black mb-[8px]">
              Товары в заказе
            </p>
            <div v-if="order.items && order.items.length" class="space-y-[6px]">
              <div
                v-for="item in order.items"
                :key="item.id"
                class="flex justify-between items-center text-[13px] border-b border-gray-100 pb-[4px]"
              >
                <div class="flex-1">
                  <p class="font-['Montserrat'] text-black">
                    {{ item.display_name || item.product_name || item.product?.name || 'Товар (удалён из каталога)' }}
                  </p>
                </div>
                <div class="w-[80px] text-right font-['Montserrat'] text-[#555555]">
                  x{{ item.quantity }}
                </div>
                <div class="w-[90px] text-right font-['Montserrat'] text-black">
                  {{ item.price }} ₽
                </div>
              </div>
            </div>
            <p v-else class="font-['Montserrat'] text-[13px] text-[#888888]">
              В заказе нет товаров.
            </p>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import Breadcrumb from '@/Components/Breadcrumb.vue'
import { downloadExport } from '@/utils/downloadExport'
import { Link, router } from '@inertiajs/vue3'

const props = defineProps({
  orders: Object,
  orderSort: { type: String, default: 'created_at' },
  orderSortDir: { type: String, default: 'desc' },
  statusFilter: { type: String, default: 'all' },
  statusCounts: { type: Object, default: () => ({}) },
  statusOptions: { type: Array, default: () => [] },
})

const dateFrom = ref('')
const dateTo = ref('')

const orderItemsSubtotal = (order) => {
  if (!order.items?.length) return 0
  return order.items.reduce(
    (sum, item) => sum + Number(item.price) * Number(item.quantity),
    0
  )
}

const formatOrderDate = (iso) => {
  if (!iso) return '—'
  const d = new Date(iso)
  if (Number.isNaN(d.getTime())) return iso
  const pad = (n) => String(n).padStart(2, '0')
  return `${pad(d.getDate())}.${pad(d.getMonth() + 1)}.${d.getFullYear()} ${pad(d.getHours())}:${pad(d.getMinutes())}`
}

const statusFilterHref = (status) => {
  const params = new URLSearchParams({
    status_filter: status,
    order_sort: props.orderSort,
    dir: props.orderSortDir,
  })
  return `/admin/orders?${params.toString()}`
}

const dateSortHref = (dir) => {
  const params = new URLSearchParams({
    order_sort: 'created_at',
    dir,
    status_filter: props.statusFilter,
  })
  return `/admin/orders?${params.toString()}`
}

const dateSortBtnClass = (dir) => {
  const active = props.orderSort === 'created_at' && props.orderSortDir === dir
  return active
    ? 'bg-[#2E7D32] text-white border-[#2E7D32] font-semibold'
    : 'bg-white text-[#2E7D32] border-[#A5D6A7] hover:bg-[#E8F5E9]'
}

const statusBadgeClass = (status) => {
  const map = {
    'в обработке': 'bg-amber-50 text-amber-800 border-amber-200',
    отправлен: 'bg-blue-50 text-blue-800 border-blue-200',
    выполнен: 'bg-green-50 text-green-800 border-green-200',
    отменен: 'bg-red-50 text-red-800 border-red-200',
  }
  return map[status] || 'bg-gray-50 text-gray-700 border-gray-200'
}

const updateOrderStatus = (id, status) => {
  router.patch(`/admin/orders/${id}/status`, { status })
}

const cancelOrder = (id) => {
  if (confirm('Отменить этот заказ? Товары вернутся на склад.')) {
    router.patch(`/admin/orders/${id}/cancel`, {}, { preserveScroll: true })
  }
}

const ordersExportFilename = (status) => {
  if (!status || status === 'all') {
    return 'orders.xlsx'
  }
  return `orders-${status.replace(/\s+/g, '-')}.xlsx`
}

const exportOrdersByStatus = (status) => {
  const params = new URLSearchParams()
  if (status && status !== 'all') {
    params.set('status', status)
  }
  if (dateFrom.value) params.set('date_from', dateFrom.value)
  if (dateTo.value) params.set('date_to', dateTo.value)
  const qs = params.toString()
  const url = qs ? `/admin/orders/export?${qs}` : '/admin/orders/export'
  const label = status && status !== 'all' ? status : null
  downloadExport(url, {
    emptyMessage: label
      ? `Пустой отчёт. Нет заказов со статусом «${label}» за выбранный период.`
      : 'Пустой отчёт. Нет заказов за выбранный период.',
    filename: ordersExportFilename(status),
  })
}

const exportCurrentFilter = () => exportOrdersByStatus(props.statusFilter)
</script>
