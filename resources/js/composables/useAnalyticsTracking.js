/**
 * useAnalyticsTracking
 *
 * Sets up the global click tracking listener. Call once after your Vue app
 * is mounted, typically inside createInertiaApp's setup callback.
 *
 * Basic usage (non-Inertia):
 *   import { setupClickTracking } from '@/vendor/laraprints/composables/useAnalyticsTracking'
 *   setupClickTracking()
 *
 * Inertia usage (in app.ts / app.js):
 *   import { setupClickTracking } from '@/vendor/laraprints/composables/useAnalyticsTracking'
 *   createInertiaApp({
 *     setup({ el, App, props, plugin }) {
 *       const vueApp = createApp({ render: () => h(App, props) }).use(plugin)
 *       vueApp.mount(el)
 *       setupClickTracking({ inertia: true })
 *     }
 *   })
 *
 * For @click on plain elements (div, span, tr, etc.) to be tracked, call
 * patchClickListeners() BEFORE vueApp.mount() so the patch is in place when
 * Vue attaches its event listeners:
 *
 *   import { patchClickListeners, setupClickTracking } from '@/vendor/laraprints/composables/useAnalyticsTracking'
 *   createInertiaApp({
 *     setup({ el, App, props, plugin }) {
 *       patchClickListeners()
 *       const vueApp = createApp({ render: () => h(App, props) }).use(plugin)
 *       vueApp.mount(el)
 *       setupClickTracking({ inertia: true })
 *     }
 *   })
 *
 * HTTP client resolution order:
 *   1. options.axios  — pass your own axios instance
 *   2. window.axios   — used automatically when present (standard Laravel scaffold)
 *   3. fetch          — built-in fallback; always available
 */

const getOrCreateId = (key, serverValue) => {
    const stored = window.sessionStorage.getItem(key)

    if (serverValue && serverValue !== stored) {
        window.sessionStorage.setItem(key, serverValue)
        return serverValue
    }

    if (!stored) {
        const newId = crypto.randomUUID()
        window.sessionStorage.setItem(key, newId)
        return newId
    }

    return stored
}

const getInertiaPageProps = () => {
    try {
        // Inertia stores page state on the root element
        const el = document.getElementById('app')
        if (el && el.__vue_app__) {
            const pageProps = el.__vue_app__.config.globalProperties.$inertia?.page?.props
            return pageProps ?? {}
        }
    } catch {}
    return {}
}

/**
 * Monkey-patches EventTarget.prototype.addEventListener so that any Element
 * which receives a 'click' listener gets a `data-lp-click` attribute. This is
 * what makes Vue's @click directive detectable — Vue compiles @click to a plain
 * addEventListener('click', ...) call, leaving no other trace in the DOM.
 *
 * Must be called before vueApp.mount() to catch all @click bindings.
 * Safe to call multiple times; the patch is applied only once.
 */
export const patchClickListeners = () => {
    if (window.__lp_click_patched) {
        if (typeof process !== 'undefined' && process.env?.NODE_ENV === 'development') {
            console.warn('[laraprints] patchClickListeners() called more than once — patch already applied, skipping.')
        }
        return
    }
    window.__lp_click_patched = true

    const _add = EventTarget.prototype.addEventListener
    EventTarget.prototype.addEventListener = function (type, listener, options) {
        if (type === 'click' && this instanceof Element) {
            this.setAttribute('data-lp-click', '')
        }
        return _add.call(this, type, listener, options)
    }
}

/**
 * @param {object}   options
 * @param {boolean}  [options.inertia=false]          Whether to read session/visit IDs from Inertia page props
 * @param {string}   [options.endpoint='/api/clicks']  The click tracking endpoint
 * @param {string}   [options.eventsEndpoint='/api/events'] The custom events endpoint
 * @param {string}   [options.sessionId]               Pre-seeded session ID (overrides sessionStorage)
 * @param {string}   [options.visitId]                 Pre-seeded visit ID (overrides sessionStorage)
 * @param {Function} [options.axios]                   Axios instance to use. Falls back to window.axios, then fetch.
 * @returns {{ trackEvent: (name: string, properties?: object) => void }}
 */
export const setupClickTracking = (options = {}) => {
    const endpoint       = options.endpoint       ?? '/api/clicks'
    const eventsEndpoint = options.eventsEndpoint ?? '/api/events'

    const post = (url, data) => {
        const client = options.axios ?? window.axios ?? null

        if (client) {
            return client.post(url, data)
        }

        return fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify(data),
        })
    }

    /**
     * Track a custom named event with optional properties.
     *
     * @param {string} name        Event name, e.g. 'signup', 'checkout_completed'
     * @param {object} [properties] Arbitrary key/value metadata
     */
    const trackEvent = (name, properties = {}) => {
        const pageProps = options.inertia ? getInertiaPageProps() : {}
        const sessionId = getOrCreateId('tracking_session_id', options.sessionId ?? pageProps.tracking_session_id)
        const visitId   = getOrCreateId('tracking_visit_id',   options.visitId   ?? pageProps.tracking_visit_id)

        post(eventsEndpoint, {
            session_id:  sessionId,
            visit_id:    visitId,
            name,
            properties:  Object.keys(properties).length > 0 ? properties : undefined,
        }).catch((err) => console.error('[analytics] event tracking error:', err))
    }

    document.addEventListener('click', (event) => {
        const target = event.target
        const el = target?.closest(
            'button,a,input,select,textarea,[data-lp-click],[data-event-click],[data-inertia],.inertia-link'
        )

        if (!el) return

        const tagName = el.tagName.toLowerCase()

        const href = el.getAttribute('href')
        const path = href?.startsWith('/') ? href.slice(1) : window.location.pathname.slice(1) || 'home'

        let className = null
        if (el.className) {
            if (typeof el.className === 'string') {
                className = el.className
            } else if (el.className.baseVal) {
                className = el.className.baseVal
            } else if (el.classList) {
                className = Array.from(el.classList).join(' ') || null
            }
        }
        if (className && className.length > 255) {
            className = className.substring(0, 255)
        }

        const pageProps = options.inertia ? getInertiaPageProps() : {}

        const sessionId = getOrCreateId(
            'tracking_session_id',
            options.sessionId ?? pageProps.tracking_session_id
        )
        const visitId = getOrCreateId(
            'tracking_visit_id',
            options.visitId ?? pageProps.tracking_visit_id
        )

        post(endpoint, {
            session_id: sessionId,
            visit_id: visitId,
            element: tagName,
            class: className || null,
            id: el.id || null,
            style: el.getAttribute('style') || null,
            path,
        }).catch((err) => console.error('[analytics] click tracking error:', err))
    })

    return { trackEvent }
}
