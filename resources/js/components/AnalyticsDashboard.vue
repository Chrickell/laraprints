<template>
  <div class="space-y-5">

    <!-- ── Header ─────────────────────────────────────────────────────────────── -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
      <div class="flex items-center gap-3">
        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-linear-to-br from-violet-500 to-indigo-600 shadow-md shadow-indigo-200">
          <svg class="h-4.5 w-4.5 text-white" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
          </svg>
        </div>
        <div>
          <h1 class="text-lg font-bold leading-tight text-gray-900">Analytics</h1>
          <p class="text-[11px] font-medium text-gray-400">{{ periodLabel }}</p>
        </div>
      </div>

      <div class="flex flex-wrap items-center gap-2">

        <!-- Export dropdown -->
        <div class="relative">
          <button
            @click="exportOpen = !exportOpen"
            class="flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-gray-600 shadow-sm transition-colors hover:bg-gray-50"
          >
            <svg class="h-3.5 w-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
            </svg>
            Export
            <svg class="h-3 w-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
            </svg>
          </button>
          <div
            v-if="exportOpen"
            class="absolute right-0 z-10 mt-1 w-44 overflow-hidden rounded-xl border border-gray-100 bg-white shadow-lg"
            @click.stop
          >
            <a
              :href="exportUrl('page_views')"
              @click="exportOpen = false"
              class="flex items-center gap-2 px-4 py-2.5 text-xs font-medium text-gray-700 hover:bg-gray-50 transition-colors"
              download
            >
              <svg class="h-3.5 w-3.5 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
              Page Views CSV
            </a>
            <a
              :href="exportUrl('sessions')"
              @click="exportOpen = false"
              class="flex items-center gap-2 px-4 py-2.5 text-xs font-medium text-gray-700 hover:bg-gray-50 transition-colors"
              download
            >
              <svg class="h-3.5 w-3.5 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
              </svg>
              Sessions CSV
            </a>
          </div>
        </div>

        <div class="flex rounded-lg bg-gray-100 p-0.5">
          <button
            v-for="r in dateRanges"
            :key="r.value"
            @click="setRange(r.value)"
            :class="[
              'rounded-md px-3 py-1.5 text-xs font-semibold transition-all duration-150',
              dateRange === r.value
                ? 'bg-white text-gray-900 shadow-sm'
                : 'text-gray-500 hover:text-gray-700',
            ]"
          >
            {{ r.label }}
          </button>
        </div>

        <template v-if="dateRange === 'custom'">
          <input v-model="customStart" type="date" class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs text-gray-700 shadow-sm focus:border-indigo-400 focus:ring-1 focus:ring-indigo-400 focus:outline-none" />
          <span class="text-xs text-gray-300">→</span>
          <input v-model="customEnd" type="date" class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs text-gray-700 shadow-sm focus:border-indigo-400 focus:ring-1 focus:ring-indigo-400 focus:outline-none" />
          <button
            @click="applyCustomRange"
            :disabled="!customStart || !customEnd"
            class="rounded-lg bg-linear-to-r from-violet-600 to-indigo-600 px-3.5 py-1.5 text-xs font-semibold text-white shadow-sm hover:from-violet-500 hover:to-indigo-500 disabled:cursor-not-allowed disabled:opacity-40 transition-all"
          >
            Apply
          </button>
        </template>
      </div>
    </div>

    <!-- ── Stat Cards ──────────────────────────────────────────────────────────── -->
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">

      <!-- Page Views -->
      <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-900/5">
        <div class="mb-3 flex items-center justify-between">
          <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-indigo-50">
            <svg class="h-3.5 w-3.5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
              <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
          </div>
        </div>
        <div v-if="loading" class="space-y-1.5">
          <div class="h-2 w-16 animate-pulse rounded-full bg-gray-200" />
          <div class="h-7 w-14 animate-pulse rounded-full bg-gray-200" />
        </div>
        <template v-else>
          <p class="text-[10px] font-semibold uppercase tracking-wider text-gray-400">Page Views</p>
          <p class="mt-0.5 text-2xl font-bold tabular-nums text-gray-900">{{ fmt(statsData?.totals?.page_views) }}</p>
          <p v-if="deltaValues.page_views != null" :class="['mt-1 text-[10px] font-semibold tabular-nums', deltaValues.page_views > 0 ? 'text-emerald-500' : deltaValues.page_views < 0 ? 'text-red-400' : 'text-gray-300']">
            {{ deltaValues.page_views > 0 ? '↑' : '↓' }} {{ Math.abs(deltaValues.page_views) }}% vs prior period
          </p>
        </template>
      </div>

      <!-- Unique Sessions -->
      <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-900/5">
        <div class="mb-3 flex items-center justify-between">
          <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-blue-50">
            <svg class="h-3.5 w-3.5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
            </svg>
          </div>
        </div>
        <div v-if="loading" class="space-y-1.5">
          <div class="h-2 w-20 animate-pulse rounded-full bg-gray-200" />
          <div class="h-7 w-14 animate-pulse rounded-full bg-gray-200" />
        </div>
        <template v-else>
          <p class="text-[10px] font-semibold uppercase tracking-wider text-gray-400">Visitors</p>
          <p class="mt-0.5 text-2xl font-bold tabular-nums text-gray-900">{{ fmt(statsData?.totals?.unique_sessions) }}</p>
          <p v-if="deltaValues.unique_sessions != null" :class="['mt-1 text-[10px] font-semibold tabular-nums', deltaValues.unique_sessions > 0 ? 'text-emerald-500' : deltaValues.unique_sessions < 0 ? 'text-red-400' : 'text-gray-300']">
            {{ deltaValues.unique_sessions > 0 ? '↑' : '↓' }} {{ Math.abs(deltaValues.unique_sessions) }}% vs prior period
          </p>
        </template>
      </div>

      <!-- Total Clicks -->
      <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-900/5">
        <div class="mb-3 flex items-center justify-between">
          <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-sky-50">
            <svg class="h-3.5 w-3.5 text-sky-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15.042 21.672L13.684 16.6m0 0l-2.51 2.225.569-9.47 5.227 7.917-3.286-.672zM12 2.25V4.5m5.834.166l-1.591 1.591M20.25 10.5H18M7.757 14.743l-1.59 1.59M6 10.5H3.75m4.007-4.243l-1.59-1.59" />
            </svg>
          </div>
        </div>
        <div v-if="loading" class="space-y-1.5">
          <div class="h-2 w-12 animate-pulse rounded-full bg-gray-200" />
          <div class="h-7 w-14 animate-pulse rounded-full bg-gray-200" />
        </div>
        <template v-else>
          <p class="text-[10px] font-semibold uppercase tracking-wider text-gray-400">Clicks</p>
          <p class="mt-0.5 text-2xl font-bold tabular-nums text-gray-900">{{ fmt(statsData?.totals?.clicks) }}</p>
          <p v-if="deltaValues.clicks != null" :class="['mt-1 text-[10px] font-semibold tabular-nums', deltaValues.clicks > 0 ? 'text-emerald-500' : deltaValues.clicks < 0 ? 'text-red-400' : 'text-gray-300']">
            {{ deltaValues.clicks > 0 ? '↑' : '↓' }} {{ Math.abs(deltaValues.clicks) }}% vs prior period
          </p>
        </template>
      </div>

      <!-- Pages / Session -->
      <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-900/5">
        <div class="mb-3 flex items-center justify-between">
          <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-emerald-50">
            <svg class="h-3.5 w-3.5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
            </svg>
          </div>
        </div>
        <div v-if="loading" class="space-y-1.5">
          <div class="h-2 w-24 animate-pulse rounded-full bg-gray-200" />
          <div class="h-7 w-10 animate-pulse rounded-full bg-gray-200" />
        </div>
        <template v-else>
          <p class="text-[10px] font-semibold uppercase tracking-wider text-gray-400">Pages / Visit</p>
          <p class="mt-0.5 text-2xl font-bold tabular-nums text-gray-900">{{ pagesPerSession }}</p>
          <p v-if="deltaValues.pages_per_visit != null" :class="['mt-1 text-[10px] font-semibold tabular-nums', deltaValues.pages_per_visit > 0 ? 'text-emerald-500' : deltaValues.pages_per_visit < 0 ? 'text-red-400' : 'text-gray-300']">
            {{ deltaValues.pages_per_visit > 0 ? '↑' : '↓' }} {{ Math.abs(deltaValues.pages_per_visit) }}% vs prior period
          </p>
        </template>
      </div>

      <!-- Mobile % -->
      <div class="col-span-2 sm:col-span-1 rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-900/5">
        <div class="mb-3 flex items-center justify-between">
          <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-rose-50">
            <svg class="h-3.5 w-3.5 text-rose-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3" />
            </svg>
          </div>
        </div>
        <div v-if="loading" class="space-y-1.5">
          <div class="h-2 w-20 animate-pulse rounded-full bg-gray-200" />
          <div class="h-7 w-12 animate-pulse rounded-full bg-gray-200" />
        </div>
        <template v-else>
          <p class="text-[10px] font-semibold uppercase tracking-wider text-gray-400">Mobile</p>
          <p class="mt-0.5 text-2xl font-bold tabular-nums text-gray-900">{{ mobilePercent }}%</p>
          <p v-if="deltaValues.mobile_pct != null && deltaValues.mobile_pct !== 0" :class="['mt-1 text-[10px] font-semibold tabular-nums', deltaValues.mobile_pct > 0 ? 'text-emerald-500' : 'text-red-400']">
            {{ deltaValues.mobile_pct > 0 ? '↑' : '↓' }} {{ Math.abs(deltaValues.mobile_pct) }}pp vs prior period
          </p>
        </template>
      </div>

    </div>

    <!-- ── Traffic Chart ────────────────────────────────────────────────────────── -->
    <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-900/5">
      <div class="flex flex-wrap items-start justify-between gap-4 px-6 pt-5 pb-2">
        <div>
          <h2 class="text-sm font-semibold text-gray-800">Traffic Over Time</h2>
          <p class="mt-0.5 text-xs text-gray-400">Daily page views and clicks</p>
        </div>
        <div v-if="!loading && statsData" class="flex items-center gap-4">
          <div class="flex items-center gap-1.5">
            <div class="h-2 w-4 rounded-full bg-indigo-500" />
            <span class="text-xs font-semibold tabular-nums text-gray-700">{{ fmt(statsData.totals.page_views) }}</span>
            <span class="text-xs text-gray-400">views</span>
          </div>
          <div class="flex items-center gap-1.5">
            <div class="h-2 w-4 rounded-full bg-sky-400" />
            <span class="text-xs font-semibold tabular-nums text-gray-700">{{ fmt(statsData.totals.clicks) }}</span>
            <span class="text-xs text-gray-400">clicks</span>
          </div>
        </div>
      </div>

      <div v-if="error" class="mx-6 mb-4 flex items-center justify-between rounded-lg border border-red-100 bg-red-50 px-4 py-2.5 text-sm text-red-600">
        <div class="flex items-center gap-2">
          <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
          </svg>
          {{ error }}
        </div>
        <button @click="fetchStats" class="ml-4 text-xs font-semibold underline underline-offset-2 hover:text-red-800">Retry</button>
      </div>

      <div class="px-6 pb-5">
        <AnalyticsLineChart :data="statsData?.by_date ?? []" :loading="loading" />
      </div>
    </div>

    <!-- ── Tab Navigation ──────────────────────────────────────────────────────── -->
    <div class="flex gap-1 w-fit rounded-lg bg-gray-100 p-0.5">
      <button
        v-for="tab in tabs"
        :key="tab.value"
        @click="activeTab = tab.value"
        :class="[
          'flex items-center gap-1.5 rounded-md px-4 py-1.5 text-xs font-semibold transition-all duration-150',
          activeTab === tab.value
            ? 'bg-white text-gray-900 shadow-sm'
            : 'text-gray-500 hover:text-gray-700',
        ]"
      >
        <component :is="'svg'" v-bind="tab.iconProps" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" :d="tab.iconPath" />
        </component>
        {{ tab.label }}
      </button>
    </div>

    <!-- ── Page Views Tab ──────────────────────────────────────────────────────── -->
    <template v-if="activeTab === 'pageViews'">
      <div class="grid gap-4 lg:grid-cols-3">

        <!-- Top Pages (2/3) -->
        <div class="lg:col-span-2 overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-900/5">
          <div class="flex items-center justify-between border-b border-gray-50 px-5 py-3.5">
            <div>
              <h2 class="text-sm font-semibold text-gray-800">Top Pages</h2>
              <p class="text-[10px] text-gray-400 mt-0.5">By page views in the selected period</p>
            </div>
            <span class="rounded-full bg-indigo-50 px-2 py-0.5 text-xs font-semibold text-indigo-600">{{ statsData?.top_pages?.length ?? 0 }}</span>
          </div>

          <div v-if="loading" class="space-y-0">
            <div v-for="i in 8" :key="i" class="flex items-center gap-4 border-b border-gray-50 px-5 py-3">
              <div class="h-2.5 rounded-full bg-gray-100 animate-pulse" :style="{ width: `${75 - i * 6}%` }" />
              <div class="ml-auto h-2.5 w-10 shrink-0 animate-pulse rounded-full bg-gray-100" />
              <div class="h-2.5 w-8 shrink-0 animate-pulse rounded-full bg-gray-100" />
            </div>
          </div>

          <table v-else-if="sortedTopPages.length" class="w-full text-sm">
            <thead>
              <tr class="border-b border-gray-50 bg-gray-50/60">
                <th @click="pvSort('current_path')" class="cursor-pointer select-none px-5 py-2.5 text-left text-[10px] font-semibold uppercase tracking-wider text-gray-400 hover:text-gray-600 transition-colors">
                  Page
                  <span class="ml-1" :class="pvSortCol === 'current_path' ? 'text-indigo-500' : 'text-gray-300'">{{ pvSortCol === 'current_path' ? (pvSortDir === 'desc' ? '↓' : '↑') : '↕' }}</span>
                </th>
                <th @click="pvSort('count')" class="cursor-pointer select-none px-4 py-2.5 text-right text-[10px] font-semibold uppercase tracking-wider text-gray-400 hover:text-gray-600 transition-colors whitespace-nowrap">
                  Views
                  <span class="ml-1" :class="pvSortCol === 'count' ? 'text-indigo-500' : 'text-gray-300'">{{ pvSortCol === 'count' ? (pvSortDir === 'desc' ? '↓' : '↑') : '↕' }}</span>
                </th>
                <th class="hidden md:table-cell px-4 py-2.5 text-right text-[10px] font-semibold uppercase tracking-wider text-gray-400 whitespace-nowrap">Desktop</th>
                <th class="hidden md:table-cell px-5 py-2.5 text-right text-[10px] font-semibold uppercase tracking-wider text-gray-400 whitespace-nowrap">Mobile</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
              <tr v-for="page in sortedTopPages" :key="page.current_path" class="group cursor-pointer hover:bg-gray-50/60 transition-colors" @click="openDetail('page', page.current_path)">
                <td class="px-5 py-2.5">
                  <div class="relative flex h-6 items-center overflow-hidden">
                    <div
                      class="absolute inset-y-0 left-0 rounded-r-sm bg-indigo-50 transition-all duration-700 ease-out"
                      :style="{ width: barWidth(page.count, maxTopPageCount) }"
                    />
                    <span class="relative truncate font-mono text-xs text-indigo-700 group-hover:underline max-w-60 sm:max-w-xs">/{{ page.current_path }}</span>
                  </div>
                </td>
                <td class="px-4 py-2.5 text-right font-semibold tabular-nums text-gray-900 text-xs whitespace-nowrap">{{ fmt(page.count) }}</td>
                <td class="hidden md:table-cell px-4 py-2.5 text-right tabular-nums text-xs text-gray-400 whitespace-nowrap">{{ fmt(page.desktop) }}</td>
                <td class="hidden md:table-cell px-5 py-2.5 text-right tabular-nums text-xs text-gray-400 whitespace-nowrap">{{ fmt(page.mobile) }}</td>
              </tr>
            </tbody>
          </table>

          <div v-else class="flex flex-col items-center justify-center gap-3 py-14">
            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-gray-100">
              <svg class="h-5 w-5 text-gray-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
              </svg>
            </div>
            <p class="text-sm font-medium text-gray-400">No page views recorded</p>
            <p class="text-xs text-gray-300">Data will appear as visitors browse your site</p>
          </div>
        </div>

        <!-- Right column: Devices + Referrers -->
        <div class="flex flex-col gap-4">

          <!-- Devices -->
          <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-900/5">
            <h2 class="mb-4 text-sm font-semibold text-gray-800">Devices</h2>
            <div v-if="loading" class="space-y-4">
              <div v-for="i in 3" :key="i" class="space-y-2">
                <div class="flex justify-between">
                  <div class="h-2 w-14 animate-pulse rounded-full bg-gray-100" />
                  <div class="h-2 w-10 animate-pulse rounded-full bg-gray-100" />
                </div>
                <div class="h-1.5 w-full animate-pulse rounded-full bg-gray-100" />
              </div>
            </div>
            <div v-else class="space-y-4">
              <div v-for="device in deviceBreakdown" :key="device.label">
                <div class="mb-1.5 flex items-center justify-between">
                  <div class="flex items-center gap-2">
                    <div class="h-2 w-2 rounded-full" :class="device.dotColor" />
                    <span class="text-xs font-medium text-gray-600">{{ device.label }}</span>
                  </div>
                  <div class="flex items-center gap-1.5">
                    <span class="text-xs font-semibold tabular-nums text-gray-900">{{ fmt(device.count) }}</span>
                    <span class="text-[10px] font-medium text-gray-400">{{ pct(device.count, statsData?.totals?.page_views) }}</span>
                  </div>
                </div>
                <div class="h-1.5 w-full overflow-hidden rounded-full bg-gray-100">
                  <div :class="['h-full rounded-full transition-all duration-700 ease-out', device.barColor]" :style="{ width: pct(device.count, statsData?.totals?.page_views) }" />
                </div>
              </div>
            </div>
          </div>

          <!-- Top Referrers -->
          <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-900/5">
            <div class="flex items-center justify-between border-b border-gray-50 px-5 py-3.5">
              <h2 class="text-sm font-semibold text-gray-800">Top Sources</h2>
              <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-semibold text-gray-500">{{ statsData?.top_referrers?.length ?? 0 }}</span>
            </div>
            <div v-if="loading" class="space-y-0">
              <div v-for="i in 5" :key="i" class="flex items-center gap-3 border-b border-gray-50 px-5 py-2.5">
                <div class="h-2.5 rounded-full bg-gray-100 animate-pulse" :style="{ width: `${70 - i * 10}%` }" />
                <div class="ml-auto h-2.5 w-8 shrink-0 animate-pulse rounded-full bg-gray-100" />
              </div>
            </div>
            <div v-else-if="sortedTopReferrers.length" class="divide-y divide-gray-50">
              <div v-for="ref in sortedTopReferrers" :key="ref.referrer_path" class="flex items-center gap-3 px-5 py-2.5 hover:bg-gray-50/60 transition-colors">
                <div class="relative min-w-0 flex-1">
                  <div
                    class="absolute inset-y-0 left-0 rounded-r-sm bg-violet-50 transition-all duration-700 ease-out"
                    :style="{ width: barWidth(ref.count, maxReferrerCount) }"
                  />
                  <span class="relative truncate text-xs font-medium text-gray-700 block">{{ ref.referrer_path || 'Direct' }}</span>
                </div>
                <span class="shrink-0 text-xs font-semibold tabular-nums text-gray-900">{{ fmt(ref.count) }}</span>
              </div>
            </div>
            <div v-else class="flex flex-col items-center justify-center gap-2 py-8">
              <svg class="h-5 w-5 text-gray-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244" />
              </svg>
              <p class="text-xs text-gray-400">No referrer data yet</p>
            </div>
          </div>

        </div>
      </div>
    </template>

    <!-- ── Clicks Tab ──────────────────────────────────────────────────────────── -->
    <template v-if="activeTab === 'clicks'">
      <div class="grid gap-4 lg:grid-cols-2">

        <!-- Top Clicked Pages -->
        <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-900/5">
          <div class="flex items-center justify-between border-b border-gray-50 px-5 py-3.5">
            <div>
              <h2 class="text-sm font-semibold text-gray-800">Top Clicked Pages</h2>
              <p class="text-[10px] text-gray-400 mt-0.5">Pages with the most interactions</p>
            </div>
            <span class="rounded-full bg-sky-50 px-2 py-0.5 text-[10px] font-semibold text-sky-600">{{ statsData?.top_paths?.length ?? 0 }}</span>
          </div>
          <div v-if="loading" class="space-y-0">
            <div v-for="i in 8" :key="i" class="flex items-center gap-4 border-b border-gray-50 px-5 py-3">
              <div class="h-2.5 rounded-full bg-gray-100 animate-pulse" :style="{ width: `${75 - i * 6}%` }" />
              <div class="ml-auto h-2.5 w-10 shrink-0 animate-pulse rounded-full bg-gray-100" />
            </div>
          </div>
          <div v-else-if="statsData?.top_paths?.length" class="divide-y divide-gray-50">
            <div v-for="p in statsData.top_paths" :key="p.path" class="group flex cursor-pointer items-center gap-3 px-5 py-2.5 hover:bg-gray-50/60 transition-colors" @click="openDetail('page', p.path)">
              <div class="relative min-w-0 flex-1">
                <div
                  class="absolute inset-y-0 left-0 rounded-r-sm bg-sky-50 transition-all duration-700 ease-out"
                  :style="{ width: barWidth(p.count, maxClickPathCount) }"
                />
                <span class="relative font-mono text-xs text-sky-700 group-hover:underline truncate block">/{{ p.path }}</span>
              </div>
              <span class="shrink-0 text-xs font-semibold tabular-nums text-gray-900">{{ fmt(p.count) }}</span>
              <span class="shrink-0 text-[10px] font-medium text-sky-500 w-8 text-right">{{ pct(p.count, statsData?.totals?.clicks) }}</span>
            </div>
          </div>
          <div v-else class="flex flex-col items-center justify-center gap-3 py-14">
            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-gray-100">
              <svg class="h-5 w-5 text-gray-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.042 21.672L13.684 16.6m0 0l-2.51 2.225.569-9.47 5.227 7.917-3.286-.672zM12 2.25V4.5m5.834.166l-1.591 1.591M20.25 10.5H18M7.757 14.743l-1.59 1.59M6 10.5H3.75m4.007-4.243l-1.59-1.59" />
              </svg>
            </div>
            <p class="text-sm font-medium text-gray-400">No click data recorded</p>
            <p class="text-xs text-gray-300">Clicks are tracked via the JS tracking script</p>
          </div>
        </div>

        <!-- Top Clicked Elements -->
        <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-900/5">
          <div class="flex items-center justify-between border-b border-gray-50 px-5 py-3.5">
            <div>
              <h2 class="text-sm font-semibold text-gray-800">Top Elements</h2>
              <p class="text-[10px] text-gray-400 mt-0.5">Most clicked HTML elements</p>
            </div>
            <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-semibold text-gray-500">{{ statsData?.top_elements?.length ?? 0 }}</span>
          </div>
          <div v-if="loading" class="space-y-0">
            <div v-for="i in 8" :key="i" class="flex items-center gap-4 border-b border-gray-50 px-5 py-3">
              <div class="h-5 w-14 animate-pulse rounded-md bg-gray-100" />
              <div class="h-2.5 rounded-full bg-gray-100 animate-pulse" :style="{ width: `${50 - i * 4}%` }" />
              <div class="ml-auto h-2.5 w-8 shrink-0 animate-pulse rounded-full bg-gray-100" />
            </div>
          </div>
          <div v-else-if="statsData?.top_elements?.length" class="divide-y divide-gray-50">
            <div v-for="(el, i) in statsData.top_elements" :key="i" class="flex items-center gap-3 px-5 py-2.5 hover:bg-gray-50/60 transition-colors">
              <span class="shrink-0 inline-flex items-center rounded-md bg-sky-50 px-2 py-0.5 font-mono text-[11px] font-semibold text-sky-700 ring-1 ring-inset ring-sky-600/10">&lt;{{ el.element }}&gt;</span>
              <div class="min-w-0 flex-1">
                <span v-if="el.element_id" class="font-mono text-xs font-semibold text-amber-600 truncate block">#{{ el.element_id }}</span>
                <span v-else-if="el.element_class" class="font-mono text-xs text-gray-400 truncate block">.{{ el.element_class.split(' ')[0] }}</span>
                <span v-else class="text-xs text-gray-300">—</span>
              </div>
              <span class="shrink-0 text-xs font-semibold tabular-nums text-gray-900">{{ fmt(el.count) }}</span>
            </div>
          </div>
          <div v-else class="flex flex-col items-center justify-center gap-3 py-14">
            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-gray-100">
              <svg class="h-5 w-5 text-gray-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 7.5l3 2.25-3 2.25m4.5 0h3m-9 8.25h13.5A2.25 2.25 0 0021 18V6a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 6v12a2.25 2.25 0 002.25 2.25z" />
              </svg>
            </div>
            <p class="text-sm font-medium text-gray-400">No element clicks recorded</p>
            <p class="text-xs text-gray-300">Ensure the tracking script is installed</p>
          </div>
        </div>

      </div>
    </template>

    <!-- ── Visitors Tab ────────────────────────────────────────────────────────── -->
    <template v-if="activeTab === 'visitors'">

      <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-900/5">

        <!-- Table header -->
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-50 px-5 py-3.5">
          <div>
            <h2 class="text-sm font-semibold text-gray-800">Sessions</h2>
            <p v-if="visitorMeta && !loadingVisitors" class="text-[10px] text-gray-400 mt-0.5">
              {{ fmt(visitorMeta.from) }}–{{ fmt(visitorMeta.to) }} of {{ fmt(visitorMeta.total) }} sessions
            </p>
          </div>
          <div class="flex flex-wrap items-center gap-2">
            <!-- Country filter -->
            <input
              v-model="visitorFilterCountry"
              type="text"
              placeholder="Country (US)"
              maxlength="2"
              class="w-28 rounded-lg border border-gray-200 bg-white px-2.5 py-1.5 text-xs text-gray-700 shadow-sm placeholder:text-gray-300 focus:border-indigo-300 focus:ring-1 focus:ring-indigo-300 focus:outline-none uppercase"
              @change="applyVisitorFilters"
            />
            <!-- Device filter -->
            <select
              v-model="visitorFilterDevice"
              class="rounded-lg border border-gray-200 bg-white px-2.5 py-1.5 text-xs text-gray-700 shadow-sm focus:border-indigo-300 focus:ring-1 focus:ring-indigo-300 focus:outline-none"
              @change="applyVisitorFilters"
            >
              <option value="">All devices</option>
              <option value="desktop">Desktop</option>
              <option value="mobile">Mobile</option>
              <option value="unknown">Unknown</option>
            </select>
            <!-- Browser filter -->
            <input
              v-model="visitorFilterBrowser"
              type="text"
              placeholder="Browser"
              class="w-28 rounded-lg border border-gray-200 bg-white px-2.5 py-1.5 text-xs text-gray-700 shadow-sm placeholder:text-gray-300 focus:border-indigo-300 focus:ring-1 focus:ring-indigo-300 focus:outline-none"
              @change="applyVisitorFilters"
            />
            <!-- Clear filters -->
            <button
              v-if="visitorFilterCountry || visitorFilterDevice || visitorFilterBrowser"
              @click="clearVisitorFilters"
              class="text-[10px] font-semibold text-gray-400 hover:text-gray-600 transition-colors"
            >
              Clear
            </button>
            <div v-if="errorVisitors" class="flex items-center gap-1.5 text-xs text-red-500">
              <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
              </svg>
              <button @click="fetchVisitors" class="underline underline-offset-2 font-medium">Retry</button>
            </div>
          </div>
        </div>

        <!-- Loading skeleton -->
        <div v-if="loadingVisitors" class="divide-y divide-gray-50">
          <div v-for="i in 12" :key="i" class="flex items-center gap-4 px-5 py-3">
            <div class="h-2.5 w-20 animate-pulse rounded-full bg-gray-100" />
            <div class="h-2.5 w-8 animate-pulse rounded-full bg-gray-100" />
            <div class="hidden h-2.5 w-16 animate-pulse rounded-full bg-gray-100 sm:block" />
            <div class="hidden h-2.5 w-14 animate-pulse rounded-full bg-gray-100 lg:block" />
            <div class="ml-auto h-2.5 w-6 animate-pulse rounded-full bg-gray-100" />
            <div class="h-2.5 w-6 animate-pulse rounded-full bg-gray-100" />
            <div class="h-2.5 w-12 animate-pulse rounded-full bg-gray-100" />
          </div>
        </div>

        <!-- Table -->
        <template v-else-if="visitorsData?.data?.length">
          <div class="overflow-x-auto">
            <table class="w-full">
              <thead>
                <tr class="border-b border-gray-50 bg-gray-50/60">
                  <th class="px-5 py-2.5 text-left text-[10px] font-semibold uppercase tracking-wider text-gray-400">Session</th>
                  <th class="px-4 py-2.5 text-left text-[10px] font-semibold uppercase tracking-wider text-gray-400">Location</th>
                  <th class="hidden sm:table-cell px-4 py-2.5 text-left text-[10px] font-semibold uppercase tracking-wider text-gray-400">Client</th>
                  <th class="hidden lg:table-cell px-4 py-2.5 text-left text-[10px] font-semibold uppercase tracking-wider text-gray-400">Entry</th>
                  <th class="hidden lg:table-cell px-4 py-2.5 text-left text-[10px] font-semibold uppercase tracking-wider text-gray-400">Source</th>
                  <th @click="sortVisitors('page_views')" class="cursor-pointer select-none px-4 py-2.5 text-right text-[10px] font-semibold uppercase tracking-wider transition-colors hover:text-gray-600" :class="visitorSortCol === 'page_views' ? 'text-indigo-500' : 'text-gray-400'">
                    Views <span class="ml-0.5">{{ visitorSortCol === 'page_views' ? (visitorSortDir === 'desc' ? '↓' : '↑') : '↕' }}</span>
                  </th>
                  <th @click="sortVisitors('clicks')" class="hidden sm:table-cell cursor-pointer select-none px-4 py-2.5 text-right text-[10px] font-semibold uppercase tracking-wider transition-colors hover:text-gray-600" :class="visitorSortCol === 'clicks' ? 'text-indigo-500' : 'text-gray-400'">
                    Clicks <span class="ml-0.5">{{ visitorSortCol === 'clicks' ? (visitorSortDir === 'desc' ? '↓' : '↑') : '↕' }}</span>
                  </th>
                  <th @click="sortVisitors('duration')" class="hidden sm:table-cell cursor-pointer select-none px-4 py-2.5 text-right text-[10px] font-semibold uppercase tracking-wider transition-colors hover:text-gray-600" :class="visitorSortCol === 'duration' ? 'text-indigo-500' : 'text-gray-400'">
                    Duration <span class="ml-0.5">{{ visitorSortCol === 'duration' ? (visitorSortDir === 'desc' ? '↓' : '↑') : '↕' }}</span>
                  </th>
                  <th @click="sortVisitors('last_seen_at')" class="cursor-pointer select-none px-5 py-2.5 text-right text-[10px] font-semibold uppercase tracking-wider transition-colors hover:text-gray-600" :class="visitorSortCol === 'last_seen_at' ? 'text-indigo-500' : 'text-gray-400'">
                    Last Seen <span class="ml-0.5">{{ visitorSortCol === 'last_seen_at' ? (visitorSortDir === 'desc' ? '↓' : '↑') : '↕' }}</span>
                  </th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-50">
                <tr v-for="v in visitorsData.data" :key="v.id" class="cursor-pointer hover:bg-indigo-50/40 transition-colors" @click="openDetail('session', v.id)">

                  <!-- Session ID + device icon -->
                  <td class="px-5 py-2.5">
                    <div class="flex items-center gap-2">
                      <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-md" :class="v.device === 'mobile' ? 'bg-rose-50' : 'bg-gray-100'">
                        <svg v-if="v.device !== 'mobile'" class="h-3 w-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0H3" />
                        </svg>
                        <svg v-else class="h-3 w-3 text-rose-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3" />
                        </svg>
                      </div>
                      <div>
                        <span class="font-mono text-[11px] font-semibold text-indigo-600">{{ v.id?.slice(0, 8) }}…</span>
                        <p v-if="v.ip_address" class="font-mono text-[10px] text-gray-400">{{ v.ip_address }}</p>
                      </div>
                    </div>
                  </td>

                  <!-- Country -->
                  <td class="px-4 py-2.5">
                    <span v-if="v.country" class="flex items-center gap-1.5">
                      <span class="text-sm leading-none">{{ countryFlag(v.country) }}</span>
                      <span class="text-xs font-medium text-gray-600">{{ v.country }}</span>
                    </span>
                    <span v-else class="text-xs text-gray-300">—</span>
                  </td>

                  <!-- Browser · OS · UA -->
                  <td class="hidden sm:table-cell px-4 py-2.5">
                    <div class="flex flex-wrap items-center gap-1">
                      <span v-if="v.browser" class="inline-flex items-center rounded bg-gray-100 px-1.5 py-0.5 text-[10px] font-semibold text-gray-600">{{ v.browser }}</span>
                      <span v-if="v.os" class="text-[10px] text-gray-400">{{ v.os }}</span>
                    </div>
                    <p v-if="v.user_agent" class="mt-0.5 max-w-[180px] truncate font-mono text-[9px] text-gray-300" :title="v.user_agent">{{ v.user_agent }}</p>
                  </td>

                  <!-- Entry page -->
                  <td class="hidden lg:table-cell px-4 py-2.5">
                    <span class="font-mono text-[11px] text-gray-500 truncate block max-w-32">{{ v.entry_page ? '/' + v.entry_page.replace(/^\//, '') : '—' }}</span>
                  </td>

                  <!-- Referrer -->
                  <td class="hidden lg:table-cell px-4 py-2.5">
                    <span v-if="v.referrer" class="text-[11px] text-gray-500 truncate block max-w-28">{{ v.referrer }}</span>
                    <span v-else class="inline-flex items-center rounded bg-emerald-50 px-1.5 py-0.5 text-[10px] font-semibold text-emerald-600">Direct</span>
                  </td>

                  <!-- Page views -->
                  <td class="px-4 py-2.5 text-right">
                    <span class="text-xs font-semibold tabular-nums text-gray-900">{{ fmt(v.page_views) }}</span>
                  </td>

                  <!-- Clicks -->
                  <td class="hidden sm:table-cell px-4 py-2.5 text-right tabular-nums text-xs text-gray-500">{{ fmt(v.clicks) }}</td>

                  <!-- Duration -->
                  <td class="hidden sm:table-cell px-4 py-2.5 text-right font-mono text-[11px] text-gray-400">{{ fmtDuration(v.duration) }}</td>

                  <!-- Last active -->
                  <td class="px-5 py-2.5 text-right text-[11px] text-gray-400 whitespace-nowrap">{{ fmtRelTime(v.last_seen_at) }}</td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination -->
          <div v-if="visitorMeta && visitorMeta.last_page > 1" class="flex items-center justify-between border-t border-gray-50 px-5 py-3">
            <span class="text-xs text-gray-400">
              Page {{ visitorMeta.current_page }} of {{ fmt(visitorMeta.last_page) }}
            </span>
            <div class="flex items-center gap-1">
              <button
                @click="changePage(visitorPage - 1)"
                :disabled="visitorPage <= 1"
                class="flex items-center gap-1 rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-semibold text-gray-600 transition-colors hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40"
              >
                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                </svg>
                Prev
              </button>

              <!-- Page number pills -->
              <template v-for="p in pageNumbers" :key="p">
                <span v-if="p === '…'" class="px-1 text-xs text-gray-300">…</span>
                <button
                  v-else
                  @click="changePage(p)"
                  :class="[
                    'min-w-7 rounded-lg border px-2 py-1.5 text-xs font-semibold transition-colors',
                    p === visitorPage
                      ? 'border-indigo-200 bg-indigo-50 text-indigo-600'
                      : 'border-gray-200 text-gray-500 hover:bg-gray-50',
                  ]"
                >{{ p }}</button>
              </template>

              <button
                @click="changePage(visitorPage + 1)"
                :disabled="visitorPage >= visitorMeta.last_page"
                class="flex items-center gap-1 rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-semibold text-gray-600 transition-colors hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40"
              >
                Next
                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                </svg>
              </button>
            </div>
          </div>
        </template>

        <!-- Empty -->
        <div v-else class="flex flex-col items-center justify-center gap-3 py-16">
          <div class="flex h-12 w-12 items-center justify-center rounded-full bg-gray-100">
            <svg class="h-5 w-5 text-gray-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
            </svg>
          </div>
          <p class="text-sm font-medium text-gray-400">No sessions in this period</p>
          <p class="text-xs text-gray-300">Sessions are created when visitors arrive on your site</p>
        </div>

      </div>
    </template>

    <!-- ── Events Tab ──────────────────────────────────────────────────────────── -->
    <template v-if="activeTab === 'events'">
      <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-900/5">
        <div class="flex items-center justify-between border-b border-gray-50 px-5 py-3.5">
          <div>
            <h2 class="text-sm font-semibold text-gray-800">Custom Events</h2>
            <p class="text-[10px] text-gray-400 mt-0.5">Named events tracked via <code class="font-mono">Laraprints::track()</code> or <code class="font-mono">trackEvent()</code></p>
          </div>
          <span class="rounded-full bg-violet-50 px-2 py-0.5 text-[10px] font-semibold text-violet-600">{{ statsData?.top_events?.length ?? 0 }}</span>
        </div>

        <div v-if="loading" class="divide-y divide-gray-50">
          <div v-for="i in 6" :key="i" class="flex items-center gap-4 px-5 py-3">
            <div class="h-2.5 rounded-full bg-gray-100 animate-pulse" :style="{ width: `${60 - i * 7}%` }" />
            <div class="ml-auto h-2.5 w-10 shrink-0 animate-pulse rounded-full bg-gray-100" />
            <div class="h-2.5 w-16 shrink-0 animate-pulse rounded-full bg-gray-100" />
          </div>
        </div>

        <table v-else-if="statsData?.top_events?.length" class="w-full text-sm">
          <thead>
            <tr class="border-b border-gray-50 bg-gray-50/60">
              <th class="px-5 py-2.5 text-left text-[10px] font-semibold uppercase tracking-wider text-gray-400">Event</th>
              <th class="px-4 py-2.5 text-right text-[10px] font-semibold uppercase tracking-wider text-gray-400">Count</th>
              <th class="px-5 py-2.5 text-right text-[10px] font-semibold uppercase tracking-wider text-gray-400">Unique Sessions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50">
            <tr v-for="ev in statsData.top_events" :key="ev.name" class="hover:bg-gray-50/60 transition-colors">
              <td class="px-5 py-2.5">
                <div class="relative flex h-6 items-center overflow-hidden">
                  <div
                    class="absolute inset-y-0 left-0 rounded-r-sm bg-violet-50 transition-all duration-700 ease-out"
                    :style="{ width: barWidth(ev.count, maxEventCount) }"
                  />
                  <span class="relative font-mono text-xs font-semibold text-violet-700">{{ ev.name }}</span>
                </div>
              </td>
              <td class="px-4 py-2.5 text-right font-semibold tabular-nums text-gray-900 text-xs">{{ fmt(ev.count) }}</td>
              <td class="px-5 py-2.5 text-right tabular-nums text-xs text-gray-400">{{ fmt(ev.unique_sessions) }}</td>
            </tr>
          </tbody>
        </table>

        <div v-else class="flex flex-col items-center justify-center gap-3 py-16">
          <div class="flex h-12 w-12 items-center justify-center rounded-full bg-violet-50">
            <svg class="h-5 w-5 text-violet-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
            </svg>
          </div>
          <p class="text-sm font-medium text-gray-400">No events recorded yet</p>
          <p class="text-xs text-gray-300">Call <code class="font-mono">Laraprints::track('event_name')</code> in your app</p>
        </div>
      </div>
    </template>

  </div>

  <!-- Detail slide-over panel -->
  <AnalyticsDetailPanel
    :show="detail.show"
    :type="detail.type"
    :id="detail.id"
    :base-url="baseUrl"
    :start="getRange()?.start"
    :end="getRange()?.end"
    @close="closeDetail"
    @navigate="handleDetailNavigate"
  />

</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import axios from 'axios'
import AnalyticsLineChart from './AnalyticsLineChart.vue'
import AnalyticsDetailPanel from './AnalyticsDetailPanel.vue'

// ─── Props ────────────────────────────────────────────────────────────────────

const props = defineProps({
  baseUrl: { type: String, default: '/laraprints' },
})

// ─── Tabs ─────────────────────────────────────────────────────────────────────

const tabs = [
  {
    label: 'Pages',
    value: 'pageViews',
    iconPath: 'M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z',
  },
  {
    label: 'Clicks',
    value: 'clicks',
    iconPath: 'M15.042 21.672L13.684 16.6m0 0l-2.51 2.225.569-9.47 5.227 7.917-3.286-.672zM12 2.25V4.5m5.834.166l-1.591 1.591M20.25 10.5H18M7.757 14.743l-1.59 1.59M6 10.5H3.75m4.007-4.243l-1.59-1.59',
  },
  {
    label: 'Visitors',
    value: 'visitors',
    iconPath: 'M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z',
  },
  {
    label: 'Events',
    value: 'events',
    iconPath: 'M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z',
  },
]
const activeTab = ref('pageViews')

// ─── Date range ───────────────────────────────────────────────────────────────

const dateRanges = [
  { label: 'Today',  value: 'today'  },
  { label: '7d',     value: '7d'     },
  { label: '30d',    value: '30d'    },
  { label: '90d',    value: '90d'    },
  { label: 'Custom', value: 'custom' },
]
const dateRange   = ref('30d')
const customStart = ref('')
const customEnd   = ref('')

const subDays = (date, days) => {
  const d = new Date(date)
  d.setDate(d.getDate() - days)
  return d
}
const isoDate = (d) => d.toISOString().split('T')[0]

const getRange = () => {
  const today = new Date()
  switch (dateRange.value) {
    case 'today':  return { start: isoDate(today),              end: isoDate(today) }
    case '7d':     return { start: isoDate(subDays(today, 6)),  end: isoDate(today) }
    case '30d':    return { start: isoDate(subDays(today, 29)), end: isoDate(today) }
    case '90d':    return { start: isoDate(subDays(today, 89)), end: isoDate(today) }
    case 'custom': return { start: customStart.value,           end: customEnd.value }
  }
}

const setRange = (val) => { dateRange.value = val }

const applyCustomRange = () => {
  resetVisitors()
  fetchStats()
  if (activeTab.value === 'visitors') fetchVisitors()
}

// ─── Period label ─────────────────────────────────────────────────────────────

const periodLabel = computed(() => {
  const range = getRange()
  if (!range?.start) return ''
  const fmt = (str) => new Date(str + 'T00:00:00').toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' })
  if (range.start === range.end) return fmt(range.start)
  return `${fmt(range.start)} – ${fmt(range.end)}`
})

// ─── Stats data ───────────────────────────────────────────────────────────────

const statsData = ref(null)
const loading   = ref(false)
const error     = ref(null)

const fetchStats = async () => {
  const range = getRange()
  if (!range.start || !range.end) return
  loading.value = true
  error.value   = null
  try {
    const { data } = await axios.get(`${props.baseUrl}/stats`, { params: range })
    statsData.value = data
  } catch {
    error.value = 'Failed to load analytics data. Check your network or try again.'
  } finally {
    loading.value = false
  }
}

// ─── Visitors data ────────────────────────────────────────────────────────────

const visitorsData    = ref(null)
const loadingVisitors = ref(false)
const errorVisitors   = ref(null)
const visitorSortCol     = ref('last_seen_at')
const visitorSortDir     = ref('desc')
const visitorPage        = ref(1)
const visitorFilterCountry = ref('')
const visitorFilterDevice  = ref('')
const visitorFilterBrowser = ref('')

const visitorMeta = computed(() => visitorsData.value?.meta ?? null)

const resetVisitors = () => {
  visitorPage.value          = 1
  visitorsData.value         = null
  visitorFilterCountry.value = ''
  visitorFilterDevice.value  = ''
  visitorFilterBrowser.value = ''
}

const applyVisitorFilters = () => {
  visitorPage.value  = 1
  visitorsData.value = null
  fetchVisitors()
}

const clearVisitorFilters = () => {
  visitorFilterCountry.value = ''
  visitorFilterDevice.value  = ''
  visitorFilterBrowser.value = ''
  applyVisitorFilters()
}

const fetchVisitors = async () => {
  const range = getRange()
  if (!range.start || !range.end) return
  loadingVisitors.value = true
  errorVisitors.value   = null
  try {
    const { data } = await axios.get(`${props.baseUrl}/visitors`, {
      params: {
        ...range,
        sort:    visitorSortCol.value,
        dir:     visitorSortDir.value,
        page:    visitorPage.value,
        country: visitorFilterCountry.value || undefined,
        device:  visitorFilterDevice.value  || undefined,
        browser: visitorFilterBrowser.value || undefined,
      },
    })
    visitorsData.value = data
  } catch {
    errorVisitors.value = 'Failed to load visitor data.'
  } finally {
    loadingVisitors.value = false
  }
}

const sortVisitors = (col) => {
  if (visitorSortCol.value === col) {
    visitorSortDir.value = visitorSortDir.value === 'desc' ? 'asc' : 'desc'
  } else {
    visitorSortCol.value = col
    visitorSortDir.value = 'desc'
  }
  visitorPage.value = 1
  fetchVisitors()
}

const changePage = (page) => {
  visitorPage.value = page
  fetchVisitors()
}

// Visible page numbers with ellipsis (like GitHub/GA pagination)
const pageNumbers = computed(() => {
  const last = visitorMeta.value?.last_page ?? 1
  const cur  = visitorPage.value
  if (last <= 7) return Array.from({ length: last }, (_, i) => i + 1)
  const pages = new Set([1, 2, cur - 1, cur, cur + 1, last - 1, last].filter(p => p >= 1 && p <= last))
  const sorted = [...pages].sort((a, b) => a - b)
  const result = []
  for (let i = 0; i < sorted.length; i++) {
    if (i > 0 && sorted[i] - sorted[i - 1] > 1) result.push('…')
    result.push(sorted[i])
  }
  return result
})

// ─── Computed ─────────────────────────────────────────────────────────────────

const calcDelta = (current, previous) => {
  if (previous == null || previous === 0) return current > 0 ? null : null
  return Math.round(((current - previous) / previous) * 100)
}

const deltaValues = computed(() => {
  const t  = statsData.value?.totals
  const p  = statsData.value?.previous_totals
  if (!t || !p) return {}
  const pagesPerVisitCurrent  = t.unique_sessions > 0 ? t.page_views / t.unique_sessions : 0
  const pagesPerVisitPrevious = p.unique_sessions > 0 ? p.page_views / p.unique_sessions : 0
  const mobilePctCurrent  = t.page_views > 0 ? Math.round((t.mobile / t.page_views) * 100) : 0
  const mobilePctPrevious = p.page_views > 0 ? Math.round((p.mobile / p.page_views) * 100) : 0
  return {
    page_views:      calcDelta(t.page_views, p.page_views),
    unique_sessions: calcDelta(t.unique_sessions, p.unique_sessions),
    clicks:          calcDelta(t.clicks, p.clicks),
    pages_per_visit: calcDelta(pagesPerVisitCurrent, pagesPerVisitPrevious),
    mobile_pct:      mobilePctCurrent - mobilePctPrevious,
  }
})

const mobilePercent = computed(() => {
  const t = statsData.value?.totals
  if (!t || !t.page_views) return 0
  return Math.round((t.mobile / t.page_views) * 100)
})

const pagesPerSession = computed(() => {
  const t = statsData.value?.totals
  if (!t || !t.unique_sessions) return '–'
  return (t.page_views / t.unique_sessions).toFixed(1)
})

const deviceBreakdown = computed(() => [
  { label: 'Desktop', count: statsData.value?.totals?.desktop ?? 0, dotColor: 'bg-indigo-500', barColor: 'bg-linear-to-r from-indigo-500 to-indigo-400' },
  { label: 'Mobile',  count: statsData.value?.totals?.mobile  ?? 0, dotColor: 'bg-violet-500', barColor: 'bg-linear-to-r from-violet-500 to-violet-400' },
  { label: 'Unknown', count: statsData.value?.totals?.unknown ?? 0, dotColor: 'bg-gray-300',   barColor: 'bg-gray-300' },
])

// ─── Inline bar helpers ───────────────────────────────────────────────────────

const barWidth = (count, max) => `${Math.max(((count ?? 0) / (max || 1)) * 100, 1.5)}%`

const maxTopPageCount = computed(() =>
  Math.max(...(statsData.value?.top_pages?.map(p => p.count) ?? [1]), 1)
)
const maxReferrerCount = computed(() =>
  Math.max(...(statsData.value?.top_referrers?.map(r => r.count) ?? [1]), 1)
)
const maxClickPathCount = computed(() =>
  Math.max(...(statsData.value?.top_paths?.map(p => p.count) ?? [1]), 1)
)
const maxEventCount = computed(() =>
  Math.max(...(statsData.value?.top_events?.map(e => e.count) ?? [1]), 1)
)

// ─── Top pages sort ───────────────────────────────────────────────────────────

const pvSortCol = ref('count')
const pvSortDir = ref('desc')

const pvSort = (col) => {
  if (pvSortCol.value === col) {
    pvSortDir.value = pvSortDir.value === 'desc' ? 'asc' : 'desc'
  } else {
    pvSortCol.value = col
    pvSortDir.value = 'desc'
  }
}

const sortedTopPages = computed(() => {
  const pages = [...(statsData.value?.top_pages ?? [])]
  const col   = pvSortCol.value
  const dir   = pvSortDir.value === 'desc' ? -1 : 1
  return pages.sort((a, b) =>
    col === 'current_path'
      ? dir * a.current_path.localeCompare(b.current_path)
      : dir * ((a[col] ?? 0) - (b[col] ?? 0))
  )
})

const sortedTopReferrers = computed(() => [...(statsData.value?.top_referrers ?? [])])

// ─── Helpers ──────────────────────────────────────────────────────────────────

const fmt = (n) => (n ?? 0).toLocaleString()
const pct = (count, total) =>
  total > 0 ? `${Math.round(((count ?? 0) / total) * 100)}%` : '0%'

const fmtDuration = (seconds) => {
  if (!seconds) return '—'
  if (seconds < 60) return `${seconds}s`
  const m = Math.floor(seconds / 60)
  const s = seconds % 60
  if (m < 60) return `${m}m${s > 0 ? ` ${s}s` : ''}`
  const h  = Math.floor(m / 60)
  const rm = m % 60
  return `${h}h${rm > 0 ? ` ${rm}m` : ''}`
}

const fmtRelTime = (dateStr) => {
  if (!dateStr) return '—'
  const diff = Date.now() - new Date(dateStr).getTime()
  const mins = Math.floor(diff / 60_000)
  const hrs  = Math.floor(diff / 3_600_000)
  const days = Math.floor(diff / 86_400_000)
  if (mins < 1)  return 'just now'
  if (mins < 60) return `${mins}m ago`
  if (hrs  < 24) return `${hrs}h ago`
  return `${days}d ago`
}

const countryFlag = (code) => {
  if (!code || code.length !== 2) return ''
  const base  = 0x1F1E6
  const upper = code.toUpperCase()
  return String.fromCodePoint(base + upper.charCodeAt(0) - 65) +
         String.fromCodePoint(base + upper.charCodeAt(1) - 65)
}

// ─── Export ───────────────────────────────────────────────────────────────────

const exportOpen = ref(false)

const exportUrl = (type) => {
  const range  = getRange()
  const params = new URLSearchParams({ type, start: range.start, end: range.end })
  return `${props.baseUrl}/export?${params}`
}

const closeExportOnOutsideClick = () => {
  if (exportOpen.value) exportOpen.value = false
}

onMounted(() => {
  fetchStats()
  document.addEventListener('click', closeExportOnOutsideClick)
})

onUnmounted(() => {
  document.removeEventListener('click', closeExportOnOutsideClick)
})

// ─── Detail panel ─────────────────────────────────────────────────────────────

const detail = ref({ show: false, type: null, id: null })

const openDetail = (type, id) => {
  detail.value = { show: true, type, id }
}

const closeDetail = () => {
  detail.value = { show: false, type: null, id: null }
}

const handleDetailNavigate = ({ type, id }) => {
  detail.value = { show: true, type, id }
}

// ─── Watchers ─────────────────────────────────────────────────────────────────

watch(dateRange, (val) => {
  if (val !== 'custom') {
    resetVisitors()
    fetchStats()
    if (activeTab.value === 'visitors') fetchVisitors()
  }
})

watch(activeTab, (val) => {
  if (val === 'visitors' && !visitorsData.value) fetchVisitors()
})
</script>
