<template>
  <AppLayout>
    <div class="page-container pt-6 md:pt-[40px] pb-8 md:pb-[40px]">
      <Breadcrumb
        compact
        class="mb-[8px]"
        :breadcrumbs="['Главная страница', 'Каталог', selectedCategory === 'all' ? 'Все товары' : selectedCategory]"
      />

      <button
        type="button"
        class="lg:hidden w-full mb-4 py-3 rounded-lg border border-[#2E7D32] text-[#2E7D32] font-['Montserrat'] text-[14px] font-semibold bg-[#F1F8E9]"
        @click="filtersOpen = !filtersOpen"
      >
        {{ filtersOpen ? 'Скрыть фильтры' : 'Показать фильтры' }}
      </button>

      <div class="flex flex-col lg:flex-row gap-6 lg:gap-[32px] items-start">
      <aside
        class="w-full lg:w-[280px] shrink-0 space-y-5 lg:space-y-[28px]"
        :class="filtersOpen ? 'block' : 'hidden lg:block'"
      >
        <div>
          <h3 class="font-['Montserrat'] font-bold text-[20px] text-black mb-[12px]">Категории</h3>
          <CategoryTree :tree="categoryTree" :active-slug="selectedCategory" @select="onSelectCategory" />
        </div>

        <div>
          <h3 class="font-['Montserrat'] font-bold text-[20px] text-black mb-[12px]">Сложность ухода</h3>
          <div class="flex flex-wrap gap-[8px]">
            <button
              v-for="opt in careOptions"
              :key="opt.value"
              type="button"
              class="px-[10px] py-[6px] rounded-full border text-[13px] font-['Montserrat']"
              :class="careSelected.includes(opt.value) ? 'border-[#2E7D32] bg-[#E8F5E9] text-[#2E7D32]' : 'border-gray-300 text-[#666666]'"
              @click="toggleMulti('care', opt.value)"
            >
              {{ opt.label }}
            </button>
          </div>
        </div>

        <div>
          <h3 class="font-['Montserrat'] font-bold text-[20px] text-black mb-[12px]">Размер</h3>
          <div class="flex flex-wrap gap-[8px]">
            <button
              v-for="opt in sizeOptions"
              :key="opt.value"
              type="button"
              class="px-[10px] py-[6px] rounded-full border text-[13px] font-['Montserrat']"
              :class="sizeSelected.includes(opt.value) ? 'border-[#2E7D32] bg-[#E8F5E9] text-[#2E7D32]' : 'border-gray-300 text-[#666666]'"
              @click="toggleMulti('size', opt.value)"
            >
              {{ opt.label }}
            </button>
          </div>
        </div>

        <div>
          <h3 class="font-['Montserrat'] font-bold text-[20px] text-black mb-[12px]">Возраст</h3>
          <div class="flex flex-wrap gap-[8px]">
            <button
              v-for="opt in ageOptions"
              :key="opt.value"
              type="button"
              class="px-[10px] py-[6px] rounded-full border text-[13px] font-['Montserrat']"
              :class="ageSelected.includes(opt.value) ? 'border-[#2E7D32] bg-[#E8F5E9] text-[#2E7D32]' : 'border-gray-300 text-[#666666]'"
              @click="toggleMulti('age', opt.value)"
            >
              {{ opt.label }}
            </button>
          </div>
        </div>

        <div>
          <h3 class="font-['Montserrat'] font-bold text-[20px] text-black mb-[12px]">Сортировка</h3>
          <div class="space-y-[8px] font-['Montserrat'] text-[14px]">
            <button type="button" class="block w-full text-left" :class="sort === 'popular' ? 'text-[#2E7D32] font-bold' : 'text-[#666666]'" @click="setSort('popular')">По популярности</button>
            <button type="button" class="block w-full text-left" :class="sort === 'price_asc' ? 'text-[#2E7D32] font-bold' : 'text-[#666666]'" @click="setSort('price_asc')">Цена: по возрастанию</button>
            <button type="button" class="block w-full text-left" :class="sort === 'price_desc' ? 'text-[#2E7D32] font-bold' : 'text-[#666666]'" @click="setSort('price_desc')">Цена: по убыванию</button>
            <button type="button" class="block w-full text-left" :class="sort === 'name' ? 'text-[#2E7D32] font-bold' : 'text-[#666666]'" @click="setSort('name')">По названию</button>
          </div>
        </div>
      </aside>

      <div class="flex-1 min-w-0">
        <div class="mb-[12px] flex flex-wrap items-center gap-[12px]">
          <input
            v-model="searchLocal"
            type="text"
            placeholder="Поиск по каталогу"
            class="flex-1 min-w-0 w-full h-[44px] rounded-[12px] border border-gray-300 px-[14px] font-['Montserrat'] text-[14px]"
            @keyup.enter="applyQuery"
          />
          <button type="button" class="bg-[#2E7D32] text-white px-[18px] h-[44px] rounded-[12px] font-['Montserrat'] text-[14px]" @click="applyQuery">
            Найти
          </button>
        </div>

        <h2 class="font-['Vetrino'] text-[28px] md:text-[40px] text-[#2E7D32] mb-[14px] leading-tight">Каталог</h2>

        <PromoBanner v-if="categoryPromo" :promo="categoryPromo" class="mb-[20px]" />

        <div class="grid grid-cols-2 sm:grid-cols-2 xl:grid-cols-3 gap-4 sm:gap-6 md:gap-10 justify-items-center">
          <ProductCard
            v-for="product in products.data"
            :key="product.id"
            :product="product"
            :is-favorite="favorites.includes(product.id)"
            :catalog-from="catalogFromUrl"
          />
        </div>

        <div v-if="products.last_page > 1" class="flex flex-wrap gap-[10px] justify-center mt-[40px]">
          <Link
            v-for="page in products.last_page"
            :key="page"
            :href="pageHref(page)"
            preserve-scroll
            :class="{ 'bg-[#2E7D32] text-white': page === products.current_page }"
            class="px-[12px] py-[8px] border border-[#2E7D32] text-[#2E7D32] rounded hover:bg-[#2E7D32] hover:text-white"
          >
            {{ page }}
          </Link>
        </div>
      </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import Breadcrumb from '@/Components/Breadcrumb.vue'
