<template>
  <AppLayout>
    <div class="page-container pt-6 md:pt-[40px]">
      <div class="flex flex-wrap items-center justify-between gap-[12px] mb-[8px]">
        <Breadcrumb
          compact
          :breadcrumbs="['Главная страница', 'Каталог', 'Все товары', product.name]"
        />
        <Link
          :href="catalogBackUrl"
          class="font-['Montserrat'] text-[14px] text-[#2E7D32] hover:underline shrink-0"
        >
          ← Назад в каталог
        </Link>
      </div>
      <Notification />
    </div>

    <div class="page-container py-4 md:py-[16px] flex flex-col lg:flex-row gap-4 md:gap-[33px]">
      <!-- Left Column: Thumbnails -->
      <div v-if="product.images && product.images.length > 0" class="flex flex-row lg:flex-col gap-[10px] w-full lg:w-[90px] order-2 lg:order-1 overflow-x-auto lg:overflow-visible pb-1">
        <img v-for="(img, idx) in product.images" :key="idx" :src="img.image_url" class="w-[72px] h-[96px] lg:w-[90px] lg:h-[126px] rounded cursor-pointer hover:opacity-70 shrink-0" @click="selectedImage = idx" />
      </div>

      <!-- Center Column: Main Image -->
      <div class="w-full lg:w-[500px] h-[420px] sm:h-[560px] lg:h-[700px] bg-gray-200 rounded-lg overflow-hidden order-1 lg:order-2">
        <img :src="product.images && product.images[selectedImage] ? product.images[selectedImage].image_url : product.main_image" :alt="product.name" class="w-full h-full object-cover" />
      </div>

      <!-- Right Column: Product Info -->
      <div class="flex-1 min-w-0 order-3">
        <h1 class="font-['Montserrat'] text-[19px] font-medium text-black mb-[10px]">{{ product.name }}</h1>
        
        <div class="flex items-center gap-[10px] mb-[20px]">
          <div class="flex gap-[2px]">
            <svg v-for="i in 5" :key="i" class="w-4 h-4" :fill="i <= Math.floor(averageRating) ? '#FFA500' : '#CCCCCC'" viewBox="0 0 20 20">
              <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
            </svg>
          </div>
          <span class="font-['Montserrat'] text-[13px] text-[#666666]">{{ averageRating.toFixed(1) }} • {{ reviews.length }} оценок</span>
        </div>

        <!-- Characteristics -->
        <div class="space-y-[10px] mb-[20px] pb-[20px] border-b">
          <div class="flex items-center gap-[20px]">
            <span class="font-['Montserrat'] text-[12px] text-[#888888] w-[100px]">Артикул</span>
            <span class="flex-1 border-b border-dotted border-[#888888]"></span>
            <span class="font-['Montserrat'] text-[12px] text-black">{{ product.article }}</span>
          </div>
          <div class="flex items-center gap-[20px]">
            <span class="font-['Montserrat'] text-[12px] text-[#888888] w-[100px]">Категория</span>
            <span class="flex-1 border-b border-dotted border-[#888888]"></span>
            <span class="font-['Montserrat'] text-[12px] text-black">{{ primaryCategoryName }}</span>
          </div>
          <div class="flex items-center gap-[20px]">
            <span class="font-['Montserrat'] text-[12px] text-[#888888] w-[100px]">Наличие</span>
            <span class="flex-1 border-b border-dotted border-[#888888]"></span>
            <span class="font-['Montserrat'] text-[12px] text-black">
              {{ product.quantity > 0 ? `${product.quantity} шт.` : 'Закончился' }}
            </span>
          </div>
        </div>

        <button
          type="button"
          class="font-['Montserrat'] text-[12px] text-[#242429] bg-[#F2F2F2] px-[16px] py-[10px] rounded-[12px] hover:bg-gray-300 mb-[30px]"
          @click="showSpecsModal = true"
        >
          Характеристики и описание
        </button>

        <PromoBanner v-if="categoryPromo" :promo="categoryPromo" class="mb-[16px]" />

        <!-- Price Block -->
        <div class="w-full sm:w-[360px] lg:w-[300px] bg-white rounded-[10px] shadow-lg p-5 md:p-[35px] space-y-[20px]">
          <div class="flex items-baseline gap-[10px]">
            <svg class="w-[30px] h-[30px] text-[#2E7D32]" fill="currentColor" viewBox="0 0 20 20">
              <path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z" />
              <path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1V5a1 1 0 00-1-1H3zM14 7a1 1 0 00-1 1v6.05A2.5 2.5 0 0115.95 16H17a1 1 0 001-1v-5a1 1 0 00-.293-.707l-2-2A1 1 0 0015 7h-1z" />
            </svg>
            <span class="font-['Vetrino'] text-[28px] text-[#2E7D32] font-bold">{{ product.price }} ₽</span>
          </div>

          <div class="w-full">
            <p v-if="isAdmin" class="text-center font-['Montserrat'] text-[13px] text-[#888888] mb-[8px]">
              Не доступно администратору
            </p>

            <div v-else-if="cartItem" class="flex items-center justify-center gap-[14px]">
              <button
                type="button"
                class="w-[36px] h-[36px] rounded-full border border-gray-300 text-[#666666] hover:border-[#2E7D32] hover:text-[#2E7D32]"
                @click="changeCartQty(-1)"
              >
                −
              </button>
              <span class="font-['Montserrat'] text-[18px] text-black w-[40px] text-center">{{ cartItem.quantity }}</span>
              <button
                type="button"
                class="w-[36px] h-[36px] rounded-full border border-gray-300 text-[#666666] hover:border-[#2E7D32] hover:text-[#2E7D32]"
                @click="changeCartQty(1)"
              >
                +
              </button>
            </div>

            <div
              v-else-if="isUnavailable"
              class="w-full bg-gray-200 text-[#666666] font-['Montserrat'] font-medium text-[19px] py-[15px] rounded-[20px] text-center cursor-not-allowed"
            >
              Нет в наличии
            </div>
            <button
              v-else
              type="button"
              @click="addToCart"
              :disabled="addingToCart"
              class="w-full bg-[#2E7D32] text-white font-['Montserrat'] font-medium text-[19px] py-[15px] rounded-[20px] hover:bg-[#1b5e2b] disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {{ addingToCart ? 'Добавляем...' : 'Добавить в корзину' }}
            </button>
            <p v-if="stockMessage" class="text-center font-['Montserrat'] text-[12px] text-red-500 mt-[8px]">
              {{ stockMessage }}
            </p>
          </div>

          <button
            v-if="!isAdmin"
            type="button"
            @click="toggleFavorite"
            :disabled="togglingFavorite"
            class="w-full bg-[#8BC34A] text-white font-['Montserrat'] font-medium text-[19px] py-[15px] rounded-[20px] hover:bg-[#7bb340] disabled:opacity-50"
          >
            {{ isFavoriteLocal ? 'Удалить из избранного' : 'Добавить в избранное' }}
          </button>

          <div class="w-full">
            <button 
              @click="showReviewModal = true" 
              :disabled="!userCanReview" 
              class="w-full bg-[#2E942C] text-white font-['Montserrat'] font-medium text-[19px] py-[15px] rounded-[20px] hover:bg-[#23702a] disabled:opacity-50 disabled:cursor-not-allowed"
            >
              Оставить отзыв
            </button>
            <p v-if="!userCanReview" class="text-center font-['Montserrat'] text-[12px] text-[#888888] mt-[5px]">
              Отзыв можно оставить только после выполнения заказа с этим товаром
            </p>
          </div>

        </div>
      </div>
    </div>

    <!-- Reviews Section -->
    <section class="page-container py-10 md:py-[80px]">
      <h2 class="font-['Montserrat'] font-medium text-[24px] text-black mb-[15px]">Отзывы</h2>
      
      <div class="flex flex-wrap items-center gap-3 md:gap-[20px] mb-6 md:mb-[40px]">
        <span class="font-['Montserrat'] font-medium text-[24px] text-black">{{ averageRating.toFixed(1) }}</span>
        <div class="flex gap-[2px]">
          <svg v-for="i in 5" :key="i" class="w-5 h-5" :fill="i <= Math.floor(averageRating) ? '#FFA500' : '#CCCCCC'" viewBox="0 0 20 20">
            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
          </svg>
        </div>
        <span class="font-['Montserrat'] font-bold text-[16px] text-[#868695]">{{ reviews.length }} оценок</span>
        <a v-if="reviews.length > 3" href="#" class="ml-auto font-['Montserrat'] text-[14px] text-[#666666]">Показать больше ></a>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-[24px] lg:gap-[40px]">
        <div v-for="review in reviews.slice(0, 3)" :key="review.id" class="w-full min-h-[177px] bg-white rounded-[10px] shadow p-[20px] space-y-[10px]">
          <p class="font-['Montserrat'] font-medium text-[15px] text-black">{{ review.user?.name || 'Пользователь' }}</p>
          <div class="flex gap-[4px]">
            <svg v-for="i in 5" :key="i" class="w-4 h-4" :fill="i <= review.rating ? '#FFA500' : '#CCCCCC'" viewBox="0 0 20 20">
              <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
            </svg>
          </div>
          <p class="font-['Montserrat'] font-medium text-[12px] text-black">{{ review.comment || review.content }}</p>
        </div>
      </div>
    </section>

    <ReviewModal :show="showReviewModal" :product-id="product.id" @close="showReviewModal = false" />

    <Modal :show="showSpecsModal" max-width="lg" @close="showSpecsModal = false">
      <div class="p-6 max-h-[80vh] overflow-y-auto">
        <h3 class="font-['Montserrat'] text-[20px] font-bold text-black mb-[16px]">
          Характеристики и описание
        </h3>

        <dl class="space-y-[12px] font-['Montserrat'] text-[14px]">
          <div class="flex gap-[12px]">
            <dt class="text-[#888888] w-[160px] shrink-0">Артикул</dt>
            <dd class="text-black">{{ product.article }}</dd>
          </div>
          <div class="flex gap-[12px]">
            <dt class="text-[#888888] w-[160px] shrink-0">Категории</dt>
            <dd class="text-black">{{ allCategoriesLabel }}</dd>
          </div>
          <div class="flex gap-[12px]">
            <dt class="text-[#888888] w-[160px] shrink-0">Сложность ухода</dt>
            <dd class="text-black">{{ careLabel }}</dd>
          </div>
          <div class="flex gap-[12px]">
            <dt class="text-[#888888] w-[160px] shrink-0">Размер растения</dt>
            <dd class="text-black">{{ sizeLabel }}</dd>
          </div>
          <div class="flex gap-[12px]">
            <dt class="text-[#888888] w-[160px] shrink-0">Возрастная группа</dt>
            <dd class="text-black">{{ ageLabel }}</dd>
          </div>
          <div class="flex gap-[12px]">
            <dt class="text-[#888888] w-[160px] shrink-0">Наличие</dt>
            <dd class="text-black">
              {{ product.quantity > 0 ? `${product.quantity} шт. (В наличии)` : 'Закончился' }}
            </dd>
          </div>
          <div class="flex gap-[12px]">
            <dt class="text-[#888888] w-[160px] shrink-0">Цена</dt>
            <dd class="text-black">
              {{ product.price }} ₽
            </dd>
          </div>
        </dl>

        <h4 class="font-['Montserrat'] font-bold text-[16px] text-black mt-[24px] mb-[10px]">Описание</h4>
        <p class="font-['Montserrat'] text-[14px] text-[#333333] whitespace-pre-line leading-relaxed">
          {{ product.description }}
        </p>
      </div>
    </Modal>
  </AppLayout>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import Breadcrumb from '@/Components/Breadcrumb.vue'
