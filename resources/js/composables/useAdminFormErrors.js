import { computed, nextTick } from 'vue'

/**
 * Подсказки и подсветка полей для Inertia useForm в админке.
 */
export function useAdminFormErrors(form, fieldLabels = {}) {
  const hasFormErrors = computed(() => Object.keys(form.errors).length > 0)

  const errorSummary = computed(() =>
    Object.entries(form.errors).map(([field, message]) => ({
      field,
      label: fieldLabels[field] || field,
      message: Array.isArray(message) ? message[0] : message,
    }))
  )

  const inputClass = (field, extra = '') => {
    const base = `w-full border rounded px-3 py-2 font-['Montserrat'] text-[14px] ${extra}`.trim()

    return form.errors[field]
      ? `${base} border-red-500 bg-red-50 ring-1 ring-red-200`
      : `${base} border-gray-300`
  }

  /** Для полей с готовыми классами (например, в Products.vue). */
  const withFieldError = (field, baseClasses) => {
    return form.errors[field]
      ? `${baseClasses} border-red-500 bg-red-50 ring-1 ring-red-200`
      : baseClasses
  }

  const modalSubmitOptions = (showModalRef, modalBodyRef, onSuccess) => ({
    preserveScroll: true,
    onSuccess: () => {
      if (typeof onSuccess === 'function') {
        onSuccess()
      } else if (showModalRef) {
        showModalRef.value = false
      }
      form.clearErrors()
    },
    onError: () => {
      if (showModalRef) {
        showModalRef.value = true
      }
      nextTick(() => {
        modalBodyRef?.value?.scrollTo({ top: 0, behavior: 'smooth' })
      })
    },
  })

  return {
    hasFormErrors,
    errorSummary,
    inputClass,
    withFieldError,
    modalSubmitOptions,
  }
}
