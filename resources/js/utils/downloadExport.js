/**
 * Скачивание отчёта (Excel/PDF) с проверкой пустого ответа.
 */
export async function downloadExport(url, { emptyMessage = 'Пустой отчёт', filename = 'report' } = {}) {
  const response = await fetch(url, {
    headers: {
      'X-Requested-With': 'XMLHttpRequest',
      Accept: 'application/json, application/octet-stream, */*',
    },
    credentials: 'same-origin',
  })

  const contentType = response.headers.get('content-type') || ''

  if (response.status === 422 || contentType.includes('application/json')) {
    let message = emptyMessage
    try {
      const data = await response.json()
      if (data?.message) {
        message = data.message
      }
    } catch {
      // ignore
    }
    alert(message)
    return false
  }

  if (!response.ok) {
    alert('Не удалось сформировать отчёт')
    return false
  }

  const blob = await response.blob()
  if (!blob.size) {
    alert(emptyMessage)
    return false
  }

  const disposition = response.headers.get('content-disposition') || ''
  const match = disposition.match(/filename="?([^"]+)"?/i)
  const resolvedName = match?.[1] || filename

  const link = document.createElement('a')
  link.href = URL.createObjectURL(blob)
  link.download = resolvedName
  document.body.appendChild(link)
  link.click()
  link.remove()
  URL.revokeObjectURL(link.href)

  return true
}
