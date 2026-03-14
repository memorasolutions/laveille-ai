<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
<div>
    @if($lookerUrl)
        <div class="w-100" style="aspect-ratio: 16/10;">
            <iframe
                src="{{ $lookerUrl }}"
                frameborder="0"
                allowfullscreen
                sandbox="allow-storage-access-by-user-activation allow-scripts allow-same-origin allow-popups allow-popups-to-escape-sandbox"
                loading="lazy"
                class="w-100 h-100 rounded-3 border"
            ></iframe>
        </div>
    @else
        <div class="text-center py-5">
            <i data-lucide="bar-chart-2" class="d-block mx-auto mb-3 text-muted" style="width:48px;height:48px;opacity:.4;"></i>
            <h6 class="fw-semibold text-body mb-2">{{ __('Aucun rapport configuré') }}</h6>
            <p class="small text-muted mb-4">{{ __('Configurez votre rapport Google Looker Studio en 3 étapes simples :') }}</p>

            <div class="row g-3 justify-content-center mb-4" style="max-width:672px;margin-left:auto;margin-right:auto;">
                <div class="col-12 col-md-4">
                    <div class="card border rounded-3 h-100 p-4 text-center">
                        <div class="d-flex align-items-center justify-content-center rounded-circle bg-primary bg-opacity-10 text-primary mx-auto mb-3" style="width:48px;height:48px;">
                            <span class="fw-bold">1</span>
                        </div>
                        <h6 class="small fw-semibold text-body mb-2">{{ __('Créer un rapport') }}</h6>
                        <p class="text-muted" style="font-size:.75rem;">
                            {{ __('Rendez-vous sur') }} <a href="https://lookerstudio.google.com" target="_blank" rel="noopener"
                            class="text-primary">lookerstudio.google.com</a> {{ __('et créez votre rapport avec Google Analytics ou d\'autres sources.') }}
                        </p>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="card border rounded-3 h-100 p-4 text-center">
                        <div class="d-flex align-items-center justify-content-center rounded-circle bg-success bg-opacity-10 text-success mx-auto mb-3" style="width:48px;height:48px;">
                            <span class="fw-bold">2</span>
                        </div>
                        <h6 class="small fw-semibold text-body mb-2">{{ __('Copier le lien embed') }}</h6>
                        <p class="text-muted" style="font-size:.75rem;">
                            {{ __('Dans Looker Studio, cliquez') }} <strong>{{ __('Fichier') }} &rarr; {{ __('Intégrer le rapport') }}</strong> {{ __('et copiez l\'URL (commence par') }} <code class="text-primary">https://lookerstudio.google.com/embed/</code>).
                        </p>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="card border rounded-3 h-100 p-4 text-center">
                        <div class="d-flex align-items-center justify-content-center rounded-circle bg-warning bg-opacity-10 text-warning mx-auto mb-3" style="width:48px;height:48px;">
                            <span class="fw-bold">3</span>
                        </div>
                        <h6 class="small fw-semibold text-body mb-2">{{ __('Coller dans les paramètres') }}</h6>
                        <p class="text-muted" style="font-size:.75rem;">
                            {{ __('Allez dans') }} <a href="{{ route('admin.settings.index') }}" class="text-primary">{{ __('Paramètres') }} &rarr; SEO</a> {{ __('et collez l\'URL dans le champ « URL Looker Studio ».') }}
                        </p>
                    </div>
                </div>
            </div>

            <a href="{{ route('admin.settings.index') }}"
               class="btn btn-primary btn-sm d-inline-flex align-items-center gap-2">
                <i data-lucide="settings" style="width:14px;height:14px;"></i>
                {{ __('Configurer maintenant') }}
            </a>
        </div>
    @endif
</div>
