<template>
  <AppLayout>
    <Breadcrumb :breadcrumbs="['Главная страница', 'Оплата']" />
    <Notification />

    <div class="page-container py-10 md:py-16 max-w-xl mx-auto text-center">
      <div class="bg-white rounded-xl shadow p-8 space-y-4">
        <div class="w-16 h-16 mx-auto rounded-full bg-[#E8F5E9] flex items-center justify-center">
          <svg class="w-8 h-8 text-[#2E7D32]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
          </svg>
        </div>
        <h1 class="font-['Montserrat'] text-[24px] font-bold text-black">Оплата принята</h1>
        <p v-if="order" class="font-['Montserrat'] text-[15px] text-[#666666]">
          Заказ #{{ order.id }} — статус: <strong>{{ order.status }}</strong>
          <span v-if="order.payment_status"> ({{ order.payment_status }})</span>
        </p>
        <p v-else class="font-['Montserrat'] text-[15px] text-[#666666]">
          Спасибо! Заказ появится в личном кабинете после подтверждения платежа.
        </p>
        <div class="flex flex-col sm:flex-row gap-3 justify-center pt-4">
          <Link href="/cabinet" class="px-6 py-3 bg-[#2E7D32] text-white font-['Montserrat'] font-semibold rounded-lg hover:bg-[#1b5e2b]">
            Личный кабинет
          </Link>
          <Link href="/" class="px-6 py-3 border border-gray-300 font-['Montserrat'] text-[#333333] rounded-lg hover:bg-gray-50">
            На главную
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
