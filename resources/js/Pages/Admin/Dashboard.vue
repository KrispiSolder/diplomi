<template>
  <AppLayout>
    <div class="page-container pt-3 md:pt-[12px] pb-6 md:pb-[30px]">
      <div class="flex items-center justify-between mb-[20px]">
        <Breadcrumb admin :breadcrumbs="['Главная страница', 'Личный кабинет', 'Админ панель', 'Аналитика']" />
        <Link href="/cabinet" class="font-['Montserrat'] text-[14px] text-[#2E7D32] hover:underline">← В кабинет</Link>
      </div>

      <div class="flex flex-col lg:flex-row gap-4 md:gap-[20px]">
        <aside class="w-[220px] space-y-[12px]">
          <Link href="/admin/dashboard" class="block font-['Montserrat'] text-[16px] text-black bg-gray-100 px-[15px] py-[10px] rounded hover:bg-[#2E7D32] hover:text-white">
            Аналитика
          </Link>
          <Link href="/admin/categories" class="block font-['Montserrat'] text-[16px] text-black bg-gray-100 px-[15px] py-[10px] rounded hover:bg-[#2E7D32] hover:text-white">
            Категории
          </Link>
          <Link href="/admin/products" class="block font-['Montserrat'] text-[16px] text-black bg-gray-100 px-[15px] py-[10px] rounded hover:bg-[#2E7D32] hover:text-white">
            Товары
          </Link>
          <Link href="/admin/reviews" class="block font-['Montserrat'] text-[16px] text-black bg-gray-100 px-[15px] py-[10px] rounded hover:bg-[#2E7D32] hover:text-white">
            Отзывы
          </Link>
          <Link href="/admin/orders" class="block font-['Montserrat'] text-[16px] text-black bg-gray-100 px-[15px] py-[10px] rounded hover:bg-[#2E7D32] hover:text-white">
            Заказы
          </Link>
          <Link href="/admin/promo-codes" class="block font-['Montserrat'] text-[16px] text-black bg-gray-100 px-[15px] py-[10px] rounded hover:bg-[#2E7D32] hover:text-white">
            Промокоды
          </Link>
        </aside>

        <div class="flex-1 space-y-[24px]">
          <div class="grid grid-cols-1 xl:grid-cols-2 gap-[20px]">
            <div class="bg-white rounded-lg p-[20px] shadow">
              <h3 class="font-['Montserrat'] font-bold text-[16px] mb-[10px]">Остатки на складе (топ)</h3>
              <canvas ref="stockChart" class="w-full max-h-[320px]"></canvas>
            </div>
            <div class="bg-white rounded-lg p-[20px] shadow">
              <h3 class="font-['Montserrat'] font-bold text-[16px] mb-[10px]">Популярность за месяц</h3>
              <canvas ref="popularChart" class="w-full max-h-[320px]"></canvas>
            </div>
          </div>

          <div class="bg-white rounded-lg p-[20px] shadow">
            <h3 class="font-['Montserrat'] font-bold text-[16px] mb-[10px]">Заказы за неделю</h3>
            <canvas ref="weekChart" class="w-full max-h-[320px]"></canvas>
            <p class="font-['Montserrat'] text-[13px] text-[#666666] mt-[10px]">
              Вчера: {{ ordersYesterday }} · Сегодня: {{ ordersToday }}
            </p>
          </div>

          <div class="bg-white rounded-lg p-[20px] shadow">
            <div class="flex items-center justify-between gap-[12px] mb-[10px]">
              <h3 class="font-['Montserrat'] font-bold text-[16px]">Мало на складе (≤ 5)</h3>
              <button
                type="button"
                class="font-['Montserrat'] text-[13px] text-[#2E7D32] hover:underline"
                @click="downloadLowStockPdf"
              >
                Скачать PDF
              </button>
            </div>

            <div class="overflow-x-auto">
              <table class="w-full border-collapse">
                <thead>
                  <tr class="bg-gray-50 border-b">
                    <th class="text-left font-['Montserrat'] text-[13px] px-[10px] py-[8px]">Товар</th>
                    <th class="text-left font-['Montserrat'] text-[13px] px-[10px] py-[8px]">Остаток</th>
                    <th class="text-left font-['Montserrat'] text-[13px] px-[10px] py-[8px]">Цена</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="p in lowStockProducts" :key="p.id" class="border-b">
                    <td class="font-['Montserrat'] text-[13px] px-[10px] py-[8px]">{{ p.name }}</td>
                    <td class="font-['Montserrat'] text-[13px] px-[10px] py-[8px]">{{ p.quantity }}</td>
                    <td class="font-['Montserrat'] text-[13px] px-[10px] py-[8px]">{{ p.price }} ₽</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Breadcrumb from '@/Components/Breadcrumb.vue'
import { downloadExport } from '@/utils/downloadExport'
import { Chart, BarController, BarElement, CategoryScale, LinearScale, Tooltip, Legend, Title } from 'chart.js'

Chart.register(BarController, BarElement, CategoryScale, LinearScale, Tooltip, Legend, Title)

const props = defineProps({
  chartStock: { type: Array, default: () => [] },
  chartPopular: { type: Array, default: () => [] },
  chartWeekLabels: { type: Array, default: () => [] },
  chartWeekValues: { type: Array, default: () => [] },
  lowStockProducts: { type: Array, default: () => [] },
  ordersToday: { type: Number, default: 0 },
  ordersYesterday: { type: Number, default: 0 },
})

const stockChart = ref(null)
const popularChart = ref(null)
const weekChart = ref(null)

const downloadLowStockPdf = () => {
  downloadExport('/admin/orders/low-stock-pdf', {
    emptyMessage: 'Пустой отчёт. Нет товаров с низким остатком.',
    filename: 'low-stock.pdf',
  })
}

onMounted(() => {
  if (stockChart.value) {
    new Chart(stockChart.value, {
      type: 'bar',
      data: {
        labels: props.chartStock.map((i) => i.label),
        datasets: [
          {
            label: 'Кол-во',
            data: props.chartStock.map((i) => i.value),
            backgroundColor: '#2E7D32',
          },
        ],
      },
      options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { x: { ticks: { maxRotation: 45, minRotation: 0 } } },
      },
    })
  }

  if (popularChart.value) {
    new Chart(popularChart.value, {
      type: 'bar',
      data: {
        labels: props.chartPopular.map((i) => i.label),
        datasets: [
          {
            label: 'Заказано (шт.)',
            data: props.chartPopular.map((i) => i.value),
            backgroundColor: '#8BC34A',
          },
        ],
      },
      options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { x: { ticks: { maxRotation: 45, minRotation: 0 } } },
      },
    })
  }

  if (weekChart.value) {
    new Chart(weekChart.value, {
      type: 'bar',
      data: {
        labels: props.chartWeekLabels,
        datasets: [
          {
            label: 'Заказы',
            data: props.chartWeekValues.map((v) => Math.round(Number(v))),
            backgroundColor: '#1B5E20',
          },
        ],
      },
      options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              stepSize: 1,
              precision: 0,
              callback: (value) => (Number.isInteger(value) ? value : ''),
            },
          },
        },
      },
    })
  }
})
</script>
