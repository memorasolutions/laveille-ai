<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin', ['title' => $title, 'subtitle' => $subtitle])

@section('content')

<nav class="page-breadcrumb" aria-label="{{ __('Fil d\'Ariane') }}">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Administration') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Emails envoyés') }}</li>
    </ol>
</nav>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
    <h4 class="fw-bold mb-0 d-flex align-items-center gap-2"><i data-lucide="send" class="icon-md text-primary"></i>{{ __('Emails envoyés') }}</h4>
    <x-backoffice::help-modal id="helpMailLogModal" :title="__('Journal des emails')" icon="send" :buttonLabel="__('Aide')">
        @include('backoffice::themes.backend.mail-log._help')
    </x-backoffice::help-modal>
</div>

<div class="card">
    <div class="card-header py-3 px-4 border-bottom">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
            <h4 class="fw-bold mb-0 d-flex align-items-center gap-2">
                <i data-lucide="send" class="text-primary icon-md"></i>
                {{ __('Emails envoyés') }} ({{ $emails->total() }})
            </h4>
        </div>
    </div>
    <div class="card-body p-0">
        @if($emails->isEmpty())
            <div class="text-center py-5">
                <i data-lucide="mail" class="text-muted mb-3" style="width:64px;height:64px;opacity:.3;"></i>
                <p class="text-muted">{{ __('Aucun email envoyé pour le moment.') }}</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th class="py-3 px-4 fw-semibold text-body">{{ __('Destinataire') }}</th>
                            <th class="py-3 px-4 fw-semibold text-body">{{ __('Sujet') }}</th>
                            <th class="py-3 px-4 fw-semibold text-body">{{ __('Classe') }}</th>
                            <th class="py-3 px-4 fw-semibold text-body">{{ __('Statut') }}</th>
                            <th class="py-3 px-4 fw-semibold text-body">{{ __("Date d'envoi") }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($emails as $email)
                        <tr>
                            <td class="py-3 px-4 fw-semibold small text-body">{{ $email->to }}</td>
                            <td class="py-3 px-4 small text-muted">
                                {{ \Illuminate\Support\Str::limit($email->subject, 50) }}
                            </td>
                            <td class="py-3 px-4">
                                @if($email->mailable_class)
                                    <code class="text-primary small bg-primary bg-opacity-10 px-2 py-1 rounded">
                                        {{ class_basename($email->mailable_class) }}
                                    </code>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="py-3 px-4">
                                <span class="badge {{ $email->status === 'sent' ? 'bg-success bg-opacity-10 text-success' : 'bg-danger bg-opacity-10 text-danger' }}">
                                    {{ $email->status === 'sent' ? __('Envoyé') : __('Échoué') }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-muted small">
                                {{ $email->sent_at?->format('Y-m-d H:i:s') ?? '-' }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-footer d-flex align-items-center justify-content-between flex-wrap gap-3">
                <span class="text-muted small">{{ $emails->total() }} {{ __('entrée(s)') }}</span>
                {{ $emails->links() }}
            </div>
        @endif
    </div>
</div>

@endsection
