<template>
  <AppLayout>
    <section class="relative h-[360px] sm:h-[520px] md:h-[700px] lg:h-[870px]">
      <img src="/images/1.png" alt="" class="absolute inset-0 w-full h-full object-cover" />
      <div class="relative z-10 flex h-full flex-col items-center justify-start px-2 pt-[80px] pb-10 sm:pt-[100px] md:pt-[120px]">
        <h1 class="font-['Vetrino'] text-center text-[52px] leading-none text-[#2E7D32] sm:text-[90px] md:-translate-y-[30px] md:text-[120px] lg:text-[140px]">
          Plantform
        </h1>
      </div>
    </section>

    <section class="page-container py-10 md:py-20">
      <h2 class="section-title mb-10 md:mb-[87px]">Преимущества</h2>

      <div class="mb-12 flex flex-col gap-10 md:mb-[100px] md:flex-row md:items-center md:justify-between md:gap-12 lg:gap-[100px]">
        <div class="space-y-8 text-center md:flex-1 md:space-y-[50px] md:text-left">
          <div v-for="item in advantagesLeft" :key="item.title">
            <h3 class="mb-2 font-['Montserrat'] text-[22px] font-bold text-[#2E7D32] md:text-[32px]">{{ item.title }}</h3>
            <p class="font-['Montserrat'] text-[15px] text-[#888888] md:text-[20px]">{{ item.text }}</p>
          </div>
        </div>

        <div class="mx-auto w-full max-w-[280px] shrink-0 md:max-w-[360px] lg:max-w-[493px]">
          <img src="/images/2.png" alt="Комнатные растения" class="w-full rounded-lg" />
        </div>

        <div class="space-y-8 text-center md:flex-1 md:space-y-[50px] md:text-right">
          <div v-for="item in advantagesRight" :key="item.title">
            <h3 class="mb-2 font-['Montserrat'] text-[22px] font-bold text-[#2E7D32] md:text-[32px]">{{ item.title }}</h3>
            <p class="font-['Montserrat'] text-[15px] text-[#888888] md:text-[20px]">{{ item.text }}</p>
          </div>
        </div>
      </div>
    </section>

    <section class="mb-10 md:mb-20">
      <div
        class="page-container flex min-h-[280px] flex-col gap-4 rounded-xl bg-cover bg-center py-6 md:min-h-[426px] md:flex-row md:items-center md:justify-center md:gap-10 md:rounded-none md:py-10"
        style="background-image: url('/images/3.jpg')"
      >
        <article
          v-for="card in promoCards"
          :key="card.slug"
          class="relative flex min-h-[200px] w-full shrink-0 flex-col justify-between rounded-xl bg-black/60 p-[18px] text-white md:h-[234px] md:w-[526px]"
        >
          <div class="pr-[100px] md:pr-[190px]">
            <h3 class="mb-2 font-['Montserrat'] text-[22px] md:text-[30px]">{{ card.title }}</h3>
            <p class="font-['Montserrat'] text-[14px] md:text-[16px]">{{ card.text }}</p>
          </div>
          <Link
            :href="`/catalog?category=${card.slug}`"
            class="mt-3 w-fit rounded-[20px] bg-white px-[15px] py-2 font-['Montserrat'] text-[12px] font-bold text-black hover:bg-gray-100"
          >
            В каталог →
          </Link>
          <img
            :src="card.image"
            :alt="card.title"
            class="absolute right-3 top-3 h-[100px] w-[90px] rounded object-cover md:right-[18px] md:top-[18px] md:h-[198px] md:w-[170px]"
          />
        </article>
      </div>
    </section>

    <section class="page-container mb-10 md:mb-20">
      <h2 class="section-title mb-8 md:mb-[77px]">Популярные категории</h2>

      <div v-if="props.categories.length" class="mb-6 flex flex-wrap justify-center gap-4 sm:justify-end sm:gap-12 md:mb-5">
        <button
          v-for="category in props.categories.slice(0, 3)"
          :key="category.id"
          type="button"
          class="border-b-2 pb-1.5 font-['Montserrat'] text-[15px] md:text-[20px]"
          :class="isActiveCategory(category.id) ? 'border-[#2E7D32] text-[#2E7D32]' : 'border-transparent text-[#666666] hover:border-[#2E7D32]'"
          @click="selectCategory(category.id)"
        >
          {{ category.name }}
        </button>
      </div>

      <div v-if="popularProducts.length" class="grid grid-cols-2 justify-items-center gap-4 md:grid-cols-3 md:gap-10 lg:grid-cols-4 lg:gap-[84px]">
        <ProductCard
          v-for="product in popularProducts.slice(0, 4)"
          :key="product.id"
          :product="product"
          :is-favorite="props.favorites.includes(product.id)"
        />
      </div>
      <p v-else class="text-center font-['Montserrat'] text-[15px] text-[#888888]">
        В выбранной категории пока нет товаров в наличии
      </p>
    </section>

    <section id="about" class="page-container py-10 md:py-20">
      <h2 class="mb-8 text-center font-['Vetrino'] text-[32px] text-[#093816] sm:text-[42px] md:mb-[70px] md:text-[55px]">О нас</h2>

      <div class="flex flex-col items-start gap-6 lg:flex-row lg:gap-10">
        <div class="w-full shrink-0 lg:w-[42%]">
          <img
            src="/images/6.jpg"
            alt="Уход за комнатными растениями"
            class="h-[280px] w-full rounded-lg object-cover sm:h-[360px] lg:h-[520px] xl:h-[600px]"
          />
        </div>

        <div class="min-w-0 flex-1">
          <h3 class="mb-4 leading-tight md:mb-5">
            <span class="block font-['Vetrino'] text-[28px] text-[#2E7D32] sm:inline sm:text-[36px] md:text-[44px]">Plantform</span>
            <span class="mt-1 block font-['Montserrat'] text-[14px] font-medium uppercase tracking-wide text-[#1a1a1a] sm:mt-0 sm:inline sm:text-lg md:text-[22px]">
              — ваш проводник в мир домашних растений
            </span>
          </h3>

          <p class="mb-5 font-['Montserrat'] text-[14px] leading-relaxed text-[#222222] md:mb-6 md:text-[17px]">
            Наша история началась в 2019 году, когда основательница компании, Мария, решила превратить свою любовь к зелени и уходу за растениями в дело жизни. Вдохновлённая идеей сделать домашние растения доступными и понятными для каждого, она запустила Plantform — как цифровой магазин, где можно не просто купить растение, а подобрать его под свой интерьер, уровень ухода и даже настроение.
          </p>

          <div class="flex flex-col items-start gap-5 sm:flex-row sm:items-end md:gap-7">
            <div class="flex-1 space-y-4 font-['Montserrat'] text-[14px] leading-relaxed text-[#222222] md:text-[17px]">
              <p>
                Сначала это был скромный ассортимент из нескольких видов кактусов и фикусов, но благодаря искренней заботе о клиентах и качественному сервису, магазин быстро завоевал популярность.
              </p>
              <p>
                Сегодня Plantform — это не просто магазин, а сообщество зелёных энтузиастов, где каждый может найти своё растение и научиться его любить. Ведь растения — это не просто декор, а живые спутники нашей повседневной жизни.
              </p>
            </div>

            <img
              src="/images/7.jpg"
              alt="Комнатные растения Plantform"
              class="h-[220px] w-full shrink-0 rounded-lg object-cover sm:w-[180px] sm:h-[260px] md:h-[300px] md:w-[220px]"
            />
          </div>
        </div>
      </div>
    </section>
  </AppLayout>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import ProductCard from '@/Components/ProductCard.vue'
