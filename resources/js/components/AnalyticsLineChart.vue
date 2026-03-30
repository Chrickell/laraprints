<template>
  <div class="relative" ref="wrapperEl" @mouseleave="hovered = null">

    <!-- Loading -->
    <div v-if="loading" class="animate-pulse rounded-xl bg-linear-to-r from-gray-100 to-gray-50" :style="{ height: (H + 40) + 'px' }" />

    <!-- Empty -->
    <div v-else-if="!data.length" class="flex flex-col items-center justify-center gap-3" :style="{ height: (H + 40) + 'px' }">
      <div class="flex h-10 w-10 items-center justify-center rounded-full bg-gray-100">
        <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941" />
        </svg>
      </div>
      <p class="text-sm text-gray-400">No data for this period</p>
    </div>

    <!-- Chart -->
    <div v-else class="relative">

      <!-- Legend -->
      <div class="mb-4 flex items-center gap-5">
        <div class="flex items-center gap-1.5">
          <div class="h-2 w-5 rounded-full bg-indigo-500" />
          <span class="text-xs font-medium text-gray-500">Page Views</span>
        </div>
        <div class="flex items-center gap-1.5">
          <div class="h-2 w-5 rounded-full bg-sky-400" />
          <span class="text-xs font-medium text-gray-500">Clicks</span>
        </div>
      </div>

      <!-- Floating tooltip -->
      <div
        v-if="hovered"
        class="pointer-events-none absolute z-20 -translate-x-1/2 rounded-xl border border-gray-100 bg-white px-3.5 py-2.5 shadow-xl"
        :style="{ left: tooltipLeft + 'px', top: '40px' }"
      >
        <p class="mb-2 text-[11px] font-semibold uppercase tracking-wider text-gray-400">{{ hovered.date }}</p>
        <div class="space-y-1.5">
          <div class="flex items-center gap-3">
            <div class="flex items-center gap-1.5">
              <div class="h-2 w-2 rounded-full bg-indigo-500" />
              <span class="text-xs text-gray-500">Page Views</span>
            </div>
            <span class="ml-auto pl-3 text-xs font-bold tabular-nums text-gray-900">{{ hovered.page_views.toLocaleString() }}</span>
          </div>
          <div class="flex items-center gap-3">
            <div class="flex items-center gap-1.5">
              <div class="h-2 w-2 rounded-full bg-sky-400" />
              <span class="text-xs text-gray-500">Clicks</span>
            </div>
            <span class="ml-auto pl-3 text-xs font-bold tabular-nums text-gray-900">{{ hovered.clicks.toLocaleString() }}</span>
          </div>
        </div>
      </div>

      <!-- SVG -->
      <svg
        ref="svgEl"
        :viewBox="`0 0 ${W} ${H}`"
        class="w-full overflow-visible"
        :style="{ height: H + 'px' }"
        @mousemove="onMove"
      >
        <defs>
          <linearGradient :id="`${uid}-pv`" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0%" stop-color="#6366f1" stop-opacity="0.2" />
            <stop offset="100%" stop-color="#6366f1" stop-opacity="0.02" />
          </linearGradient>
          <linearGradient :id="`${uid}-cl`" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0%" stop-color="#38bdf8" stop-opacity="0.18" />
            <stop offset="100%" stop-color="#38bdf8" stop-opacity="0.02" />
          </linearGradient>
        </defs>

        <!-- Grid lines + Y-axis labels -->
        <g v-for="tick in yTicks" :key="tick.val">
          <line :x1="PL" :x2="W - PR" :y1="tick.y" :y2="tick.y" stroke="#f1f5f9" stroke-width="1" />
          <text :x="PL - 8" :y="tick.y + 4" text-anchor="end" fill="#9ca3af" font-size="11" font-family="system-ui,-apple-system,sans-serif">{{ tick.label }}</text>
        </g>

        <!-- Area fills -->
        <path :d="pvArea" :fill="`url(#${uid}-pv)`" />
        <path :d="clArea" :fill="`url(#${uid}-cl)`" />

        <!-- Lines -->
        <path :d="pvLine" fill="none" stroke="#6366f1" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
        <path :d="clLine" fill="none" stroke="#38bdf8" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />

        <!-- Hover crosshair + dots -->
        <template v-if="hovered">
          <line :x1="hovered.svgX" :x2="hovered.svgX" :y1="PT" :y2="H - PB" stroke="#e2e8f0" stroke-width="1.5" />
          <circle :cx="hovered.svgX" :cy="hovered.pvY" r="5" fill="#6366f1" stroke="white" stroke-width="2.5" />
          <circle :cx="hovered.svgX" :cy="hovered.clY" r="5" fill="#38bdf8" stroke="white" stroke-width="2.5" />
        </template>
      </svg>

      <!-- X-axis date labels -->
      <div class="mt-2 flex justify-between text-[11px] font-medium text-gray-400" style="padding-left: 5.2%">
        <span>{{ fmtDate(data[0]?.date) }}</span>
        <span>{{ fmtDate(data[Math.floor(data.length / 2)]?.date) }}</span>
        <span>{{ fmtDate(data[data.length - 1]?.date) }}</span>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
  data:    { type: Array,   default: () => [] },
  loading: { type: Boolean, default: false    },
})

