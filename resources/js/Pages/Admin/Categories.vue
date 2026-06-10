<template>
  <AppLayout>
    <div class="page-container pt-3 md:pt-[12px] pb-6 md:pb-[30px]">
      <div class="flex flex-wrap items-center justify-between gap-[12px] mb-[20px]">
        <Breadcrumb admin :breadcrumbs="['Главная страница', 'Личный кабинет', 'Админ панель', 'Категории']" />
        <Link href="/admin/dashboard" class="font-['Montserrat'] text-[14px] text-[#2E7D32] hover:underline">
          ← Назад в аналитику
        </Link>
      </div>

      <div class="flex justify-between items-center mb-[20px]">
        <h1 class="font-['Montserrat'] text-[20px] text-black font-normal">Категории</h1>
        <button type="button" class="bg-[#2E7D32] text-white font-['Montserrat'] text-[14px] px-[20px] py-[10px] rounded hover:bg-[#1b5e2b]" @click="openCreate">
          Добавить категорию
        </button>
      </div>

      <Notification />

      <div class="overflow-x-auto bg-white rounded-lg shadow">
        <table class="w-full border-collapse">
          <thead>
            <tr class="bg-gray-100 border-b">
              <th class="text-left font-['Montserrat'] text-[14px] px-[12px] py-[10px]">Название</th>
              <th class="text-left font-['Montserrat'] text-[14px] px-[12px] py-[10px]">Slug</th>
              <th class="text-left font-['Montserrat'] text-[14px] px-[12px] py-[10px]">Родитель</th>
              <th class="text-left font-['Montserrat'] text-[14px] px-[12px] py-[10px]">Активна</th>
              <th class="text-left font-['Montserrat'] text-[14px] px-[12px] py-[10px]">Действия</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="cat in categories.data" :key="cat.id" class="border-b hover:bg-gray-50">
              <td class="font-['Montserrat'] text-[14px] px-[12px] py-[10px]">{{ cat.name }}</td>
              <td class="font-['Montserrat'] text-[13px] px-[12px] py-[10px] text-[#666666]">{{ cat.slug }}</td>
              <td class="font-['Montserrat'] text-[13px] px-[12px] py-[10px]">{{ cat.parent?.name || '—' }}</td>
              <td class="font-['Montserrat'] text-[13px] px-[12px] py-[10px]">{{ cat.is_active ? 'да' : 'нет' }}</td>
              <td class="font-['Montserrat'] text-[13px] px-[12px] py-[10px] space-x-2">
                <button type="button" class="text-[#2E7D32] hover:underline" @click="openEdit(cat)">Изменить</button>
                <button type="button" class="text-red-600 hover:underline" @click="remove(cat.id)">Удалить</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <Modal :show="showModal" max-width="lg" @close="closeModal">
      <div ref="modalBody" class="p-6 space-y-4 max-h-[85vh] overflow-y-auto">
        <h2 class="font-['Montserrat'] text-[18px] font-bold">{{ editingId ? 'Редактировать' : 'Создать' }} категорию</h2>

        <FormErrorsSummary :items="errorSummary" />

        <div>
          <label class="block font-['Montserrat'] text-[13px] mb-1 font-semibold">Название *</label>
          <input v-model="form.name" type="text" :class="inputClass('name')" />
          <FormFieldError :error="form.errors.name" />
        </div>

        <div>
          <label class="block font-['Montserrat'] text-[13px] mb-1">Slug (латиница; пусто — автогенерация)</label>
          <input v-model="form.slug" type="text" :class="inputClass('slug')" />
          <FormFieldError :error="form.errors.slug" />
        </div>

        <div>
          <label class="block font-['Montserrat'] text-[13px] mb-1">Родительская категория</label>
          <select v-model="form.parent_id" :class="inputClass('parent_id')">
            <option :value="null">— корневая —</option>
            <option v-for="p in parentOptions" :key="p.id" :value="p.id" :disabled="p.id === editingId">
              {{ p.name }}
            </option>
          </select>
          <FormFieldError :error="form.errors.parent_id" />
        </div>

        <div>
          <label class="flex items-center gap-2 font-['Montserrat'] text-[13px]">
            <input v-model="form.is_active" type="checkbox" class="rounded border-gray-300 text-[#2E7D32]" />
            Видна в каталоге
          </label>
          <FormFieldError :error="form.errors.is_active" />
        </div>

        <div>
          <label class="block font-['Montserrat'] text-[13px] mb-1">Описание</label>
          <textarea v-model="form.description" rows="3" :class="inputClass('description')"></textarea>
          <FormFieldError :error="form.errors.description" />
        </div>

        <div class="flex justify-end gap-2">
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
import { ref } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import Breadcrumb from '@/Components/Breadcrumb.vue'
import Notification from '@/Components/Notification.vue'
import FormFieldError from '@/Components/FormFieldError.vue'
import FormErrorsSummary from '@/Components/FormErrorsSummary.vue'
import Modal from '@/Components/Modal.vue'
import { useAdminFormErrors } from '@/composables/useAdminFormErrors'
import { Link, router, useForm } from '@inertiajs/vue3'

defineProps({
  categories: Object,
  parentOptions: Array,
})

const FIELD_LABELS = {
  name: 'Название',
  slug: 'Slug',
  parent_id: 'Родительская категория',
  is_active: 'Видимость',
  description: 'Описание',
}

const showModal = ref(false)
const editingId = ref(null)
const modalBody = ref(null)

const form = useForm({
  name: '',
  slug: '',
  parent_id: null,
  is_active: true,
  description: '',
})

const { errorSummary, inputClass, modalSubmitOptions } = useAdminFormErrors(form, FIELD_LABELS)

const closeModal = () => {
  showModal.value = false
  form.clearErrors()
}

const openCreate = () => {
  editingId.value = null
  form.reset()
  form.clearErrors()
  form.is_active = true
  form.parent_id = null
  showModal.value = true
}

const openEdit = (cat) => {
  editingId.value = cat.id
  form.clearErrors()
  form.name = cat.name
  form.slug = cat.slug
  form.parent_id = cat.parent_id
  form.is_active = !!cat.is_active
  form.description = cat.description || ''
  showModal.value = true
}

const submit = () => {
  const opts = modalSubmitOptions(showModal, modalBody)
  if (editingId.value) {
    form.patch(`/admin/categories/${editingId.value}`, opts)
  } else {
    form.post('/admin/categories', opts)
  }
}

const remove = (id) => {
  if (!confirm('Удалить категорию?')) return
  router.delete(`/admin/categories/${id}`, { preserveScroll: true })
}
</script>
