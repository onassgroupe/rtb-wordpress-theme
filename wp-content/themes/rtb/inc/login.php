<?php
/**
 * RTB — Habillage de l'écran de connexion (wp-login.php).
 *
 * Disposition : carrousel (gauche, covers des antennes) + carte formulaire
 *               (droite, centrée). Mobile : fond fixe + carte centrée.
 * Couleurs nationales (rouge / vert / jaune), fontes Archivo + Libre Franklin,
 * Font Awesome auto-hébergé (comme le reste du thème).
 */
defined( 'ABSPATH' ) || exit;

// Logo cliquable → accueil du site (pas WordPress.org)
add_filter( 'login_headerurl',  fn() => home_url('/') );
add_filter( 'login_headertext', fn() => get_bloginfo('name') );
add_filter( 'login_display_language_dropdown', '__return_false' );

/**
 * Épingle l'écran de connexion sur la langue du site (français), quelle que soit
 * la langue de navigation mémorisée (cookie Polylang `pll_language`, etc.).
 */
add_action( 'login_init', function () {
    $loc = get_option( 'WPLANG' ) ?: 'fr_FR';
    if ( function_exists( 'switch_to_locale' ) ) {
        switch_to_locale( $loc );
    }
}, 0 );

/**
 * Lockup de marque (même texte que le header public) affiché sous le logo,
 * au-dessus de la carte. Reprend les deux lignes de l'identité du site.
 */
add_filter( 'login_message', function ( $message ) {
    $l1 = function_exists( 'onass_mod' ) ? onass_mod( 'rtb_brand_l1', 'Radiodiffusion Télévision' ) : 'Radiodiffusion Télévision';
    $l2 = function_exists( 'onass_mod' ) ? onass_mod( 'rtb_brand_l2', 'DU BURKINA FASO' ) : 'DU BURKINA FASO';
    $lockup  = '<div class="rtb-login-word">';
    $lockup .= '<b>' . esc_html( $l1 ) . '</b>';
    $lockup .= '<span>' . esc_html( $l2 ) . '</span>';
    $lockup .= '</div>';
    return $lockup . $message;
} );

/** Covers du carrousel (les heros des antennes). */
function rtb_login_slides(): array {
    $base = get_template_directory_uri() . '/assets/img/';
    return [
        $base . 'hero-rtb1.png',
        $base . 'hero-zenith.png',
        $base . 'hero-rtb3.png',
        $base . 'hero-guiriko.png',
    ];
}

