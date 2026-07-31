{{-- Floating FAQ chatbot (only included when faq_chatbot_enabled) --}}
@php
    $honeypotField = config('security.honeypot.field', 'website');
@endphp
<div id="faq-chatbot" class="faq-chatbot" data-locale="{{ app()->getLocale() }}" data-api-url="{{ url('/api/v1/faq/chat') }}" data-honeypot="{{ $honeypotField }}">
    <button type="button" id="faq-chatbot-toggle" class="faq-chatbot__toggle" aria-expanded="false" aria-controls="faq-chatbot-panel">
        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
        <span class="sr-only">{{ __('messages.pages.faq.chat.open') }}</span>
    </button>

    <div id="faq-chatbot-panel" class="faq-chatbot__panel hidden" role="dialog" aria-labelledby="faq-chatbot-title" aria-hidden="true">
        <header class="faq-chatbot__header">
            <div>
                <h2 id="faq-chatbot-title" class="faq-chatbot__title">{{ __('messages.pages.faq.chat.title') }}</h2>
                <p class="faq-chatbot__subtitle">{{ __('messages.pages.faq.chat.subtitle') }}</p>
            </div>
            <button type="button" id="faq-chatbot-close" class="faq-chatbot__close" aria-label="{{ __('messages.pages.faq.chat.close') }}">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
        </header>

        <div id="faq-chatbot-messages" class="faq-chatbot__messages" aria-live="polite">
            <div class="faq-chatbot__bubble faq-chatbot__bubble--assistant">
                {{ __('messages.pages.faq.chat.welcome') }}
            </div>
        </div>

        <form id="faq-chatbot-form" class="faq-chatbot__form" autocomplete="off">
            <input type="text" name="{{ $honeypotField }}" value="" tabindex="-1" autocomplete="off" class="faq-chatbot__honeypot" aria-hidden="true">
            <label for="faq-chatbot-input" class="sr-only">{{ __('messages.pages.faq.chat.placeholder') }}</label>
            <input id="faq-chatbot-input" type="text" maxlength="2000" required placeholder="{{ __('messages.pages.faq.chat.placeholder') }}" class="faq-chatbot__input">
            <button type="submit" id="faq-chatbot-send" class="faq-chatbot__send">
                {{ __('messages.pages.faq.chat.send') }}
            </button>
        </form>
        <p id="faq-chatbot-error" class="faq-chatbot__error hidden" role="alert"></p>
    </div>
</div>

<style>
.faq-chatbot { position: fixed; right: 1.25rem; bottom: 1.25rem; z-index: 60; font-family: inherit; }
.faq-chatbot__toggle {
    display: inline-flex; align-items: center; justify-content: center;
    width: 3.25rem; height: 3.25rem; border-radius: 9999px;
    background: var(--primary); color: var(--primary-foreground);
    border: none; box-shadow: 0 8px 24px rgba(0,0,0,.18); cursor: pointer;
}
.faq-chatbot__toggle:hover { opacity: .92; }
.faq-chatbot__panel {
    position: absolute; right: 0; bottom: 4rem; width: min(22rem, calc(100vw - 2rem));
    max-height: min(28rem, calc(100vh - 6rem));
    display: flex; flex-direction: column;
    border-radius: .75rem; border: 1px solid var(--border);
    background: var(--card); color: var(--foreground);
    box-shadow: 0 16px 40px rgba(0,0,0,.18); overflow: hidden;
}
.faq-chatbot__panel.hidden { display: none; }
.faq-chatbot__header {
    display: flex; align-items: flex-start; justify-content: space-between; gap: .75rem;
    padding: .9rem 1rem; border-bottom: 1px solid var(--border); background: var(--muted);
}
.faq-chatbot__title { margin: 0; font-size: .95rem; font-weight: 600; }
.faq-chatbot__subtitle { margin: .2rem 0 0; font-size: .75rem; color: var(--muted-foreground); }
.faq-chatbot__close {
    border: none; background: transparent; color: var(--muted-foreground);
    padding: .25rem; cursor: pointer; border-radius: .375rem;
}
.faq-chatbot__close:hover { background: var(--muted); color: var(--foreground); }
.faq-chatbot__messages {
    flex: 1; overflow-y: auto; padding: .9rem 1rem; display: flex; flex-direction: column; gap: .65rem;
    min-height: 12rem;
}
.faq-chatbot__bubble {
    max-width: 90%; padding: .65rem .8rem; border-radius: .75rem; font-size: .85rem; line-height: 1.45;
    white-space: pre-wrap;
}
.faq-chatbot__bubble--assistant { align-self: flex-start; background: var(--muted); }
.faq-chatbot__bubble--user { align-self: flex-end; background: var(--primary); color: var(--primary-foreground); }
.faq-chatbot__form {
    display: flex; gap: .5rem; padding: .75rem 1rem; border-top: 1px solid var(--border);
}
.faq-chatbot__input {
    flex: 1; min-width: 0; border: 1px solid var(--border); border-radius: .5rem;
    padding: .55rem .7rem; font-size: .85rem; background: var(--background); color: var(--foreground);
}
.faq-chatbot__send {
    border: none; border-radius: .5rem; padding: .55rem .85rem; font-size: .8rem; font-weight: 600;
    background: var(--primary); color: var(--primary-foreground); cursor: pointer;
}
.faq-chatbot__send:disabled { opacity: .6; cursor: not-allowed; }
.faq-chatbot__error { margin: 0; padding: 0 1rem .75rem; font-size: .75rem; color: var(--destructive); }
.faq-chatbot__error.hidden { display: none; }
.faq-chatbot__honeypot {
    position: absolute; left: -9999px; width: 1px; height: 1px; opacity: 0; pointer-events: none;
}
.sr-only {
    position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden;
    clip: rect(0,0,0,0); white-space: nowrap; border: 0;
}
</style>

