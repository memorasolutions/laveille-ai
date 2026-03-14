{{-- Driver.js 1.4.0 interactive onboarding tour --}}
@props(['steps' => [], 'storageKey' => 'driver_tour_default'])

@push('plugin-styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/driver.js@1.4.0/dist/driver.css">
<style>
    .driver-popover {
        border-radius: 8px;
        font-family: inherit;
    }
    .driver-popover .driver-popover-title {
        font-weight: 600;
        font-size: 1.05rem;
    }
    .driver-popover .driver-popover-description {
        font-size: 0.9rem;
        line-height: 1.5;
    }
    .driver-popover-navigation-btns .driver-popover-prev-btn {
        background-color: #f3f4f6;
        color: #374151;
        border: 1px solid #d1d5db;
        border-radius: 6px;
    }
    .driver-popover-navigation-btns .driver-popover-next-btn {
        border-radius: 6px;
    }
</style>
@endpush

@push('plugin-scripts')
<script src="https://cdn.jsdelivr.net/npm/driver.js@1.4.0/dist/driver.js.iife.js"></script>
@endpush

@push('custom-scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var storageKey = @json($storageKey);
    var tourSteps = @json($steps);

    if (!tourSteps.length) return;

    var driverObj = window.driver.js.driver({
        showProgress: true,
        animate: true,
        overlayOpacity: 0.5,
        stagePadding: 8,
        nextBtnText: '{{ __("Suivant") }}',
        prevBtnText: '{{ __("Précédent") }}',
        doneBtnText: '{{ __("Terminer") }}',
        onDestroyStarted: function() {
            localStorage.setItem(storageKey, '1');
            driverObj.destroy();
        },
        steps: tourSteps
    });

    if (!localStorage.getItem(storageKey)) {
        setTimeout(function() { driverObj.drive(); }, 800);
    }

    var restartBtn = document.getElementById('restartTour');
    if (restartBtn) {
        restartBtn.addEventListener('click', function(e) {
            e.preventDefault();
            localStorage.removeItem(storageKey);
            driverObj.drive();
        });
    }
});
</script>
@endpush
