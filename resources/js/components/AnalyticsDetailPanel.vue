<template>
  <Teleport to="body">
    <!-- Backdrop -->
    <Transition name="lp-fade">
      <div v-if="show" class="fixed inset-0 z-40 bg-black/25 backdrop-blur-[2px]" @click="$emit('close')" />
    </Transition>

    <!-- Slide-over panel -->
    <Transition name="lp-slide">
      <div v-if="show" class="fixed inset-y-0 right-0 z-50 flex w-full max-w-2xl flex-col bg-white shadow-2xl ring-1 ring-gray-900/10" @keydown.esc.window="$emit('close')">

        <!-- Header -->
        <div class="flex shrink-0 items-center justify-between border-b border-gray-100 bg-white px-6 py-4">
          <div class="flex items-center gap-3 min-w-0">
            <button
              v-if="history.length"
              @click="goBack"
              class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600"
              title="Go back"
            >
              <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
              </svg>
            </button>
            <div v-if="type === 'session'" class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-indigo-50">
              <svg class="h-4 w-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
              </svg>
            </div>
            <div v-else-if="type === 'visit'" class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-sky-50">
              <svg class="h-4 w-4 text-sky-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5m.75-9 3-3 2.148 2.148A12.061 12.061 0 0116.5 7.605" />
              </svg>
            </div>
            <div v-else class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-emerald-50">
              <svg class="h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
              </svg>
            </div>
            <div class="min-w-0">
              <h2 class="text-sm font-bold text-gray-900">{{ typeLabel }}</h2>
              <button
                class="flex items-center gap-1 font-mono text-[11px] text-gray-400 transition-colors hover:text-gray-600"
                @click="copyId"
                title="Copy full ID"
              >
                <span class="truncate max-w-[200px]">{{ id }}</span>
                <span v-if="copied" class="font-sans text-[10px] font-semibold text-emerald-500">Copied!</span>
                <svg v-else class="h-3 w-3 shrink-0 opacity-40" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 01-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 011.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 00-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 01-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 00-3.375-3.375h-1.5a1.125 1.125 0 01-1.125-1.125v-1.5a3.375 3.375 0 00-3.375-3.375H9.375" />
                </svg>
              </button>
            </div>
          </div>
          <button
            @click="$emit('close')"
            class="ml-4 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600"
          >
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Body -->
        <div class="flex-1 overflow-y-auto">

          <!-- Loading skeleton -->
          <div v-if="loading" class="space-y-5 p-6">
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
              <div v-for="i in 6" :key="i" class="rounded-xl bg-gray-50 p-3">
                <div class="mb-2 h-2 w-16 animate-pulse rounded-full bg-gray-200" />
                <div class="h-3 w-24 animate-pulse rounded-full bg-gray-200" />
              </div>
            </div>
            <div class="space-y-2">
              <div v-for="i in 8" :key="i" class="h-8 animate-pulse rounded-lg bg-gray-100" />
            </div>
          </div>

          <!-- Error -->
          <div v-else-if="error" class="flex flex-col items-center justify-center gap-3 p-12">
            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-red-50">
              <svg class="h-5 w-5 text-red-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
              </svg>
            </div>
            <p class="text-sm font-medium text-gray-500">{{ error }}</p>
            <button @click="load" class="text-xs font-semibold text-indigo-600 underline underline-offset-2">Retry</button>
          </div>

          <!-- ── Session detail ──────────────────────────────────────────────── -->
          <div v-else-if="data && type === 'session'" class="space-y-6 p-6">

            <!-- Meta grid -->
            <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
              <div v-for="row in sessionMeta" :key="row.label" :class="['rounded-xl bg-gray-50 px-3 py-2.5', row.full ? 'col-span-2 sm:col-span-3' : '']">
                <p class="mb-0.5 text-[10px] font-semibold uppercase tracking-wider text-gray-400">{{ row.label }}</p>
                <p :class="['truncate text-xs font-medium text-gray-800', row.mono ? 'font-mono text-[11px]' : '']">{{ row.value }}</p>
              </div>
            </div>

            <!-- Visits -->
            <div class="overflow-hidden rounded-xl bg-white ring-1 ring-gray-100">
              <div class="flex items-center justify-between border-b border-gray-50 px-4 py-3">
                <h3 class="text-xs font-semibold text-gray-700">Visits</h3>
                <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-semibold text-gray-500">{{ data.visits?.length ?? 0 }}</span>
              </div>
              <table v-if="data.visits?.length" class="w-full text-xs">
                <thead>
                  <tr class="border-b border-gray-100 bg-gray-50/70">
                    <th class="px-4 py-2 text-left font-semibold uppercase tracking-wider text-gray-400">Visit ID</th>
                    <th class="px-4 py-2 text-left font-semibold uppercase tracking-wider text-gray-400">Entry</th>
                    <th class="px-3 py-2 text-right font-semibold uppercase tracking-wider text-gray-400">Views</th>
                    <th class="px-3 py-2 text-right font-semibold uppercase tracking-wider text-gray-400">Clicks</th>
                    <th class="px-4 py-2 text-right font-semibold uppercase tracking-wider text-gray-400">Time</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                  <tr v-for="v in data.visits" :key="v.visit_id" class="cursor-pointer transition-colors hover:bg-indigo-50/50" @click="navigate('visit', v.visit_id)">
                    <td class="px-4 py-2.5"><span class="font-mono text-[11px] text-indigo-600 hover:underline">{{ v.visit_id?.slice(0, 8) }}…</span></td>
                    <td class="max-w-[140px] truncate px-4 py-2.5 font-mono text-[11px] text-gray-600">/{{ v.entry_page }}</td>
                    <td class="px-3 py-2.5 text-right tabular-nums text-gray-700">{{ v.pages }}</td>
                    <td class="px-3 py-2.5 text-right tabular-nums text-gray-500">{{ v.clicks }}</td>
                    <td class="px-4 py-2.5 text-right text-gray-400">{{ fmtRelTime(v.first_at) }}</td>
                  </tr>
                </tbody>
              </table>
              <div v-else class="flex items-center justify-center py-8 text-xs text-gray-400">No visits found</div>
            </div>

            <!-- Page views -->
            <div class="overflow-hidden rounded-xl bg-white ring-1 ring-gray-100">
              <div class="flex items-center justify-between border-b border-gray-50 px-4 py-3">
                <h3 class="text-xs font-semibold text-gray-700">Page Views</h3>
                <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-semibold text-gray-500">{{ data.page_views?.length ?? 0 }}</span>
              </div>
              <div v-if="data.page_views?.length" class="overflow-x-auto">
                <table class="w-full text-xs">
                  <thead>
                    <tr class="border-b border-gray-100 bg-gray-50/70">
                      <th class="px-4 py-2 text-left font-semibold uppercase tracking-wider text-gray-400">Path</th>
                      <th class="px-3 py-2 text-left font-semibold uppercase tracking-wider text-gray-400">Visit</th>
                      <th class="px-3 py-2 text-left font-semibold uppercase tracking-wider text-gray-400">Device</th>
                      <th class="px-3 py-2 text-left font-semibold uppercase tracking-wider text-gray-400">IP</th>
                      <th class="px-3 py-2 text-left font-semibold uppercase tracking-wider text-gray-400">Country</th>
                      <th class="px-4 py-2 text-right font-semibold uppercase tracking-wider text-gray-400">Time</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-gray-50">
                    <tr v-for="row in data.page_views" :key="row.id" class="hover:bg-gray-50/60">
                      <td class="max-w-[140px] truncate px-4 py-2 font-mono text-[11px] text-gray-700">/{{ row.path }}</td>
                      <td class="px-3 py-2">
                        <button v-if="row.visit_id" class="font-mono text-[11px] text-sky-500 hover:underline" @click="navigate('visit', row.visit_id)">{{ row.visit_id?.slice(0, 8) }}…</button>
                        <span v-else class="text-gray-300">—</span>
                      </td>
                      <td class="px-3 py-2 capitalize text-gray-500">{{ row.device_type ?? '—' }}</td>
                      <td class="px-3 py-2 font-mono text-[10px] text-gray-400">{{ row.ip_address ?? '—' }}</td>
                      <td class="px-3 py-2 text-sm">{{ countryFlag(row.country_code) }} <span class="text-[11px] text-gray-500">{{ row.country_code ?? '' }}</span></td>
                      <td class="px-4 py-2 text-right text-[11px] text-gray-400 whitespace-nowrap">{{ fmtDatetime(row.viewed_at) }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div v-else class="flex items-center justify-center py-8 text-xs text-gray-400">No page views</div>
            </div>

            <!-- Clicks -->
            <div class="overflow-hidden rounded-xl bg-white ring-1 ring-gray-100">
              <div class="flex items-center justify-between border-b border-gray-50 px-4 py-3">
                <h3 class="text-xs font-semibold text-gray-700">Clicks</h3>
                <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-semibold text-gray-500">{{ data.clicks?.length ?? 0 }}</span>
              </div>
              <div v-if="data.clicks?.length" class="overflow-x-auto">
                <table class="w-full text-xs">
                  <thead>
                    <tr class="border-b border-gray-100 bg-gray-50/70">
                      <th class="px-4 py-2 text-left font-semibold uppercase tracking-wider text-gray-400">Element</th>
                      <th class="px-3 py-2 text-left font-semibold uppercase tracking-wider text-gray-400">Path</th>
                      <th class="px-3 py-2 text-left font-semibold uppercase tracking-wider text-gray-400">Visit</th>
                      <th class="px-4 py-2 text-right font-semibold uppercase tracking-wider text-gray-400">Time</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-gray-50">
                    <tr v-for="row in data.clicks" :key="row.id" class="hover:bg-gray-50/60">
                      <td class="px-4 py-2">
                        <span class="inline-flex items-center rounded bg-sky-50 px-1.5 py-0.5 font-mono text-[11px] font-semibold text-sky-700 ring-1 ring-inset ring-sky-600/10">&lt;{{ row.element }}&gt;</span>
                        <span v-if="row.element_id" class="ml-1.5 font-mono text-[11px] font-semibold text-amber-600">#{{ row.element_id }}</span>
                        <span v-else-if="row.element_class" class="ml-1.5 font-mono text-[11px] text-gray-400">.{{ row.element_class.split(' ')[0] }}</span>
                      </td>
                      <td class="max-w-[120px] truncate px-3 py-2 font-mono text-[11px] text-gray-600">/{{ row.path }}</td>
                      <td class="px-3 py-2">
                        <button v-if="row.visit_id" class="font-mono text-[11px] text-sky-500 hover:underline" @click="navigate('visit', row.visit_id)">{{ row.visit_id?.slice(0, 8) }}…</button>
                        <span v-else class="text-gray-300">—</span>
                      </td>
                      <td class="px-4 py-2 text-right text-[11px] text-gray-400 whitespace-nowrap">{{ fmtDatetime(row.clicked_at) }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div v-else class="flex items-center justify-center py-8 text-xs text-gray-400">No clicks</div>
            </div>

          </div>

          <!-- ── Visit detail ────────────────────────────────────────────────── -->
          <div v-else-if="data && type === 'visit'" class="space-y-6 p-6">

            <!-- Meta grid -->
            <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
              <div v-for="row in visitMeta" :key="row.label" :class="['rounded-xl bg-gray-50 px-3 py-2.5', row.full ? 'col-span-2 sm:col-span-3' : '']">
                <p class="mb-0.5 text-[10px] font-semibold uppercase tracking-wider text-gray-400">{{ row.label }}</p>
                <p :class="['truncate text-xs font-medium text-gray-800', row.mono ? 'font-mono text-[11px]' : '']">{{ row.value }}</p>
              </div>
            </div>

            <!-- Parent session -->
            <div v-if="data.session" class="overflow-hidden rounded-xl bg-white ring-1 ring-gray-100">
              <div class="border-b border-gray-50 px-4 py-3">
                <h3 class="text-xs font-semibold text-gray-700">Session</h3>
              </div>
              <button
                class="flex w-full items-center justify-between px-4 py-3 text-left transition-colors hover:bg-indigo-50/40"
                @click="navigate('session', data.session.session_id)"
              >
                <div class="flex items-center gap-3">
                  <div :class="['flex h-6 w-6 shrink-0 items-center justify-center rounded-md', data.session.device === 'mobile' ? 'bg-rose-50' : 'bg-gray-100']">
                    <svg v-if="data.session.device !== 'mobile'" class="h-3 w-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0H3" />
                    </svg>
                    <svg v-else class="h-3 w-3 text-rose-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3" />
                    </svg>
                  </div>
                  <div>
                    <p class="font-mono text-xs font-semibold text-indigo-600">{{ data.session.session_id?.slice(0, 16) }}…</p>
                    <p class="text-[11px] text-gray-400">{{ data.session.browser }} · {{ data.session.os }}</p>
                  </div>
                </div>
                <div class="flex items-center gap-3 text-right text-[11px] text-gray-400">
                  <span>{{ data.session.page_views }} views</span>
                  <span>{{ data.session.clicks }} clicks</span>
                  <svg class="h-3 w-3 text-gray-300" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                  </svg>
                </div>
              </button>
            </div>

            <!-- Pages visited -->
            <div class="overflow-hidden rounded-xl bg-white ring-1 ring-gray-100">
              <div class="flex items-center justify-between border-b border-gray-50 px-4 py-3">
                <h3 class="text-xs font-semibold text-gray-700">Pages Visited</h3>
                <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-semibold text-gray-500">{{ data.page_views?.length ?? 0 }}</span>
              </div>
              <div v-if="data.page_views?.length" class="overflow-x-auto">
                <table class="w-full text-xs">
                  <thead>
                    <tr class="border-b border-gray-100 bg-gray-50/70">
                      <th class="px-4 py-2 text-left font-semibold uppercase tracking-wider text-gray-400">Path</th>
                      <th class="px-3 py-2 text-left font-semibold uppercase tracking-wider text-gray-400">Device</th>
                      <th class="px-3 py-2 text-left font-semibold uppercase tracking-wider text-gray-400">IP</th>
                      <th class="px-3 py-2 text-left font-semibold uppercase tracking-wider text-gray-400">Country</th>
                      <th class="px-4 py-2 text-right font-semibold uppercase tracking-wider text-gray-400">Time</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-gray-50">
                    <tr v-for="row in data.page_views" :key="row.id" class="hover:bg-gray-50/60">
                      <td class="max-w-[160px] truncate px-4 py-2 font-mono text-[11px] text-gray-700">/{{ row.path }}</td>
                      <td class="px-3 py-2 capitalize text-gray-500">{{ row.device_type ?? '—' }}</td>
                      <td class="px-3 py-2 font-mono text-[10px] text-gray-400">{{ row.ip_address ?? '—' }}</td>
                      <td class="px-3 py-2 text-sm">{{ countryFlag(row.country_code) }} <span class="text-[11px] text-gray-500">{{ row.country_code ?? '' }}</span></td>
                      <td class="px-4 py-2 text-right text-[11px] text-gray-400 whitespace-nowrap">{{ fmtDatetime(row.viewed_at) }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div v-else class="flex items-center justify-center py-8 text-xs text-gray-400">No page views</div>
            </div>

            <!-- Clicks -->
            <div class="overflow-hidden rounded-xl bg-white ring-1 ring-gray-100">
              <div class="flex items-center justify-between border-b border-gray-50 px-4 py-3">
                <h3 class="text-xs font-semibold text-gray-700">Clicks</h3>
                <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-semibold text-gray-500">{{ data.clicks?.length ?? 0 }}</span>
              </div>
              <div v-if="data.clicks?.length" class="overflow-x-auto">
                <table class="w-full text-xs">
                  <thead>
                    <tr class="border-b border-gray-100 bg-gray-50/70">
                      <th class="px-4 py-2 text-left font-semibold uppercase tracking-wider text-gray-400">Element</th>
                      <th class="px-3 py-2 text-left font-semibold uppercase tracking-wider text-gray-400">Path</th>
                      <th class="px-4 py-2 text-right font-semibold uppercase tracking-wider text-gray-400">Time</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-gray-50">
                    <tr v-for="row in data.clicks" :key="row.id" class="hover:bg-gray-50/60">
                      <td class="px-4 py-2">
                        <span class="inline-flex items-center rounded bg-sky-50 px-1.5 py-0.5 font-mono text-[11px] font-semibold text-sky-700 ring-1 ring-inset ring-sky-600/10">&lt;{{ row.element }}&gt;</span>
                        <span v-if="row.element_id" class="ml-1.5 font-mono text-[11px] font-semibold text-amber-600">#{{ row.element_id }}</span>
                        <span v-else-if="row.element_class" class="ml-1.5 font-mono text-[11px] text-gray-400">.{{ row.element_class.split(' ')[0] }}</span>
                      </td>
                      <td class="max-w-[120px] truncate px-3 py-2 font-mono text-[11px] text-gray-600">/{{ row.path }}</td>
                      <td class="px-4 py-2 text-right text-[11px] text-gray-400 whitespace-nowrap">{{ fmtDatetime(row.clicked_at) }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div v-else class="flex items-center justify-center py-8 text-xs text-gray-400">No clicks</div>
            </div>

          </div>

          <!-- ── Page detail ─────────────────────────────────────────────────── -->
          <div v-else-if="data && type === 'page'" class="space-y-6 p-6">

            <!-- Stat cards -->
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
              <div v-for="card in pageStats" :key="card.label" class="rounded-xl bg-gray-50 px-3 py-2.5">
                <p class="text-[10px] font-semibold uppercase tracking-wider text-gray-400">{{ card.label }}</p>
                <p class="mt-0.5 text-lg font-bold tabular-nums text-gray-900">{{ card.value }}</p>
              </div>
            </div>

            <!-- Sessions that visited this page -->
            <div class="overflow-hidden rounded-xl bg-white ring-1 ring-gray-100">
              <div class="flex items-center justify-between border-b border-gray-50 px-4 py-3">
                <h3 class="text-xs font-semibold text-gray-700">Sessions</h3>
                <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-semibold text-gray-500">{{ data.sessions?.length ?? 0 }}</span>
              </div>
              <div v-if="data.sessions?.length" class="divide-y divide-gray-50">
                <div
                  v-for="s in data.sessions"
                  :key="s.session_id"
                  class="flex cursor-pointer items-center gap-3 px-4 py-2.5 transition-colors hover:bg-indigo-50/50"
                  @click="navigate('session', s.session_id)"
                >
                  <div :class="['flex h-6 w-6 shrink-0 items-center justify-center rounded-md', s.device === 'mobile' ? 'bg-rose-50' : 'bg-gray-100']">
                    <svg v-if="s.device !== 'mobile'" class="h-3 w-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0H3" />
                    </svg>
                    <svg v-else class="h-3 w-3 text-rose-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3" />
                    </svg>
                  </div>
                  <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2">
                      <span class="font-mono text-xs font-semibold text-indigo-600">{{ s.session_id?.slice(0, 12) }}…</span>
                      <span v-if="s.country" class="text-sm leading-none">{{ countryFlag(s.country) }}</span>
                      <span v-if="s.browser" class="rounded bg-gray-100 px-1.5 py-0.5 text-[10px] font-semibold text-gray-600">{{ s.browser }}</span>
                    </div>
                    <p v-if="s.ip_address" class="font-mono text-[10px] text-gray-400">{{ s.ip_address }}</p>
                  </div>
                  <div class="shrink-0 text-right text-[11px] text-gray-400">
                    <p>{{ s.views_on_page }} views</p>
                    <p>{{ s.clicks_on_page }} clicks</p>
                  </div>
                </div>
              </div>
              <div v-else class="flex items-center justify-center py-8 text-xs text-gray-400">No sessions found</div>
            </div>

            <!-- Page views on this page -->
            <div class="overflow-hidden rounded-xl bg-white ring-1 ring-gray-100">
              <div class="flex items-center justify-between border-b border-gray-50 px-4 py-3">
                <h3 class="text-xs font-semibold text-gray-700">Page Views</h3>
                <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-semibold text-gray-500">{{ data.page_views?.length ?? 0 }}</span>
              </div>
              <div v-if="data.page_views?.length" class="overflow-x-auto">
                <table class="w-full text-xs">
                  <thead>
                    <tr class="border-b border-gray-100 bg-gray-50/70">
                      <th class="px-4 py-2 text-left font-semibold uppercase tracking-wider text-gray-400">Session</th>
                      <th class="px-3 py-2 text-left font-semibold uppercase tracking-wider text-gray-400">Visit</th>
                      <th class="px-3 py-2 text-left font-semibold uppercase tracking-wider text-gray-400">Device</th>
                      <th class="px-3 py-2 text-left font-semibold uppercase tracking-wider text-gray-400">IP</th>
                      <th class="px-3 py-2 text-left font-semibold uppercase tracking-wider text-gray-400">Country</th>
                      <th class="px-4 py-2 text-right font-semibold uppercase tracking-wider text-gray-400">Time</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-gray-50">
                    <tr v-for="row in data.page_views" :key="row.id" class="hover:bg-gray-50/60">
                      <td class="px-4 py-2">
                        <button v-if="row.session_id" class="font-mono text-[11px] text-indigo-500 hover:underline" @click="navigate('session', row.session_id)">{{ row.session_id?.slice(0, 8) }}…</button>
                        <span v-else class="text-gray-300">—</span>
                      </td>
                      <td class="px-3 py-2">
                        <button v-if="row.visit_id" class="font-mono text-[11px] text-sky-500 hover:underline" @click="navigate('visit', row.visit_id)">{{ row.visit_id?.slice(0, 8) }}…</button>
                        <span v-else class="text-gray-300">—</span>
                      </td>
                      <td class="px-3 py-2 capitalize text-gray-500">{{ row.device_type ?? '—' }}</td>
                      <td class="px-3 py-2 font-mono text-[10px] text-gray-400">{{ row.ip_address ?? '—' }}</td>
                      <td class="px-3 py-2 text-sm">{{ countryFlag(row.country_code) }} <span class="text-[11px] text-gray-500">{{ row.country_code ?? '' }}</span></td>
                      <td class="px-4 py-2 text-right text-[11px] text-gray-400 whitespace-nowrap">{{ fmtDatetime(row.viewed_at) }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div v-else class="flex items-center justify-center py-8 text-xs text-gray-400">No page views</div>
            </div>

            <!-- Clicks on this page -->
            <div class="overflow-hidden rounded-xl bg-white ring-1 ring-gray-100">
              <div class="flex items-center justify-between border-b border-gray-50 px-4 py-3">
                <h3 class="text-xs font-semibold text-gray-700">Clicks</h3>
                <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-semibold text-gray-500">{{ data.clicks?.length ?? 0 }}</span>
              </div>
              <div v-if="data.clicks?.length" class="overflow-x-auto">
                <table class="w-full text-xs">
                  <thead>
                    <tr class="border-b border-gray-100 bg-gray-50/70">
                      <th class="px-4 py-2 text-left font-semibold uppercase tracking-wider text-gray-400">Element</th>
                      <th class="px-3 py-2 text-left font-semibold uppercase tracking-wider text-gray-400">Session</th>
                      <th class="px-3 py-2 text-left font-semibold uppercase tracking-wider text-gray-400">Visit</th>
                      <th class="px-4 py-2 text-right font-semibold uppercase tracking-wider text-gray-400">Time</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-gray-50">
                    <tr v-for="row in data.clicks" :key="row.id" class="hover:bg-gray-50/60">
                      <td class="px-4 py-2">
                        <span class="inline-flex items-center rounded bg-sky-50 px-1.5 py-0.5 font-mono text-[11px] font-semibold text-sky-700 ring-1 ring-inset ring-sky-600/10">&lt;{{ row.element }}&gt;</span>
                        <span v-if="row.element_id" class="ml-1.5 font-mono text-[11px] font-semibold text-amber-600">#{{ row.element_id }}</span>
                        <span v-else-if="row.element_class" class="ml-1.5 font-mono text-[11px] text-gray-400">.{{ row.element_class.split(' ')[0] }}</span>
                      </td>
                      <td class="px-3 py-2">
                        <button v-if="row.session_id" class="font-mono text-[11px] text-indigo-500 hover:underline" @click="navigate('session', row.session_id)">{{ row.session_id?.slice(0, 8) }}…</button>
                        <span v-else class="text-gray-300">—</span>
                      </td>
                      <td class="px-3 py-2">
                        <button v-if="row.visit_id" class="font-mono text-[11px] text-sky-500 hover:underline" @click="navigate('visit', row.visit_id)">{{ row.visit_id?.slice(0, 8) }}…</button>
                        <span v-else class="text-gray-300">—</span>
                      </td>
                      <td class="px-4 py-2 text-right text-[11px] text-gray-400 whitespace-nowrap">{{ fmtDatetime(row.clicked_at) }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div v-else class="flex items-center justify-center py-8 text-xs text-gray-400">No clicks</div>
            </div>

          </div>

        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import axios from 'axios'

const props = defineProps({
  show:    { type: Boolean, default: false },
  type:    { type: String,  default: null },
  id:      { type: String,  default: null },
  baseUrl: { type: String,  default: '/laraprints' },
  start:   { type: String,  default: null },
  end:     { type: String,  default: null },
})

const emit = defineEmits(['close', 'navigate'])

const data    = ref(null)
const loading = ref(false)
const error   = ref(null)
const history = ref([])
const copied  = ref(false)

const load = async () => {
  if (! props.type || ! props.id) return
  loading.value = true
  error.value   = null
  data.value    = null
  try {
    let url, params = {}
    if (props.type === 'session') {
      url = `${props.baseUrl}/sessions/${encodeURIComponent(props.id)}`
    } else if (props.type === 'visit') {
      url = `${props.baseUrl}/visits/${encodeURIComponent(props.id)}`
    } else {
      url    = `${props.baseUrl}/page`
      params = { path: props.id, start: props.start, end: props.end }
    }
    const { data: res } = await axios.get(url, { params })
    data.value = res
  } catch {
    error.value = 'Failed to load detail data.'
  } finally {
    loading.value = false
  }
}

watch(() => props.show, (show) => {
  if (show) {
    history.value = []
    load()
  }
}, { immediate: true })

watch(() => [props.type, props.id], () => {
  if (props.show) load()
})

const navigate = (type, id) => {
  history.value.push({ type: props.type, id: props.id })
  emit('navigate', { type, id })
}

const goBack = () => {
  const prev = history.value.pop()
  if (prev) emit('navigate', prev)
}

const copyId = async () => {
  await navigator.clipboard.writeText(props.id)
  copied.value = true
  setTimeout(() => { copied.value = false }, 2000)
}

const typeLabel = computed(() => {
  if (props.type === 'session') return 'Session Detail'
  if (props.type === 'visit')   return 'Visit Detail'
  return 'Page Detail'
})

const sessionMeta = computed(() => {
  const s = data.value?.session
  if (! s) return []
  return [
    { label: 'Device',     value: s.device ?? '—' },
    { label: 'Country',    value: s.country ? `${countryFlag(s.country)} ${s.country}` : '—' },
    { label: 'Browser',    value: s.browser ?? '—' },
    { label: 'OS',         value: s.os ?? '—' },
    { label: 'IP Address', value: s.ip_address ?? '—', mono: true },
    { label: 'User',       value: s.user_id ? `#${s.user_id}` : 'Guest', mono: true },
    { label: 'First Seen', value: fmtDatetime(s.first_seen_at) },
    { label: 'Last Seen',  value: fmtDatetime(s.last_seen_at) },
    { label: 'Duration',   value: fmtDuration(s.duration) },
    { label: 'Entry Page', value: s.entry_page ? `/${s.entry_page.replace(/^\//, '')}` : '—', mono: true },
    { label: 'Referrer',   value: s.referrer ?? 'Direct' },
    { label: 'User Agent', value: s.user_agent ?? '—', mono: true, full: true },
  ]
})

const visitMeta = computed(() => {
  const v = data.value?.visit
  if (! v) return []
  return [
    { label: 'Device',     value: v.device_type ?? '—' },
    { label: 'Country',    value: v.country_code ? `${countryFlag(v.country_code)} ${v.country_code}` : '—' },
    { label: 'IP Address', value: v.ip_address ?? '—', mono: true },
    { label: 'User',       value: v.user_id ? `#${v.user_id}` : 'Guest', mono: true },
    { label: 'Pages',      value: String(v.page_views ?? 0) },
    { label: 'Clicks',     value: String(v.clicks ?? 0) },
    { label: 'Duration',   value: fmtDuration(v.duration) },
    { label: 'Started',    value: fmtDatetime(v.first_at) },
    { label: 'Ended',      value: fmtDatetime(v.last_at) },
    { label: 'Entry',      value: v.entry_page ? `/${v.entry_page.replace(/^\//, '')}` : '—', mono: true },
    { label: 'Referrer',   value: v.referrer ?? 'Direct' },
    { label: 'User Agent', value: v.user_agent ?? '—', mono: true, full: true },
  ]
})

const pageStats = computed(() => {
  const s = data.value?.stats
  if (! s) return []
  const fmt = (n) => (n ?? 0).toLocaleString()
  const total = (s.desktop ?? 0) + (s.mobile ?? 0)
  const mobilePct = total > 0 ? Math.round(((s.mobile ?? 0) / total) * 100) : 0
  return [
    { label: 'Views',    value: fmt(s.total_views) },
    { label: 'Visitors', value: fmt(s.unique_sessions) },
    { label: 'Clicks',   value: fmt(s.total_clicks) },
    { label: 'Desktop',  value: fmt(s.desktop) },
    { label: 'Mobile',   value: fmt(s.mobile) },
    { label: 'Mobile %', value: `${mobilePct}%` },
  ]
})

const countryFlag = (code) => {
  if (! code || code.length !== 2) return ''
  const base  = 0x1F1E6
  const upper = code.toUpperCase()
  return String.fromCodePoint(base + upper.charCodeAt(0) - 65) +
         String.fromCodePoint(base + upper.charCodeAt(1) - 65)
}

const fmtDatetime = (str) => {
  if (! str) return '—'
  return new Date(str).toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' })
}

const fmtRelTime = (str) => {
  if (! str) return '—'
  const diff = Date.now() - new Date(str).getTime()
  const mins = Math.floor(diff / 60_000)
  const hrs  = Math.floor(diff / 3_600_000)
  const days = Math.floor(diff / 86_400_000)
  if (mins < 1)  return 'just now'
  if (mins < 60) return `${mins}m ago`
  if (hrs  < 24) return `${hrs}h ago`
  return `${days}d ago`
}

const fmtDuration = (seconds) => {
  if (! seconds) return '—'
  if (seconds < 60) return `${seconds}s`
  const m = Math.floor(seconds / 60)
  const s = seconds % 60
  if (m < 60) return `${m}m${s > 0 ? ` ${s}s` : ''}`
  const h  = Math.floor(m / 60)
  const rm = m % 60
  return `${h}h${rm > 0 ? ` ${rm}m` : ''}`
}
</script>

<style scoped>
.lp-fade-enter-active,
.lp-fade-leave-active  { transition: opacity 0.2s ease; }
.lp-fade-enter-from,
.lp-fade-leave-to      { opacity: 0; }

.lp-slide-enter-active,
.lp-slide-leave-active  { transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1); }
.lp-slide-enter-from,
.lp-slide-leave-to      { transform: translateX(100%); }
</style>