// Unique ID so multiple instances on one page don't share gradient defs
const uid = `lp-${Math.random().toString(36).slice(2, 8)}`

// ─── SVG geometry ─────────────────────────────────────────────────────────────
const W  = 1000  // viewBox width
const H  = 200   // viewBox + CSS height
const PL = 52    // left padding (Y labels)
const PR = 8     // right padding
const PT = 10    // top padding
const PB = 10    // bottom padding

// ─── Y scale ──────────────────────────────────────────────────────────────────
const maxVal = computed(() => {
  const vals = props.data.flatMap(d => [d.page_views ?? 0, d.clicks ?? 0])
  return Math.max(...vals, 1)
})

const niceMax = computed(() => {
  const m = maxVal.value
  const steps = [5, 10, 20, 50, 100, 200, 500, 1000, 2000, 5000, 10000, 20000, 50000, 100000, 500000, 1000000]
  return steps.find(s => s > m) ?? Math.ceil(m * 1.25)
})

const yPos = (val) => PT + (H - PT - PB) - (val / niceMax.value) * (H - PT - PB)
const xPos = (i) => {
  const n = props.data.length
  return n <= 1
    ? PL + (W - PL - PR) / 2
    : PL + (i / (n - 1)) * (W - PL - PR)
}

const yTicks = computed(() => {
  const max = niceMax.value
  return [1, 0.67, 0.33, 0].map(frac => {
    const val = Math.round(frac * max)
    return { val, y: yPos(val), label: fmtNum(val) }
  })
})

// ─── Path data ────────────────────────────────────────────────────────────────
const pvPts = computed(() => props.data.map((d, i) => ({ x: xPos(i), y: yPos(d.page_views ?? 0) })))
const clPts = computed(() => props.data.map((d, i) => ({ x: xPos(i), y: yPos(d.clicks ?? 0) })))

const smoothLine = (pts) => {
  if (!pts.length) return ''
  if (pts.length === 1) return `M ${pts[0].x} ${pts[0].y}`
  let d = `M ${pts[0].x} ${pts[0].y}`
  for (let i = 1; i < pts.length; i++) {
    const cx = (pts[i - 1].x + pts[i].x) / 2
    d += ` C ${cx} ${pts[i-1].y} ${cx} ${pts[i].y} ${pts[i].x} ${pts[i].y}`
  }
  return d
}
const smoothArea = (pts) => {
  if (!pts.length) return ''
  return `${smoothLine(pts)} L ${pts[pts.length-1].x} ${H - PB} L ${pts[0].x} ${H - PB} Z`
}

const pvLine = computed(() => smoothLine(pvPts.value))
const clLine = computed(() => smoothLine(clPts.value))
const pvArea = computed(() => smoothArea(pvPts.value))
const clArea = computed(() => smoothArea(clPts.value))

// ─── Hover ────────────────────────────────────────────────────────────────────
const wrapperEl   = ref(null)
const svgEl       = ref(null)
const hovered     = ref(null)
const tooltipLeft = ref(0)

const onMove = (event) => {
  const svg = svgEl.value
  const wrapper = wrapperEl.value
  if (!svg || !wrapper || !props.data.length) return
  const rect  = svg.getBoundingClientRect()
  const mouseX = event.clientX - rect.left
  const scaleX = W / rect.width
  const svgX   = mouseX * scaleX
  const n      = props.data.length
  if (n <= 1) return
  const i = Math.max(0, Math.min(n - 1, Math.round(((svgX - PL) / (W - PL - PR)) * (n - 1))))
  const d = props.data[i]
  hovered.value = {
    svgX: pvPts.value[i].x,
    pvY:  pvPts.value[i].y,
    clY:  clPts.value[i].y,
    date: fmtDate(d.date),
    page_views: d.page_views ?? 0,
    clicks:     d.clicks     ?? 0,
  }
  // Snap tooltip X to the nearest data point (same as crosshair), clamped to avoid overflow
  const wrapperRect = wrapper.getBoundingClientRect()
  const snapX = rect.left - wrapperRect.left + (pvPts.value[i].x / W * rect.width)
  tooltipLeft.value = Math.max(80, Math.min(wrapperRect.width - 80, snapX))
}

// ─── Formatters ───────────────────────────────────────────────────────────────
const fmtDate = (str) => {
  if (!str) return ''
  return new Date(str + 'T00:00:00').toLocaleDateString(undefined, { month: 'short', day: 'numeric' })
}
const fmtNum = (n) => {
  if (n >= 1_000_000) return `${(n / 1_000_000).toFixed(1)}M`
  if (n >= 1_000)     return `${+(n / 1_000).toFixed(1)}k`
  return String(n)
}
</script>
