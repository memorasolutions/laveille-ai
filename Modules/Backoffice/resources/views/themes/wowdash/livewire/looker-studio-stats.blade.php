<div>
    @if($lookerUrl)
        <div class="ratio" style="--bs-aspect-ratio: 62.5%;">
            <iframe
                src="{{ $lookerUrl }}"
                frameborder="0"
                allowfullscreen
                sandbox="allow-storage-access-by-user-activation allow-scripts allow-same-origin allow-popups allow-popups-to-escape-sandbox"
                loading="lazy"
                style="border: 0; border-radius: 12px;"
            ></iframe>
        </div>
    @else
        <div class="text-center py-40">
            <iconify-icon icon="solar:chart-square-outline" class="text-6xl text-secondary-light mb-16 d-block"></iconify-icon>
            <h6 class="mb-8">Aucun rapport configuré</h6>
            <p class="text-secondary-light mb-24">Configurez votre rapport Google Looker Studio en 3 étapes simples :</p>

            <div class="row g-3 justify-content-center mb-24">
                <div class="col-md-4">
                    <div class="card border radius-8 h-100">
                        <div class="card-body text-center p-20">
                            <div class="w-48-px h-48-px bg-primary-50 text-primary-600 rounded-circle d-flex justify-content-center align-items-center mx-auto mb-12">
                                <span class="fw-bold text-lg">1</span>
                            </div>
                            <h6 class="text-sm fw-semibold mb-4">Créer un rapport</h6>
                            <p class="text-secondary-light text-xs mb-0">
                                Rendez-vous sur <a href="https://lookerstudio.google.com" target="_blank" rel="noopener" class="text-primary-600">lookerstudio.google.com</a> et créez votre rapport avec Google Analytics ou d'autres sources.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border radius-8 h-100">
                        <div class="card-body text-center p-20">
                            <div class="w-48-px h-48-px bg-success-50 text-success-600 rounded-circle d-flex justify-content-center align-items-center mx-auto mb-12">
                                <span class="fw-bold text-lg">2</span>
                            </div>
                            <h6 class="text-sm fw-semibold mb-4">Copier le lien embed</h6>
                            <p class="text-secondary-light text-xs mb-0">
                                Dans Looker Studio, cliquez <strong>Fichier → Intégrer le rapport</strong> et copiez l'URL (commence par <code>https://lookerstudio.google.com/embed/</code>).
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border radius-8 h-100">
                        <div class="card-body text-center p-20">
                            <div class="w-48-px h-48-px bg-warning-50 text-warning-600 rounded-circle d-flex justify-content-center align-items-center mx-auto mb-12">
                                <span class="fw-bold text-lg">3</span>
                            </div>
                            <h6 class="text-sm fw-semibold mb-4">Coller dans les paramètres</h6>
                            <p class="text-secondary-light text-xs mb-0">
                                Allez dans <a href="{{ route('admin.settings.index') }}" class="text-primary-600">Paramètres → SEO</a> et collez l'URL dans le champ « URL Looker Studio ».
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <a href="{{ route('admin.settings.index') }}" class="btn btn-primary-600 radius-8 d-inline-flex align-items-center gap-2">
                <iconify-icon icon="solar:settings-outline" class="icon text-xl"></iconify-icon>
                Configurer maintenant
            </a>
        </div>
    @endif
</div>
