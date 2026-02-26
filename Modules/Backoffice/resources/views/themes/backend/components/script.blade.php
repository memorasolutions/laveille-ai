<!-- Vendor JS -->
<script src="{{ asset('assets/backoffice/backend/vendor/global/global.min.js') }}"></script>
<script src="{{ asset('assets/backoffice/backend/vendor/flatpickr-master/js/flatpickr.js') }}"></script>
<script src="{{ asset('assets/backoffice/backend/vendor/deznav/deznav.min.js') }}"></script>
<script src="{{ asset('assets/backoffice/backend/vendor/apexchart/apexchart.js') }}"></script>

<!-- Custom JS -->
<script src="{{ asset('assets/backoffice/backend/js/dlabnav-init.js') }}"></script>
<script src="{{ asset('assets/backoffice/backend/js/custom.js') }}"></script>

@livewireScripts
@stack('js')

<script>
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/service-worker.js');
}
</script>
@if(\Modules\Settings\Facades\Settings::get('push.web_push_enabled', false) && \Modules\Settings\Facades\Settings::get('push.vapid_public_key'))
<script>
(function() {
    const vapidKey = '{{ \Modules\Settings\Facades\Settings::get("push.vapid_public_key") }}';
    if (!('PushManager' in window) || !('serviceWorker' in navigator)) return;
    if (Notification.permission === 'denied') return;

    function urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
        const raw = atob(base64);
        const arr = new Uint8Array(raw.length);
        for (let i = 0; i < raw.length; ++i) arr[i] = raw.charCodeAt(i);
        return arr;
    }

    async function subscribePush() {
        try {
            const reg = await navigator.serviceWorker.ready;
            const sub = await reg.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array(vapidKey)
            });
            const key = sub.getKey('p256dh');
            const auth = sub.getKey('auth');
            await fetch('/api/v1/push-subscriptions', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    endpoint: sub.endpoint,
                    keys: {
                        p256dh: btoa(String.fromCharCode.apply(null, new Uint8Array(key))),
                        auth: btoa(String.fromCharCode.apply(null, new Uint8Array(auth)))
                    }
                })
            });
        } catch (e) { /* Push registration failed silently */ }
    }

    if (Notification.permission === 'granted') {
        subscribePush();
    } else if (Notification.permission === 'default') {
        Notification.requestPermission().then(function(p) { if (p === 'granted') subscribePush(); });
    }
})();
</script>
@endif