import Notification from '@/Components/Notification.vue'
import ReviewModal from '@/Components/ReviewModal.vue'
import Modal from '@/Components/Modal.vue'
import PromoBanner from '@/Components/PromoBanner.vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import { isProductUnavailable, STOCK_EXHAUSTED_MESSAGE } from '@/utils/productAvailability'

const props = defineProps({
  product: Object,
  reviews: Array,
  averageRating: Number,
  isFavorite: Boolean,
  userCanReview: Boolean,
  cartQuantity: { type: Number, default: 0 },
  cartItem: { type: Object, default: null },
  catalogBackUrl: { type: String, default: '/catalog' },
  categoryPromo: { type: Object, default: null },
})

const page = usePage()
const isAdmin = computed(() => {
  const role = page.props.auth?.user?.role
  return role === 'admin' || role === 'administrator'
})

const primaryCategoryName = computed(() => {
  if (!props.product.categories?.length) {
    return '—'
  }
  const primary = props.product.categories.find((c) => c.pivot?.is_primary)

  return (primary ?? props.product.categories[0]).name
})

const allCategoriesLabel = computed(() => {
  if (props.product.categories && props.product.categories.length) {
    return props.product.categories.map((c) => c.name).join(', ')
  }
  return primaryCategoryName.value
})

