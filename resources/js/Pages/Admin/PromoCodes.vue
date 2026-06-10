<template>
  <AppLayout>
    <div class="page-container pt-3 md:pt-[12px] pb-6 md:pb-[30px]">
      <div class="flex flex-wrap items-center justify-between gap-[12px] mb-[20px]">
        <Breadcrumb admin :breadcrumbs="['Главная страница', 'Личный кабинет', 'Админ панель', 'Промокоды']" />
        <Link href="/admin/dashboard" class="font-['Montserrat'] text-[14px] text-[#2E7D32] hover:underline">
          ← Назад в аналитику
        </Link>
      </div>

      <div class="flex justify-between items-center mb-[20px]">
        <h1 class="font-['Montserrat'] text-[20px] text-black font-normal">Промокоды</h1>
        <button type="button" class="bg-[#2E7D32] text-white font-['Montserrat'] text-[14px] px-[20px] py-[10px] rounded hover:bg-[#1b5e2b]" @click="openCreate">
          Добавить промокод
        </button>
      </div>

      <Notification />

      <div class="mb-[16px] rounded-lg border-2 border-[#E8F5E9] bg-[#F1F8E9] p-[12px] space-y-[12px]">
        <div>
          <p class="font-['Montserrat'] text-[13px] font-bold text-[#1B5E20] mb-[8px]">Активность</p>
          <div class="flex flex-wrap gap-[8px]">
            <Link
              v-for="opt in activeFilterOptions"
              :key="opt.value"
              :href="filterHref({ active_filter: opt.value })"
              class="px-[14px] py-[8px] rounded-lg border border-[#A5D6A7] text-[13px] font-['Montserrat'] transition"
              :class="activeFilter === opt.value ? 'bg-[#2E7D32] text-white' : 'bg-white text-[#2E7D32] hover:bg-[#E8F5E9]'"
            >
              {{ opt.label }} ({{ activeCounts[opt.value] ?? 0 }})
            </Link>
          </div>
        </div>
        <div>
          <p class="font-['Montserrat'] text-[13px] font-bold text-[#1B5E20] mb-[8px]">Тип промокода</p>
          <div class="flex flex-wrap gap-[8px]">
            <Link
              v-for="opt in typeFilterOptions"
              :key="opt.value"
              :href="filterHref({ type_filter: opt.value })"
              class="px-[14px] py-[8px] rounded-lg border border-[#A5D6A7] text-[13px] font-['Montserrat'] transition"
              :class="typeFilter === opt.value ? 'bg-[#2E7D32] text-white' : 'bg-white text-[#2E7D32] hover:bg-[#E8F5E9]'"
            >
              {{ opt.label }} ({{ typeCounts[opt.value] ?? 0 }})
            </Link>
          </div>
        </div>
      </div>

      <div class="overflow-x-auto bg-white rounded-lg shadow">
        <table class="w-full border-collapse min-w-[720px]">
          <thead>
            <tr class="bg-gray-100 border-b">
              <th class="text-left font-['Montserrat'] text-[14px] px-[12px] py-[10px]">Код</th>
              <th class="text-left font-['Montserrat'] text-[14px] px-[12px] py-[10px]">Тип</th>
              <th class="text-left font-['Montserrat'] text-[14px] px-[12px] py-[10px]">Условие</th>
              <th class="text-left font-['Montserrat'] text-[14px] px-[12px] py-[10px]">Описание</th>
              <th class="text-left font-['Montserrat'] text-[14px] px-[12px] py-[10px]">Активен</th>
              <th class="text-left font-['Montserrat'] text-[14px] px-[12px] py-[10px]">Действия</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="promo in promoCodes.data" :key="promo.id" class="border-b hover:bg-gray-50">
              <td class="font-['Montserrat'] text-[14px] px-[12px] py-[10px] font-semibold tracking-wide">{{ promo.code }}</td>
              <td class="font-['Montserrat'] text-[13px] px-[12px] py-[10px]">{{ typeLabel(promo.type) }}</td>
              <td class="font-['Montserrat'] text-[13px] px-[12px] py-[10px] text-[#444444]">{{ promo.condition_summary }}</td>
              <td class="font-['Montserrat'] text-[12px] px-[12px] py-[10px] text-[#666666] max-w-[200px]">{{ promo.description || '—' }}</td>
              <td class="font-['Montserrat'] text-[13px] px-[12px] py-[10px]">{{ promo.active ? 'да' : 'нет' }}</td>
              <td class="font-['Montserrat'] text-[13px] px-[12px] py-[10px] space-x-2 whitespace-nowrap">
                <button type="button" class="text-[#2E7D32] hover:underline" @click="openEdit(promo)">Изменить</button>
                <button type="button" class="text-red-600 hover:underline" @click="remove(promo.id)">Удалить</button>
              </td>
            </tr>
            <tr v-if="!promoCodes.data?.length">
              <td colspan="6" class="text-center py-8 font-['Montserrat'] text-[#888888]">Промокодов пока нет</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <Modal :show="showModal" max-width="lg" @close="closeModal">
      <div ref="modalBody" class="p-6 space-y-4 max-h-[85vh] overflow-y-auto">
        <h2 class="font-['Montserrat'] text-[18px] font-bold">{{ editingId ? 'Редактировать' : 'Создать' }} промокод</h2>

        <FormErrorsSummary :items="errorSummary" />

        <div>
          <label class="block font-['Montserrat'] text-[13px] mb-1 font-semibold">Код промокода *</label>
          <input
            v-model="form.code"
            type="text"
            :class="inputClass('code', 'uppercase')"
            placeholder="Grunt4100"
            @input="form.code = String(form.code || '').toUpperCase().replace(/[^A-Z0-9]/g, '')"
          />
          <p class="font-['Montserrat'] text-[11px] text-[#888888] mt-1">A–Z и цифры, минимум 2 цифры, без пробелов</p>
          <FormFieldError :error="form.errors.code" />
        </div>

        <div>
          <label class="block font-['Montserrat'] text-[13px] mb-1 font-semibold">Тип акции *</label>
          <select v-model="form.type" :class="inputClass('type')">
            <option v-for="t in promoTypes" :key="t.value" :value="t.value">{{ t.label }}</option>
          </select>
          <FormFieldError :error="form.errors.type" />
        </div>

        <template v-if="form.type === 'bundle_free'">
          <p class="font-['Montserrat'] text-[12px] text-[#666666] bg-gray-50 rounded p-3">
            Пример: купите 3 - 4-й бесплатно (100% скидка на самую дешёвую единицу в комплекте).
          </p>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-['Montserrat'] text-[13px] mb-1">Платных в комплекте *</label>
              <input v-model.number="form.buy_quantity" type="number" min="1" :class="inputClass('buy_quantity')" />
              <FormFieldError :error="form.errors.buy_quantity" />
            </div>
            <div>
              <label class="block font-['Montserrat'] text-[13px] mb-1">Бесплатных в комплекте *</label>
              <input v-model.number="form.free_quantity" type="number" min="1" :class="inputClass('free_quantity')" />
              <FormFieldError :error="form.errors.free_quantity" />
            </div>
          </div>

          <div>
            <label class="block font-['Montserrat'] text-[13px] mb-2 font-semibold">Действует на *</label>
            <div class="flex flex-wrap gap-4 mb-2">
              <label class="flex items-center gap-2 font-['Montserrat'] text-[13px]">
                <input v-model="bundleTarget" type="radio" value="category" class="text-[#2E7D32]" />
                Категорию
              </label>
              <label class="flex items-center gap-2 font-['Montserrat'] text-[13px]">
                <input v-model="bundleTarget" type="radio" value="product" class="text-[#2E7D32]" />
                Конкретный товар
              </label>
            </div>
            <select
              v-if="bundleTarget === 'category'"
              v-model="form.category_id"
              :class="inputClass('category_id')"
            >
              <option :value="null">— выберите категорию —</option>
              <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }} ({{ c.slug }})</option>
            </select>
            <select
              v-else
              v-model="form.product_id"
              :class="inputClass('product_id')"
            >
              <option :value="null">— выберите товар —</option>
              <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }} — {{ p.price }} ₽</option>
            </select>
            <FormFieldError :error="form.errors.category_id" />
            <FormFieldError :error="form.errors.product_id" />
          </div>
        </template>

        <template v-else>
          <p class="font-['Montserrat'] text-[12px] text-[#666666] bg-gray-50 rounded p-3">
            Скидка на всю корзину при достижении минимальной суммы (например, от 8000 ₽ - 10%).
          </p>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-['Montserrat'] text-[13px] mb-1">Мин. сумма заказа, ₽ *</label>
              <input v-model.number="form.min_order_amount" type="number" min="1" step="100" :class="inputClass('min_order_amount')" />
              <FormFieldError :error="form.errors.min_order_amount" />
            </div>
            <div>
              <label class="block font-['Montserrat'] text-[13px] mb-1">Скидка, % *</label>
              <input v-model.number="form.discount_percent" type="number" min="1" max="100" step="1" :class="inputClass('discount_percent')" />
              <FormFieldError :error="form.errors.discount_percent" />
            </div>
          </div>
        </template>

        <div>
          <label class="block font-['Montserrat'] text-[13px] mb-1 font-semibold">Описание для покупателя *</label>
          <textarea
            v-model="form.description"
            rows="2"
            :class="inputClass('description')"
            placeholder="Краткий текст на сайте (обязательно)"
          />
          <FormFieldError :error="form.errors.description" />
        </div>

        <label class="flex items-center gap-2 font-['Montserrat'] text-[13px]">
          <input v-model="form.active" type="checkbox" class="rounded border-gray-300 text-[#2E7D32]" />
          Промокод активен
        </label>
        <FormFieldError :error="form.errors.active" />

        <div class="flex justify-end gap-2 pt-2">
          <button type="button" class="px-4 py-2 text-gray-600" @click="closeModal">Отмена</button>
          <button type="button" class="px-4 py-2 bg-[#2E7D32] text-white rounded disabled:opacity-50" :disabled="form.processing" @click="submit">
            {{ form.processing ? 'Сохранение...' : 'Сохранить' }}
          </button>
        </div>
      </div>
    </Modal>
  </AppLayout>
