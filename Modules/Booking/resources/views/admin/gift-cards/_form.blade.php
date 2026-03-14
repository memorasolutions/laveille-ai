<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
<div class="card">
    <div class="card-body">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="code" class="form-label">Code <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('code') is-invalid @enderror" id="code" name="code" value="{{ old('code', $giftCard->code ?? '') }}" required>
                @error('code')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6 mb-3">
                <label for="currency" class="form-label">Devise</label>
                <input type="text" class="form-control @error('currency') is-invalid @enderror" id="currency" name="currency" value="{{ old('currency', $giftCard->currency ?? 'CAD') }}" maxlength="3">
                @error('currency')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="purchaser_name" class="form-label">Nom de l'acheteur <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('purchaser_name') is-invalid @enderror" id="purchaser_name" name="purchaser_name" value="{{ old('purchaser_name', $giftCard->purchaser_name ?? '') }}" required>
                @error('purchaser_name')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6 mb-3">
                <label for="purchaser_email" class="form-label">Courriel de l'acheteur <span class="text-danger">*</span></label>
                <input type="email" class="form-control @error('purchaser_email') is-invalid @enderror" id="purchaser_email" name="purchaser_email" value="{{ old('purchaser_email', $giftCard->purchaser_email ?? '') }}" required>
                @error('purchaser_email')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="recipient_name" class="form-label">Nom du destinataire</label>
                <input type="text" class="form-control @error('recipient_name') is-invalid @enderror" id="recipient_name" name="recipient_name" value="{{ old('recipient_name', $giftCard->recipient_name ?? '') }}">
                @error('recipient_name')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6 mb-3">
                <label for="recipient_email" class="form-label">Courriel du destinataire</label>
                <input type="email" class="form-control @error('recipient_email') is-invalid @enderror" id="recipient_email" name="recipient_email" value="{{ old('recipient_email', $giftCard->recipient_email ?? '') }}">
                @error('recipient_email')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="mb-3">
            <label for="recipient_message" class="form-label">Message personnalisé</label>
            <textarea class="form-control @error('recipient_message') is-invalid @enderror" id="recipient_message" name="recipient_message" rows="3">{{ old('recipient_message', $giftCard->recipient_message ?? '') }}</textarea>
            @error('recipient_message')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="initial_amount" class="form-label">Montant initial <span class="text-danger">*</span></label>
                <input type="number" step="0.01" class="form-control @error('initial_amount') is-invalid @enderror" id="initial_amount" name="initial_amount" value="{{ old('initial_amount', $giftCard->initial_amount ?? '') }}" required>
                @error('initial_amount')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6 mb-3">
                <label for="remaining_amount" class="form-label">Solde restant <span class="text-danger">*</span></label>
                <input type="number" step="0.01" class="form-control @error('remaining_amount') is-invalid @enderror" id="remaining_amount" name="remaining_amount" value="{{ old('remaining_amount', $giftCard->remaining_amount ?? '') }}" required>
                @error('remaining_amount')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="status" class="form-label">Statut <span class="text-danger">*</span></label>
                <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                    <option value="active" {{ old('status', $giftCard->status ?? '') == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="used" {{ old('status', $giftCard->status ?? '') == 'used' ? 'selected' : '' }}>Utilisée</option>
                    <option value="expired" {{ old('status', $giftCard->status ?? '') == 'expired' ? 'selected' : '' }}>Expirée</option>
                    <option value="exhausted" {{ old('status', $giftCard->status ?? '') == 'exhausted' ? 'selected' : '' }}>Épuisée</option>
                </select>
                @error('status')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6 mb-3">
                <label for="purchased_at" class="form-label">Date d'achat</label>
                <input type="datetime-local" class="form-control @error('purchased_at') is-invalid @enderror" id="purchased_at" name="purchased_at" value="{{ old('purchased_at', isset($giftCard) && $giftCard->purchased_at ? $giftCard->purchased_at->format('Y-m-d\TH:i') : '') }}">
                @error('purchased_at')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="expires_at" class="form-label">Date d'expiration</label>
                <input type="datetime-local" class="form-control @error('expires_at') is-invalid @enderror" id="expires_at" name="expires_at" value="{{ old('expires_at', isset($giftCard) && $giftCard->expires_at ? $giftCard->expires_at->format('Y-m-d\TH:i') : '') }}">
                @error('expires_at')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>
</div>
