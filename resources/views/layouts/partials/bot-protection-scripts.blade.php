{{-- Shared bot-protection helpers for AJAX public writes --}}
@php
    $turnstileSiteKey = config('security.turnstile.site_key');
    $honeypotField = config('security.honeypot.field', 'website');
@endphp
<meta name="turnstile-site-key" content="{{ $turnstileSiteKey }}">
<meta name="honeypot-field" content="{{ $honeypotField }}">
@if(config('security.hardening_enabled') && filled($turnstileSiteKey))
    <div id="bilskyen-turnstile-host" class="cf-turnstile" data-sitekey="{{ $turnstileSiteKey }}" data-size="invisible" data-callback="bilskyenTurnstileCb" style="position:absolute;left:-9999px;height:0;overflow:hidden;" aria-hidden="true"></div>
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit" async defer></script>
@endif
<script>
(function () {
    window.__bilskyenTurnstileToken = null;
    window.__bilskyenTurnstileWidgetId = null;
    window.bilskyenTurnstileCb = function (token) {
        window.__bilskyenTurnstileToken = token;
    };

    function ensureWidget() {
        var siteKey = document.querySelector('meta[name="turnstile-site-key"]')?.content || '';
        if (!siteKey || typeof turnstile === 'undefined') return null;
        if (window.__bilskyenTurnstileWidgetId !== null) return window.__bilskyenTurnstileWidgetId;
        var host = document.getElementById('bilskyen-turnstile-host');
        if (!host) return null;
        window.__bilskyenTurnstileWidgetId = turnstile.render(host, {
            sitekey: siteKey,
            size: 'invisible',
            callback: function (token) { window.__bilskyenTurnstileToken = token; }
        });
        return window.__bilskyenTurnstileWidgetId;
    }

    /**
     * Returns { website: '', 'cf-turnstile-response': '...' } for public POST bodies.
     */
    window.bilskyenBotFields = async function () {
        var honeypot = document.querySelector('meta[name="honeypot-field"]')?.content || 'website';
        var fields = {};
        fields[honeypot] = '';

        var siteKey = document.querySelector('meta[name="turnstile-site-key"]')?.content || '';
        if (!siteKey) {
            return fields;
        }

        try {
            await new Promise(function (resolve) {
                if (typeof turnstile !== 'undefined') return resolve();
                var tries = 0;
                var t = setInterval(function () {
                    tries++;
                    if (typeof turnstile !== 'undefined' || tries > 40) {
                        clearInterval(t);
                        resolve();
                    }
                }, 50);
            });

            if (typeof turnstile === 'undefined') {
                return fields;
            }

            var widgetId = ensureWidget();
            var token = await new Promise(function (resolve) {
                window.__bilskyenTurnstileToken = null;
                turnstile.execute(widgetId, {
                    callback: function (t) { resolve(t); }
                });
                // Fallback if execute doesn't fire callback (already solved).
                setTimeout(function () {
                    resolve(window.__bilskyenTurnstileToken || turnstile.getResponse(widgetId) || '');
                }, 4000);
            });
            if (token) {
                fields['cf-turnstile-response'] = token;
            }
        } catch (e) {
            console.warn('Turnstile unavailable', e);
        }

        return fields;
    };

    /**
     * Guests must provide contact details before phone reveal / lead create.
     */
    window.bilskyenCollectGuestContact = async function () {
        function getCookie(name) {
            var value = '; ' + document.cookie;
            var parts = value.split('; ' + name + '=');
            if (parts.length === 2) return parts.pop().split(';').shift();
            return null;
        }
        if (getCookie('bilskyen_auth')) {
            return {};
        }
        var name = window.prompt('{{ __("messages.forms.full_name") }}');
        if (!name) throw new Error('cancelled');
        var email = window.prompt('{{ __("messages.forms.email") }}');
        if (!email) throw new Error('cancelled');
        var phone = window.prompt('{{ __("messages.forms.phone") }}');
        if (!phone) throw new Error('cancelled');
        return { name: name.trim(), email: email.trim(), phone: phone.trim() };
    };
})();
</script>
