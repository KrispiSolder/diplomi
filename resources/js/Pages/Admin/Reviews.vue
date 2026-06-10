<template>
  <AppLayout>
    <div class="page-container pt-3 md:pt-[12px] pb-6 md:pb-[30px]">
      <div class="flex flex-wrap items-center justify-between gap-[12px] mb-[20px]">
        <Breadcrumb admin :breadcrumbs="['Главная страница', 'Личный кабинет', 'Админ панель', 'Отзывы']" />
        <Link href="/admin/dashboard" class="font-['Montserrat'] text-[14px] text-[#2E7D32] hover:underline">
          ← Назад в аналитику
        </Link>
      </div>

      <h1 class="font-['Montserrat'] text-[20px] text-black font-normal mb-[30px]">Управление отзывами</h1>

      <div class="space-y-[20px]">
        <div v-for="review in reviews.data" :key="review.id" class="bg-white rounded-lg p-[20px] shadow">
          <div class="flex justify-between items-start mb-[15px]">
            <div>
              <p class="font-['Montserrat'] font-bold text-[16px] text-black">{{ review.user?.name || 'Пользователь' }}</p>
              <p class="font-['Montserrat'] text-[14px] text-[#888888]">{{ review.product.name }}</p>
            </div>
            <div class="flex gap-[2px]">
              <svg v-for="i in 5" :key="i" class="w-4 h-4" :fill="i <= review.rating ? '#FFA500' : '#CCCCCC'" viewBox="0 0 20 20">
                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
              </svg>
            </div>
          </div>
          
          <p class="font-['Montserrat'] text-[14px] text-black mb-[15px]">{{ review.comment || review.content }}</p>

          <div v-if="!review.approved" class="flex gap-[10px]">
            <button class="bg-[#2E7D32] text-white font-['Montserrat'] text-[12px] px-[15px] py-[8px] rounded hover:bg-[#1b5e2b]" @click="approveReview(review.id)">
              Одобрить
            </button>
            <button class="bg-red-500 text-white font-['Montserrat'] text-[12px] px-[15px] py-[8px] rounded hover:bg-red-600" @click="rejectReview(review.id)">
              Отклонить
            </button>
          </div>
          <div v-else class="text-[#2E7D32] font-['Montserrat'] text-[12px]">Одобрено</div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import Breadcrumb from '@/Components/Breadcrumb.vue'
import { Link, router } from '@inertiajs/vue3'

defineProps({
  reviews: Object,
})

const approveReview = (id) => {
  router.patch(`/admin/reviews/${id}/approve`)
}

const rejectReview = (id) => {
  if (confirm('Удалить отзыв?')) {
    router.delete(`/admin/reviews/${id}`)
  }
}
</script>
