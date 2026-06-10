<template>
  <AppLayout>
    <div class="page-container pt-3 md:pt-[12px] pb-6 md:pb-[30px]">
      <div class="flex flex-wrap items-center justify-between gap-[12px] mb-[20px]">
        <Breadcrumb admin :breadcrumbs="['Главная страница', 'Личный кабинет', 'Админ панель', 'Товары']" />
        <Link href="/admin/dashboard" class="font-['Montserrat'] text-[14px] text-[#2E7D32] hover:underline">
          ← Назад в аналитику
        </Link>
      </div>

      <div class="flex justify-between items-center mb-[30px]">
        <h1 class="font-['Montserrat'] text-[20px] text-black font-normal">Управление товарами</h1>
        <button type="button" class="bg-[#2E7D32] text-white font-['Montserrat'] text-[14px] px-[20px] py-[10px] rounded hover:bg-[#1b5e2b]" @click="openAddModal">
          Добавить товар
        </button>
      </div>

      <Notification />

      <div class="mb-[16px] rounded-lg border-2 border-[#E8F5E9] bg-[#F1F8E9] p-[14px]">
        <p class="font-['Montserrat'] text-[14px] font-bold text-[#1B5E20] mb-[10px]">Сортировка списка</p>
        <div class="flex flex-wrap gap-[8px] font-['Montserrat'] text-[13px]">
          <Link
            :href="sortHref('quantity', 'desc')"
            class="inline-flex items-center gap-1 px-[12px] py-[8px] rounded-lg border transition"
            :class="sortBtnClass('quantity', 'desc')"
          >
            <span>Наличие</span>
            <span aria-hidden="true">↓</span>
            <span class="text-[11px] opacity-80">больше → меньше</span>
          </Link>
          <Link
            :href="sortHref('quantity', 'asc')"
            class="inline-flex items-center gap-1 px-[12px] py-[8px] rounded-lg border transition"
            :class="sortBtnClass('quantity', 'asc')"
          >
            <span>Наличие</span>
            <span aria-hidden="true">↑</span>
            <span class="text-[11px] opacity-80">меньше → больше</span>
          </Link>
          <Link
            :href="sortHref('name', adminSort === 'name' && adminSortDir === 'asc' ? 'desc' : 'asc')"
            class="inline-flex items-center gap-1 px-[12px] py-[8px] rounded-lg border transition"
            :class="sortBtnClass('name', adminSortDir)"
          >
            <span>Название</span>
            <span v-if="adminSort === 'name'">{{ adminSortDir === 'asc' ? '↑' : '↓' }}</span>
          </Link>
          <Link
            :href="sortHref('price', adminSort === 'price' && adminSortDir === 'asc' ? 'desc' : 'asc')"
            class="inline-flex items-center gap-1 px-[12px] py-[8px] rounded-lg border transition"
            :class="sortBtnClass('price', adminSortDir)"
          >
            <span>Цена</span>
            <span v-if="adminSort === 'price'">{{ adminSortDir === 'asc' ? '↑' : '↓' }}</span>
          </Link>
        </div>
      </div>

      <div class="overflow-x-auto bg-white rounded-lg shadow">
        <table class="w-full border-collapse">
          <thead>
            <tr class="bg-gray-100 border-b">
              <th class="font-['Montserrat'] text-[14px] text-black px-[15px] py-[12px] text-left">Фото</th>
              <th class="font-['Montserrat'] text-[14px] text-black px-[15px] py-[12px] text-left">Наименование</th>
              <th class="font-['Montserrat'] text-[14px] text-black px-[15px] py-[12px] text-left">Количество</th>
              <th class="font-['Montserrat'] text-[14px] text-black px-[15px] py-[12px] text-left">Категории</th>
              <th class="font-['Montserrat'] text-[14px] text-black px-[15px] py-[12px] text-left">Цена</th>
              <th class="font-['Montserrat'] text-[14px] text-black px-[15px] py-[12px] text-left">Действия</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="product in products.data" :key="product.id" class="border-b hover:bg-gray-50">
              <td class="px-[15px] py-[10px]">
                <img :src="product.main_image" alt="" class="w-[50px] h-[50px] rounded object-cover border" />
              </td>
              <td class="font-['Montserrat'] text-[14px] text-black px-[15px] py-[12px]">{{ product.name }}</td>
              <td class="font-['Montserrat'] text-[14px] text-black px-[15px] py-[12px]">{{ product.quantity }}</td>
              <td class="font-['Montserrat'] text-[13px] text-black px-[15px] py-[12px]">
                <span v-if="product.categories?.length">
                  {{ product.categories.map((c) => c.name).join(', ') }}
                </span>
                <span v-else>—</span>
              </td>
              <td class="font-['Montserrat'] text-[14px] text-black px-[15px] py-[12px]">{{ product.price }} ₽</td>
              <td class="font-['Montserrat'] text-[14px] px-[15px] py-[12px]">
                <button type="button" class="text-[#2E7D32] hover:underline mr-[10px]" @click="openEditModal(product)">Редактировать</button>
                <button type="button" class="text-red-500 hover:underline" @click="deleteProduct(product.id)">Удалить</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <Modal :show="showAddModal" max-width="lg" @close="closeAddModal">
      <div ref="addModalBody" class="p-6">
        <h2 class="font-['Montserrat'] text-[20px] font-bold text-black mb-4">Добавить товар</h2>
        <form @submit.prevent="submitAddProduct">
          <div class="space-y-4 max-h-[70vh] overflow-y-auto pr-1">
            <FormErrorsSummary :items="addErrorSummary" />

            <div>
              <label class="block font-['Montserrat'] text-[14px] text-black mb-2">Название</label>
              <input v-model="addForm.name" type="text" required :class="addWithFieldError('name', INPUT_BASE)" />
              <p class="mt-1 font-['Montserrat'] text-[12px] text-[#888888]">От 2 до 200 символов</p>
              <FormFieldError :error="addForm.errors.name" />
            </div>
            <div>
              <label class="block font-['Montserrat'] text-[14px] text-black mb-2">Артикул</label>
              <input v-model="addForm.article" type="text" required :class="addWithFieldError('article', INPUT_BASE)" />
              <p class="mt-1 font-['Montserrat'] text-[12px] text-[#888888]">Латиница, цифры, дефис, подчёркивание; уникальный</p>
              <FormFieldError :error="addForm.errors.article" />
            </div>
            <div>
              <label class="block font-['Montserrat'] text-[14px] text-black mb-2">Категории</label>
              <div :class="addWithFieldError('category_ids', 'max-h-[160px] overflow-y-auto border border-gray-200 rounded-lg p-2 space-y-1')">
                <label v-for="cat in categories" :key="cat.id" class="flex items-center gap-2 font-['Montserrat'] text-[13px]">
                  <input v-model="addForm.category_ids" type="checkbox" :value="cat.id" class="rounded border-gray-300 text-[#2E7D32]" />
                  {{ cat.name }}
                </label>
              </div>
              <FormFieldError :error="addForm.errors.category_ids" />
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
              <div>
                <label class="block font-['Montserrat'] text-[14px] text-black mb-2">Сложность ухода</label>
                <select v-model="addForm.care_difficulty" :class="addWithFieldError('care_difficulty', INPUT_BASE)">
                  <option :value="null">—</option>
                  <option value="easy">Лёгкий</option>
                  <option value="medium">Средний</option>
                  <option value="hard">Сложный</option>
                </select>
                <FormFieldError :error="addForm.errors.care_difficulty" />
              </div>
              <div>
                <label class="block font-['Montserrat'] text-[14px] text-black mb-2">Размер</label>
                <select v-model="addForm.size" :class="addWithFieldError('size', INPUT_BASE)">
                  <option :value="null">—</option>
                  <option value="small">Малый</option>
                  <option value="medium">Средний</option>
                  <option value="large">Крупный</option>
                  <option value="extra_large">Очень крупный</option>
                </select>
                <FormFieldError :error="addForm.errors.size" />
              </div>
              <div>
                <label class="block font-['Montserrat'] text-[14px] text-black mb-2">Возраст</label>
                <select v-model="addForm.age_group" :class="addWithFieldError('age_group', INPUT_BASE)">
                  <option :value="null">—</option>
                  <option value="young">Молодое</option>
                  <option value="mature">Зрелое</option>
                  <option value="old">Взрослое</option>
                </select>
                <FormFieldError :error="addForm.errors.age_group" />
              </div>
            </div>
            <div>
              <label class="block font-['Montserrat'] text-[14px] text-black mb-2">Цена, ₽</label>
              <input v-model.number="addForm.price" type="number" step="0.01" min="0.01" required :class="addWithFieldError('price', INPUT_BASE)" />
              <p class="mt-1 font-['Montserrat'] text-[12px] text-[#888888]">Больше 0</p>
              <FormFieldError :error="addForm.errors.price" />
            </div>
            <div>
              <label class="block font-['Montserrat'] text-[14px] text-black mb-2">Описание</label>
              <textarea v-model="addForm.description" rows="3" required :class="addWithFieldError('description', INPUT_BASE)"></textarea>
              <p class="mt-1 font-['Montserrat'] text-[12px] text-[#888888]">Не менее 10 символов</p>
              <FormFieldError :error="addForm.errors.description" />
            </div>
            <div>
              <label class="block font-['Montserrat'] text-[14px] text-black mb-2">Количество на складе</label>
              <input v-model.number="addForm.quantity" type="number" min="0" required :class="addWithFieldError('quantity', INPUT_BASE)" />
              <FormFieldError :error="addForm.errors.quantity" />
              <p class="mt-1 font-['Montserrat'] text-[12px] text-[#888888]">
                Статус «В наличии» / «Закончился» выставляется автоматически по количеству.
              </p>
            </div>
            <div>
              <label class="block font-['Montserrat'] text-[14px] text-black mb-2">Изображение (URL)</label>
              <input v-model="addForm.main_image" type="url" required placeholder="https://..." :class="addWithFieldError('main_image', INPUT_BASE)" />
              <p class="mt-1 font-['Montserrat'] text-[12px] text-[#888888]">Полная ссылка http:// или https://</p>
              <FormFieldError :error="addForm.errors.main_image" />
            </div>
          </div>
          <div class="flex gap-3 justify-end mt-6">
            <button type="button" class="px-4 py-2 font-['Montserrat'] text-[14px] text-gray-600 hover:text-gray-800" @click="closeAddModal">
              Отмена
            </button>
            <button type="submit" :disabled="addForm.processing" class="px-4 py-2 bg-[#2E7D32] text-white font-['Montserrat'] text-[14px] rounded hover:bg-[#1b5e2b] disabled:opacity-50">
              {{ addForm.processing ? 'Сохранение...' : 'Добавить' }}
            </button>
          </div>
        </form>
      </div>
    </Modal>

    <Modal :show="showEditModal" max-width="lg" @close="closeEditModal">
      <div ref="editModalBody" class="p-6">
        <div class="flex items-center justify-between gap-[12px] mb-4">
          <h2 class="font-['Montserrat'] text-[20px] font-bold text-black">Редактировать товар</h2>
          <Link
            href="/admin/products"
            class="font-['Montserrat'] text-[14px] text-[#2E7D32] hover:underline shrink-0"
            @click="showEditModal = false"
          >
            ← К списку товаров
          </Link>
        </div>
        <form @submit.prevent="submitEditProduct">
          <div class="space-y-4 max-h-[70vh] overflow-y-auto pr-1">
            <FormErrorsSummary :items="editErrorSummary" />

            <div>
              <label class="block font-['Montserrat'] text-[14px] text-black mb-2">Название</label>
              <input v-model="editForm.name" type="text" required :class="editWithFieldError('name', INPUT_BASE)" />
              <p class="mt-1 font-['Montserrat'] text-[12px] text-[#888888]">От 2 до 200 символов</p>
              <FormFieldError :error="editForm.errors.name" />
            </div>
            <div>
              <label class="block font-['Montserrat'] text-[14px] text-black mb-2">Категории</label>
              <div :class="editWithFieldError('category_ids', 'max-h-[160px] overflow-y-auto border border-gray-200 rounded-lg p-2 space-y-1')">
                <label v-for="cat in categories" :key="cat.id" class="flex items-center gap-2 font-['Montserrat'] text-[13px]">
                  <input v-model="editForm.category_ids" type="checkbox" :value="cat.id" class="rounded border-gray-300 text-[#2E7D32]" />
                  {{ cat.name }}
                </label>
              </div>
              <FormFieldError :error="editForm.errors.category_ids" />
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
              <div>
                <label class="block font-['Montserrat'] text-[14px] text-black mb-2">Сложность ухода</label>
                <select v-model="editForm.care_difficulty" :class="editWithFieldError('care_difficulty', INPUT_BASE)">
                  <option :value="null">—</option>
                  <option value="easy">Лёгкий</option>
                  <option value="medium">Средний</option>
                  <option value="hard">Сложный</option>
                </select>
                <FormFieldError :error="editForm.errors.care_difficulty" />
              </div>
              <div>
                <label class="block font-['Montserrat'] text-[14px] text-black mb-2">Размер</label>
                <select v-model="editForm.size" :class="editWithFieldError('size', INPUT_BASE)">
                  <option :value="null">—</option>
                  <option value="small">Малый</option>
                  <option value="medium">Средний</option>
                  <option value="large">Крупный</option>
                  <option value="extra_large">Очень крупный</option>
                </select>
                <FormFieldError :error="editForm.errors.size" />
              </div>
              <div>
                <label class="block font-['Montserrat'] text-[14px] text-black mb-2">Возраст</label>
                <select v-model="editForm.age_group" :class="editWithFieldError('age_group', INPUT_BASE)">
                  <option :value="null">—</option>
                  <option value="young">Молодое</option>
                  <option value="mature">Зрелое</option>
                  <option value="old">Взрослое</option>
                </select>
                <FormFieldError :error="editForm.errors.age_group" />
              </div>
            </div>
            <div>
              <label class="block font-['Montserrat'] text-[14px] text-black mb-2">Изображение (URL)</label>
              <input v-model="editForm.main_image" type="url" required placeholder="https://..." :class="editWithFieldError('main_image', INPUT_BASE)" />
              <p class="mt-1 font-['Montserrat'] text-[12px] text-[#888888]">Полная ссылка http:// или https://</p>
              <FormFieldError :error="editForm.errors.main_image" />
            </div>
            <div>
              <label class="block font-['Montserrat'] text-[14px] text-black mb-2">Описание</label>
              <textarea v-model="editForm.description" rows="3" required :class="editWithFieldError('description', INPUT_BASE)"></textarea>
              <p class="mt-1 font-['Montserrat'] text-[12px] text-[#888888]">Не менее 10 символов</p>
              <FormFieldError :error="editForm.errors.description" />
            </div>
            <div>
              <label class="block font-['Montserrat'] text-[14px] text-black mb-2">Цена, ₽</label>
              <input v-model.number="editForm.price" type="number" step="0.01" min="0.01" required :class="editWithFieldError('price', INPUT_BASE)" />
              <p class="mt-1 font-['Montserrat'] text-[12px] text-[#888888]">Больше 0</p>
              <FormFieldError :error="editForm.errors.price" />
            </div>
            <div>
              <label class="block font-['Montserrat'] text-[14px] text-black mb-2">Количество на складе</label>
              <input v-model.number="editForm.quantity" type="number" min="0" required :class="editWithFieldError('quantity', INPUT_BASE)" />
              <FormFieldError :error="editForm.errors.quantity" />
              <p class="mt-1 font-['Montserrat'] text-[12px] text-[#888888]">
                Сейчас: {{ editForm.quantity > 0 ? 'В наличии' : 'Закончился' }}
              </p>
            </div>
          </div>
          <div class="flex gap-3 justify-end mt-6">
            <button type="button" class="px-4 py-2 font-['Montserrat'] text-[14px] text-gray-600 hover:text-gray-800" @click="closeEditModal">
              Отмена
            </button>
            <button type="submit" :disabled="editForm.processing" class="px-4 py-2 bg-[#2E7D32] text-white font-['Montserrat'] text-[14px] rounded hover:bg-[#1b5e2b] disabled:opacity-50">
              {{ editForm.processing ? 'Сохранение...' : 'Сохранить' }}
            </button>
          </div>
        </form>
      </div>
    </Modal>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import Breadcrumb from '@/Components/Breadcrumb.vue'