const CARE_LABELS = { easy: 'Лёгкий', medium: 'Средний', hard: 'Сложный' }
const SIZE_LABELS = {
  small: 'Малый',
  medium: 'Средний',
  large: 'Крупный',
  extra_large: 'Очень крупный',
}
const AGE_LABELS = {
  young: 'Молодое',
  mature: 'Зрелое',
  old: 'Взрослое',
}

const careLabel = computed(() => CARE_LABELS[props.product.care_difficulty] ?? '—')
const sizeLabel = computed(() => SIZE_LABELS[props.product.size] ?? '—')
const ageLabel = computed(() => AGE_LABELS[props.product.age_group] ?? '—')

const isUnavailable = computed(() => isProductUnavailable(props.product))

const maxCartQty = computed(() => Number(props.product.quantity) || 0)

const stockMessage = ref('')

const selectedImage = ref(0)
const addingToCart = ref(false)
const togglingFavorite = ref(false)
const showReviewModal = ref(false)
const showSpecsModal = ref(false)
const isFavoriteLocal = ref(props.isFavorite)
const cartItem = ref(props.cartItem)

watch(() => props.isFavorite, (newVal) => {
  isFavoriteLocal.value = newVal
})

watch(() => props.cartItem, (newVal) => {
  cartItem.value = newVal
})

