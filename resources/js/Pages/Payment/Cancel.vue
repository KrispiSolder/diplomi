<template>
  <AppLayout>
    <Breadcrumb :breadcrumbs="['Главная страница', 'Оплата']" />
    <Notification />

    <div class="page-container py-10 md:py-16 max-w-xl mx-auto text-center">
      <div class="bg-white rounded-xl shadow p-8 space-y-4">
        <div class="w-16 h-16 mx-auto rounded-full bg-red-50 flex items-center justify-center">
          <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </div>
        <h1 class="font-['Montserrat'] text-[24px] font-bold text-black">Оплата не завершена</h1>
        <p class="font-['Montserrat'] text-[15px] text-[#666666]">
          <template v-if="order">
            Заказ #{{ order.id }} отменён или ожидает повторной оплаты. Товары остались в корзине — вы можете оформить заказ снова.
          </template>
          <template v-else>
            Платёж был отменён. Вы можете вернуться в корзину и попробовать снова.
          </template>
        </p>
        <div class="flex flex-col sm:flex-row gap-3 justify-center pt-4">
          <Link href="/cart" class="px-6 py-3 bg-[#2E7D32] text-white font-['Montserrat'] font-semibold rounded-lg hover:bg-[#1b5e2b]">
            В корзину
          </Link>
          <Link href="/cabinet" class="px-6 py-3 border border-gray-300 font-['Montserrat'] text-[#333333] rounded-lg hover:bg-gray-50">
            Личный кабинет
          </Link>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import Breadcrumb from '@/Components/Breadcrumb.vue'
import Notification from '@/Components/Notification.vue'
import { Link } from '@inertiajs/vue3'

defineProps({
  order: { type: Object, default: null },
})
</script>
