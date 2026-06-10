export const PAYMENT_METHODS = [
  {
    code: 'cash',
    label: 'Наличные',
    description: 'Оплата курьеру при получении',
    online: false,
  },
  {
    code: 'card_courier',
    label: 'Картой курьеру',
    description: 'Оплата банковской картой при доставке',
    online: false,
  },
  {
    code: 'yookassa',
    label: 'Банковской картой онлайн',
    description: 'Оплата картой или СБП на защищённой странице ЮKassa',
    online: true,
  },
]

export function checkoutPaymentMethods(yookassaEnabled = false) {
  return PAYMENT_METHODS.map((m) => ({
    ...m,
    available: !m.online || yookassaEnabled,
  }))
}

export function paymentMethodLabel(codeOrLabel) {
  if (!codeOrLabel) {
    return '—'
  }
  const found = PAYMENT_METHODS.find((m) => m.code === codeOrLabel || m.label === codeOrLabel)
  return found?.label ?? codeOrLabel
}
