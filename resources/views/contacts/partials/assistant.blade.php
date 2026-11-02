{{-- ===================== ASSISTANT IA D'URGENCE ===================== --}}
<style>
    .ai-box { border: 2px solid #ffd2bd; border-radius: 18px; background: linear-gradient(180deg, #fff8f4, #fff); }
    .ai-badge { background: var(--heat-navy); color: #fff; border-radius: 30px; padding: 3px 12px; font-size: .75rem; font-weight: 700; }
    .ai-example { cursor: pointer; font-size: .8rem; }
    .ai-main { border-radius: 14px; border-left: 8px solid var(--heat-red); }
    .ai-phone { font-size: 2.4rem; font-weight: 900; color: var(--heat-red); line-height: 1; }
    .urg-critique { background: #dc3545; } .urg-elevee { background: #fd7e14; }
    .urg-moderee { background: #ffc107; color: #222 !important; } .urg-faible { background: #198754; }
</style>

<section class="ai-box p-4 mb-5 shadow-sm" id="assistant">
    <div class="d-flex align-items-center gap-2 mb-2">
        <span class="ai-badge"><i class="bi bi-stars"></i> Assistant IA</span>
        <h2 class="h5 fw-bold mb-0" style="color: var(--heat-navy)">Quel numéro dois-je appeler ?</h2>
    </div>
    <p class="text-muted small mb-3">
        Décrivez votre situation (ce qui se passe, qui est concerné, où vous êtes). L'assistant vous propose le contact le plus adapté.
    </p>

    <form id="aiForm" novalidate>
        <textarea id="aiSituation" class="form-control mb-2" rows="3" maxlength="1000"
                  placeholder="Ex : Mon grand-père a très chaud, il est confus et ne transpire plus. Nous sommes à Ariana."></textarea>

        <div class="d-flex flex-wrap gap-2 mb-3">
            <span class="badge rounded-pill text-bg-light border ai-example">Un voisin âgé a fait un malaise à cause de la chaleur</span>
            <span class="badge rounded-pill text-bg-light border ai-example">Un câble électrique est tombé dans la rue à Tunis</span>
            <span class="badge rounded-pill text-bg-light border ai-example">Coupure de courant depuis 6 heures dans mon quartier</span>
            <span class="badge rounded-pill text-bg-light border ai-example">Il y a de la fumée qui sort d'un appartement</span>
        </div>

        <div class="d-flex align-items-center gap-3">
            <button type="submit" id="aiSubmit" class="btn btn-heat btn-lg">
                <span class="spinner-border spinner-border-sm d-none me-1" id="aiSpinner"></span>
                <i class="bi bi-search-heart" id="aiIcon"></i> Analyser ma situation
            </button>
            <small class="text-muted"><span id="aiCount">0</span>/1000</small>
        </div>
        <div id="aiError" class="alert alert-danger mt-3 d-none"></div>
    </form>

    {{-- Résultat (rempli en JS) --}}
    <div id="aiResult" class="mt-4 d-none">
        <div class="card ai-main shadow-sm">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                    <div>
                        <div class="small text-muted text-uppercase fw-bold">Contact recommandé</div>
                        <div class="h5 fw-bold mb-1" id="aiName"></div>
                        <span class="badge rounded-pill" id="aiCategory"></span>
                        <span class="badge rounded-pill text-bg-success d-none" id="ai24h">24h/24</span>
                        <span class="small text-muted ms-1" id="aiCity"></span>
                    </div>
                    <span class="badge rounded-pill text-white px-3 py-2" id="aiUrgency"></span>
                </div>

                <div class="d-flex flex-wrap align-items-center gap-3 my-3">
                    <div class="ai-phone" id="aiPhone"></div>
                    <a href="#" id="aiCall" class="btn btn-heat btn-lg"><i class="bi bi-telephone-outbound"></i> Appeler maintenant</a>
                    <a href="#" id="aiDetails" class="btn btn-outline-secondary rounded-pill">Détails</a>
                </div>

                <p class="mb-2" id="aiReason"></p>
                <div id="aiAdviceWrap" class="d-none">
                    <div class="fw-bold small text-uppercase text-muted mb-1">En attendant :</div>
                    <ul class="mb-0" id="aiAdvice"></ul>
                </div>
            </div>
        </div>

        <div id="aiAltWrap" class="mt-3 d-none">
            <div class="fw-bold small text-uppercase text-muted mb-2">Autres contacts utiles</div>
            <div class="row g-2" id="aiAlternatives"></div>
        </div>

        <p class="small text-muted mt-3 mb-0">
            <i class="bi bi-info-circle"></i> <span id="aiSource"></span>
            Suggestion automatique : en cas de danger immédiat, appelez directement le <strong>190</strong> (SAMU),
            le <strong>198</strong> (Protection civile) ou le <strong>197</strong> (Police).
        </p>
    </div>
</section>

@push('scripts')
<script>
(function () {
    const form = document.getElementById('aiForm');
    if (!form) return;

    const $ = id => document.getElementById(id);
    const textarea = $('aiSituation');
    const urgencyLabels = { critique: 'Urgence critique', elevee: 'Urgence élevée', moderee: 'Urgence modérée', faible: 'Peu urgent' };

    textarea.addEventListener('input', () => $('aiCount').textContent = textarea.value.length);

    document.querySelectorAll('.ai-example').forEach(chip => chip.addEventListener('click', () => {
        textarea.value = chip.textContent.trim();
        $('aiCount').textContent = textarea.value.length;
        textarea.focus();
    }));

    function setLoading(on) {
        $('aiSubmit').disabled = on;
        $('aiSpinner').classList.toggle('d-none', !on);
        $('aiIcon').classList.toggle('d-none', on);
    }

    function showError(msg) {
        $('aiError').textContent = msg;
        $('aiError').classList.remove('d-none');
    }

    function render(data) {
        const c = data.contact;
        if (!c) { showError(data.reason || 'Aucun contact disponible.'); return; }

        $('aiName').textContent = c.name;
        $('aiCategory').textContent = c.category || '';
        $('aiCategory').style.background = c.color || '#e8590c';
        $('ai24h').classList.toggle('d-none', !c.is_24h);
        $('aiCity').textContent = c.city ? '· ' + c.city : '';
        $('aiPhone').textContent = c.phone;
        $('aiCall').href = 'tel:' + c.phone_link;
        $('aiDetails').href = c.url;
        $('aiReason').textContent = data.reason || '';

        const urg = $('aiUrgency');
        urg.className = 'badge rounded-pill text-white px-3 py-2 urg-' + data.urgency;
        urg.textContent = urgencyLabels[data.urgency] || 'Urgence';

        const list = $('aiAdvice');
        list.innerHTML = '';
        (data.advice || []).forEach(a => { const li = document.createElement('li'); li.textContent = a; list.appendChild(li); });
        $('aiAdviceWrap').classList.toggle('d-none', !(data.advice || []).length);

        const alts = $('aiAlternatives');
        alts.innerHTML = '';
        (data.alternatives || []).forEach(a => {
            const col = document.createElement('div');
            col.className = 'col-md-6';
            const link = document.createElement('a');
            link.href = 'tel:' + a.phone_link;
            link.className = 'd-flex justify-content-between align-items-center border rounded-3 p-2 text-decoration-none text-dark bg-white';
            const name = document.createElement('span'); name.textContent = a.name + (a.category ? ' — ' + a.category : '');
            const phone = document.createElement('strong'); phone.textContent = a.phone; phone.style.color = 'var(--heat-red)';
            link.append(name, phone);
            col.appendChild(link);
            alts.appendChild(col);
        });
        $('aiAltWrap').classList.toggle('d-none', !(data.alternatives || []).length);

        $('aiSource').textContent = data.source === 'ai' ? 'Analyse réalisée par l\'IA.' : 'Analyse par mots-clés (IA indisponible).';

        $('aiResult').classList.remove('d-none');
        $('aiResult').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        $('aiError').classList.add('d-none');

        const situation = textarea.value.trim();
        if (situation.length < 10) { showError('Donnez un peu plus de détails (au moins 10 caractères).'); return; }

        setLoading(true);
        try {
            const res = await fetch(@json(route('contacts.assistant')), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': @json(csrf_token()),
                },
                body: JSON.stringify({ situation }),
            });

            const data = await res.json().catch(() => ({}));

            if (res.status === 422) {
                showError(data.errors?.situation?.[0] || 'Description invalide.');
            } else if (res.status === 429) {
                showError('Trop de demandes. Patientez une minute avant de réessayer.');
            } else if (!res.ok) {
                showError('Le service est momentanément indisponible. En cas de danger : 190, 198 ou 197.');
            } else {
                render(data);
            }
        } catch (err) {
            showError('Connexion impossible. En cas de danger immédiat : 190 (SAMU), 198 (Protection civile), 197 (Police).');
        } finally {
            setLoading(false);
        }
    });
})();
</script>
@endpush