</template>

<script setup>
import { computed, nextTick, ref, watch } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import Breadcrumb from '@/Components/Breadcrumb.vue'
import Notification from '@/Components/Notification.vue'
import FormFieldError from '@/Components/FormFieldError.vue'
import FormErrorsSummary from '@/Components/FormErrorsSummary.vue'
import Modal from '@/Components/Modal.vue'
import { Link, router, useForm } from '@inertiajs/vue3'

const props = defineProps({
  promoCodes: Object,
  categories: Array,
  products: Array,
  promoTypes: Array,
  activeFilter: { type: String, default: 'all' },
  typeFilter: { type: String, default: 'all' },
  activeCounts: { type: Object, default: () => ({}) },
  typeCounts: { type: Object, default: () => ({}) },
})

const activeFilterOptions = [
  { value: 'all', label: 'Все' },
  { value: '1', label: 'Активные' },
  { value: '0', label: 'Неактивные' },
]

const typeFilterOptions = computed(() => [
  { value: 'all', label: 'Все типы' },
  ...(props.promoTypes || []).map((t) => ({
    value: t.value,
    label: (t.label || '').split('(')[0]?.trim() || t.value,
  })),
])

const filterHref = (overrides) => {
  const params = {
    active_filter: props.activeFilter,
    type_filter: props.typeFilter,
    ...overrides,
  }
  const query = Object.fromEntries(
    Object.entries(params).filter(([, v]) => v && v !== 'all')
  )
  return Object.keys(query).length ? `/admin/promo-codes?${new URLSearchParams(query)}` : '/admin/promo-codes'
}

