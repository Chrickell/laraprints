<template>
  <div class="relative select-none" ref="wrapperEl">

    <!-- Loading skeleton -->
    <div v-if="loading" class="h-44 w-full animate-pulse rounded-xl bg-linear-to-r from-gray-100 to-gray-50" />

    <!-- Empty state -->
    <div v-else-if="!data.length" class="flex h-44 flex-col items-center justify-center gap-3">
      <div class="flex h-10 w-10 items-center justify-center rounded-full bg-gray-100">
        <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
        </svg>
      </div>
      <p class="text-sm text-gray-400">No data for this period</p>
    </div>

    <!-- Chart -->
    <div v-else>
      <!-- Floating tooltip -->
      <div
        v-if="hovered"
        class="pointer-events-none absolute z-20 -translate-x-1/2 -translate-y-full rounded-xl border border-gray-100 bg-white px-3 py-2 shadow-xl"
        :style="{ left: hovered.x + 'px', top: (hovered.y - 10) + 'px' }"
      >
        <p class="text-sm font-bold text-gray-900">{{ hovered.value.toLocaleString() }}</p>
        <p class="text-xs text-gray-400">{{ hovered.date }}</p>
        <div class="absolute left-1/2 top-full h-0 w-0 -translate-x-1/2 border-x-[5px] border-t-[5px] border-x-transparent border-t-white" />
      </div>

      <!-- Bars -->
      <div
        class="flex items-end gap-px overflow-hidden rounded-b-sm"
        style="height: 160px;"
        @mouseleave="hovered = null"
      >
        <div
          v-for="(point, i) in data"
          :key="i"
          class="relative flex-1 cursor-default rounded-t-sm"
          :style="{
            height: barHeight(point[valueKey]),
            background: (hovered && hovered.index !== i) ? fadeGradient : activeGradient,
            transition: 'height 0.5s cubic-bezier(0.4,0,0.2,1), background 0.15s ease',
          }"
          @mousemove.stop="onHover($event, point, i)"
        />
      </div>

      <!-- Date axis labels -->
      <div class="mt-3 flex justify-between text-[11px] font-medium text-gray-400">
        <span>{{ fmtDate(data[0]?.[dateKey]) }}</span>
        <span>{{ fmtDate(data[Math.floor(data.length / 2)]?.[dateKey]) }}</span>
        <span>{{ fmtDate(data[data.length - 1]?.[dateKey]) }}</span>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
  data:     { type: Array,   default: () => [] },
  loading:  { type: Boolean, default: false    },
  valueKey: { type: String,  default: 'count'  },
  dateKey:  { type: String,  default: 'date'   },
  color:    { type: String,  default: 'indigo' },
})

const wrapperEl = ref(null)
const hovered   = ref(null)

const gradients = {
  indigo: {
    active: 'linear-gradient(to top, #a5b4fc, #6366f1)',
    fade:   'linear-gradient(to top, #e0e7ff, #c7d2fe)',
  },
  sky: {
    active: 'linear-gradient(to top, #7dd3fc, #0ea5e9)',
    fade:   'linear-gradient(to top, #e0f2fe, #bae6fd)',
  },
  violet: {
    active: 'linear-gradient(to top, #c4b5fd, #8b5cf6)',
    fade:   'linear-gradient(to top, #ede9fe, #ddd6fe)',
  },
}

const g              = computed(() => gradients[props.color] ?? gradients.indigo)
const activeGradient = computed(() => g.value.active)
const fadeGradient   = computed(() => g.value.fade)

const maxVal = computed(() => Math.max(...props.data.map(d => d[props.valueKey] ?? 0), 1))

const barHeight = (val) => `${Math.max(((val ?? 0) / maxVal.value) * 100, 1.5)}%`

const fmtDate = (str) => {
  if (!str) return ''
  return new Date(str + 'T00:00:00').toLocaleDateString(undefined, { month: 'short', day: 'numeric' })
}

const onHover = (event, point, index) => {
  const rect = wrapperEl.value?.getBoundingClientRect()
  if (!rect) return
  hovered.value = {
    index,
    x: event.clientX - rect.left,
    y: event.clientY - rect.top,
    value: point[props.valueKey] ?? 0,
    date: fmtDate(point[props.dateKey]),
  }
}
</script>
