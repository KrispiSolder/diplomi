<template>
  <AuthContainer>
    <div class="absolute top-[29px] right-[29px]">
      <button type="button" @click="goHome" class="text-white hover:opacity-70 z-10">
        <svg class="w-[20px] h-[20px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>

    <h1 class="font-['Montserrat'] font-medium text-[24px] text-white text-center mt-[40px]">Восстановление пароля</h1>

    <form @submit.prevent="submit" class="space-y-[20px] mt-[35px] flex flex-col items-center">
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

      <div class="w-full flex flex-col items-center">
        <label class="font-['Montserrat'] text-white text-[18px] block mb-[10px]">Новый пароль</label>
        <input
          v-model="form.password"
          type="password"
          placeholder="Введите пароль"
          class="w-[340px] h-[44px] bg-[#F6F6F9] rounded-[15px] px-[20px] font-['Montserrat'] text-[#BABABA] placeholder-[#BABABA] focus:outline-none focus:ring-2 focus:ring-[#2E7D32] mx-auto"
          required
        />
        <div v-if="form.errors.password" class="text-red-400 text-[12px] mt-[5px]">{{ form.errors.password }}</div>
      </div>

      <div class="w-full flex flex-col items-center">
        <label class="font-['Montserrat'] text-white text-[18px] block mb-[10px]">Подтверждение пароля</label>
        <input
          v-model="form.password_confirmation"
          type="password"
          placeholder="Введите пароль"
          class="w-[340px] h-[44px] bg-[#F6F6F9] rounded-[15px] px-[20px] font-['Montserrat'] text-[#BABABA] placeholder-[#BABABA] focus:outline-none focus:ring-2 focus:ring-[#2E7D32] mx-auto"
          required
        />
        <div v-if="form.errors.password_confirmation" class="text-red-400 text-[12px] mt-[5px]">{{ form.errors.password_confirmation }}</div>
      </div>

      <div class="flex justify-center pt-[10px]">
        <button
          type="submit"
          class="w-[204px] h-[46px] bg-[#2E7D32] text-white font-['Montserrat'] font-bold text-[18px] rounded-[18px] hover:bg-[#1b5e2b] disabled:opacity-50"
          :disabled="form.processing"
        >
          Сбросить пароль
        </button>
      </div>
    </form>

    <div class="flex justify-center gap-[35px] mt-[20px]">
      <Link href="/login" class="font-['Montserrat'] text-white text-[14px] hover:underline">
        Авторизироваться
      </Link>
      <Link href="/register" class="font-['Montserrat'] text-white text-[14px] hover:underline">
        Зарегистрироваться
      </Link>
    </div>
  </AuthContainer>
</template>

<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import AuthContainer from '@/Components/AuthContainer.vue'

const form = useForm({
  email: '',
  password: '',
  password_confirmation: '',
})

const submit = () => {
  form.post('/password-reset')
}

const goHome = () => {
  window.location.href = '/'
}
</script>
