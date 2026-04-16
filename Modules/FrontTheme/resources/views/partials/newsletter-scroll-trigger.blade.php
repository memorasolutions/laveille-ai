@if(Route::has('newsletter.subscribe'))
<div
    id="newsletterScrollTrigger"
    role="dialog"
    aria-label="Inscription à la newsletter"
    style="
        position: fixed;
        bottom: 20px;
        right: 20px;
        max-width: 380px;
        display: none;
        z-index: 9990;
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 10px 40px rgba(0,0,0,.15);
        padding: 20px;
        transform: translateY(120%);
        transition: transform .4s ease;
    "
>
    <style>
        @media (max-width: 640px) {
            #newsletterScrollTrigger {
                bottom: 0 !important;
                right: 0 !important;
                max-width: 100% !important;
                width: 100% !important;
                border-radius: 12px 12px 0 0 !important;
            }
        }
    </style>

    <button
        type="button"
        id="newsletterScrollClose"
        aria-label="Fermer"
        style="
            position: absolute;
            top: 8px;
            right: 8px;
            background: none;
            border: none;
            font-size: 20px;
            line-height: 1;
            cursor: pointer;
            color: #666;
            padding: 4px 8px;
            border-radius: 4px;
        "
    >&times;</button>

    <h4 style="margin: 0 0 4px; font-size: 18px; font-weight: 700; color: #1a1a1a;">
        Reste à jour en veille IA
    </h4>
    <p style="margin: 0 0 14px; font-size: 13px; color: #666; line-height: 1.4;">
        Reçois chaque semaine le meilleur de l'actualité IA, directement dans ta boîte.
    </p>

    <form id="newsletterScrollForm" method="POST" action="{{ route('newsletter.subscribe') }}">
        @csrf
        <input type="hidden" name="source" value="scroll_trigger">

        <div style="margin-bottom: 10px;">
            <input
                type="email"
                name="email"
                id="newsletterScrollEmail"
                required
                autocomplete="email"
                aria-label="Adresse courriel"
                placeholder="ton@courriel.com"
                class="form-control"
                style="
                    width: 100%;
                    padding: 10px 12px;
                    font-size: 14px;
                    border: 1px solid #ccc;
                    border-radius: 6px;
                    box-sizing: border-box;
                "
            >
        </div>

        <button
            type="submit"
            id="newsletterScrollSubmit"
            class="btn"
            style="
                width: 100%;
                padding: 10px;
                font-size: 14px;
                font-weight: 600;
                color: #fff;
                background-color: var(--c-primary, #0d6efd);
                border: none;
                border-radius: 6px;
                cursor: pointer;
            "
        >S'abonner</button>
    </form>

    <div
        id="newsletterScrollMessage"
        class="d-none"
        aria-live="polite"
        style="margin-top: 10px; font-size: 13px; padding: 8px 10px; border-radius: 6px;"
    ></div>

    <p style="margin: 10px 0 0; font-size: 11px; color: #999; text-align: center; line-height: 1.3;">
        Pas de pourriel, désinscription en 1 clic.
    </p>
</div>
@endif
