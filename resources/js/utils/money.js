export function toKopecks(rubles) {
  return Math.round(Number(rubles) * 100)
}

export function fromKopecks(kopecks) {
  return (Number(kopecks) / 100).toFixed(2)
}

export function lineTotalKopecks(unitPrice, quantity) {
  return toKopecks(unitPrice) * Math.max(0, Number(quantity) || 0)
}

export function cartSubtotalKopecks(cartItems) {
  return (cartItems || []).reduce((sum, item) => {
    if (!item?.product) return sum
    return sum + lineTotalKopecks(item.product.price, item.quantity)
  }, 0)
}

export function formatMoney(value) {
  const kopecks = toKopecks(value)
  return (kopecks / 100).toLocaleString('ru-RU', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  })
}
