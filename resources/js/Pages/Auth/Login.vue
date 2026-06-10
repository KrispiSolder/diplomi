<template>
  <div class="min-h-screen bg-cover bg-center flex items-center justify-center" style="background-image: url('/images/9.jpg')">
    <!-- Modal -->
    <div class="w-[526px] min-h-[520px] bg-black/20 rounded-[15px] p-[30px] shadow-lg backdrop-blur-sm flex flex-col gap-[20px]">
      <!-- Close Button -->
      <button @click="goHome" class="absolute top-[29px] right-[29px] text-white hover:opacity-70">
        <svg class="w-[20px] h-[20px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>

      <!-- Title -->
      <h1 class="font-['Montserrat'] font-medium text-[24px] text-white text-center mt-[10px]">Авторизация</h1>

      <!-- Form -->
      <form @submit.prevent="submit" class="space-y-[16px] flex flex-col items-center">
        <!-- Email Field -->
        <div class="w-full flex flex-col items-center">
          <label class="font-['Montserrat'] text-white text-[18px] block mb-[10px]">Email</label>
          <input
            v-model="form.email"
            type="email"
            placeholder="Youremail@mail.ru"
            class="w-[340px] h-[44px] bg-[#F6F6F9] rounded-[15px] px-[20px] font-['Montserrat'] text-[#BABABA] placeholder-[#BABABA] focus:outline-none focus:ring-2 focus:ring-[#2E7D32] mx-auto"
            required
          />
          <div v-if="form.errors.email" class="text-red-400 text-[12px] mt-[5px]">{{ form.errors.email }}</div>
        </div>

        <!-- Password Field -->
        <div class="w-full flex flex-col items-center">
          <label class="font-['Montserrat'] text-white text-[18px] block mb-[10px]">Пароль</label>
          <input
            v-model="form.password"
            type="password"
            placeholder="Введите пароль"
            class="w-[340px] h-[44px] bg-[#F6F6F9] rounded-[15px] px-[20px] font-['Montserrat'] text-[#BABABA] placeholder-[#BABABA] focus:outline-none focus:ring-2 focus:ring-[#2E7D32] mx-auto"
            required
          />
          <div v-if="form.errors.password" class="text-red-400 text-[12px] mt-[5px]">{{ form.errors.password }}</div>
        </div>

        <!-- Login Button -->
        <div class="flex justify-center pt-[6px]">
          <button
            type="submit"
            class="w-[180px] h-[46px] bg-[#2E7D32] text-white font-['Montserrat'] font-bold text-[18px] rounded-[18px] hover:bg-[#1b5e2b] disabled:opacity-50"
            :disabled="form.processing"
          >
            Войти
          </button>
        </div>

        <!-- Yandex OAuth -->
        <div class="flex justify-center">
          <button
            type="button"
            class="w-[340px] h-[44px] bg-white text-black font-['Montserrat'] font-medium text-[16px] rounded-[14px] border border-gray-200 hover:bg-gray-100 transition shadow-sm"
            @click="loginWithYandex"
          >
            Войти через Яндекс
          </button>
        </div>
      </form>

      <!-- Links -->
      <div class="flex justify-center gap-[35px] mb-[6px]">
        <Link href="/register" class="font-['Montserrat'] text-white text-[14px] hover:underline">
          Зарегистрироваться
        </Link>
        <Link href="/password-reset" class="font-['Montserrat'] text-white text-[14px] hover:underline">
          Восстановить пароль
        </Link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Link, useForm } from '@inertiajs/vue3'

const form = useForm({
  email: '',
  password: '',
})

const submit = () => {
  form.post('/login')
}

const goHome = () => {
  window.location.href = '/'
}

const loginWithYandex = () => {
  window.location.href = '/auth/yandex/redirect'
}
</script>
