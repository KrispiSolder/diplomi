export function isProductUnavailable(product) {
  if (!product) {
    return true
  }

  return Number(product.quantity) <= 0
}

export const STOCK_EXHAUSTED_MESSAGE = 'Товар на складе закончился'
