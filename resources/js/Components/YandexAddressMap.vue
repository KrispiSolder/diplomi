<template>
  <div class="space-y-[10px]">
    <label class="font-['Montserrat'] text-[12px] text-[#666666] block">Адрес на карте</label>
    <div :id="mapContainerId" class="w-full h-[220px] rounded-[12px] overflow-hidden border border-gray-200 bg-[#F6F6F9]" />
    <p v-if="!apiKey" class="text-[12px] text-red-600">
      Укажите YANDEX_MAPS_JS_API_KEY (или YANDEX_MAPS_API_KEY) в .env для карты.
    </p>
    <textarea
      :value="modelValue"
      rows="3"
      class="w-full rounded-[12px] border border-gray-300 px-[14px] py-[10px] font-['Montserrat'] text-[14px]"
      placeholder="Начните вводить адрес — карта подстроится автоматически"
      @input="onAddressInput"
    />
    <p v-if="geocoding" class="font-['Montserrat'] text-[12px] text-[#888888]">Поиск на карте…</p>
  </div>
</template>

<script setup>
import { onBeforeUnmount, onMounted, ref, watch } from 'vue'

const props = defineProps({
  modelValue: { type: String, default: '' },
  apiKey: { type: String, default: '' },
})

const emit = defineEmits(['update:modelValue'])

const mapContainerId = `ymap-${Math.random().toString(36).slice(2)}`
const geocoding = ref(false)

/** Иркутск [широта, долгота] */
const IRKUTSK_CENTER = [52.286974, 104.305018]

/** Упрощённый прямоугольник РФ (юго-запад — северо-восток) */
const RU_BOUNDS_POINTS = [
  [41.0, 19.0],
  [82.0, 179.0],
]

let mapInstance = null
let placemark = null
let geocodeRequestId = 0
let textGeocodeTimer = null
let ruBounds = null
let mapReady = false

function formatCoords(coords) {
  return `${Number(coords[0]).toFixed(6)}, ${Number(coords[1]).toFixed(6)}`
}

function clampCoordsToRu(coords) {
  const lat = Number(coords[0])
  const lon = Number(coords[1])
  return [
    Math.min(RU_BOUNDS_POINTS[1][0], Math.max(RU_BOUNDS_POINTS[0][0], lat)),
    Math.min(RU_BOUNDS_POINTS[1][1], Math.max(RU_BOUNDS_POINTS[0][1], lon)),
  ]
}

function pointInsideRuBounds(coords) {
  try {
    if (ruBounds && window.ymaps?.util?.bounds?.containsPoint) {
      return window.ymaps.util.bounds.containsPoint(ruBounds, coords)
    }
  } catch {
    // ignore
  }
  const c = clampCoordsToRu(coords)
  return Math.abs(c[0] - Number(coords[0])) < 1e-8 && Math.abs(c[1] - Number(coords[1])) < 1e-8
}

function stripLeadingCountry(text) {
  if (!text || typeof text !== 'string') {
    return ''
  }
  let t = text.trim()
  t = t.replace(/^Россия,\s*/i, '').replace(/^Russia,\s*/i, '')

  return t.trim()
}

async function fetchReverseAddress(lat, lon) {
  const r = await fetch(
    `/yandex/reverse-geocode?lat=${encodeURIComponent(lat)}&lon=${encodeURIComponent(lon)}`,
    {
      credentials: 'same-origin',
      headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    },
  )
  if (!r.ok) {
    return null
  }
  const data = await r.json()
  if (data.address && typeof data.address === 'string') {
    return stripLeadingCountry(data.address) || data.address
  }

  return null
}

async function fetchForwardCoords(query) {
  const r = await fetch(`/yandex/forward-geocode?${new URLSearchParams({ q: query })}`, {
    credentials: 'same-origin',
    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
  })
  if (!r.ok) {
    return null
  }
  const data = await r.json()
  if (data.lat != null && data.lon != null) {
    return [Number(data.lat), Number(data.lon)]
  }

  return null
}

function loadScript(key) {
  return new Promise((resolve, reject) => {
    if (window.ymaps) {
      resolve()
      return
    }
    const s = document.createElement('script')
    s.src = `https://api-maps.yandex.ru/2.1/?apikey=${encodeURIComponent(key)}&lang=ru_RU&load=package.full`
    s.async = true
    s.onload = () => resolve()
    s.onerror = () => reject(new Error('ymaps load failed'))
    document.head.appendChild(s)
  })
}

function clearPlacemark() {
  if (mapInstance && placemark) {
    mapInstance.geoObjects.remove(placemark)
    placemark = null
  }
}