const FIELD_LABELS = {
  code: 'Код промокода',
  type: 'Тип акции',
  description: 'Описание',
  active: 'Активность',
  category_id: 'Категория',
  product_id: 'Товар',
  buy_quantity: 'Платных в комплекте',
  free_quantity: 'Бесплатных в комплекте',
  min_order_amount: 'Мин. сумма заказа',
  discount_percent: 'Скидка, %',
}

const showModal = ref(false)
const editingId = ref(null)
const bundleTarget = ref('category')
const modalBody = ref(null)

const form = useForm({
  code: '',
  type: 'bundle_free',
  description: '',
  active: true,
  category_id: null,
  product_id: null,
  buy_quantity: 3,
  free_quantity: 1,
  min_order_amount: null,
  discount_percent: null,
})

const hasFormErrors = computed(() => Object.keys(form.errors).length > 0)

const errorSummary = computed(() => {
  return Object.entries(form.errors).map(([field, message]) => ({
    field,
    label: FIELD_LABELS[field] || field,
    message: Array.isArray(message) ? message[0] : message,
  }))
})

const inputClass = (field, extra = '') => {
  const base = `w-full border rounded px-3 py-2 font-['Montserrat'] text-[14px] ${extra}`.trim()
  return form.errors[field] ? `${base} border-red-500 bg-red-50 ring-1 ring-red-200` : `${base} border-gray-300`
}

