<template>
  <div
    class="relative w-full aspect-square bg-cover bg-center rounded-[12px] group cursor-pointer"
    :class="compact ? 'max-w-[160px] sm:max-w-[180px] md:max-w-[200px]' : 'max-w-[270px]'"
    :style="{ backgroundImage: `url('${product.main_image}')` }"
  >
    <div
      v-if="unavailable"
      class="absolute top-[20px] left-[20px] z-10 bg-black/70 text-white font-['Montserrat'] text-[12px] px-[10px] py-[4px] rounded"
    >
      Нет в наличии
    </div>
    <Link
      :href="productHref"
      class="absolute inset-0 z-0"
    ></Link>
    <button
      v-if="!isAdmin"
      class="absolute top-[20px] right-[20px] z-10"
      @click.stop.prevent="toggleFavorite"
      :disabled="togglingFavorite"
    >
      <svg class="w-[20px] h-[20px]" :fill="isFavoriteLocal ? '#2E7D32' : 'white'" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
      </svg>
    </button>

    <Link
      :href="productHref"
      class="absolute bottom-0 w-full h-[70px] bg-black/50 flex items-center justify-between px-[20px] z-0"
    >
      <p class="font-['Montserrat'] text-white text-[14px] sm:text-[18px] md:text-[20px] truncate max-w-[55%]">{{ product.name }}</p>
      <p class="font-['Montserrat'] font-bold text-white text-[14px] sm:text-[18px] md:text-[20px] shrink-0">{{ product.price }} ₽</p>
    </Link>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import { isProductUnavailable } from '@/utils/productAvailability'

const props = defineProps({
  product: Object,
  isFavorite: {
    type: Boolean,
    default: false,
  },
  catalogFrom: {
    type: String,
    default: '',
  },
  compact: {
    type: Boolean,
    default: false,
  },
})

const productHref = computed(() => {
  const base = `/product/${props.product.id}`
  if (!props.catalogFrom) {
    return base
  }
  return `${base}?from=${encodeURIComponent(props.catalogFrom)}`
})

const unavailable = computed(() => isProductUnavailable(props.product))

const page = usePage()
const isAdmin = computed(() => {
  const role = page.props.auth?.user?.role
  return role === 'admin' || role === 'administrator'
})

const isFavoriteLocal = ref(props.isFavorite)
const togglingFavorite = ref(false)

watch(() => props.isFavorite, (newVal) => {
  isFavoriteLocal.value = newVal
})

const toggleFavorite = () => {
  const wasFavorite = isFavoriteLocal.value
  isFavoriteLocal.value = !wasFavorite
  togglingFavorite.value = true

  router.post(`/favorites/${props.product.id}`, {}, {
    preserveScroll: true,
    preserveState: true,
    onSuccess: () => {
      togglingFavorite.value = false
    },
    onError: () => {
      togglingFavorite.value = false
      isFavoriteLocal.value = wasFavorite
    },
    onFinish: () => {
      togglingFavorite.value = false
    },
  })
}
</script>