import Notification from '@/Components/Notification.vue'
import FormFieldError from '@/Components/FormFieldError.vue'
import FormErrorsSummary from '@/Components/FormErrorsSummary.vue'
import Modal from '@/Components/Modal.vue'
import { useAdminFormErrors } from '@/composables/useAdminFormErrors'
import { Link, router, useForm } from '@inertiajs/vue3'

const INPUT_BASE = "w-full border border-gray-300 rounded-lg px-3 py-2 font-['Montserrat'] text-[14px] focus:outline-none focus:ring-2 focus:ring-[#2E7D32]"

const PRODUCT_FIELD_LABELS = {
  name: 'Название',
  article: 'Артикул',
  category_ids: 'Категории',
  price: 'Цена',
  description: 'Описание',
  quantity: 'Количество',
  main_image: 'Изображение',
  care_difficulty: 'Сложность ухода',
  size: 'Размер',
  age_group: 'Возраст',
}

const props = defineProps({
  products: Object,
  categories: Array,
  adminSort: { type: String, default: 'name' },
  adminSortDir: { type: String, default: 'asc' },
})

const showAddModal = ref(false)
const showEditModal = ref(false)
const editingProductId = ref(null)
const addModalBody = ref(null)
const editModalBody = ref(null)

const addForm = useForm({
  name: '',
  article: '',
  category_ids: [],
  price: 0,
  description: '',
  quantity: 0,
  main_image: '',
  care_difficulty: null,
  size: null,
  age_group: null,
})

