<script src="{{ asset('assets/backoffice/wowdash/js/lib/jquery-3.7.1.min.js') }}"></script>
<script src="{{ asset('assets/backoffice/wowdash/js/lib/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('assets/backoffice/wowdash/js/lib/apexcharts.min.js') }}"></script>
<script src="{{ asset('assets/backoffice/wowdash/js/lib/dataTables.min.js') }}"></script>
<!-- jQuery UI js -->
<script src="{{ asset('assets/backoffice/wowdash/js/lib/jquery-ui.min.js') }}"></script>
<!-- Vector Map js -->
<script src="{{ asset('assets/backoffice/wowdash/js/lib/jquery-jvectormap-2.0.5.min.js') }}"></script>
<script src="{{ asset('assets/backoffice/wowdash/js/lib/jquery-jvectormap-world-mill-en.js') }}"></script>
<!-- Popup js -->
<script src="{{ asset('assets/backoffice/wowdash/js/lib/magnifc-popup.min.js') }}"></script>
<!-- Slick Slider js -->
<script src="{{ asset('assets/backoffice/wowdash/js/lib/slick.min.js') }}"></script>
<!-- prism js -->
<script src="{{ asset('assets/backoffice/wowdash/js/lib/prism.js') }}"></script>
<!-- file upload js -->
<script src="{{ asset('assets/backoffice/wowdash/js/lib/file-upload.js') }}"></script>
<!-- audio player js -->
<script src="{{ asset('assets/backoffice/wowdash/js/lib/audioplayer.js') }}"></script>
<!-- Iconify Font js -->
<script src="{{ asset('assets/backoffice/wowdash/js/lib/iconify-icon.min.js') }}"></script>
<script src="{{ asset('assets/backoffice/wowdash/js/app.js') }}"></script>
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
