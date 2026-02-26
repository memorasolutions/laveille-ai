{{-- Tabler Admin Theme - Scripts Component --}}

{{-- Tabler JS --}}
<script src="{{ asset('assets/backoffice/tabler/js/tabler.min.js') }}"></script>
<script src="{{ asset('assets/backoffice/tabler/js/tabler-theme.min.js') }}"></script>

{{-- ApexCharts --}}
<script src="{{ asset('assets/backoffice/tabler/libs/apexcharts/dist/apexcharts.min.js') }}"></script>

{{-- Livewire scripts --}}
@livewireScripts

{{-- Page-level JS stack --}}
@stack('js')

{{-- PWA service worker registration --}}
<script>
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/service-worker.js').catch(() => {});
}
</script>

{{-- Web push subscription --}}
@if(config('push.web_push_enabled') && config('push.vapid_public_key'))
<script>
document.addEventListener('DOMContentLoaded', function() {
    if ('serviceWorker' in navigator && 'PushManager' in window) {
        navigator.serviceWorker.ready.then(function(reg) {
            reg.pushManager.getSubscription().then(function(sub) {
                if (!sub) {
                    const vapidKey = '{{ config("push.vapid_public_key") }}';
                    const converted = urlBase64ToUint8Array(vapidKey);
                    reg.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: converted })
                        .then(function(subscription) {
                            fetch('/api/v1/push-subscriptions', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                                },
                                body: JSON.stringify(subscription)
                            });
                        });
                }
            });
        });
    }
});

function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - base64String.length % 4) % 4);
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const rawData = window.atob(base64);
    const outputArray = new Uint8Array(rawData.length);
    for (let i = 0; i < rawData.length; ++i) {
        outputArray[i] = rawData.charCodeAt(i);
    }
    return outputArray;
}
</script>
@endif