watch(bundleTarget, (target) => {
  if (target === 'category') {
    form.product_id = null
  } else {
    form.category_id = null
  }
})

const typeLabel = (type) => {
  const found = props.promoTypes?.find((t) => t.value === type)
  return found?.label?.split('(')[0]?.trim() ?? type
}

const scrollModalToTop = () => {
  nextTick(() => {
    modalBody.value?.scrollTo({ top: 0, behavior: 'smooth' })
  })
}

const closeModal = () => {
  showModal.value = false
  form.clearErrors()
}

const openCreate = () => {
  editingId.value = null
  form.reset()
  form.clearErrors()
  form.type = 'bundle_free'
  form.active = true
  form.buy_quantity = 3
  form.free_quantity = 1
  bundleTarget.value = 'category'
  showModal.value = true
}

const openEdit = (promo) => {
  editingId.value = promo.id
  form.clearErrors()
  form.code = promo.code
  form.type = promo.type
  form.description = promo.description || ''
  form.active = promo.active
  form.category_id = promo.category_id || null
  form.product_id = promo.product_id || null
  form.buy_quantity = promo.buy_quantity || 3
  form.free_quantity = promo.free_quantity || 1
  form.min_order_amount = promo.min_order_amount
  form.discount_percent = promo.discount_percent
  bundleTarget.value = promo.product_id ? 'product' : 'category'
  showModal.value = true
}

const submitOptions = () => ({
  preserveScroll: true,
  onSuccess: () => {
    closeModal()
  },
  onError: () => {
    showModal.value = true
    scrollModalToTop()
  },
})

const submit = () => {
  const payload = { ...form.data() }
  if (form.type === 'bundle_free') {
    if (bundleTarget.value === 'product') {
      payload.category_id = null
    } else {
      payload.product_id = null
    }
  }

  if (editingId.value) {
    form.transform(() => payload).patch(`/admin/promo-codes/${editingId.value}`, submitOptions())
  } else {
    form.transform(() => payload).post('/admin/promo-codes', submitOptions())
  }
}

const remove = (id) => {
  if (!confirm('Удалить промокод?')) return
  router.delete(`/admin/promo-codes/${id}`, { preserveScroll: true })
}
</script>
