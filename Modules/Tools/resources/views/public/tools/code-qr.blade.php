<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())
@section('title', $tool->name . ' - ' . config('app.name'))
@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => $tool->name, 'breadcrumbItems' => [__('Outils'), $tool->name]])
@endsection
@section('content')
<section class="wpo-blog-single-section section-padding">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 col-12">
                <div class="card shadow-sm tool-fullscreen-target" style="border-radius: var(--r-base);">
                    <div class="card-body p-4 p-md-5" x-data="qrGenerator()" x-init="renderQR(); initEditMode()">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h1 style="font-family: var(--f-heading); font-weight: 800; color: var(--c-dark); margin: 0;">{{ $tool->name }}</h1>
                                <p class="text-muted mb-0">{{ $tool->description }}</p>
                            </div>
                            <div class="d-flex gap-1">
                                @include('tools::partials.fullscreen-btn')
                                <button class="btn btn-sm" @click="jQuery('#qrHelpModal').modal('show')" style="background: var(--c-primary); color: #fff; border-radius: 50%; width: 32px; height: 32px; font-weight: 700; font-size: 1rem; padding: 0; line-height: 32px; flex-shrink: 0;" title="{{ __('Aide') }}">?</button>
                            </div>
                        </div>
                        <div class="mb-3"></div>

                        {{-- Barre sauvegarde (connectés) --}}
                        <div x-show="isAuthenticated" x-cloak style="background: rgba(11,114,133,0.04); border: 1px solid rgba(11,114,133,0.12); border-radius: 10px; padding: 12px; margin-bottom: 16px;">
                            <div class="d-flex gap-2 align-items-center">
                                <input type="text" class="form-control form-control-sm flex-fill" x-model="saveName" placeholder="{{ __('Nommer cette configuration...') }}" aria-label="{{ __('Nom de la configuration') }}" style="border-radius: 8px;">
                                <button class="btn btn-sm" @click="saveToAccount()" :disabled="!input || saving" style="background: var(--c-primary); color: #fff; border-radius: 8px; font-weight: 600; white-space: nowrap; padding: 6px 16px;"
                                        x-text="saving ? '{{ __('Sauvegarde...') }}' : (_editingId ? '{{ __('Mettre à jour') }}' : '{{ __('Sauvegarder') }}')"></button>
                            </div>
                            <div class="small mt-2" style="font-size: 0.8rem; color: var(--c-text-muted);">
                                {{ __('Retrouvez vos configurations dans') }} <a href="{{ route('user.saved') }}?tab=qr" style="color: var(--c-primary); text-decoration: underline;">{{ __('vos sauvegardes') }}</a>.
                            </div>
                            <template x-if="saveError">
                                <div class="alert alert-danger small p-1 mt-2 mb-0" style="font-size: 0.8rem; border-radius: 6px;" x-text="saveError"></div>
                            </template>
                        </div>
                        <div x-show="!isAuthenticated" x-cloak style="background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 10px; padding: 10px 14px; margin-bottom: 16px; font-size: 0.85rem; color: #0369a1;">
                            {{ __('Connectez-vous pour sauvegarder vos configurations dans votre compte.') }}
                        </div>

                        {{-- Sélecteur de type --}}
                        <div class="mb-3">
                            <label class="form-label fw-medium">{{ __('Type de contenu') }}</label>
                            <div class="d-flex flex-wrap gap-2">
                                <button class="btn btn-sm" :class="type === 'url' ? 'btn-primary' : 'btn-outline-secondary'" @click="type = 'url'; renderQR()" style="border-radius: var(--r-btn);">URL</button>
                                <button class="btn btn-sm" :class="type === 'text' ? 'btn-primary' : 'btn-outline-secondary'" @click="type = 'text'; renderQR()" style="border-radius: var(--r-btn);">{{ __('Texte') }}</button>
                                <button class="btn btn-sm" :class="type === 'wifi' ? 'btn-primary' : 'btn-outline-secondary'" @click="type = 'wifi'; renderQR()" style="border-radius: var(--r-btn);">WiFi</button>
                                <button class="btn btn-sm" :class="type === 'email' ? 'btn-primary' : 'btn-outline-secondary'" @click="type = 'email'; renderQR()" style="border-radius: var(--r-btn);">{{ __('Courriel') }}</button>
                                <button class="btn btn-sm" :class="type === 'phone' ? 'btn-primary' : 'btn-outline-secondary'" @click="type = 'phone'; renderQR()" style="border-radius: var(--r-btn);">{{ __('Téléphone') }}</button>
                                <button class="btn btn-sm" :class="type === 'sms' ? 'btn-primary' : 'btn-outline-secondary'" @click="type = 'sms'; renderQR()" style="border-radius: var(--r-btn);">SMS</button>
                                <button class="btn btn-sm" :class="type === 'whatsapp' ? 'btn-primary' : 'btn-outline-secondary'" @click="type = 'whatsapp'; renderQR()" style="border-radius: var(--r-btn);">WhatsApp</button>

                                <button class="btn btn-sm" :class="type === 'vcard' ? 'btn-primary' : 'btn-outline-secondary'" @click="type = 'vcard'; renderQR()" style="border-radius: var(--r-btn);">vCard</button>
                                <button class="btn btn-sm" :class="type === 'geo' ? 'btn-primary' : 'btn-outline-secondary'" @click="type = 'geo'; renderQR()" style="border-radius: var(--r-btn);">{{ __('Géolocalisation') }}</button>
                                <button class="btn btn-sm" :class="type === 'event' ? 'btn-primary' : 'btn-outline-secondary'" @click="type = 'event'; renderQR()" style="border-radius: var(--r-btn);">{{ __('Événement') }}</button>
                                <button class="btn btn-sm" :class="type === 'zoom' ? 'btn-primary' : 'btn-outline-secondary'" @click="type = 'zoom'; renderQR()" style="border-radius: var(--r-btn);">Zoom</button>
                                <button class="btn btn-sm" :class="type === 'paypal' ? 'btn-primary' : 'btn-outline-secondary'" @click="type = 'paypal'; renderQR()" style="border-radius: var(--r-btn);">PayPal</button>
                                <button class="btn btn-sm" :class="type === 'bitcoin' ? 'btn-primary' : 'btn-outline-secondary'" @click="type = 'bitcoin'; renderQR()" style="border-radius: var(--r-btn);">Bitcoin</button>
                                <button class="btn btn-sm" :class="type === 'instagram' ? 'btn-primary' : 'btn-outline-secondary'" @click="type = 'instagram'; renderQR()" style="border-radius: var(--r-btn);">Instagram</button>
                                <button class="btn btn-sm" :class="type === 'facebook' ? 'btn-primary' : 'btn-outline-secondary'" @click="type = 'facebook'; renderQR()" style="border-radius: var(--r-btn);">Facebook</button>
                                <button class="btn btn-sm" :class="type === 'linkedin' ? 'btn-primary' : 'btn-outline-secondary'" @click="type = 'linkedin'; renderQR()" style="border-radius: var(--r-btn);">LinkedIn</button>
                                <button class="btn btn-sm" :class="type === 'youtube' ? 'btn-primary' : 'btn-outline-secondary'" @click="type = 'youtube'; renderQR()" style="border-radius: var(--r-btn);">YouTube</button>
                                <button class="btn btn-sm" :class="type === 'twitter' ? 'btn-primary' : 'btn-outline-secondary'" @click="type = 'twitter'; renderQR()" style="border-radius: var(--r-btn);">X (Twitter)</button>
                            </div>
                        </div>

                        {{-- Champs dynamiques --}}
                        <div x-show="type === 'url' || type === 'text'" class="form-group mb-3">
                            <label class="form-label fw-medium" x-text="type === 'url' ? '{{ __('URL') }}' : '{{ __('Texte') }}'"></label>
                            <input type="text" class="form-control form-control-lg" x-model="input" @input="renderQR()" :placeholder="type === 'url' ? 'https://...' : '{{ __('Votre texte...') }}'" aria-label="{{ __('Contenu du QR') }}">
                        </div>

                        <div x-show="type === 'wifi'">
                            <div class="form-group mb-2">
                                <label class="form-label fw-medium">{{ __('Nom du réseau (SSID)') }}</label>
                                <input type="text" class="form-control" x-model="ssid" @input="renderQR()" placeholder="MonWiFi" aria-label="SSID">
                            </div>
                            <div class="form-group mb-2">
                                <label class="form-label fw-medium">{{ __('Mot de passe') }}</label>
                                <input type="text" class="form-control" x-model="wifiPass" @input="renderQR()" aria-label="{{ __('Mot de passe WiFi') }}">
                            </div>
                            <div class="form-group mb-3">
                                <label class="form-label fw-medium">{{ __('Chiffrement') }}</label>
                                <select class="form-control" x-model="encryption" @change="renderQR()" aria-label="{{ __('Chiffrement WiFi') }}">
                                    <option value="WPA">WPA/WPA2</option>
                                    <option value="WEP">WEP</option>
                                    <option value="nopass">{{ __('Aucun') }}</option>
                                </select>
                            </div>
                        </div>

                        <div x-show="type === 'email'">
                            <div class="form-group mb-2">
                                <label class="form-label fw-medium">{{ __('Destinataire') }}</label>
                                <input type="email" class="form-control" x-model="emailTo" @input="renderQR()" placeholder="nom@exemple.com" aria-label="{{ __('Destinataire') }}">
                            </div>
                            <div class="form-group mb-2">
                                <label class="form-label fw-medium">{{ __('Objet') }}</label>
                                <input type="text" class="form-control" x-model="emailSubject" @input="renderQR()" aria-label="{{ __('Objet du courriel') }}">
                            </div>
                            <div class="form-group mb-3">
                                <label class="form-label fw-medium">{{ __('Message') }}</label>
                                <textarea class="form-control" rows="2" x-model="emailBody" @input="renderQR()" aria-label="{{ __('Message du courriel') }}"></textarea>
                            </div>
                        </div>

                        <div x-show="type === 'phone'" class="form-group mb-3">
                            <label class="form-label fw-medium">{{ __('Numéro de téléphone') }}</label>
                            <input type="tel" class="form-control form-control-lg" x-model="phone" @input="renderQR()" placeholder="+1 514 555-0000" aria-label="{{ __('Numéro de téléphone') }}">
                        </div>

                        <div x-show="type === 'sms'">
                            <div class="form-group mb-2">
                                <label class="form-label fw-medium">{{ __('Numéro') }}</label>
                                <input type="tel" class="form-control" x-model="smsNumber" @input="renderQR()" placeholder="+1 514 555-0000" aria-label="{{ __('Numéro SMS') }}">
                            </div>
                            <div class="form-group mb-3">
                                <label class="form-label fw-medium">{{ __('Message') }}</label>
                                <textarea class="form-control" rows="2" x-model="smsMessage" @input="renderQR()" aria-label="{{ __('Message SMS') }}"></textarea>
                            </div>
                        </div>

                        {{-- WhatsApp --}}
                        <div x-show="type === 'whatsapp'">
                            <div class="form-group mb-2">
                                <label class="form-label fw-medium">{{ __('Numéro WhatsApp') }}</label>
                                <input type="tel" class="form-control" x-model="waNumber" @input="renderQR()" placeholder="+1 514 555-0000" aria-label="{{ __('Numéro WhatsApp') }}">
                            </div>
                            <div class="form-group mb-3">
                                <label class="form-label fw-medium">{{ __('Message') }}</label>
                                <textarea class="form-control" rows="2" x-model="waMessage" @input="renderQR()" aria-label="{{ __('Message WhatsApp') }}"></textarea>
                            </div>
                        </div>

                        {{-- vCard --}}
                        <div x-show="type === 'vcard'">
                            <div class="form-group mb-2">
                                <label class="form-label fw-medium">{{ __('Nom complet') }}</label>
                                <input type="text" class="form-control" x-model="vcName" @input="renderQR()" placeholder="Marie Dubois" aria-label="{{ __('Nom complet') }}">
                            </div>
                            <div class="form-group mb-2">
                                <label class="form-label fw-medium">{{ __('Organisation') }}</label>
                                <input type="text" class="form-control" x-model="vcOrg" @input="renderQR()" placeholder="Entreprise inc." aria-label="{{ __('Organisation') }}">
                            </div>
                            <div class="form-group mb-2">
                                <label class="form-label fw-medium">{{ __('Téléphone') }}</label>
                                <input type="tel" class="form-control" x-model="vcPhone" @input="renderQR()" placeholder="+1 514 555-0000" aria-label="{{ __('Téléphone vCard') }}">
                            </div>
                            <div class="form-group mb-2">
                                <label class="form-label fw-medium">{{ __('Courriel') }}</label>
                                <input type="email" class="form-control" x-model="vcEmail" @input="renderQR()" placeholder="nom@exemple.com" aria-label="{{ __('Courriel vCard') }}">
                            </div>
                            <div class="form-group mb-3">
                                <label class="form-label fw-medium">{{ __('Site web') }}</label>
                                <input type="url" class="form-control" x-model="vcUrl" @input="renderQR()" placeholder="https://..." aria-label="{{ __('Site web vCard') }}">
                            </div>
                        </div>

                        {{-- Géolocalisation --}}
                        <div x-show="type === 'geo'">
                            <div class="row mb-3">
                                <div class="col-md-6 mb-2">
                                    <label class="form-label fw-medium">{{ __('Latitude') }}</label>
                                    <input type="number" step="0.000001" class="form-control" x-model="geoLat" @input="renderQR()" placeholder="45.5017" aria-label="{{ __('Latitude') }}">
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label class="form-label fw-medium">{{ __('Longitude') }}</label>
                                    <input type="number" step="0.000001" class="form-control" x-model="geoLng" @input="renderQR()" placeholder="-73.5673" aria-label="{{ __('Longitude') }}">
                                </div>
                            </div>
                            <small class="text-muted d-block mb-3">{{ __('Par défaut : Montréal, Québec') }}</small>
                        </div>

                        {{-- Événement --}}
                        <div x-show="type === 'event'">
                            <div class="form-group mb-2">
                                <label class="form-label fw-medium">{{ __('Titre de l\'événement') }}</label>
                                <input type="text" class="form-control" x-model="eventTitle" @input="renderQR()" placeholder="{{ __('Réunion d\'équipe') }}" aria-label="{{ __('Titre événement') }}">
                            </div>
                            <div class="row mb-2">
                                <div class="col-md-6 mb-2">
                                    <label class="form-label fw-medium">{{ __('Début') }}</label>
                                    <input type="datetime-local" class="form-control" x-model="eventStart" @input="renderQR()" aria-label="{{ __('Date de début') }}">
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label class="form-label fw-medium">{{ __('Fin') }}</label>
                                    <input type="datetime-local" class="form-control" x-model="eventEnd" @input="renderQR()" aria-label="{{ __('Date de fin') }}">
                                </div>
                            </div>
                            <div class="form-group mb-3">
                                <label class="form-label fw-medium">{{ __('Lieu') }}</label>
                                <input type="text" class="form-control" x-model="eventLocation" @input="renderQR()" placeholder="{{ __('Salle 201, bureau Montréal') }}" aria-label="{{ __('Lieu') }}">
                            </div>
                        </div>

                        {{-- Zoom --}}
                        <div x-show="type === 'zoom'">
                            <div class="form-group mb-2">
                                <label class="form-label fw-medium">{{ __('ID de réunion') }}</label>
                                <input type="text" class="form-control" x-model="zoomId" @input="renderQR()" placeholder="123 456 7890" aria-label="{{ __('ID réunion Zoom') }}">
                            </div>
                            <div class="form-group mb-3">
                                <label class="form-label fw-medium">{{ __('Mot de passe (optionnel)') }}</label>
                                <input type="text" class="form-control" x-model="zoomPass" @input="renderQR()" aria-label="{{ __('Mot de passe Zoom') }}">
                            </div>
                        </div>

                        {{-- PayPal --}}
                        <div x-show="type === 'paypal'">
                            <div class="form-group mb-2">
                                <label class="form-label fw-medium">{{ __('Nom d\'utilisateur PayPal.me') }}</label>
                                <input type="text" class="form-control" x-model="paypalUser" @input="renderQR()" placeholder="moncompte" aria-label="{{ __('Utilisateur PayPal') }}">
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6 mb-2">
                                    <label class="form-label fw-medium">{{ __('Montant (optionnel)') }}</label>
                                    <input type="number" class="form-control" x-model="paypalAmount" @input="renderQR()" placeholder="25.00" aria-label="{{ __('Montant PayPal') }}">
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label class="form-label fw-medium">{{ __('Devise') }}</label>
                                    <select class="form-control" x-model="paypalCurrency" @change="renderQR()" aria-label="{{ __('Devise PayPal') }}">
                                        <option value="CAD">CAD</option>
                                        <option value="USD">USD</option>
                                        <option value="EUR">EUR</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        {{-- Bitcoin --}}
                        <div x-show="type === 'bitcoin'">
                            <div class="form-group mb-2">
                                <label class="form-label fw-medium">{{ __('Adresse Bitcoin') }}</label>
                                <input type="text" class="form-control" x-model="btcAddress" @input="renderQR()" placeholder="1A1zP1eP5QGefi2DMPTfTL5SLmv7DivfNa" aria-label="{{ __('Adresse Bitcoin') }}">
                            </div>
                            <div class="form-group mb-3">
                                <label class="form-label fw-medium">{{ __('Montant (optionnel)') }}</label>
                                <input type="number" step="0.00000001" class="form-control" x-model="btcAmount" @input="renderQR()" placeholder="0.001" aria-label="{{ __('Montant Bitcoin') }}">
                            </div>
                        </div>

                        {{-- Réseaux sociaux --}}
                        <div x-show="type === 'instagram'" class="form-group mb-3">
                            <label class="form-label fw-medium">{{ __('Nom d\'utilisateur Instagram') }}</label>
                            <div class="input-group">
                                <span class="input-group-addon" style="line-height: 34px; padding: 0 10px; background: #f8f9fa;">instagram.com/</span>
                                <input type="text" class="form-control" x-model="socialUser" @input="renderQR()" placeholder="moncompte" aria-label="{{ __('Utilisateur Instagram') }}">
                            </div>
                        </div>
                        <div x-show="type === 'facebook'" class="form-group mb-3">
                            <label class="form-label fw-medium">{{ __('Page ou profil Facebook') }}</label>
                            <div class="input-group">
                                <span class="input-group-addon" style="line-height: 34px; padding: 0 10px; background: #f8f9fa;">facebook.com/</span>
                                <input type="text" class="form-control" x-model="socialUser" @input="renderQR()" placeholder="laveille.ai" aria-label="{{ __('Page Facebook') }}">
                            </div>
                        </div>
                        <div x-show="type === 'linkedin'" class="form-group mb-3">
                            <label class="form-label fw-medium">{{ __('Profil LinkedIn') }}</label>
                            <div class="input-group">
                                <span class="input-group-addon" style="line-height: 34px; padding: 0 10px; background: #f8f9fa;">linkedin.com/in/</span>
                                <input type="text" class="form-control" x-model="socialUser" @input="renderQR()" placeholder="monprofil" aria-label="{{ __('Profil LinkedIn') }}">
                            </div>
                        </div>
                        <div x-show="type === 'youtube'" class="form-group mb-3">
                            <label class="form-label fw-medium">{{ __('Chaîne ou vidéo YouTube') }}</label>
                            <div class="input-group">
                                <span class="input-group-addon" style="line-height: 34px; padding: 0 10px; background: #f8f9fa;">youtube.com/</span>
                                <input type="text" class="form-control" x-model="socialUser" @input="renderQR()" placeholder="@machaîne" aria-label="{{ __('Chaîne YouTube') }}">
                            </div>
                        </div>
                        <div x-show="type === 'twitter'" class="form-group mb-3">
                            <label class="form-label fw-medium">{{ __('Profil X (Twitter)') }}</label>
                            <div class="input-group">
                                <span class="input-group-addon" style="line-height: 34px; padding: 0 10px; background: #f8f9fa;">x.com/</span>
                                <input type="text" class="form-control" x-model="socialUser" @input="renderQR()" placeholder="moncompte" aria-label="{{ __('Profil X') }}">
                            </div>
                        </div>

                        {{-- Presets visuels --}}
                        <div class="mb-3">
                            <label class="form-label fw-medium">{{ __('Style rapide') }}</label>
                            <div class="d-flex flex-wrap gap-2">
                                <template x-for="(p, pi) in presets" :key="pi">
                                    <button class="btn btn-sm" @click="applyPreset(p)" style="border-radius: var(--r-btn); font-size: 0.8rem; background: #f8f9fa; border: 1px solid #dee2e6; color: var(--c-dark);" x-text="p.name"></button>
                                </template>
                            </div>
                        </div>

                        {{-- Personnalisation --}}
                        <details class="mb-4">
                            <summary style="cursor: pointer; font-family: var(--f-heading); font-weight: 600; color: var(--c-dark);">{{ __('Personnalisation') }}</summary>
                            <div class="mt-3 p-3 rounded" style="background: #f8f9fa;">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-medium">{{ __('Taille') }} : <span x-text="qrSize + 'px'"></span></label>
                                        <input type="range" class="form-range" x-model.number="qrSize" @input="renderQR()" min="200" max="800" step="50" aria-label="{{ __('Taille du QR') }}">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-medium">{{ __('Correction d\'erreur') }}</label>
                                        <select class="form-control form-control-sm" x-model="qrErrorLevel" @change="renderQR()" aria-label="{{ __('Niveau de correction') }}">
                                            <option value="L">{{ __('Basse (L)') }}</option>
                                            <option value="M">{{ __('Moyenne (M)') }}</option>
                                            <option value="Q">{{ __('Haute (Q)') }}</option>
                                            <option value="H">{{ __('Maximale (H)') }}</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-medium">{{ __('Style des points') }}</label>
                                        <select class="form-control form-control-sm" x-model="qrDotStyle" @change="renderQR()" aria-label="{{ __('Style des points') }}">
                                            <option value="square">{{ __('Carré') }}</option>
                                            <option value="dots">{{ __('Points') }}</option>
                                            <option value="rounded">{{ __('Arrondi') }}</option>
                                            <option value="classy">{{ __('Classique') }}</option>
                                            <option value="classy-rounded">{{ __('Classique arrondi') }}</option>
                                            <option value="extra-rounded">{{ __('Très arrondi') }}</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-medium">{{ __('Style des coins') }}</label>
                                        <select class="form-control form-control-sm" x-model="qrCornerStyle" @change="renderQR()" aria-label="{{ __('Style des coins') }}">
                                            <option value="square">{{ __('Carré') }}</option>
                                            <option value="extra-rounded">{{ __('Arrondi') }}</option>
                                            <option value="dot">{{ __('Point') }}</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-medium">{{ __('Centre des coins') }}</label>
                                        <select class="form-control form-control-sm" x-model="qrCornerDotStyle" @change="renderQR()" aria-label="{{ __('Centre des coins') }}">
                                            <option value="square">{{ __('Carré') }}</option>
                                            <option value="dot">{{ __('Point') }}</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-medium">{{ __('Logo au centre') }}</label>
                                        <div class="d-flex align-items-center gap-2">
                                            <label style="cursor: pointer;">
                                                <input type="file" accept="image/*" @change="uploadLogo($event)" style="display:none" x-ref="logoInput">
                                                <span class="btn btn-sm btn-outline-secondary" @click="$refs.logoInput.click()" style="border-radius: var(--r-btn);">{{ __('Choisir un logo') }}</span>
                                            </label>
                                            <button class="btn btn-sm btn-outline-danger" x-show="qrLogo" @click="removeLogo()" style="border-radius: var(--r-btn);">{{ __('Retirer') }}</button>
                                            <img :src="qrLogo" x-show="qrLogo" style="width: 30px; height: 30px; object-fit: contain; border-radius: 4px;">
                                        </div>
                                        <small class="text-muted">{{ __('Max 500 Ko, redimensionné à 150px. Correction H recommandée. Aucune image n\'est envoyée au serveur.') }}</small>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-medium">{{ __('Options') }}</label>
                                        <div class="d-flex flex-column gap-2">
                                            <label style="display: inline-flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.85rem;">
                                                <input type="checkbox" x-model="qrTransparent" @change="renderQR()" style="display:inline-block !important; width:18px; height:18px; accent-color: var(--c-primary); margin: 0; flex-shrink: 0;"> {{ __('Fond transparent') }}
                                            </label>
                                            <label style="display: inline-flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.85rem;">
                                                <input type="checkbox" x-model="qrUseCornerColor" @change="renderQR()" style="display:inline-block !important; width:18px; height:18px; accent-color: var(--c-primary); margin: 0; flex-shrink: 0;"> {{ __('Couleur de coins séparée') }}
                                            </label>
                                            <input type="color" x-model="qrCornerColor" @input="renderQR()" x-show="qrUseCornerColor" style="width: 60px; height: 28px; border: none; cursor: pointer;" aria-label="{{ __('Couleur des coins') }}">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-medium">{{ __('Couleur QR') }}</label>
                                        <input type="color" x-model="qrColor" @input="renderQR()" style="width: 100%; height: 34px; border: none; cursor: pointer;" aria-label="{{ __('Couleur du QR') }}">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-medium">{{ __('Couleur fond') }}</label>
                                        <input type="color" x-model="qrBgColor" @input="renderQR()" style="width: 100%; height: 34px; border: none; cursor: pointer;" aria-label="{{ __('Couleur du fond') }}">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-medium">{{ __('Dégradé') }}</label>
                                        <div class="d-flex align-items-center gap-2">
                                            <input type="checkbox" x-model="qrGradient" @change="renderQR()" style="display:inline-block !important; width:18px; height:18px; accent-color: var(--c-primary);" aria-label="{{ __('Activer le dégradé') }}">
                                            <input type="color" x-model="qrGradientColor2" @input="renderQR()" x-show="qrGradient" style="width: 60px; height: 34px; border: none; cursor: pointer;" aria-label="{{ __('Deuxième couleur du dégradé') }}">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </details>

                        {{-- Cadre CTA --}}
                        <div class="mb-3">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <label style="display: inline-flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.85rem;">
                                    <input type="checkbox" x-model="qrShowFrame" style="display:inline-block !important; width:18px; height:18px; accent-color: var(--c-primary); margin: 0; flex-shrink: 0;"> {{ __('Ajouter un texte sous le QR') }}
                                </label>
                            </div>
                            <div x-show="qrShowFrame" class="p-3 rounded" style="background: #f8f9fa;">
                                <div class="form-group mb-2">
                                    <input type="text" class="form-control form-control-sm" x-model="qrFrameText" placeholder="{{ __('Scannez-moi !') }}" aria-label="{{ __('Texte du cadre') }}">
                                </div>
                                <div class="d-flex flex-wrap gap-2 align-items-end">
                                    <div>
                                        <label class="form-label" style="font-size: 0.75rem;">{{ __('Police') }}</label>
                                        <select class="form-control form-control-sm" x-model="qrFrameFont" style="width: auto;" aria-label="{{ __('Police du texte') }}">
                                            <option value="var(--f-heading)">{{ __('Titre (par défaut)') }}</option>
                                            <option value="Arial, sans-serif">Arial</option>
                                            <option value="Georgia, serif">Georgia</option>
                                            <option value="'Courier New', monospace">Courier New</option>
                                            <option value="Verdana, sans-serif">Verdana</option>
                                            <option value="Impact, sans-serif">Impact</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="form-label" style="font-size: 0.75rem;">{{ __('Taille') }}</label>
                                        <select class="form-control form-control-sm" x-model="qrFrameSize" style="width: auto;" aria-label="{{ __('Taille du texte') }}">
                                            <option value="0.8rem">{{ __('Petit') }}</option>
                                            <option value="1rem">{{ __('Moyen') }}</option>
                                            <option value="1.2rem">{{ __('Grand') }}</option>
                                            <option value="1.5rem">{{ __('Très grand') }}</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="form-label" style="font-size: 0.75rem;">{{ __('Couleur') }}</label>
                                        <input type="color" x-model="qrFrameColor" style="width: 40px; height: 30px; border: none; cursor: pointer;" aria-label="{{ __('Couleur du texte') }}">
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Aperçu QR --}}
                        <div class="text-center">
                            <div class="d-inline-block rounded shadow-sm mb-2 p-3" :style="qrShowFrame && qrFrameText ? 'border: 3px solid ' + qrColor + '; border-radius: 12px;' : ''">
                                <div x-ref="qrCanvas" style="min-height: 200px;"></div>
                                <div x-show="qrShowFrame && qrFrameText" class="mt-2 text-center" :style="'color: ' + qrFrameColor + '; font-family: ' + qrFrameFont + '; font-weight: 700; font-size: ' + qrFrameSize + ';'" x-text="qrFrameText"></div>
                            </div>
                            <div class="d-flex justify-content-center gap-2 flex-wrap mb-3">
                                <button class="btn" @click="downloadQR('png')" style="background: var(--c-accent); color: #fff; border-radius: var(--r-btn); font-family: var(--f-heading); font-weight: 700;">{{ __('Télécharger PNG') }}</button>
                                <button class="btn" @click="downloadQR('svg')" style="background: var(--c-primary); color: #fff; border-radius: var(--r-btn); font-family: var(--f-heading); font-weight: 700;">{{ __('Télécharger SVG') }}</button>
                                <button class="btn btn-outline-secondary" @click="copyData()" style="border-radius: var(--r-btn);" x-text="copied ? '{{ __('Copié !') }}' : '{{ __('Copier les données') }}'"></button>
                                <button class="btn btn-outline-secondary" @click="saveToHistory()" style="border-radius: var(--r-btn);">{{ __('Sauvegarder') }}</button>
                            </div>
                        </div>

                        {{-- Historique --}}
                        <template x-if="qrHistory.length > 0">
                            <div class="mt-3 pt-3 border-top">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h3 style="font-family: var(--f-heading); font-weight: 700; margin: 0; font-size: 1rem;">{{ __('QR sauvegardés') }} (<span x-text="qrHistory.length"></span>)</h3>
                                    <button class="btn btn-sm btn-outline-danger" @click="clearHistory()" style="font-size: 0.7rem;">{{ __('Effacer') }}</button>
                                </div>
                                <template x-for="(h, hi) in qrHistory" :key="hi">
                                    <div class="d-flex justify-content-between align-items-center p-2 mb-1 rounded" style="background: #f8f9fa; font-size: 0.85rem; cursor: pointer;" @click="loadFromHistory(hi)">
                                        <span><strong x-text="h.type.toUpperCase()"></strong> — <span class="text-muted" x-text="h.data.substring(0, 40) + (h.data.length > 40 ? '...' : '')"></span></span>
                                        <small class="text-muted" x-text="new Date(h.date).toLocaleString('fr-CA')"></small>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Modale aide --}}
