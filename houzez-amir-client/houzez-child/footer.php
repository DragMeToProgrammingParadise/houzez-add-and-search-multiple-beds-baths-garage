</main><!-- .main-wrap start in header.php-->

<?php 

if ( ! houzez_is_splash() ) {
    if ( houzez_is_dashboard() ) {
        get_template_part('template-parts/dashboard/dashboard-footer'); 
    } else {

        do_action( 'houzez_before_footer' );

        if ((!function_exists('elementor_theme_do_location') || !elementor_theme_do_location('footer')) && 
            (!houzez_is_half_map() || (houzez_is_half_map() && houzez_option('halfmap-footer', 1) == 1))) 
        {
            
            if( function_exists('fts_footer_enabled') && fts_footer_enabled() ) {
                do_action( 'houzez_footer_studio' );
            } else { 
                do_action( 'houzez_footer' );
            }
        }
    }
}

do_action( 'houzez_after_footer' );

do_action( 'houzez_before_wp_footer' );

wp_footer(); ?>


<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intl-tel-input@23.1.0/build/css/intlTelInput.css">
<script src="https://cdn.jsdelivr.net/npm/intl-tel-input@23.1.0/build/js/intlTelInput.min.js"></script>

<script>

    document.addEventListener("DOMContentLoaded", function () {
        async function setCountryCode() {
            const countryCodeCookie = getCookie('countryCode');
            if (countryCodeCookie) {
                return countryCodeCookie;
            } else {
                try {
                    const response = await fetch("https://ipapi.co/json");
                    const data = await response.json();
                    const countryCode = data.country_code || "us";
                    setCookie('countryCode', countryCode, 30); // Store the country code in a cookie for 30 days
                    return countryCode;
                } catch (error) {
                    return "us";
                }
            }
        }

        function initializePhoneInputs(countryCode) {
            const phoneInputs = document.getElementsByName('mobile');
            const phoneInputs2 = document.getElementsByName('phone');
            phoneInputs.forEach(phoneInput => {
                window.intlTelInput(phoneInput, {
                    initialCountry: countryCode,
                    separateDialCode: true,
                });
            });

            phoneInputs2.forEach(phoneInput => {
                window.intlTelInput(phoneInput, {
                    initialCountry: countryCode,
                    separateDialCode: true,
                });
            });
        }

        function setCookie(name, value, days) {
            const date = new Date();
            date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
            const expires = "; expires=" + date.toUTCString();
            document.cookie = name + "=" + (value || "") + expires + "; path=/";
        }

        function getCookie(name) {
            const nameEQ = name + "=";
            const ca = document.cookie.split(';');
            for (let i = 0; i < ca.length; i++) {
                let c = ca[i];
                while (c.charAt(0) === ' ') c = c.substring(1, c.length);
                if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length, c.length);
            }
            return null;
        }

        async function init() {
            const countryCode = await setCountryCode();
            initializePhoneInputs(countryCode);
        }

        init();
    });


</script>

</body>
</html>