function placeMarker(coords, { syncAddress = true, zoom = 16 } = {}) {
  if (!mapInstance) {
    return
  }

  const c = clampCoordsToRu(coords)
  clearPlacemark()

  placemark = new window.ymaps.Placemark(c, {}, { preset: 'islands#greenDotIcon', draggable: true })
  placemark.events.add('dragend', () => {
    let next = placemark.geometry.getCoordinates()
    if (!pointInsideRuBounds(next)) {
      next = clampCoordsToRu(next)
      placemark.geometry.setCoordinates(next)
    }
    setAddressFromCoords(next)
  })
  mapInstance.geoObjects.add(placemark)
  mapInstance.setCenter(c, zoom, { duration: 250 })

  if (syncAddress) {
    setAddressFromCoords(c)
  }
}

async function setAddressFromCoords(coords) {
  const pair = clampCoordsToRu([Number(coords[0]), Number(coords[1])])
  const reqId = ++geocodeRequestId

  try {
    const addr = await fetchReverseAddress(pair[0], pair[1])
    if (reqId !== geocodeRequestId) {
      return
    }
    if (addr) {
      emit('update:modelValue', addr)
      return
    }
  } catch {
    // ignore
  }

  if (reqId !== geocodeRequestId) {
    return
  }
  emit('update:modelValue', formatCoords(pair))
}

function buildForwardQueries(text) {
  const q = text.trim()
  const queries = [q]
  if (!/иркутск/i.test(q)) {
    queries.push(`Иркутск, ${q}`)
  }

  return queries
}

async function resolveCoordsFromText(text) {
  const q = text.trim()
  if (!q) {
    return null
  }

  const coordsMatch = q.match(/^(-?\d+\.?\d*),\s*(-?\d+\.?\d*)$/)
  if (coordsMatch) {
    const coords = [parseFloat(coordsMatch[1]), parseFloat(coordsMatch[2])]
    return pointInsideRuBounds(coords) ? coords : null
  }

  if (q.length < 3) {
    return null
  }

  for (const qq of buildForwardQueries(q)) {
    const coords = await fetchForwardCoords(qq)
    if (coords && pointInsideRuBounds(coords)) {
      return coords
    }
  }

  return null
}

async function syncMapToAddressText(text, { syncAddress = false } = {}) {
  if (!mapInstance) {
    return
  }

  const q = (text || '').trim()
  if (!q) {
    clearPlacemark()
    mapInstance.setCenter(IRKUTSK_CENTER, 12, { duration: 250 })
    return
  }

  const reqId = ++geocodeRequestId
  geocoding.value = true

  try {
    const coords = await resolveCoordsFromText(q)
    if (reqId !== geocodeRequestId) {
      return
    }
    if (coords) {
      placeMarker(coords, { syncAddress, zoom: 16 })
    }
  } finally {
    if (reqId === geocodeRequestId) {
      geocoding.value = false
    }
  }
}

function scheduleMapSyncFromText(text) {
  if (textGeocodeTimer) {
    clearTimeout(textGeocodeTimer)
  }
  textGeocodeTimer = setTimeout(() => {
    syncMapToAddressText(text, { syncAddress: false })
  }, 550)
}

function onAddressInput(event) {
  emit('update:modelValue', event.target.value)
}

async function initMap() {
  if (!props.apiKey) {
    return
  }

  await loadScript(props.apiKey)
  await window.ymaps.ready()

  if (window.ymaps.util?.bounds?.fromPoints) {
    ruBounds = window.ymaps.util.bounds.fromPoints(RU_BOUNDS_POINTS)
  }

  mapInstance = new window.ymaps.Map(document.getElementById(mapContainerId), {
    center: IRKUTSK_CENTER,
    zoom: 12,
    controls: ['zoomControl', 'geolocationControl'],
    restrictMapArea: true,
    restriction: {
      latLngBounds: RU_BOUNDS_POINTS,
      strict: true,
    },
  })

  mapInstance.events.add('click', (e) => {
    const coords = e.get('coords')
    if (!pointInsideRuBounds(coords)) {
      return
    }
    placeMarker(coords, { syncAddress: true })
  })

  mapReady = true

  if (props.modelValue?.trim()) {
    await syncMapToAddressText(props.modelValue, { syncAddress: false })
  }
}

onMounted(() => {
  if (props.apiKey) {
    initMap().catch(() => {})
  }
})

watch(
  () => props.apiKey,
  (key) => {
    if (key && !mapInstance) {
      initMap().catch(() => {})
    }
  },
)

watch(
  () => props.modelValue,
  (value) => {
    if (!mapReady) {
      return
    }
    scheduleMapSyncFromText(value || '')
  },
)

onBeforeUnmount(() => {
  if (textGeocodeTimer) {
    clearTimeout(textGeocodeTimer)
    textGeocodeTimer = null
  }
  geocodeRequestId++
  if (mapInstance) {
    mapInstance.destroy()
    mapInstance = null
    placemark = null
  }
  ruBounds = null
  mapReady = false
})
</script>
