/*
 * Kleinigkeiten für den Dauerbetrieb auf dem Wandtablet.
 *
 * Alles hier ist optional: Schlägt etwas fehl oder fehlt die Unterstützung,
 * funktioniert das Dashboard unverändert weiter.
 */

// ---------------------------------------------------------------------------
// Bildschirm wach halten
// ---------------------------------------------------------------------------
//
// Die Wake-Lock-API braucht einen sicheren Kontext: HTTPS oder localhost.
// Über eine einfache http-Adresse im Heimnetz steht sie nicht zur Verfügung –
// dann hilft nur "Automatische Sperre: Nie" in den iPad-Einstellungen.

let wakeLock = null

async function keepScreenAwake() {
    if (!('wakeLock' in navigator)) return

    try {
        wakeLock = await navigator.wakeLock.request('screen')
        wakeLock.addEventListener('release', () => {
            wakeLock = null
        })
    } catch {
        // Vom Browser abgelehnt, etwa weil der Tab im Hintergrund liegt.
        wakeLock = null
    }
}

document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'visible' && wakeLock === null) {
        keepScreenAwake()
    }
})

keepScreenAwake()

// ---------------------------------------------------------------------------
// Verbindungsverlust sichtbar machen und sich selbst wieder fangen
// ---------------------------------------------------------------------------
//
// Ohne das zeigt das Dashboard nach einem Serverneustart stundenlang veraltete
// Werte an, ohne dass man es merkt.

const OFFLINE_ID = 'verbindung-verloren'
const RECHECK_MS = 15000

let offlineSince = null
let recheckTimer = null

function showOfflineBadge() {
    if (document.getElementById(OFFLINE_ID)) return

    const badge = document.createElement('div')
    badge.id = OFFLINE_ID
    badge.setAttribute('role', 'status')
    badge.className =
        'fixed bottom-4 left-1/2 z-50 -translate-x-1/2 rounded-full border border-amber-400/30 ' +
        'bg-amber-500/15 px-4 py-2 text-xs text-amber-200 shadow-lg backdrop-blur-xl'
    badge.textContent = 'Keine Verbindung zum Server – versuche es weiter …'

    document.body.appendChild(badge)
}

function hideOfflineBadge() {
    document.getElementById(OFFLINE_ID)?.remove()
}

function goneOffline() {
    if (offlineSince !== null) return

    offlineSince = Date.now()
    showOfflineBadge()

    recheckTimer = setInterval(async () => {
        try {
            // Laravels Zustandsprüfung, absichtlich ohne Zwischenspeicher.
            const response = await fetch('/up', { cache: 'no-store' })

            if (response.ok) backOnline()
        } catch {
            // Weiter warten.
        }
    }, RECHECK_MS)
}

function backOnline() {
    clearInterval(recheckTimer)
    recheckTimer = null
    offlineSince = null
    hideOfflineBadge()

    // Neu laden statt weiterzumachen: der angezeigte Stand ist zu alt.
    window.location.reload()
}

window.addEventListener('offline', goneOffline)
window.addEventListener('online', () => {
    if (offlineSince !== null) backOnline()
})

document.addEventListener('livewire:init', () => {
    Livewire.hook('request', ({ fail }) => {
        fail(({ status, preventDefault }) => {
            // 419 heißt abgelaufene Sitzung – dafür ist ein Neuladen richtig,
            // damit die Anmeldung greift.
            if (status === 419) {
                window.location.reload()
                preventDefault()

                return
            }

            goneOffline()
            preventDefault()
        })
    })
})
