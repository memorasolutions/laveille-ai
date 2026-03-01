<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('auth::layouts.app')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">{{ __('Historique de facturation') }}</h5>
        <a href="{{ route('user.subscription') }}" class="btn btn-sm btn-outline-secondary">
            <i class="ri-arrow-left-line"></i> {{ __('Retour') }}
        </a>
    </div>
    <div class="card-body">
        @if(empty($invoices))
            <div class="text-center py-5">
                <i class="ri-file-list-3-line" style="font-size: 3rem; color: #ccc;"></i>
                <p class="mt-3 text-muted">{{ __('Aucune facture disponible') }}</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>{{ __('Date') }}</th>
                            <th>{{ __('Montant') }}</th>
                            <th>{{ __('Statut') }}</th>
                            <th>{{ __('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($invoices as $invoice)
                            <tr>
                                <td>{{ $invoice->date()->format('d/m/Y') }}</td>
                                <td>{{ $invoice->total() }}</td>
                                <td>
                                    @php
                                        $invoiceStatus = $invoice->asStripeInvoice()->status ?? 'unknown';
                                    @endphp
                                    @if($invoiceStatus === 'paid')
                                        <span class="badge bg-success">{{ __('Payée') }}</span>
                                    @elseif($invoiceStatus === 'open')
                                        <span class="badge bg-warning">{{ __('En attente') }}</span>
                                    @elseif($invoiceStatus === 'void')
                                        <span class="badge bg-danger">{{ __('Annulée') }}</span>
                                    @else
                                        <span class="badge bg-secondary">{{ $invoiceStatus }}</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('user.invoices.download', $invoice->id) }}"
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="ri-download-line"></i> {{ __('Télécharger') }}
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
