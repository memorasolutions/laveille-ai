<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
<footer class="cs_footer cs_style_1">
    <div class="cs_height_130 cs_height_lg_80"></div>
    <div class="container">
        <div class="cs_footer_main">
            <div class="row cs_gap_y_30">
                <div class="col-xl-5 col-lg-4 col-md-7">
                    <div class="cs_footer_widget cs_text_widget">
                        <div class="cs_brand cs_mb_10 wow fadeInUp">
                            <span class="fw-bold fs-4 cs_heading_color">{{ config('app.name') }}</span>
                        </div>
                        <p class="mb-0">Solution SaaS Laravel complète pour vos projets web. Modules, sécurité et performance au rendez-vous.</p>
                    </div>
                </div>
                <div class="col-xl-2 col-lg-2 col-md-4 offset-lg-0 offset-md-1">
                    <div class="cs_footer_widget cs_links_widget">
                        <h3 class="cs_footer_widget_title cs_fs_21 cs_mb_21">Liens rapides</h3>
                        <ul class="cs_footer_medu cs_mp_0">
                            <li><a href="{{ url('/') }}" aria-label="Accueil">Accueil</a></li>
                            <li><a href="{{ route('blog.index') }}" aria-label="Blog">Blog</a></li>
                            <li><a href="{{ route('faq.show') }}" aria-label="FAQ">FAQ</a></li>
                            <li><a href="{{ route('contact.show') }}" aria-label="Contact">Contact</a></li>
                        </ul>
                    </div>
                </div>
                <div class="col-xl-2 col-lg-3 col-md-7">
                    <div class="cs_footer_widget cs_help_widget">
                        <h3 class="cs_footer_widget_title cs_fs_21 cs_mb_21">Aide</h3>
                        <ul class="cs_footer_medu cs_mp_0">
                            <li><a href="{{ route('about') }}" aria-label="À propos">À propos</a></li>
                            <li><a href="{{ route('legal') }}" aria-label="Mentions légales">Mentions légales</a></li>
                            <li><a href="{{ route('privacy') }}" aria-label="Confidentialité">Confidentialité</a></li>
                            <li><a href="{{ route('terms') }}" aria-label="Conditions d'utilisation">Conditions d'utilisation</a></li>
                            <li><a href="{{ route('login') }}" aria-label="Connexion">Connexion</a></li>
                        </ul>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-3 col-md-4 offset-md-1 offset-lg-0">
                    <div class="cs_footer_widget cs_contact_widget">
                        <h3 class="cs_footer_widget_title cs_fs_21 cs_mb_21">Contact</h3>
                        <ul class="cs_footer_medu cs_mp_0">
                            <li>Support 24/7</li>
                            <li><a href="mailto:{{ config('mail.from.address', 'contact@example.com') }}" aria-label="Email">{{ config('mail.from.address', 'contact@example.com') }}</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <div class="cs_height_62 cs_height_lg_40"></div>
        <div class="cs_footer_bottom cs_gray_bg_2 cs_radius_50">
            <div class="cs_footer_text cs_heading_color">Copyright &copy; {{ date('Y') }} {{ config('app.name') }}</div>
            <div class="cs_social_links cs_style_1 cs_heading_color">
                <a href="#" aria-label="Facebook">
                    <span class="cs_social_icon cs_purple_color"><i class="fa-brands fa-facebook"></i></span>
                    <span>Facebook</span>
                </a>
                <a href="#" aria-label="YouTube">
                    <span class="cs_social_icon cs_purple_color"><i class="fa-brands fa-youtube"></i></span>
                    <span>YouTube</span>
                </a>
                <a href="#" aria-label="Instagram">
                    <span class="cs_social_icon cs_purple_color"><i class="fa-brands fa-square-instagram"></i></span>
                    <span>Instagram</span>
                </a>
            </div>
        </div>
        <div class="cs_height_70 cs_height_lg_30"></div>
    </div>
</footer>
