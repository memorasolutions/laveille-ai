<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
<div>
    {{-- Progress --}}
    <div class="d-flex justify-content-center align-items-center gap-2 mb-4">
        @foreach(['Service', 'Date', 'Détails', 'Confirmation'] as $i => $label)
            <div class="text-center">
                <div class="rounded-circle d-inline-flex align-items-center justify-content-center {{ $step > $i + 1 ? 'bg-primary text-white' : ($step == $i + 1 ? 'bg-primary text-white' : 'bg-light text-muted') }}" style="width:36px;height:36px;font-weight:600">{{ $i + 1 }}</div>
                <div class="small mt-1 {{ $step >= $i + 1 ? 'text-primary' : 'text-muted' }}">{{ $label }}</div>
            </div>
            @if($i < 3) <div style="width:40px;height:2px" class="{{ $step > $i + 1 ? 'bg-primary' : 'bg-light' }} mt-2"></div> @endif
        @endforeach
    </div>

    {{-- Step 1: Service --}}
    @if($step === 1)
    <div class="row g-3">
        @foreach($services as $service)
        <div class="col-md-4">
            <div class="card h-100 border {{ $selectedServiceId == $service->id ? 'border-primary shadow-sm' : '' }}" role="button" wire:click="selectService({{ $service->id }})">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2">
                        @if($service->color) <span class="rounded-circle me-2" style="width:10px;height:10px;display:inline-block;background:{{ $service->color }}"></span> @endif
                        <h6 class="mb-0">{{ $service->name }}</h6>
                    </div>
                    @if($service->description) <p class="text-muted small mb-2">{{ Str::limit($service->description, 80) }}</p> @endif
                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-muted">{{ $service->duration_minutes }} min</small>
                        @if($service->price) <span class="fw-bold text-primary">{{ number_format($service->price, 2) }} $</span> @endif
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    {{-- Step 2: Date --}}
    @if($step === 2)
    <div>
        <button wire:click="goBack" class="btn btn-sm btn-outline-secondary mb-3">&larr; Retour</button>
        <h6 class="mb-3">Choisissez une date</h6>
        <div class="row g-2">
            @foreach($availableDates as $date)
            @php $d = \Carbon\Carbon::parse($date); @endphp
            <div class="col-4 col-sm-3 col-md-2">
                <button wire:click="selectDate('{{ $date }}')" class="btn w-100 {{ $selectedDate === $date ? 'btn-primary' : 'btn-outline-secondary' }}">
                    <div class="small">{{ $d->translatedFormat('M') }}</div>
                    <div class="fs-5 fw-bold">{{ $d->format('d') }}</div>
                    <div class="small">{{ $d->translatedFormat('D') }}</div>
                </button>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Step 3: Time + Form --}}
    @if($step === 3)
    <div>
        <button wire:click="goBack" class="btn btn-sm btn-outline-secondary mb-3">&larr; Retour</button>
        <div class="row g-4">
            <div class="col-lg-5">
                <h6 class="mb-3">Heure</h6>
                <div class="row g-2">
                    @foreach($availableSlots as $slot)
                    <div class="col-4">
                        <button wire:click="selectTime('{{ $slot['start'] }}')" class="btn btn-sm w-100 {{ $selectedTime === $slot['start'] ? 'btn-primary' : 'btn-outline-secondary' }}">
                            {{ $slot['start'] }}
                        </button>
                    </div>
                    @endforeach
                </div>
            </div>
            <div class="col-lg-7">
                <h6 class="mb-3">Vos coordonnées</h6>
                <div class="row g-3">
                    <div class="col-6">
                        <input type="text" wire:model="firstName" class="form-control @error('firstName') is-invalid @enderror" placeholder="Prénom *">
                        @error('firstName') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-6">
                        <input type="text" wire:model="lastName" class="form-control @error('lastName') is-invalid @enderror" placeholder="Nom *">
                        @error('lastName') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12">
                        <input type="email" wire:model="email" class="form-control @error('email') is-invalid @enderror" placeholder="Courriel *">
                        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12">
                        <input type="tel" wire:model="phone" class="form-control @error('phone') is-invalid @enderror" placeholder="Téléphone">
                        @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12">
                        <textarea wire:model="notes" class="form-control" rows="2" placeholder="Notes (optionnel)"></textarea>
                    </div>
                    <div class="col-12">
                        <button wire:click="submitBooking" wire:loading.attr="disabled" class="btn btn-primary w-100" {{ !$selectedTime ? 'disabled' : '' }}>
                            <span wire:loading.remove>Confirmer le rendez-vous</span>
                            <span wire:loading>Traitement...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Step 4: Confirmation --}}
    @if($step === 4 && $appointmentData)
    <div class="text-center py-4">
        <div class="bg-success bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-4" style="width:80px;height:80px">
            <svg width="40" height="40" fill="none" stroke="#198754" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
        </div>
        <h4 class="fw-bold mb-2">Rendez-vous confirmé!</h4>
        <p class="text-muted mb-4">Un courriel de confirmation a été envoyé à {{ $email }}.</p>

        <div class="bg-light rounded-3 p-4 text-start mx-auto" style="max-width:400px">
            <p class="mb-2"><strong>Service :</strong> {{ $appointmentData['service'] }}</p>
            <p class="mb-2"><strong>Date :</strong> {{ \Carbon\Carbon::parse($appointmentData['start_at'])->translatedFormat('l j F Y') }}</p>
            <p class="mb-0"><strong>Heure :</strong> {{ \Carbon\Carbon::parse($appointmentData['start_at'])->format('H:i') }} – {{ $appointmentData['end_at'] }}</p>
        </div>

        <div class="mt-4">
            <a href="{{ route('booking.manage', $appointmentData['cancel_token']) }}" class="btn btn-outline-primary">
                Gérer mon rendez-vous &rarr;
            </a>
        </div>
    </div>
    @endif
</div>