import { Link } from '@inertiajs/vue3'

const props = defineProps({
  products: { type: Array, default: () => [] },
  categories: { type: Array, default: () => [] },
  favorites: { type: Array, default: () => [] },
})

const advantagesLeft = [
  { title: 'Бесплатная доставка', text: 'При заказе от 3000 рублей' },
  { title: 'Гарантия 30 дней', text: 'На растения категории «Суккуленты»' },
]

const advantagesRight = [
  { title: 'Поддержка 24/7', text: 'Всегда на связи для вас' },
  { title: 'Честность', text: 'Соответствие цены и качества' },
]

const promoCards = [
  {
    slug: 'monstera',
    title: 'Монстера',
    text: 'Красивое и элегантное растение',
    image: '/images/4.png',
  },
  {
    slug: 'succulents-and-cacti',
    title: 'Суккуленты',
    text: 'Неприхотливые и красивые растения',
    image: '/images/5.png',
  },
]

const activeCategoryId = ref(null)

const sameId = (a, b) => Number(a) === Number(b)

const selectCategory = (id) => {
  activeCategoryId.value = id
}

const isActiveCategory = (id) => sameId(activeCategoryId.value, id)

watch(
  () => props.categories,
  (list) => {
    if (!list?.length) {
      activeCategoryId.value = null
      return
    }
    if (!list.some((c) => sameId(c.id, activeCategoryId.value))) {
      activeCategoryId.value = list[0].id
    }
  },
  { immediate: true },
)

const popularProducts = computed(() => {
  const list = props.products ?? []

  if (!activeCategoryId.value) {
    return list
  }

  return list.filter((product) =>
    product.categories?.some((category) => sameId(category.id, activeCategoryId.value)),
  )
})
</script>
