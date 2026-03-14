<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item">SEO</li>
    <li class="breadcrumb-item active">Redirections</li>
@endsection

@section('title', 'Redirections URL')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0 d-flex align-items-center gap-2"><i data-lucide="arrow-right-left" class="icon-md text-primary"></i>{{ __('Redirections URL') }}</h4>
        <div class="d-flex align-items-center gap-2">
            <x-backoffice::help-modal id="helpRedirectsModal" :title="__('Redirections d\'URL – évitez les 404')" icon="arrow-right-left" :buttonLabel="__('Aide')">
                @include('seo::admin.redirects._help')
            </x-backoffice::help-modal>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#redirectModal">
                <i data-lucide="plus" class="icon-sm me-1"></i> {{ __('Ajouter') }}
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.redirects.index') }}" class="mb-4">
                <div class="input-group">
                    <span class="input-group-text"><i data-lucide="search" class="icon-sm"></i></span>
                    <input type="text" name="search" class="form-control"
                           placeholder="Rechercher par URL source ou destination..."
                           value="{{ request('search') }}">
                    <button class="btn btn-outline-secondary" type="submit">Rechercher</button>
                    @if(request('search'))
                        <a href="{{ route('admin.redirects.index') }}" class="btn btn-outline-danger">Effacer</a>
                    @endif
                </div>
            </form>

            @if($redirects->isEmpty())
                <div class="text-center py-5">
                    <i data-lucide="arrow-right" style="width:48px;height:48px" class="text-muted mb-3 d-block mx-auto"></i>
                    <h5 class="text-muted">Aucune redirection configurée.</h5>
                    <p class="text-muted">Commencez par ajouter votre premiere redirection.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>URL source</th>
                                <th>URL destination</th>
                                <th>Code</th>
                                <th>Hits</th>
                                <th>Dernier hit</th>
                                <th>Statut</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($redirects as $redirect)
                                <tr>
                                    <td class="font-monospace small">{{ $redirect->from_url }}</td>
                                    <td class="font-monospace small">{{ $redirect->to_url }}</td>
                                    <td>
                                        @php
                                            $badgeClass = match($redirect->status_code) {
                                                301 => 'bg-primary',
                                                302 => 'bg-warning text-dark',
                                                307 => 'bg-info',
                                                308 => 'bg-success',
                                                default => 'bg-secondary',
                                            };
                                        @endphp
                                        <span class="badge {{ $badgeClass }}">{{ $redirect->status_code }}</span>
                                    </td>
                                    <td>{{ number_format($redirect->hits, 0, ',', ' ') }}</td>
                                    <td>
                                        @if($redirect->last_hit_at)
                                            <span title="{{ $redirect->last_hit_at->format('Y-m-d H:i') }}">
                                                {{ $redirect->last_hit_at->diffForHumans() }}
                                            </span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge {{ $redirect->is_active ? 'bg-success' : 'bg-danger' }}">
                                            {{ $redirect->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm">
                                            <button type="button"
                                                    class="btn btn-outline-primary edit-redirect"
                                                    data-redirect="{{ json_encode($redirect) }}"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#redirectModal">
                                                <i data-lucide="pencil" class="icon-xs"></i>
                                            </button>
                                            <form action="{{ route('admin.redirects.destroy', $redirect) }}"
                                                  method="POST" class="d-inline"
                                                  onsubmit="return confirm('Supprimer cette redirection ?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger btn-sm">
                                                    <i data-lucide="trash-2" class="icon-xs"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $redirects->links() }}
                </div>
            @endif
        </div>
    </div>

    {{-- Modal ajout/edition --}}
    <div class="modal fade" id="redirectModal" tabindex="-1" aria-labelledby="redirectModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="redirectForm" method="POST" action="{{ route('admin.redirects.store') }}">
                    @csrf
                    <div id="formMethodField"></div>

                    <div class="modal-header">
                        <h5 class="modal-title" id="redirectModalLabel">Ajouter une redirection</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="from_url" class="form-label">URL source <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="from_url" name="from_url"
                                   placeholder="/ancien-url" required>
                            <div class="form-text">Utilisez * pour les wildcards (ex: /ancien/*)</div>
                        </div>

                        <div class="mb-3">
                            <label for="to_url" class="form-label">URL destination <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="to_url" name="to_url"
                                   placeholder="/nouveau-url" required>
                        </div>

                        <div class="mb-3">
                            <label for="status_code" class="form-label">Code de redirection</label>
                            <select class="form-select" id="status_code" name="status_code">
                                <option value="301">301 - Permanent</option>
                                <option value="302">302 - Temporaire</option>
                                <option value="307">307 - Temporaire (preserve methode)</option>
                                <option value="308">308 - Permanent (preserve methode)</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" checked>
                                <label class="form-check-label" for="is_active">Redirection active</label>
                            </div>
                        </div>

                        <div class="mb-0">
                            <label for="note" class="form-label">Note (optionnel)</label>
                            <textarea class="form-control" id="note" name="note" rows="2"
                                      placeholder="Raison de la redirection..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('redirectModal');
    const form = document.getElementById('redirectForm');
    const title = document.getElementById('redirectModalLabel');
    const methodField = document.getElementById('formMethodField');
    const storeUrl = @json(route('admin.redirects.store'));

    document.querySelectorAll('.edit-redirect').forEach(btn => {
        btn.addEventListener('click', function() {
            const r = JSON.parse(this.dataset.redirect);
            title.textContent = 'Modifier la redirection';
            form.action = storeUrl.replace(/\/store$/, '') + '/' + r.id;
            methodField.innerHTML = '<input type="hidden" name="_method" value="PUT">';
            document.getElementById('from_url').value = r.from_url;
            document.getElementById('to_url').value = r.to_url;
            document.getElementById('status_code').value = r.status_code;
            document.getElementById('is_active').checked = r.is_active;
            document.getElementById('note').value = r.note || '';
        });
    });

    modal.addEventListener('show.bs.modal', function(e) {
        if (!e.relatedTarget || !e.relatedTarget.classList.contains('edit-redirect')) {
            title.textContent = 'Ajouter une redirection';
            form.action = storeUrl;
            methodField.innerHTML = '';
            form.reset();
            document.getElementById('is_active').checked = true;
        }
    });
});
</script>
@endpush
