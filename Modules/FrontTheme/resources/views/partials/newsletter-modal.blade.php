<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@if(Route::has('newsletter.subscribe'))
<div class="modal fade" id="newsletterModal" role="dialog" aria-labelledby="newsletterModalLabel" aria-hidden="true" inert style="display:none;">
    <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 420px;">
        <div class="modal-content" style="border-radius: 12px; overflow: hidden; border: none;">
            <div style="background: linear-gradient(135deg, var(--c-primary) 0%, var(--c-primary-hover) 100%); padding: 20px 30px; position: relative;">
                <button type="button" id="newsletterModalClose" aria-label="Fermer" style="position: absolute; top: 14px; right: 18px; color: #fff; font-size: 26px; line-height: 1; cursor: pointer; opacity: 0.7; transition: opacity 0.2s; background: none; border: none; padding: 0; font-weight: 300; outline: none;">&times;</button>
                <div style="text-align: center; font-size: 40px; line-height: 1;">✉️</div>
                <h3 class="text-center" id="newsletterModalLabel" style="font-family: var(--f-heading); color: #fff; font-weight: 800; margin: 10px 0 8px;">
                    {{ __('Restez informé') }}
                </h3>
                <p class="text-center" style="color: #fff; margin: 0; font-size: 14px;">
                    {{ __('Recevez nos sélections d\'outils et articles directement dans votre boîte courriel.') }}
                </p>
            </div>
            <div style="padding: 20px 30px;">
                <form id="newsletterModalForm">
                    @csrf
                    <input type="email" name="email" placeholder="{{ __('Votre courriel *') }}" required aria-label="{{ __('Courriel') }}" autocomplete="email"
                           style="width: 100%; border: 1px solid #dee2e6; border-radius: 8px; padding: 12px 16px; font-size: 15px; font-family: var(--f-body); margin-bottom: 12px; outline: none; transition: border-color 0.2s;">
                    <input type="text" name="name" placeholder="{{ __('Votre prénom (optionnel)') }}" aria-label="{{ __('Prénom') }}" autocomplete="given-name"
                           style="width: 100%; border: 1px solid #dee2e6; border-radius: 8px; padding: 12px 16px; font-size: 15px; font-family: var(--f-body); margin-bottom: 16px; outline: none; transition: border-color 0.2s;">

                    <label style="display: flex; align-items: flex-start; gap: 8px; font-size: 12px; color: #374151; margin-bottom: 14px; cursor: pointer; line-height: 1.4;">
                        <input type="checkbox" name="consent" required style="margin-top: 2px; flex-shrink: 0;">
                        {!! __('J\'accepte de recevoir l\'infolettre conformement a la <a href=":url" target="_blank" style="color: var(--c-primary); text-decoration: underline;">politique de confidentialite</a>.', ['url' => route('legal.privacy')]) !!}
                    </label>

                    <div id="newsletterModalMessage" class="alert d-none" style="border-radius: 8px; font-size: 14px;"></div>

                    <button type="submit" id="newsletterModalSubmit"
                            style="width: 100%; background: var(--c-dark); color: #fff; border: none; border-radius: 8px; padding: 14px; font-family: var(--f-heading); font-weight: 700; font-size: 16px; cursor: pointer; transition: background 0.2s;">
                        <span class="submit-text">{{ __('S\'inscrire') }}</span>
                        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
#newsletterModal { pointer-events: none; }
#newsletterModal.show { pointer-events: auto; }
#newsletterModal .modal-content { max-height: 90vh; overflow-y: auto; overflow-x: hidden; }
#newsletterModal .modal-dialog { margin: 20px auto; }
#newsletterModal input[type="checkbox"] { display: inline-block !important; width: 16px !important; height: 16px !important; appearance: auto !important; -webkit-appearance: checkbox !important; opacity: 1 !important; position: static !important; }
#newsletterModal input:focus { border-color: var(--c-primary) !important; box-shadow: 0 0 0 3px rgba(11,114,133,0.15); }
#newsletterModalSubmit:hover { background: var(--c-primary) !important; }
</style>

@push('scripts')
<script>
$(function() {
    if ($('#newsletterModal').length === 0) return;

    $('#newsletterModalClose').on('click', function() { $('#newsletterModal').modal('hide'); })
        .on('mouseenter', function() { $(this).css('opacity','1'); })
        .on('mouseleave', function() { $(this).css('opacity','0.7'); });

    $(document).on('click', '.entry-details a, .entry-media a, .wpo-blog-content a, .post a', function(e) {
        var text = ($(this).text() || '').toLowerCase();
        var href = ($(this).attr('href') || '').toLowerCase();
        if (text.indexOf('infolettre') !== -1 || text.indexOf('newsletter') !== -1 ||
            href.indexOf('infolettre') !== -1 || (href.indexOf('newsletter') !== -1 && href.indexOf('/blog') === -1)) {
            e.preventDefault();
            document.getElementById('newsletterModal').removeAttribute('inert');
            $('#newsletterModal').modal('show');
        }
    });

    $('#newsletterModalForm').on('submit', function(e) {
        e.preventDefault();
        var $form = $(this);
        var $btn = $form.find('button[type="submit"]');
        var $msg = $('#newsletterModalMessage');

        $msg.addClass('d-none').removeClass('alert-success alert-danger');
        $btn.find('.submit-text').addClass('d-none');
        $btn.find('.spinner-border').removeClass('d-none');
        $btn.prop('disabled', true);

        $.ajax({
            url: '{{ route("newsletter.subscribe") }}',
            method: 'POST',
            data: $form.serialize(),
            headers: { 'Accept': 'application/json' },
            success: function(response) {
                $msg.removeClass('d-none').addClass('alert-success').text(response.message || '{{ __("Inscription réussie !") }}');
                $form[0].reset();
                setTimeout(function() { $('#newsletterModal').modal('hide'); }, 2500);
            },
            error: function(xhr) {
                var err = '{{ __("Une erreur est survenue.") }}';
                if (xhr.responseJSON) {
                    err = xhr.responseJSON.message || (xhr.responseJSON.errors ? Object.values(xhr.responseJSON.errors)[0] : err);
                }
                $msg.removeClass('d-none').addClass('alert-danger').text(err);
            },
            complete: function() {
                $btn.find('.spinner-border').addClass('d-none');
                $btn.find('.submit-text').removeClass('d-none');
                $btn.prop('disabled', false);
            }
        });
    });

    $('#newsletterModal').on('hidden.bs.modal', function() {
        $('#newsletterModalForm')[0].reset();
        $('#newsletterModalMessage').addClass('d-none').removeClass('alert-success alert-danger');
        this.setAttribute('inert', '');
    });
});
</script>
@endpush
@endif