const addToCart = () => {
  const nextQty = (cartItem.value?.quantity ?? 0) + 1
  if (nextQty > maxCartQty.value) {
    stockMessage.value = STOCK_EXHAUSTED_MESSAGE
    return
  }
  stockMessage.value = ''
  addingToCart.value = true
  router.post('/cart/add', {
    product_id: props.product.id,
    quantity: 1,
  }, {
    preserveScroll: true,
    onError: () => {
      stockMessage.value = page.props.errors?.cart || STOCK_EXHAUSTED_MESSAGE
    },
    onSuccess: () => {
      stockMessage.value = ''
    },
    onFinish: () => {
      addingToCart.value = false
    },
  })
}

const changeCartQty = (delta) => {
  if (!cartItem.value) return
  const next = cartItem.value.quantity + delta
  if (next < 1) {
    router.delete(`/cart/${cartItem.value.id}`, { preserveScroll: true })
    return
  }
  if (next > maxCartQty.value) {
    stockMessage.value = STOCK_EXHAUSTED_MESSAGE
    return
  }
  stockMessage.value = ''
  router.patch(`/cart/${cartItem.value.id}/quantity`, { quantity: next }, {
    preserveScroll: true,
    onError: () => {
      stockMessage.value = page.props.errors?.cart || STOCK_EXHAUSTED_MESSAGE
    },
    onSuccess: () => {
      stockMessage.value = ''
    },
  })
}

const toggleFavorite = () => {
  togglingFavorite.value = true
  router.post(`/favorites/${props.product.id}`, {}, {
    preserveScroll: true,
    preserveState: true,
    onSuccess: () => {
      isFavoriteLocal.value = !isFavoriteLocal.value
    },
    onFinish: () => {
      togglingFavorite.value = false
    },
  })
}
</script>
