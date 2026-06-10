<template>
  <Modal :show="show" @close="$emit('close')" max-width="lg">
    <div class="p-6">
      <h2 class="font-['Montserrat'] text-[20px] font-bold text-black mb-4">Оставить отзыв</h2>

      <form @submit.prevent="submit">
        <div class="mb-4">
          <label class="block font-['Montserrat'] text-[14px] text-black mb-2">Оценка</label>
          <div class="flex gap-2">
            <button
              v-for="i in 5"
              :key="i"
              type="button"
              @click="form.rating = i"
              class="focus:outline-none"
            >
              <svg class="w-8 h-8" :fill="i <= form.rating ? '#FFA500' : '#CCCCCC'" viewBox="0 0 20 20">
                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
              </svg>
            </button>
          </div>
        </div>

        <div class="mb-4">
          <label class="block font-['Montserrat'] text-[14px] text-black mb-2">Комментарий</label>
          <textarea
            v-model="form.content"
            rows="4"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 font-['Montserrat'] text-[14px] focus:outline-none focus:ring-2 focus:ring-[#2E7D32]"
            placeholder="Напишите ваш отзыв..."
            required
          ></textarea>
        </div>

        <div class="flex gap-3 justify-end">
          <button
            type="button"
            @click="$emit('close')"
            class="px-4 py-2 font-['Montserrat'] text-[14px] text-gray-600 hover:text-gray-800"
          >
            Отмена
          </button>
          <button
            type="submit"
            :disabled="form.processing"
            class="px-4 py-2 bg-[#2E7D32] text-white font-['Montserrat'] text-[14px] rounded hover:bg-[#1b5e2b] disabled:opacity-50"
          >
            {{ form.processing ? 'Отправка...' : 'Отправить' }}
          </button>
        </div>
      </form>
    </div>
  </Modal>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3'
import Modal from '@/Components/Modal.vue'

const props = defineProps({
  show: Boolean,
  productId: Number,
})

const emit = defineEmits(['close'])

const form = useForm({
  product_id: props.productId,
  rating: 5,
  content: '',
})

const submit = () => {
  form.post('/reviews', {
    preserveScroll: true,
    onSuccess: () => {
      emit('close')
      form.reset()
    },
  })
}
</script>