const editForm = useForm({
  name: '',
  price: 0,
  quantity: 0,
  main_image: '',
  description: '',
  category_ids: [],
  care_difficulty: null,
  size: null,
  age_group: null,
})

const { errorSummary: addErrorSummary, withFieldError: addWithFieldError, modalSubmitOptions: addModalSubmitOptions } =
  useAdminFormErrors(addForm, PRODUCT_FIELD_LABELS)

const { errorSummary: editErrorSummary, withFieldError: editWithFieldError, modalSubmitOptions: editModalSubmitOptions } =
  useAdminFormErrors(editForm, PRODUCT_FIELD_LABELS)

const openAddModal = () => {
  addForm.clearErrors()
  showAddModal.value = true
}

const closeAddModal = () => {
  showAddModal.value = false
  addForm.clearErrors()
}

const closeEditModal = () => {
  showEditModal.value = false
  editForm.clearErrors()
}

const sortHref = (col, dir) => {
  const params = new URLSearchParams({ admin_sort: col, dir })
  return `/admin/products?${params.toString()}`
}

const sortBtnClass = (col, dir) => {
  const active = props.adminSort === col && props.adminSortDir === dir
  return active
    ? 'bg-[#2E7D32] text-white border-[#2E7D32] font-semibold shadow-sm'
    : 'bg-white text-[#2E7D32] border-[#A5D6A7] hover:bg-[#E8F5E9]'
}

const openEditModal = (product) => {
  editingProductId.value = product.id
  editForm.clearErrors()
  editForm.name = product.name
  editForm.price = product.price
  editForm.quantity = product.quantity
  editForm.main_image = product.main_image
  editForm.description = product.description
  editForm.care_difficulty = product.care_difficulty ?? null
  editForm.size = product.size ?? null
  editForm.age_group = product.age_group ?? null
  editForm.category_ids = (product.categories && product.categories.length
    ? product.categories.map((c) => c.id)
    : (product.category_id ? [product.category_id] : []))
  showEditModal.value = true
}

const submitAddProduct = () => {
  addForm.post('/admin/products', addModalSubmitOptions(showAddModal, addModalBody, () => {
    showAddModal.value = false
    addForm.reset()
    addForm.category_ids = []
  }))
}

const submitEditProduct = () => {
  editForm.patch(`/admin/products/${editingProductId.value}`, editModalSubmitOptions(showEditModal, editModalBody, () => {
    showEditModal.value = false
    editingProductId.value = null
  }))
}

const deleteProduct = (id) => {
  if (confirm('Удалить товар?')) {
    router.delete(`/admin/products/${id}`, {
      preserveScroll: true,
    })
  }
}
</script>