@push('scripts')
<script>
(function () {
    var root = document.getElementById('faq-chatbot');
    if (!root) return;

    var toggle = document.getElementById('faq-chatbot-toggle');
    var panel = document.getElementById('faq-chatbot-panel');
    var closeBtn = document.getElementById('faq-chatbot-close');
    var form = document.getElementById('faq-chatbot-form');
    var input = document.getElementById('faq-chatbot-input');
    var sendBtn = document.getElementById('faq-chatbot-send');
    var messages = document.getElementById('faq-chatbot-messages');
    var errorEl = document.getElementById('faq-chatbot-error');
    var apiUrl = root.getAttribute('data-api-url');
    var locale = root.getAttribute('data-locale') || 'da';
    var honeypot = root.getAttribute('data-honeypot') || 'website';
    var history = [];
    var busy = false;

    var i18n = {
        thinking: @json(__('messages.pages.faq.chat.thinking')),
        error: @json(__('messages.pages.faq.chat.error')),
        open: @json(__('messages.pages.faq.chat.open')),
        close: @json(__('messages.pages.faq.chat.close')),
    };

    function setOpen(open) {
        panel.classList.toggle('hidden', !open);
        panel.setAttribute('aria-hidden', open ? 'false' : 'true');
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        if (open) input.focus();
    }

    function appendBubble(role, text) {
        var div = document.createElement('div');
        div.className = 'faq-chatbot__bubble faq-chatbot__bubble--' + role;
        div.textContent = text;
        messages.appendChild(div);
        messages.scrollTop = messages.scrollHeight;
        return div;
    }

    function showError(msg) {
        errorEl.textContent = msg || i18n.error;
        errorEl.classList.remove('hidden');
    }

    function clearError() {
        errorEl.textContent = '';
        errorEl.classList.add('hidden');
    }

    toggle.addEventListener('click', function () {
        setOpen(panel.classList.contains('hidden'));
    });
    closeBtn.addEventListener('click', function () { setOpen(false); });

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        if (busy) return;
        var text = (input.value || '').trim();
        if (!text) return;

        clearError();
        appendBubble('user', text);
        input.value = '';
        history.push({ role: 'user', content: text });

        busy = true;
        sendBtn.disabled = true;
        var thinking = appendBubble('assistant', i18n.thinking);

        var payload = {
            message: text,
            locale: locale,
            history: history.slice(0, -1).slice(-10),
        };
        payload[honeypot] = '';

        try {
            var res = await fetch(apiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(payload),
            });
            var json = await res.json().catch(function () { return {}; });
            var reply = (json && json.data && json.data.reply) || (json && json.message) || null;
            if (!res.ok || !reply) {
                thinking.remove();
                showError((json && json.message) || i18n.error);
            } else {
                thinking.textContent = reply;
                history.push({ role: 'assistant', content: reply });
            }
        } catch (err) {
            thinking.remove();
            showError(i18n.error);
        } finally {
            busy = false;
            sendBtn.disabled = false;
            input.focus();
        }
    });
})();
</script>
@endpush