import ProductCard from '@/Components/ProductCard.vue'
import CategoryTree from '@/Components/CategoryTree.vue'
import PromoBanner from '@/Components/PromoBanner.vue'
import { Link, router } from '@inertiajs/vue3'

const props = defineProps({
  products: Object,
  categories: Array,
  categoryTree: { type: Array, default: () => [] },
  selectedCategory: String,
  search: String,
  favorites: Array,
  filters: { type: Object, default: () => ({}) },
  sort: { type: String, default: 'popular' },
  categoryPromo: { type: Object, default: null },
})

const filtersOpen = ref(false)
const searchLocal = ref(props.search || '')
watch(
  () => props.search,
  (v) => {
    searchLocal.value = v || ''
  }
)

const careSelected = ref([...(props.filters?.care_difficulty || [])])
const sizeSelected = ref([...(props.filters?.size || [])])
const ageSelected = ref([...(props.filters?.age_group || [])])
const sort = ref(props.sort || 'popular')

const catalogFromUrl = computed(() => {
  if (typeof window === 'undefined') {
    return '/catalog'
  }
  return window.location.pathname + window.location.search
})

watch(
  () => props.filters,
  (f) => {
    careSelected.value = [...(f?.care_difficulty || [])]
    sizeSelected.value = [...(f?.size || [])]
    ageSelected.value = [...(f?.age_group || [])]
  },
  { deep: true }
)

watch(
  () => props.sort,
  (v) => {
    sort.value = v || 'popular'
  }
)

const careOptions = [
  { value: 'easy', label: 'Лёгкий' },
  { value: 'medium', label: 'Средний' },
  { value: 'hard', label: 'Сложный' },
]

const sizeOptions = [
  { value: 'small', label: 'Малый' },
  { value: 'medium', label: 'Средний' },
  { value: 'large', label: 'Крупный' },
  { value: 'extra_large', label: 'Очень крупный' },
]

const ageOptions = [
  { value: 'young', label: 'Молодое' },
  { value: 'mature', label: 'Зрелое' },
  { value: 'old', label: 'Взрослое' },
]

function buildQuery(extra = {}) {
  const category = extra.category ?? props.selectedCategory ?? 'all'
  const params = {
    category,
    search: searchLocal.value ? searchLocal.value : undefined,
    sort: sort.value,
    care_difficulty: careSelected.value.length ? [...careSelected.value] : undefined,
    size: sizeSelected.value.length ? [...sizeSelected.value] : undefined,
    age_group: ageSelected.value.length ? [...ageSelected.value] : undefined,
    page: extra.page,
  }

  return Object.fromEntries(Object.entries(params).filter(([, v]) => v !== undefined && v !== null && v !== ''))
}

function applyQuery() {
  router.get('/catalog', buildQuery({ page: 1 }), { preserveScroll: true, replace: true })
}

function pageHref(page) {
  const q = new URLSearchParams()
  const data = buildQuery({ page })
  Object.entries(data).forEach(([k, v]) => {
    if (Array.isArray(v)) {
      v.forEach((item) => q.append(`${k}[]`, item))
    } else if (v !== undefined) {
      q.set(k, v)
    }
  })
  return `/catalog?${q.toString()}`
}

function onSelectCategory(slug) {
  const category = slug === 'all' ? 'all' : slug
  router.get('/catalog', buildQuery({ category, page: 1 }), { preserveScroll: true, replace: true })
}

function setSort(value) {
  sort.value = value
  applyQuery()
}

function toggleMulti(kind, value) {
  const map = {
    care: careSelected,
    size: sizeSelected,
    age: ageSelected,
  }
  const arr = map[kind].value
  const idx = arr.indexOf(value)
  if (idx >= 0) {
    arr.splice(idx, 1)
  } else {
    arr.push(value)
  }
  applyQuery()
}
</script>