<div class="modal fade" id="qrHelpModal" tabindex="-1" role="dialog" aria-labelledby="qrHelpModalLabel">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content" style="border-radius: var(--r-base);">
            <div class="modal-header" style="background: var(--c-primary); border-radius: var(--r-base) var(--r-base) 0 0;">
                <h4 class="modal-title" id="qrHelpModalLabel" style="color: #fff; font-family: var(--f-heading); font-weight: 700;">{{ __('Comment utiliser cet outil') }}</h4>
                <button type="button" onclick="jQuery('#qrHelpModal').modal('hide')" aria-label="{{ __('Fermer') }}" style="background: none; border: none; color: #fff !important; opacity: 1; font-size: 1.5rem; font-weight: 700; cursor: pointer; float: right;">&times;</button>
            </div>
            <div class="modal-body" style="padding: 2rem;">
                <h4 style="font-family: var(--f-heading); font-weight: 700; color: var(--c-dark); border-bottom: 2px solid var(--c-primary); padding-bottom: 0.5rem;">{{ __('Types de contenu') }}</h4>
                <p>{{ __('Il existe 18 types de codes QR, regroupés en catégories :') }}</p>
                <ul>
                    <li><strong>{{ __('Communication') }}</strong> — URL, texte, courriel, téléphone, SMS, WhatsApp</li>
                    <li><strong>{{ __('Réseaux sociaux') }}</strong> — Instagram, Facebook, LinkedIn, YouTube, X (Twitter)</li>
                    <li><strong>{{ __('Professionnel') }}</strong> — vCard (carte de visite), événement (calendrier)</li>
                    <li><strong>{{ __('Technique') }}</strong> — WiFi, géolocalisation, Zoom, PayPal, Bitcoin</li>
                </ul>

                <h4 style="font-family: var(--f-heading); font-weight: 700; color: var(--c-dark); border-bottom: 2px solid var(--c-primary); padding-bottom: 0.5rem; margin-top: 1.5rem;">{{ __('Personnalisation') }}</h4>
                <p>{{ __('Personnalisez votre code QR selon vos préférences :') }}</p>
                <ul>
                    <li><strong>{{ __('Style rapide') }}</strong> — {{ __('5 préréglages pour changer l\'apparence en 1 clic') }}</li>
                    <li><strong>{{ __('Style des points') }}</strong> — {{ __('choisissez la forme des petits carrés (6 options)') }}</li>
                    <li><strong>{{ __('Couleurs') }}</strong> — {{ __('sélectionnez la couleur du QR et du fond, avec option dégradé') }}</li>
                    <li><strong>{{ __('Logo') }}</strong> — {{ __('ajoutez votre logo au centre (max 500 Ko, redimensionné automatiquement)') }}</li>
                    <li><strong>{{ __('Coins') }}</strong> — {{ __('personnalisez la forme des 3 carrés dans les coins') }}</li>
                </ul>

                <h4 style="font-family: var(--f-heading); font-weight: 700; color: var(--c-dark); border-bottom: 2px solid var(--c-primary); padding-bottom: 0.5rem; margin-top: 1.5rem;">{{ __('Correction d\'erreur') }}</h4>
                <p>{{ __('Ce réglage détermine si le code QR reste lisible même s\'il est partiellement abîmé ou couvert :') }}</p>
                <ul>
                    <li><strong>L ({{ __('basse') }})</strong> — {{ __('le QR est plus simple mais se lit mal s\'il est abîmé') }}</li>
                    <li><strong>M ({{ __('moyenne') }})</strong> — {{ __('bon compromis, recommandé par défaut') }}</li>
                    <li><strong>Q ({{ __('haute') }})</strong> — {{ __('résiste mieux aux dommages') }}</li>
                    <li><strong>H ({{ __('maximale') }})</strong> — {{ __('recommandé si vous ajoutez un logo au centre') }}</li>
                </ul>

                <h4 style="font-family: var(--f-heading); font-weight: 700; color: var(--c-dark); border-bottom: 2px solid var(--c-primary); padding-bottom: 0.5rem; margin-top: 1.5rem;">{{ __('Téléchargement') }}</h4>
                <ul>
                    <li><strong>PNG</strong> — {{ __('image standard, idéale pour le web et les courriels') }}</li>
                    <li><strong>SVG</strong> — {{ __('image vectorielle, idéale pour l\'impression en grand format') }}</li>
                    <li><strong>{{ __('Sauvegarder') }}</strong> — {{ __('enregistre dans l\'historique local de votre navigateur (rien n\'est envoyé au serveur)') }}</li>
                </ul>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn" onclick="jQuery('#qrHelpModal').modal('hide')" style="background: var(--c-primary); color: #fff; border-radius: var(--r-btn);">{{ __('Compris !') }}</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/qr-code-styling@1.6.0-rc.1/lib/qr-code-styling.js"></script>
