{{-- Toast container (top-right, auto-dismiss) --}}
<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index:1090;" id="toast-container">
    @foreach(['success' => ['bg-success', 'check-circle', 'Succès'], 'error' => ['bg-danger', 'alert-circle', 'Erreur'], 'warning' => ['bg-warning', 'alert-triangle', 'Attention'], 'info' => ['bg-info', 'info', 'Information']] as $type => [$bg, $icon, $label])
        @if(session($type))
            <div class="toast align-items-center text-white {{ $bg }} border-0 show" role="alert" aria-live="assertive" aria-atomic="true" data-bs-autohide="true" data-bs-delay="4000">
                <div class="d-flex">
                    <div class="toast-body d-flex align-items-center gap-2">
                        <i data-lucide="{{ $icon }}" class="icon-sm flex-shrink-0"></i>
                        <span>{{ session($type) }}</span>
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Fermer"></button>
                </div>
            </div>
        @endif
    @endforeach
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Auto-init session flash toasts
        document.querySelectorAll('#toast-container .toast.show').forEach(el => {
            new bootstrap.Toast(el).show();
        });
    });

    // Listen for Livewire toast events
    document.addEventListener('livewire:init', () => {
        Livewire.on('toast', (data) => {
            const d = Array.isArray(data) ? data[0] : data;
            const colors = { success: 'bg-success', error: 'bg-danger', warning: 'bg-warning', info: 'bg-info' };
            const icons = { success: 'check-circle', error: 'alert-circle', warning: 'alert-triangle', info: 'info' };
            const bg = colors[d.type] || 'bg-primary';
            const icon = icons[d.type] || 'info';

            const html = `<div class="toast align-items-center text-white ${bg} border-0" role="alert" aria-live="assertive" aria-atomic="true" data-bs-autohide="true" data-bs-delay="4000">
                <div class="d-flex">
                    <div class="toast-body d-flex align-items-center gap-2">
                        <i data-lucide="${icon}" class="icon-sm flex-shrink-0"></i>
                        <span>${d.message}</span>
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Fermer"></button>
                </div>
            </div>`;

            const container = document.getElementById('toast-container');
            container.insertAdjacentHTML('beforeend', html);
            const toastEl = container.lastElementChild;
            if (typeof lucide !== 'undefined') lucide.createIcons({nodes: [toastEl]});
            new bootstrap.Toast(toastEl).show();

            toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
        });
    });
</script>
@endpush