add_action( 'login_enqueue_scripts', function () {
    $slides = rtb_login_slides();
    $logo   = rtb_logo_url();
    $fa     = get_template_directory_uri() . '/assets/fontawesome/css/all.min.css';
    $first  = $slides[0] ?? '';
    ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Archivo:wght@500;600;700;800;900&family=Libre+Franklin:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="<?php echo esc_url( $fa ); ?>">
    <style>
        :root {
            --rtb-red:#E70C2F; --rtb-red-dark:#C40A28;
            --rtb-green:#10A653; --rtb-green-dark:#0B7A3B;
            --rtb-yellow:#F5DE00;
            --rtb-ink:#161310; --rtb-gray:#5E574C;
            --rtb-cream:#F7F4ED; --rtb-border:#E4E2DB;
        }
        html, body { margin:0; padding:0; }
        body.login {
            font-family:'Libre Franklin', sans-serif !important;
            background:var(--rtb-cream);
            min-height:100vh;
            display:grid;
            grid-template-columns:1fr;
            color:var(--rtb-ink);
        }
        @media (min-width:1024px) { body.login { grid-template-columns:1fr 1fr; } }
        @media (max-width:1023px) {
            body.login {
                background:
                    linear-gradient(135deg, rgba(12,10,8,.80), rgba(12,10,8,.86)),
                    url('<?php echo esc_url( $first ); ?>') center/cover no-repeat fixed;
                align-items:center; justify-content:center; padding:1.5rem;
            }
            .rtb-login-carousel { display:none !important; }
        }

        /* ─── Carrousel (desktop) ─────────────────────────────── */
        .rtb-login-carousel {
            position:fixed; inset:0 50% 0 0; z-index:0; overflow:hidden; background:#0C0A08;
        }
        .rtb-login-slide {
            position:absolute; inset:0; background-size:cover; background-position:center;
            opacity:0; transition:opacity 1.2s ease; transform:scale(1.04);
        }
        .rtb-login-slide.is-active { opacity:1; }
        .rtb-login-carousel::after {
            content:""; position:absolute; inset:0; z-index:1; pointer-events:none;
            background:linear-gradient(135deg, rgba(12,10,8,.62), rgba(231,12,47,.22));
        }
        /* Bandeau tricolore en pied du carrousel */
        .rtb-login-flag {
            position:absolute; left:0; right:0; bottom:0; height:5px; z-index:3;
            background:linear-gradient(90deg,
                var(--rtb-red) 0 33.33%, var(--rtb-green) 33.33% 66.66%, var(--rtb-yellow) 66.66% 100%);
        }
        .rtb-login-tag {
            position:absolute; left:3rem; bottom:2.75rem; z-index:2;
            color:rgba(255,255,255,.94);
            font-family:'Archivo', sans-serif; font-weight:800;
            font-size:1.05rem; letter-spacing:.16em; text-transform:uppercase;
        }
        .rtb-login-dots { position:absolute; left:3rem; bottom:1.75rem; z-index:2; display:flex; gap:.5rem; }
        .rtb-login-dot {
            width:30px; height:3px; border:none; background:rgba(255,255,255,.35);
            border-radius:4px; cursor:pointer; padding:0; transition:background .25s, width .25s;
        }
        .rtb-login-dot.is-active { background:var(--rtb-red); width:48px; }

        /* ─── Colonne formulaire ──────────────────────────────── */
        #login {
            position:relative !important; z-index:2 !important;
            width:100% !important; max-width:none !important; margin:0 auto !important;
            padding:2.5rem 1.5rem !important;
            display:flex !important; flex-direction:column !important; justify-content:center !important;
            min-height:100vh !important; box-sizing:border-box !important;
        }
        @media (min-width:1024px) { #login { grid-column:2 !important; padding:3rem 4rem !important; } }
        @media (max-width:1023px) { #login { min-height:auto !important; padding:0 !important; max-width:520px !important; } }

        .login h1 { margin:0 0 .65rem; text-align:center; }
        .login h1 a {
            background-image:url('<?php echo esc_url( $logo ); ?>') !important;
            background-size:contain; background-repeat:no-repeat; background-position:center;
            width:100% !important; height:62px !important; margin:0 !important;
            text-indent:-9999px; outline:none; box-shadow:none; pointer-events:auto;
        }
        @media (max-width:1023px) { .login h1 a { filter:brightness(0) invert(1); } }

        /* Lockup de marque (texte du header public) */
        .rtb-login-word { text-align:center; margin:0 0 1.5rem; line-height:1.18; }
        .rtb-login-word b {
            display:block; font-family:'Archivo', sans-serif; font-weight:800;
            font-size:1.0625rem; letter-spacing:.005em; color:var(--rtb-ink);
        }
        .rtb-login-word span {
            display:block; font-family:'Archivo', sans-serif; font-weight:600;
            font-size:.75rem; letter-spacing:.18em; text-transform:uppercase;
            color:var(--rtb-gray); margin-top:2px;
        }
        @media (max-width:1023px) {
            .rtb-login-word b { color:#fff; }
            .rtb-login-word span { color:rgba(255,255,255,.78); }
        }

        /* Carte */
        #loginform, #lostpasswordform, #registerform {
            background:#fff !important; border:none !important; border-radius:1.25rem !important;
            box-shadow:0 30px 60px -20px rgba(0,0,0,.25) !important;
            padding:2.5rem 2.75rem !important; margin:0 auto 1rem !important;
            overflow:hidden !important; width:100% !important; max-width:450px !important;
            min-width:0 !important; box-sizing:border-box !important; position:relative !important;
        }
        /* Liseré tricolore en haut de carte */
        #loginform::before {
            content:""; position:absolute; top:0; left:0; right:0; height:4px;
            background:linear-gradient(90deg,
                var(--rtb-red) 0 33.33%, var(--rtb-green) 33.33% 66.66%, var(--rtb-yellow) 66.66% 100%);
        }
        #loginform { padding-top:2.5rem !important; }
        #loginform p { margin:0 !important; }
        /* padding (et non margin) → insensible au collapse, gap garanti vers le 1er champ */
        .rtb-login-sub {
            display:block; text-align:center; color:var(--rtb-gray);
            font-size:.9rem; font-weight:600; margin:0 !important; padding:0 0 1.5rem;
        }

        /* Labels en sr-only — visuel via placeholder + icône */
        .login label[for="user_login"], .login label[for="user_pass"] {
            position:absolute !important; width:1px !important; height:1px !important;
            margin:-1px !important; padding:0 !important; overflow:hidden !important;
            clip:rect(0,0,0,0) !important; border:0 !important;
        }

        .login .rtb-field { position:relative; margin:0 0 1rem; }
        .login .rtb-field-icon {
            position:absolute; left:1.125rem; top:50%; transform:translateY(-50%);
            color:var(--rtb-gray); font-size:.9375rem; pointer-events:none; z-index:2; transition:color .15s;
        }
        .login .rtb-field:focus-within .rtb-field-icon,
        .login .wp-pwd:focus-within .rtb-field-icon { color:var(--rtb-red); }

        .login form .input, .login input[type="text"],
        .login input[type="email"], .login input[type="password"] {
            background:var(--rtb-cream) !important; border:1.5px solid var(--rtb-border) !important;
            border-radius:9999px !important; box-shadow:none !important; font-size:.9375rem !important;
            padding:.875rem 1.25rem .875rem 2.875rem !important; margin:0 !important;
            font-family:'Libre Franklin', sans-serif !important; color:var(--rtb-ink) !important;
            width:100% !important; box-sizing:border-box !important;
            transition:border-color .15s, background .15s, box-shadow .15s;
        }
        .login form .input::placeholder, .login input::placeholder { color:var(--rtb-gray); opacity:1; font-weight:500; }
        .login form .input:focus, .login input:focus {
            border-color:var(--rtb-red) !important; background:#fff !important;
            box-shadow:0 0 0 4px rgba(231,12,47,.12) !important; outline:none !important;
        }

        .login .user-pass-wrap { position:relative; margin-bottom:1rem; }
        .login .wp-pwd { position:relative; }
        .login .wp-pwd button.button-secondary {
            position:absolute; top:50%; right:.625rem; transform:translateY(-50%);
            background:transparent !important; border:none !important; box-shadow:none !important;
            padding:.5rem !important; margin:0 !important; color:var(--rtb-gray) !important;
            height:auto !important; min-height:0 !important;
        }
        .login .wp-pwd button.button-secondary:hover { color:var(--rtb-red) !important; }

        .forgetmenot { display:flex; align-items:center; gap:.5rem; margin:.25rem 0 1.5rem !important; }
        .forgetmenot label { color:var(--rtb-gray); font-weight:600; font-size:.875rem; margin:0 !important; cursor:pointer; }
        .forgetmenot input[type="checkbox"] { width:24px; height:24px; accent-color:var(--rtb-red); margin:0; cursor:pointer; }

        .wp-core-ui .button-primary,
        .wp-core-ui .button-primary:hover,
        .wp-core-ui .button-primary:focus {
            background:var(--rtb-red) !important; border:none !important; border-radius:9999px !important;
            color:#fff !important; font-weight:700 !important; font-size:.9375rem !important;
            padding:.875rem 1.5rem !important; box-shadow:0 10px 24px -8px rgba(231,12,47,.55) !important;
            text-shadow:none !important; text-transform:none !important; letter-spacing:.02em !important;
            float:none !important; width:100% !important; height:auto !important; line-height:1.2 !important;
            cursor:pointer; transition:background .15s, transform .15s, box-shadow .15s;
        }
        .wp-core-ui .button-primary:hover {
            background:var(--rtb-red-dark) !important; transform:translateY(-1px);
            box-shadow:0 14px 30px -10px rgba(231,12,47,.65) !important;
        }
        .submit { text-align:center; margin:0 !important; }

        #nav, #backtoblog { text-align:center; margin:.875rem 0 0 !important; padding:0 !important; font-size:.875rem; }
        #nav a, #backtoblog a { text-decoration:none !important; font-weight:600; transition:opacity .15s, color .15s; }
        @media (max-width:1023px) { #nav a, #backtoblog a { color:rgba(255,255,255,.9) !important; } }
        @media (min-width:1024px) {
            #nav a, #backtoblog a { color:var(--rtb-gray) !important; }
            #nav a:hover, #backtoblog a:hover { color:var(--rtb-red) !important; }
        }

        .login .message, .login .notice, .login #login_error {
            background:#fff !important; color:var(--rtb-ink) !important; border:none !important;
            border-left:4px solid var(--rtb-green) !important; border-radius:0 .75rem .75rem 0 !important;
            box-shadow:0 12px 28px -14px rgba(0,0,0,.25) !important; padding:1rem 1.25rem !important;
            margin:0 auto 1.25rem !important; font-size:.875rem; width:100% !important;
            max-width:450px !important; box-sizing:border-box !important;
        }
        .login #login_error { border-left-color:var(--rtb-red) !important; }
        .privacy-policy-page-link, .language-switcher { display:none !important; }
    </style>
    <?php
} );