<script>
document.addEventListener('alpine:init', function() {
    Alpine.data('qrGenerator', function() {
        return {
            type: 'url',
            input: new URLSearchParams(window.location.search).get('url') || 'https://laveille.ai',
            ssid: '', wifiPass: '', encryption: 'WPA',
            emailTo: '', emailSubject: '', emailBody: '',
            phone: '', smsNumber: '', smsMessage: '',
            waNumber: '', waMessage: '',
            vcName: '', vcOrg: '', vcPhone: '', vcEmail: '', vcUrl: '',
            geoLat: '45.5017', geoLng: '-73.5673',
            eventTitle: '', eventStart: '', eventEnd: '', eventLocation: '',
            zoomId: '', zoomPass: '',
            paypalUser: '', paypalAmount: '', paypalCurrency: 'CAD',
            btcAddress: '', btcAmount: '',
            socialUser: '',
            copied: false,
            isAuthenticated: {{ auth()->check() ? 'true' : 'false' }},
            saveName: '',
            saving: false,
            saveError: '',
            _editingId: null,
            qrInstance: null,
            qrLogo: '',
            qrTransparent: false,
            qrCornerColor: '#0B7285',
            qrUseCornerColor: false,
            qrShowFrame: false,
            qrFrameText: '',
            qrFrameFont: 'var(--f-heading)',
            qrFrameSize: '1.2rem',
            qrFrameColor: '#0B7285',
            qrHistory: JSON.parse(localStorage.getItem('qr_history') || '[]'),

            // Presets visuels
            presets: [
                { name: 'Classique', dotStyle: 'square', cornerStyle: 'square', cornerDotStyle: 'square', color: '#000000', bgColor: '#ffffff', gradient: false },
                { name: 'Moderne', dotStyle: 'rounded', cornerStyle: 'extra-rounded', cornerDotStyle: 'dot', color: '#0B7285', bgColor: '#ffffff', gradient: false },
                { name: 'Élégant', dotStyle: 'classy-rounded', cornerStyle: 'dot', cornerDotStyle: 'dot', color: '#1a1a2e', bgColor: '#ffffff', gradient: false },
                { name: 'Dégradé', dotStyle: 'dots', cornerStyle: 'extra-rounded', cornerDotStyle: 'dot', color: '#0B7285', bgColor: '#ffffff', gradient: true, gradientColor2: '#E67E22' },
                { name: 'Minimaliste', dotStyle: 'extra-rounded', cornerStyle: 'extra-rounded', cornerDotStyle: 'dot', color: '#333333', bgColor: '#f8f9fa', gradient: false }
            ],

            // Personnalisation
            qrSize: 300,
            qrDotStyle: 'rounded',
            qrCornerStyle: 'extra-rounded',
            qrCornerDotStyle: 'dot',
            qrErrorLevel: 'M',
            qrColor: '#0B7285',
            qrBgColor: '#ffffff',
            qrGradient: false,
            qrGradientColor2: '#E67E22',

            formatEventDate: function(dt) {
                if (!dt) return '';
                return dt.replace(/[-:]/g, '').replace('T', 'T') + '00';
            },

            get qrData() {
                switch(this.type) {
                    case 'wifi': return 'WIFI:T:' + this.encryption + ';S:' + this.ssid + ';P:' + this.wifiPass + ';;';
                    case 'email': return 'mailto:' + this.emailTo + '?subject=' + encodeURIComponent(this.emailSubject) + '&body=' + encodeURIComponent(this.emailBody);
                    case 'phone': return 'tel:' + this.phone;
                    case 'sms': return 'smsto:' + this.smsNumber + ':' + this.smsMessage;
                    case 'whatsapp': return 'https://wa.me/' + this.waNumber.replace(/[^0-9]/g, '') + (this.waMessage ? '?text=' + encodeURIComponent(this.waMessage) : '');
                    case 'vcard': return 'BEGIN:VCARD\nVERSION:3.0\nFN:' + this.vcName + '\nORG:' + this.vcOrg + '\nTEL:' + this.vcPhone + '\nEMAIL:' + this.vcEmail + '\nURL:' + this.vcUrl + '\nEND:VCARD';
                    case 'geo': return 'geo:' + this.geoLat + ',' + this.geoLng;
                    case 'event': return 'BEGIN:VEVENT\nSUMMARY:' + this.eventTitle + '\nDTSTART:' + this.formatEventDate(this.eventStart) + '\nDTEND:' + this.formatEventDate(this.eventEnd) + '\nLOCATION:' + this.eventLocation + '\nEND:VEVENT';
                    case 'zoom': return 'https://zoom.us/j/' + this.zoomId.replace(/[^0-9]/g, '') + (this.zoomPass ? '?pwd=' + this.zoomPass : '');
                    case 'paypal': return 'https://www.paypal.com/paypalme/' + this.paypalUser + (this.paypalAmount ? '/' + this.paypalAmount + this.paypalCurrency : '');
                    case 'bitcoin': return 'bitcoin:' + this.btcAddress + (this.btcAmount ? '?amount=' + this.btcAmount : '');
                    case 'instagram': return 'https://www.instagram.com/' + this.socialUser + '/';
                    case 'facebook': return 'https://www.facebook.com/' + this.socialUser;
                    case 'linkedin': return 'https://www.linkedin.com/in/' + this.socialUser;
                    case 'youtube': return 'https://www.youtube.com/' + this.socialUser;
                    case 'twitter': return 'https://x.com/' + this.socialUser;
                    default: return this.input;
                }
            },

            renderQR: function() {
                var self = this;
                setTimeout(function() {
                    var data = self.qrData;
                    if (!data || data.length < 2) data = 'https://laveille.ai';

                    var dotsOptions = { type: self.qrDotStyle, color: self.qrColor };
                    if (self.qrGradient) {
                        dotsOptions = {
                            type: self.qrDotStyle,
                            gradient: {
                                type: 'linear', rotation: 0,
                                colorStops: [{ offset: 0, color: self.qrColor }, { offset: 1, color: self.qrGradientColor2 }]
                            }
                        };
                    }

                    var cornerColor = self.qrUseCornerColor ? self.qrCornerColor : self.qrColor;
                    var bgColor = self.qrTransparent ? 'rgba(0,0,0,0)' : self.qrBgColor;

                    var config = {
                        width: self.qrSize,
                        height: self.qrSize,
                        type: 'canvas',
                        data: data,
                        dotsOptions: dotsOptions,
                        cornersSquareOptions: { type: self.qrCornerStyle, color: cornerColor },
                        cornersDotOptions: { type: self.qrCornerDotStyle, color: cornerColor },
                        backgroundOptions: { color: bgColor },
                        qrOptions: { errorCorrectionLevel: self.qrErrorLevel }
                    };

                    if (self.qrLogo) {
                        config.image = self.qrLogo;
                        config.imageOptions = { crossOrigin: 'anonymous', margin: 8, hideBackgroundDots: true, imageSize: 0.3 };
                    }

                    self.qrInstance = new QRCodeStyling(config);
                    if (self.$refs.qrCanvas) {
                        self.$refs.qrCanvas.innerHTML = '';
                        self.qrInstance.append(self.$refs.qrCanvas);
                    }
                }, 50);
            },

            downloadQR: function(format) {
                if (this.qrInstance) {
                    this.qrInstance.download({ name: 'qr-code', extension: format });
                }
            },

            uploadLogo: function(event) {
                var self = this;
                var file = event.target.files[0];
                if (!file) return;
                if (file.size > 512000) { alert('Le fichier dépasse 500 Ko. Choisissez une image plus petite.'); event.target.value = ''; return; }
                var reader = new FileReader();
                reader.onload = function(e) {
                    var img = new Image();
                    img.onload = function() {
                        var maxSize = 150;
                        var w = img.width, h = img.height;
                        if (w > maxSize || h > maxSize) {
                            if (w > h) { h = Math.round(h * maxSize / w); w = maxSize; }
                            else { w = Math.round(w * maxSize / h); h = maxSize; }
                        }
                        var canvas = document.createElement('canvas');
                        canvas.width = w; canvas.height = h;
                        var ctx = canvas.getContext('2d');
                        ctx.drawImage(img, 0, 0, w, h);
                        self.qrLogo = canvas.toDataURL('image/png', 0.85);
                        self.renderQR();
                    };
                    img.src = e.target.result;
                };
                reader.readAsDataURL(file);
            },

            removeLogo: function() { this.qrLogo = ''; this.renderQR(); },

            applyPreset: function(p) {
                this.qrDotStyle = p.dotStyle;
                this.qrCornerStyle = p.cornerStyle;
                this.qrCornerDotStyle = p.cornerDotStyle;
                this.qrColor = p.color;
                this.qrBgColor = p.bgColor;
                this.qrGradient = p.gradient || false;
                if (p.gradientColor2) this.qrGradientColor2 = p.gradientColor2;
                this.renderQR();
            },

            saveToHistory: function() {
                var entry = { type: this.type, data: this.qrData, date: new Date().toISOString() };
                this.qrHistory.unshift(entry);
                if (this.qrHistory.length > 10) this.qrHistory.pop();
                localStorage.setItem('qr_history', JSON.stringify(this.qrHistory));
            },

            loadFromHistory: function(index) {
                var h = this.qrHistory[index];
                if (h) { this.type = h.type || 'url'; this.input = h.data || ''; this.renderQR(); }
            },

            clearHistory: function() { this.qrHistory = []; localStorage.removeItem('qr_history'); },

            copyData: function() {
                var self = this;
                navigator.clipboard.writeText(this.qrData);
                this.copied = true;
                setTimeout(function() { self.copied = false; }, 2000);
            },

            _headers: function() {
                return { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' };
            },
            saveToAccount: function() {
                if (this.saving || !this.input) return;
                var self = this;
                var title = this.saveName.trim() || this.type.toUpperCase() + ' — ' + (this.input || '').substring(0, 30);
                this.saving = true;
                this.saveError = '';
                var isEdit = !!this._editingId;
                var url = isEdit ? '/api/qr-presets/' + this._editingId : '/api/qr-presets';
                var method = isEdit ? 'PUT' : 'POST';
                fetch(url, {
                    method: method, headers: this._headers(),
                    body: JSON.stringify({ name: title, config_text: this.input, params: {
                        type: this.type, ssid: this.ssid, wifiPass: this.wifiPass, encryption: this.encryption,
                        emailTo: this.emailTo, emailSubject: this.emailSubject, emailBody: this.emailBody,
                        phone: this.phone, smsNumber: this.smsNumber, smsMessage: this.smsMessage,
                        waNumber: this.waNumber, waMessage: this.waMessage,
                        vcName: this.vcName, vcOrg: this.vcOrg, vcPhone: this.vcPhone, vcEmail: this.vcEmail, vcUrl: this.vcUrl,
                        geoLat: this.geoLat, geoLng: this.geoLng,
                        eventTitle: this.eventTitle, eventStart: this.eventStart, eventEnd: this.eventEnd, eventLocation: this.eventLocation,
                        zoomId: this.zoomId, zoomPass: this.zoomPass,
                        paypalUser: this.paypalUser, paypalAmount: this.paypalAmount, paypalCurrency: this.paypalCurrency,
                        btcAddress: this.btcAddress, btcAmount: this.btcAmount, socialUser: this.socialUser,
                        qrSize: this.qrSize, qrDotStyle: this.qrDotStyle, qrCornerStyle: this.qrCornerStyle,
                        qrCornerDotStyle: this.qrCornerDotStyle, qrErrorLevel: this.qrErrorLevel,
                        qrColor: this.qrColor, qrBgColor: this.qrBgColor, qrGradient: this.qrGradient, qrGradientColor2: this.qrGradientColor2,
                        qrLogo: this.qrLogo, qrTransparent: this.qrTransparent,
                        qrCornerColor: this.qrCornerColor, qrUseCornerColor: this.qrUseCornerColor,
                        qrShowFrame: this.qrShowFrame, qrFrameText: this.qrFrameText
                    } })
                })
                .then(function(r) { if (!r.ok) throw new Error('Erreur ' + r.status); return r.json(); })
                .then(function() { self._editingId = null; self.saveName = ''; self.saving = false; })
                .catch(function(e) { self.saveError = e.message; self.saving = false; setTimeout(function() { self.saveError = ''; }, 4000); });
            },
            initEditMode: function() {
                if (!this.isAuthenticated) return;
                var self = this;
                var editId = new URLSearchParams(window.location.search).get('edit');
                if (!editId) return;
                fetch('/api/qr-presets', { headers: this._headers() })
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        var found = (data.data || []).find(function(p) { return p.public_id === editId; });
                        if (!found) return;
                        self.input = found.config_text || '';
                        var pr = found.params || {};
                        Object.keys(pr).forEach(function(k) { if (self.hasOwnProperty(k)) self[k] = pr[k]; });
                        self.saveName = found.name;
                        self._editingId = found.public_id;
                        self.$nextTick(function() { self.renderQR(); });
                    });
            }
        };
    });
});
</script>
@endpush
