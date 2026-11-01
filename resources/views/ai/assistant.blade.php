{{-- resources/views/ai/assistant.blade.php --}}
<x-app-layout>
    <x-slot name="header">Assistant HeatAlert (IA)</x-slot>

    <style>
        .chat-box { height: 440px; overflow-y: auto; background: #f8f9fc; }
        .chat-avatar {
            width: 32px; height: 32px; flex: 0 0 32px;
            display: flex; align-items: center; justify-content: center;
            border-radius: 50%; background: #4e73df; color: #fff; font-size: .8rem;
        }
        .bubble {
            max-width: 80%; padding: .6rem .9rem; border-radius: 1rem;
            white-space: pre-line; overflow-wrap: anywhere; line-height: 1.45;
        }
        .bubble-user { background: #4e73df; color: #fff; border-bottom-right-radius: .25rem; }
        .bubble-ai   { background: #fff; color: #5a5c69; border: 1px solid #e3e6f0; border-bottom-left-radius: .25rem; }
        .bubble-error { background: #fdf0ee; color: #a52a1c; border-color: #f5c6c0; }
        .typing span {
            display: inline-block; width: 6px; height: 6px; margin: 0 2px;
            border-radius: 50%; background: #b7b9cc; animation: chat-blink 1.2s infinite;
        }
        .typing span:nth-child(2) { animation-delay: .2s; }
        .typing span:nth-child(3) { animation-delay: .4s; }
        @@keyframes chat-blink { 0%, 80%, 100% { opacity: .3; } 40% { opacity: 1; } }
        @@media (prefers-reduced-motion: reduce) { .typing span { animation: none; opacity: .7; } }
    </style>

    <div class="row justify-content-center">
        <div class="col-xl-8 col-lg-10">
            <div class="card shadow mb-4">

                <div class="card-header py-3 d-flex align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-robot mr-2"></i>Assistant HeatAlert
                    </h6>
                    <button type="button" id="chat-reset" class="btn btn-sm btn-light" title="Nouvelle conversation">
                        <i class="fas fa-redo fa-sm mr-1"></i>Nouvelle conversation
                    </button>
                </div>

                <div id="chat-box" class="chat-box p-3" aria-live="polite"></div>

                <div class="card-footer bg-white">
                    <div class="mb-2" id="chat-suggestions">
                        <button type="button" class="btn btn-outline-primary btn-sm rounded-pill mb-1" data-q="Y a-t-il une coupure dans mon quartier ?">
                            Y a-t-il une coupure dans mon quartier ?
                        </button>
                        <button type="button" class="btn btn-outline-primary btn-sm rounded-pill mb-1" data-q="Quel est le risque de coupure cette semaine ?">
                            Quel est le risque de coupure cette semaine ?
                        </button>
                        <button type="button" class="btn btn-outline-primary btn-sm rounded-pill mb-1" data-q="Quand le courant sera-t-il rétabli ?">
                            Quand le courant sera-t-il rétabli ?
                        </button>
                    </div>

                    <div class="input-group">
                        <input type="text" id="chat-input" class="form-control" maxlength="300" autocomplete="off"
                               placeholder="Ex : Quand le courant revient-il dans mon quartier ?"
                               aria-label="Ta question">
                        <div class="input-group-append">
                            <button type="button" id="chat-send" class="btn btn-primary">
                                <i class="fas fa-paper-plane mr-1"></i>Envoyer
                            </button>
                        </div>
                    </div>
                    <small id="chat-counter" class="form-text text-muted">0 / 300 caractères</small>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            var ASK_URL = @json(route('ai.assistant.ask'));
            var CSRF    = @json(csrf_token());
            var WELCOME = "Bonjour ! Pose-moi une question sur les coupures de courant ou le risque dans ton quartier.";

            var box     = document.getElementById('chat-box');
            var input   = document.getElementById('chat-input');
            var sendBtn = document.getElementById('chat-send');
            var counter = document.getElementById('chat-counter');
            var resetBtn = document.getElementById('chat-reset');
            var busy = false;

            function scrollDown() { box.scrollTop = box.scrollHeight; }

            function addMessage(role, text, isError) {
                var row = document.createElement('div');
                row.className = 'd-flex align-items-end mb-3' + (role === 'user' ? ' justify-content-end' : '');

                if (role === 'ai') {
                    var avatar = document.createElement('div');
                    avatar.className = 'chat-avatar mr-2';
                    avatar.innerHTML = '<i class="fas fa-robot"></i>';
                    row.appendChild(avatar);
                }

                var bubble = document.createElement('div');
                bubble.className = 'bubble ' + (role === 'user' ? 'bubble-user' : 'bubble-ai') + (isError ? ' bubble-error' : '');
                bubble.textContent = text;   // textContent : pas de risque XSS
                row.appendChild(bubble);

                box.appendChild(row);
                scrollDown();
                return row;
            }

            function showTyping() {
                var row = document.createElement('div');
                row.className = 'd-flex align-items-end mb-3';
                row.innerHTML =
                    '<div class="chat-avatar mr-2"><i class="fas fa-robot"></i></div>' +
                    '<div class="bubble bubble-ai typing" aria-label="L\'assistant écrit"><span></span><span></span><span></span></div>';
                box.appendChild(row);
                scrollDown();
                return row;
            }

            function setBusy(state) {
                busy = state;
                sendBtn.disabled = state;
                input.disabled = state;
            }

            function updateCounter() {
                var n = input.value.length;
                counter.textContent = n + ' / 300 caractères';
                counter.className = 'form-text ' + (n > 0 && n < 3 ? 'text-danger' : 'text-muted');
            }

            function errorMessage(err) {
                if (err && err.status) {
                    if (err.status === 419) return "Ta session a expiré. Recharge la page puis réessaie.";
                    if (err.status === 401 || err.status === 403) return "Tu n'as pas accès à l'assistant. Reconnecte-toi ou contacte un administrateur.";
                    if (err.status === 422) return (err.data && err.data.message) || "Ta question n'est pas valide (entre 3 et 300 caractères).";
                    if (err.status === 429) return "Trop de questions d'affilée. Attends un instant puis réessaie.";
                    if (err.status === 404) return "L'assistant est introuvable (erreur 404).";
                    return "Le service IA ne répond pas (erreur " + err.status + "). Réessaie dans un instant.";
                }
                return "Connexion impossible. Vérifie ta connexion internet puis réessaie.";
            }

            async function ask(q) {
                q = (q || '').trim();
                if (busy) return;
                if (q.length < 3) {
                    counter.textContent = 'Écris au moins 3 caractères.';
                    counter.className = 'form-text text-danger';
                    input.focus();
                    return;
                }

                addMessage('user', q);
                input.value = '';
                updateCounter();
                setBusy(true);
                var typing = showTyping();

                try {
                    var res = await fetch(ASK_URL, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': CSRF
                        },
                        body: JSON.stringify({ question: q })
                    });

                    var data = {};
                    try { data = await res.json(); } catch (e) { /* réponse non JSON */ }

                    if (!res.ok) { throw { status: res.status, data: data }; }

                    addMessage('ai', data.answer || data.message || "Je n'ai pas de réponse pour le moment.");
                } catch (err) {
                    console.error('[assistant IA]', err);   // détail technique visible dans la console F12
                    addMessage('ai', errorMessage(err), true);
                } finally {
                    typing.remove();
                    setBusy(false);
                    input.focus();
                }
            }

            function reset() {
                box.innerHTML = '';
                addMessage('ai', WELCOME);
                input.value = '';
                updateCounter();
            }

            sendBtn.addEventListener('click', function () { ask(input.value); });
            input.addEventListener('input', updateCounter);
            input.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') { e.preventDefault(); ask(input.value); }
            });
            document.querySelectorAll('#chat-suggestions [data-q]').forEach(function (btn) {
                btn.addEventListener('click', function () { ask(btn.getAttribute('data-q')); });
            });
            resetBtn.addEventListener('click', reset);

            reset();
        })();
    </script>
</x-app-layout>