/** Carrousel (image + dots) + injection des icônes/placeholders. */
add_action( 'login_footer', function () {
    $slides = rtb_login_slides();
    if ( empty( $slides ) ) return;
    ?>
    <div class="rtb-login-carousel" aria-hidden="true">
        <?php foreach ( $slides as $i => $url ) : ?>
            <div class="rtb-login-slide<?php echo 0 === $i ? ' is-active' : ''; ?>"
                 style="background-image:url('<?php echo esc_url( $url ); ?>');"></div>
        <?php endforeach; ?>
        <span class="rtb-login-tag">Radiodiffusion Télévision du Burkina</span>
        <div class="rtb-login-dots">
            <?php foreach ( $slides as $i => $url ) : ?>
                <button class="rtb-login-dot<?php echo 0 === $i ? ' is-active' : ''; ?>"
                        data-slide="<?php echo (int) $i; ?>" type="button" tabindex="-1"
                        aria-label="<?php echo esc_attr( sprintf( 'Image %d', $i + 1 ) ); ?>"></button>
            <?php endforeach; ?>
        </div>
        <span class="rtb-login-flag"></span>
    </div>
    <script>
    (function () {
        var slides = document.querySelectorAll('.rtb-login-slide');
        var dots   = document.querySelectorAll('.rtb-login-dot');
        if (slides.length < 2) return;
        var i = 0, timer;
        function go (n) {
            slides[i].classList.remove('is-active');
            dots[i] && dots[i].classList.remove('is-active');
            i = (n + slides.length) % slides.length;
            slides[i].classList.add('is-active');
            dots[i] && dots[i].classList.add('is-active');
        }
        function start () { timer = setInterval(function () { go(i + 1); }, 5500); }
        function stop  () { clearInterval(timer); }
        dots.forEach(function (d) {
            d.addEventListener('click', function () { stop(); go(parseInt(d.dataset.slide, 10)); start(); });
        });
        start();
    })();
    </script>
    <script>
    (function () {
        function addIcon (target, faClass) {
            if (!target || target.querySelector('.rtb-field-icon')) return;
            var i = document.createElement('i');
            i.className = 'fa-solid ' + faClass + ' rtb-field-icon';
            i.setAttribute('aria-hidden', 'true');
            target.appendChild(i);
        }
        // Landmark principal pour les lecteurs d'écran
        var loginBox = document.getElementById('login');
        if (loginBox && !loginBox.getAttribute('role')) { loginBox.setAttribute('role', 'main'); }

        // Sous-titre en tête de carte
        var form = document.getElementById('loginform');
        if (form && !form.querySelector('.rtb-login-sub')) {
            var s = document.createElement('span'); s.className = 'rtb-login-sub';
            s.textContent = 'Connectez-vous à votre espace';
            form.insertBefore(s, form.firstChild);
        }
        var u = document.getElementById('user_login');
        if (u) {
            u.placeholder = 'Identifiant ou e-mail';
            if (!u.parentElement.classList.contains('rtb-field')) {
                var wrap = document.createElement('div'); wrap.className = 'rtb-field';
                u.parentNode.insertBefore(wrap, u); wrap.appendChild(u);
            }
            addIcon(u.parentElement, 'fa-user');
        }
        var p = document.getElementById('user_pass');
        if (p) {
            p.placeholder = 'Mot de passe';
            addIcon(p.closest('.wp-pwd') || p.parentElement, 'fa-lock');
        }
        var rEmail = document.querySelector('input#user_email');
        if (rEmail && !rEmail.parentElement.classList.contains('rtb-field')) {
            rEmail.placeholder = 'Adresse e-mail';
            var w2 = document.createElement('div'); w2.className = 'rtb-field';
            rEmail.parentNode.insertBefore(w2, rEmail); w2.appendChild(rEmail);
            addIcon(w2, 'fa-envelope');
        }
    })();
    </script>
    <?php
} );
