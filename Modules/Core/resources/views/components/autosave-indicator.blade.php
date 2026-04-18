@props([
    'formSelector'     => 'form',
    'saveUrl'          => '',
    'debounceMs'       => 2000,
    'contentFieldName' => 'content',
])

<div
    x-data="{
        state: 'idle',
        lastSavedAt: null,
        timeAgoText: '',
        _timer: null,
        _intervalId: null,
        _form: null,

        init() {
            this._form = this.$el.closest('{{ $formSelector }}')
                         || document.querySelector('{{ $formSelector }}');

            if (!this._form) {
                console.warn('[autosave] Formulaire introuvable avec le sélecteur « {{ $formSelector }} »');
                return;
            }

            const listen = (el) => {
                el.addEventListener('input',  () => this.scheduleSync());
                el.addEventListener('change', () => this.scheduleSync());
            };

            const contentField = this._form.querySelector('[name={{ $contentFieldName }}]');
            if (contentField) {
                const observer = new MutationObserver(() => this.scheduleSync());
                observer.observe(contentField, { attributes: true, attributeFilter: ['value'] });
                listen(contentField);
            }

            this._form.querySelectorAll('input:not([type=hidden]), textarea, select').forEach(listen);

            this._intervalId = setInterval(() => this.computeTimeAgo(), 30000);
        },

        destroy() {
            clearTimeout(this._timer);
            clearInterval(this._intervalId);
        },

        scheduleSync() {
            clearTimeout(this._timer);
            this._timer = setTimeout(() => this.save(), {{ (int) $debounceMs }});
        },

        async save() {
            if (this.state === 'saving') return;
            this.state = 'saving';

            try {
                const formData = new FormData(this._form);
                formData.delete('_token');
                formData.delete('_method');

                const response = await fetch('{{ $saveUrl }}', {
                    method: 'PATCH',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content
                                        || '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: formData,
                });

                if (response.ok) {
                    const data = await response.json();
                    this.state = 'saved';
                    if (data.saved_at) {
                        this.lastSavedAt = new Date(data.saved_at.replace(' ', 'T'));
                    } else {
                        this.lastSavedAt = new Date();
                    }
                    this.computeTimeAgo();
                    setTimeout(() => { if (this.state === 'saved') this.state = 'idle'; }, 3000);
                } else {
                    this.state = 'error';
                    setTimeout(() => { if (this.state === 'error') this.state = 'idle'; }, 5000);
                }
            } catch (e) {
                console.error('[autosave]', e);
                this.state = 'error';
                setTimeout(() => { if (this.state === 'error') this.state = 'idle'; }, 5000);
            }
        },

        computeTimeAgo() {
            if (!this.lastSavedAt) { this.timeAgoText = ''; return; }
            const diff = Math.floor((Date.now() - this.lastSavedAt.getTime()) / 1000);
            if (diff < 5)   { this.timeAgoText = 'à l\'instant'; return; }
            if (diff < 60)  { this.timeAgoText = 'il y a ' + diff + 's'; return; }
            if (diff < 3600){ this.timeAgoText = 'il y a ' + Math.floor(diff / 60) + 'min'; return; }
            this.timeAgoText = 'il y a ' + Math.floor(diff / 3600) + 'h';
        }
    }"
    x-init="init()"
    x-on:beforeunmount.window="destroy()"
    aria-live="polite"
    class="d-inline-flex align-items-center"
    style="z-index:1050;"
>
    <span x-show="state === 'idle'" x-cloak
          class="badge bg-light text-muted border rounded-pill px-3 py-2 small">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor"
             class="bi bi-cloud me-1" viewBox="0 0 16 16">
            <path d="M4.406 3.342A5.53 5.53 0 0 1 8 2c2.69 0 4.923 2 5.166 4.579C14.758 6.804 16 8.137 16 9.773
                     16 11.569 14.502 13 12.687 13H3.781C1.708 13 0 11.366 0 9.318c0-1.763 1.266-3.223
                     2.942-3.593.143-.863.698-1.723 1.464-2.383z"/>
        </svg>
        Auto-save activé
    </span>

    <span x-show="state === 'saving'" x-cloak
          class="badge bg-light text-primary border rounded-pill px-3 py-2 small">
        <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
        Sauvegarde…
    </span>

    <span x-show="state === 'saved'" x-cloak
          class="badge bg-light text-success border rounded-pill px-3 py-2 small">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor"
             class="bi bi-check-circle-fill me-1" viewBox="0 0 16 16">
            <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM6.97 11.03a.75.75 0 0 0
                     1.07 0l3.992-3.992a.75.75 0 0 0-1.07-1.06L7.5 9.439 5.53
                     7.47a.75.75 0 0 0-1.06 1.06l2.5 2.5z"/>
        </svg>
        Brouillon sauvegardé <span x-text="timeAgoText ? '• ' + timeAgoText : ''" class="ms-1"></span>
    </span>

    <span x-show="state === 'error'" x-cloak
          class="badge bg-light text-danger border rounded-pill px-3 py-2 small">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor"
             class="bi bi-exclamation-triangle-fill me-1" viewBox="0 0 16 16">
            <path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091
                     1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982
                     1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552
                     0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002
                     6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/>
        </svg>
        Erreur sauvegarde
    </span>
</div>
