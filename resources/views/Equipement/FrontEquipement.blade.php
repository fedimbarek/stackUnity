@extends('layouts.front')
@section('title', 'Équipements')

@section('content')
<style>
    #equipements-root[dir="rtl"] {
        text-align: right;
    }

    #equipements-root[dir="rtl"] .form-control,
    #equipements-root[dir="rtl"] .form-select {
        text-align: right;
    }

    #equipements-root[dir="rtl"] .price-value,
    #equipements-root[dir="rtl"] [dir="ltr"] {
        direction: ltr;
        unicode-bidi: plaintext;
        text-align: left;
    }

    #equipements-root[dir="rtl"] details > summary,
    #equipements-root[dir="rtl"] details form > button {
        display: block;
        margin-inline-start: auto;
    }
</style>

<div id="equipements-root" lang="fr">
<header class="masthead" style="min-height: 40vh;">
    <div class="container">
        <h1 style="font-size: 2.2rem;" data-i18n="pageTitle">Équipements</h1>
        <p data-i18n="pageDescription">Découvrez les équipements disponibles pour les résidents.</p>
    </div>
</header>

<section class="section-light">
    <div class="container">
        <h2 data-i18n="equipmentTitle">Équipements disponibles</h2>

        <div class="mb-4" style="max-width: 320px;">
            <label for="equipements-language" class="form-label" data-i18n="languageLabel">Langue</label>
            <select id="equipements-language" class="form-select" aria-label="Langue" data-i18n-aria-label="languageLabel">
                <option value="fr">Français</option>
                <option value="en">English</option>
                <option value="ar">العربية</option>
                <option value="es">Español</option>
            </select>
            <div id="equipements-language-status" class="form-text" role="status" hidden></div>
            <div id="equipements-language-error" class="text-danger small mt-1" role="alert" hidden></div>
        </div>

        @if (session('success'))
            <div class="alert alert-success" role="status">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger" role="alert">
                <p class="mb-1" data-i18n="reservationErrors">Vérifiez les informations de réservation :</p>
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <h3 class="h5 card-title" data-i18n="advisorTitle">Que me conseillez-vous&nbsp;?</h3>
                <p class="text-muted" data-i18n="advisorDescription">Décrivez votre situation pour recevoir des recommandations adaptées à la météo et aux équipements disponibles.</p>
                <form id="equipement-advisor-form"
                      action="{{ route('front.equipements.recommander') }}"
                      method="POST"
                      data-url="{{ route('front.equipements.recommander') }}">
                    @csrf
                    <input type="hidden" name="lang" id="equipement-advisor-language" value="fr">
                    <label for="equipement-besoin" class="form-label" data-i18n="needLabel">Votre besoin</label>
                    <textarea id="equipement-besoin"
                              name="besoin"
                              class="form-control"
                              rows="3"
                              minlength="5"
                              maxlength="500"
                              required
                              data-i18n-placeholder="needPlaceholder"
                              placeholder="Ex. famille de 5 personnes, coupure d’eau demain, 42 °C"></textarea>
                    <button id="equipement-advisor-submit" type="submit" class="btn btn-primary mt-3" data-i18n="advisorSubmit">
                        Obtenir une recommandation
                    </button>
                </form>
                <div id="equipement-advisor-error" class="alert alert-danger mt-3 mb-0" role="alert" hidden></div>
                <div id="equipement-advisor-results" class="mt-4" aria-live="polite"></div>
            </div>
        </div>

        @forelse ($equipements as $equipement)
            @if ($loop->first)
                <div class="row g-4">
            @endif

            <div class="col-md-6 col-lg-4">
                <article id="equipement-card-{{ $equipement->id }}" class="card h-100 shadow-sm" data-equipement-id="{{ $equipement->id }}">
                    @if ($equipement->image)
                        <a href="{{ asset('storage/' . $equipement->image) }}"
                           target="_blank"
                           rel="noopener"
                           data-image-link
                           aria-label="Voir l’image de {{ $equipement->nom }} en grand">
                            <img
                                src="{{ asset('storage/' . $equipement->image) }}"
                                class="card-img-top"
                                alt="{{ $equipement->nom }}"
                                data-equipement-image
                                data-i18n-title="imageZoom"
                                title="Cliquer pour voir l’image en grand"
                                style="height: 220px; object-fit: cover;"
                            >
                            <span class="d-block text-center small py-1" data-i18n="imageZoom">Voir l’image en grand</span>
                        </a>
                    @else
                        <div class="d-flex align-items-center justify-content-center bg-light text-muted" style="height: 220px;" data-i18n="noImage">
                            Aucune image disponible
                        </div>
                    @endif

                    <div class="card-body" data-translate-card>
                        <h3 class="card-title h5" data-field="nom" data-original="{{ $equipement->nom }}">{{ $equipement->nom }}</h3>
                        <p class="card-text text-muted" data-field="type" data-original="{{ $equipement->type_equipement }}">{{ $equipement->type_equipement }}</p>
                        <p class="mb-1 fw-bold"><span class="price-value" dir="ltr">{{ number_format($equipement->prix_louer, 2) }} <span data-i18n="currency">DT</span></span></p>
                        <small class="text-muted d-block">
                            <span data-i18n="addedOn">Ajouté le</span>
                            <time data-equipement-date data-date="{{ \Illuminate\Support\Carbon::parse($equipement->date_ajout)->format('Y-m-d') }}">{{ \Illuminate\Support\Carbon::parse($equipement->date_ajout)->format('d/m/Y') }}</time>
                        </small>
                    </div>
                    @if ($equipement->etat === 'reserve')
                        <div class="card-body pt-0">
                            <button type="button" class="btn btn-secondary" disabled aria-disabled="true" data-i18n="alreadyReserved">
                                Déjà réservé
                            </button>
                        </div>
                    @else
                        <details id="reservation-form-{{ $equipement->id }}" class="card-body pt-0" @if ($errors->any()) open @endif>
                            <summary class="btn btn-primary"><span data-i18n="reserve">Réserver</span></summary>
                            <form action="{{ route('front.equipements.reservations.store', $equipement) }}" method="POST" class="mt-3">
                                @csrf

                                <div class="mb-2">
                                    <label for="nom-{{ $equipement->id }}" class="form-label" data-i18n="lastName">Nom</label>
                                    <input id="nom-{{ $equipement->id }}" name="nom" type="text" class="form-control" value="{{ old('nom') }}" maxlength="255" required>
                                </div>
                                <div class="mb-2">
                                    <label for="prenom-{{ $equipement->id }}" class="form-label" data-i18n="firstName">Prénom</label>
                                    <input id="prenom-{{ $equipement->id }}" name="prenom" type="text" class="form-control" value="{{ old('prenom') }}" maxlength="255" required>
                                </div>
                                <div class="mb-2">
                                    <label for="email-{{ $equipement->id }}" class="form-label" data-i18n="email">E-mail</label>
                                    <input id="email-{{ $equipement->id }}" name="email" type="email" class="form-control" dir="ltr" value="{{ old('email') }}" maxlength="255" required>
                                </div>
                                <div class="mb-2">
                                    <label for="numero-{{ $equipement->id }}" class="form-label" data-i18n="phone">Numéro de téléphone</label>
                                    <input id="numero-{{ $equipement->id }}" name="numero" type="tel" class="form-control" dir="ltr" value="{{ old('numero') }}" maxlength="30" required>
                                </div>
                                <div class="mb-2">
                                    <label for="date-debut-{{ $equipement->id }}" class="form-label" data-i18n="startDate">Date de début</label>
                                    <input id="date-debut-{{ $equipement->id }}" name="date_debut" type="date" class="form-control" value="{{ old('date_debut') }}" min="{{ now()->toDateString() }}" required>
                                </div>
                                <div class="mb-3">
                                    <label for="date-fin-{{ $equipement->id }}" class="form-label" data-i18n="endDate">Date de fin</label>
                                    <input id="date-fin-{{ $equipement->id }}" name="date_fin" type="date" class="form-control" value="{{ old('date_fin') }}" min="{{ old('date_debut', now()->toDateString()) }}" required>
                                </div>
                                <button type="submit" class="btn btn-primary" data-i18n="sendRequest">Envoyer la demande</button>
                            </form>
                        </details>
                    @endif
                </article>
            </div>

            @if ($loop->last)
                </div>
            @endif
        @empty
            <p class="text-center text-muted mb-0">Aucun équipement n’est disponible pour le moment.</p>
        @endforelse
    </div>
</section>
 </div>

<script>
const I18N = {
    fr: {
        pageTitle: 'Équipements',
        pageDescription: 'Découvrez les équipements disponibles pour les résidents.',
        equipmentTitle: 'Équipements disponibles',
        languageLabel: 'Langue',
        reservationErrors: 'Vérifiez les informations de réservation :',
        advisorTitle: 'Que me conseillez-vous ?',
        advisorDescription: 'Décrivez votre situation pour recevoir des recommandations adaptées à la météo et aux équipements disponibles.',
        needLabel: 'Votre besoin',
        needPlaceholder: 'Ex. famille de 5 personnes, coupure d’eau demain, 42 °C',
        advisorSubmit: 'Obtenir une recommandation',
        advisorLoading: 'Analyse en cours…',
        advisorError: 'Impossible d’obtenir une recommandation. Veuillez réessayer.',
        translating: 'Traduction…',
        translationError: 'La traduction est indisponible. Les textes français sont conservés.',
        translationLimit: 'La traduction est limitée à 50 équipements par page.',
        imageZoom: 'Voir l’image en grand',
        noImage: 'Aucune image disponible',
        currency: 'DT',
        addedOn: 'Ajouté le',
        alreadyReserved: 'Déjà réservé',
        reserve: 'Réserver',
        lastName: 'Nom',
        firstName: 'Prénom',
        email: 'E-mail',
        phone: 'Numéro de téléphone',
        startDate: 'Date de début',
        endDate: 'Date de fin',
        sendRequest: 'Envoyer la demande',
        aiResults: 'Recommandations de l’assistant IA',
        ruleResults: 'Recommandations par règles de prévention',
        noRecommendation: 'Aucun équipement disponible ne correspond à ce besoin pour le moment.',
        reserveRecommended: 'Réserver'
    },
    en: {
        pageTitle: 'Equipment',
        pageDescription: 'Discover the equipment available to residents.',
        equipmentTitle: 'Available equipment',
        languageLabel: 'Language',
        reservationErrors: 'Please check the reservation details:',
        advisorTitle: 'What do you recommend?',
        advisorDescription: 'Describe your situation to receive recommendations based on the weather and available equipment.',
        needLabel: 'What do you need?',
        needPlaceholder: 'e.g. family of 5, water outage tomorrow, 42°C',
        advisorSubmit: 'Get a recommendation',
        advisorLoading: 'Analyzing…',
        advisorError: 'Unable to get a recommendation. Please try again.',
        translating: 'Translating…',
        translationError: 'Translation is unavailable. The original French text is kept.',
        translationLimit: 'Translation is limited to 50 pieces of equipment per page.',
        imageZoom: 'View full-size image',
        noImage: 'No image available',
        currency: 'TND',
        addedOn: 'Added on',
        alreadyReserved: 'Already reserved',
        reserve: 'Reserve',
        lastName: 'Last name',
        firstName: 'First name',
        email: 'Email',
        phone: 'Phone number',
        startDate: 'Start date',
        endDate: 'End date',
        sendRequest: 'Send request',
        aiResults: 'AI assistant recommendations',
        ruleResults: 'Prevention rule recommendations',
        noRecommendation: 'No available equipment matches this need right now.',
        reserveRecommended: 'Reserve'
    },
    ar: {
        pageTitle: 'المعدات',
        pageDescription: 'اكتشف المعدات المتاحة للسكان.',
        equipmentTitle: 'المعدات المتاحة',
        languageLabel: 'اللغة',
        reservationErrors: 'يرجى التحقق من معلومات الحجز:',
        advisorTitle: 'ما الذي تنصحونني به؟',
        advisorDescription: 'صف حالتك للحصول على توصيات تناسب الطقس والمعدات المتاحة.',
        needLabel: 'ما احتياجك؟',
        needPlaceholder: 'مثال: أسرة من 5 أشخاص، انقطاع المياه غدًا، 42 درجة مئوية',
        advisorSubmit: 'الحصول على توصية',
        advisorLoading: 'جارٍ التحليل…',
        advisorError: 'تعذر الحصول على توصية. يرجى المحاولة مرة أخرى.',
        translating: 'جارٍ الترجمة…',
        translationError: 'الترجمة غير متاحة. تم الاحتفاظ بالنص الفرنسي الأصلي.',
        translationLimit: 'الترجمة محدودة بـ 50 معدة في الصفحة.',
        imageZoom: 'عرض الصورة بالحجم الكامل',
        noImage: 'لا توجد صورة متاحة',
        currency: 'د.ت',
        addedOn: 'أُضيف في',
        alreadyReserved: 'محجوز بالفعل',
        reserve: 'احجز',
        lastName: 'اللقب',
        firstName: 'الاسم',
        email: 'البريد الإلكتروني',
        phone: 'رقم الهاتف',
        startDate: 'تاريخ البداية',
        endDate: 'تاريخ النهاية',
        sendRequest: 'إرسال الطلب',
        aiResults: 'توصيات المساعد الذكي',
        ruleResults: 'توصيات قواعد الوقاية',
        noRecommendation: 'لا توجد معدات متاحة تناسب هذا الاحتياج حاليًا.',
        reserveRecommended: 'احجز'
    },
    es: {
        pageTitle: 'Equipos',
        pageDescription: 'Descubra los equipos disponibles para los residentes.',
        equipmentTitle: 'Equipos disponibles',
        languageLabel: 'Idioma',
        reservationErrors: 'Revise los datos de la reserva:',
        advisorTitle: '¿Qué me recomienda?',
        advisorDescription: 'Describa su situación para recibir recomendaciones según el clima y los equipos disponibles.',
        needLabel: '¿Qué necesita?',
        needPlaceholder: 'Ej.: familia de 5 personas, corte de agua mañana, 42 °C',
        advisorSubmit: 'Obtener una recomendación',
        advisorLoading: 'Analizando…',
        advisorError: 'No se pudo obtener una recomendación. Inténtelo de nuevo.',
        translating: 'Traduciendo…',
        translationError: 'La traducción no está disponible. Se conserva el texto original en francés.',
        translationLimit: 'La traducción está limitada a 50 equipos por página.',
        imageZoom: 'Ver imagen ampliada',
        noImage: 'No hay ninguna imagen disponible',
        currency: 'TND',
        addedOn: 'Añadido el',
        alreadyReserved: 'Ya reservado',
        reserve: 'Reservar',
        lastName: 'Apellido',
        firstName: 'Nombre',
        email: 'Correo electrónico',
        phone: 'Número de teléfono',
        startDate: 'Fecha de inicio',
        endDate: 'Fecha de finalización',
        sendRequest: 'Enviar solicitud',
        aiResults: 'Recomendaciones del asistente de IA',
        ruleResults: 'Recomendaciones según reglas de prevención',
        noRecommendation: 'No hay equipos disponibles que se ajusten a esta necesidad.',
        reserveRecommended: 'Reservar'
    }
};

const equipementsRoot = document.getElementById('equipements-root');
const languageSelect = document.getElementById('equipements-language');
const languageStatus = document.getElementById('equipements-language-status');
const languageError = document.getElementById('equipements-language-error');
const advisorLanguage = document.getElementById('equipement-advisor-language');
const equipmentCards = Array.from(equipementsRoot.querySelectorAll('[data-equipement-id]'));
const originalEquipmentText = new Map(equipmentCards.map((card) => [
    card.dataset.equipementId,
    {
        nom: card.querySelector('[data-field="nom"]').dataset.original,
        type: card.querySelector('[data-field="type"]').dataset.original
    }
]));

function applyStaticLanguage(lang) {
    const dictionary = I18N[lang] || I18N.fr;
    equipementsRoot.querySelectorAll('[data-i18n]').forEach((element) => {
        const key = element.dataset.i18n;
        if (dictionary[key]) element.textContent = dictionary[key];
    });
    equipementsRoot.querySelectorAll('[data-i18n-placeholder]').forEach((element) => {
        const key = element.dataset.i18nPlaceholder;
        if (dictionary[key]) element.placeholder = dictionary[key];
    });
    equipementsRoot.querySelectorAll('[data-i18n-title]').forEach((element) => {
        const key = element.dataset.i18nTitle;
        if (dictionary[key]) element.title = dictionary[key];
    });
    equipementsRoot.querySelectorAll('[data-i18n-aria-label]').forEach((element) => {
        const key = element.dataset.i18nAriaLabel;
        if (dictionary[key]) element.setAttribute('aria-label', dictionary[key]);
    });
    equipementsRoot.lang = lang;
    if (lang === 'ar') {
        equipementsRoot.setAttribute('dir', 'rtl');
    } else {
        equipementsRoot.removeAttribute('dir');
    }
    advisorLanguage.value = lang;
}

function applyOriginalEquipmentText() {
    equipmentCards.forEach((card) => {
        const original = originalEquipmentText.get(card.dataset.equipementId);
        card.querySelector('[data-field="nom"]').textContent = original.nom;
        card.querySelector('[data-field="type"]').textContent = original.type;
        const image = card.querySelector('[data-equipement-image]');
        if (image) {
            image.alt = original.nom;
            image.title = I18N[languageSelect.value].imageZoom;
        }
        const link = card.querySelector('[data-image-link]');
        if (link) link.setAttribute('aria-label', `${I18N[languageSelect.value].imageZoom} : ${original.nom}`);
    });
}

function applyEquipmentTranslations(items) {
    equipmentCards.forEach((card) => {
        const original = originalEquipmentText.get(card.dataset.equipementId);
        const translated = items[card.dataset.equipementId] || {};
        const name = translated.nom || original.nom;
        card.querySelector('[data-field="nom"]').textContent = name;
        card.querySelector('[data-field="type"]').textContent = translated.type_equipement || original.type;
        const image = card.querySelector('[data-equipement-image]');
        if (image) {
            image.alt = name;
            image.title = I18N[languageSelect.value].imageZoom;
        }
        const link = card.querySelector('[data-image-link]');
        if (link) link.setAttribute('aria-label', `${I18N[languageSelect.value].imageZoom} : ${name}`);
    });
}

function persistLanguage(lang) {
    try {
        localStorage.setItem('equipements_lang', lang);
    } catch (error) {
        // The language remains usable for this page even when storage is disabled.
    }
}

function localizedDate(dateString, lang) {
    const date = new Date(`${dateString}T12:00:00`);
    const locale = { fr: 'fr-FR', en: 'en', ar: 'ar', es: 'es' }[lang] || 'fr-FR';
    return new Intl.DateTimeFormat(locale).format(date);
}

function applyEquipmentDates(lang) {
    equipementsRoot.querySelectorAll('[data-equipement-date]').forEach((date) => {
        date.textContent = localizedDate(date.dataset.date, lang);
    });
}

async function changeEquipmentLanguage(lang, persist = true) {
    const selectedLang = I18N[lang] ? lang : 'fr';
    languageSelect.value = selectedLang;
    applyStaticLanguage(selectedLang);
    applyEquipmentDates(selectedLang);
    applyOriginalEquipmentText();
    languageError.hidden = true;
    languageStatus.hidden = true;
    languageStatus.textContent = '';
    document.getElementById('equipement-advisor-results').replaceChildren();
    if (persist) persistLanguage(selectedLang);

    if (selectedLang === 'fr') return;

    if (equipmentCards.length > 50) {
        languageError.textContent = I18N[selectedLang].translationLimit;
        languageError.hidden = false;
        return;
    }

    languageSelect.disabled = true;
    languageStatus.textContent = I18N[selectedLang].translating;
    languageStatus.hidden = false;

    try {
        const params = new URLSearchParams({ lang: selectedLang });
        equipmentCards.forEach((card) => params.append('ids[]', card.dataset.equipementId));
        const response = await fetch(`{{ route('front.equipements.translations') }}?${params.toString()}`, {
            headers: { 'Accept': 'application/json' }
        });
        if (!response.ok) throw new Error('Translation request failed');
        const data = await response.json();
        applyEquipmentTranslations(data.items || {});
    } catch (error) {
        applyOriginalEquipmentText();
        languageError.textContent = I18N[selectedLang].translationError;
        languageError.hidden = false;
    } finally {
        languageSelect.disabled = false;
        languageStatus.hidden = true;
    }
}

languageSelect.addEventListener('change', () => changeEquipmentLanguage(languageSelect.value));

let savedLanguage = 'fr';
try {
    savedLanguage = localStorage.getItem('equipements_lang') || 'fr';
} catch (error) {
    savedLanguage = 'fr';
}
changeEquipmentLanguage(savedLanguage, false);

const advisorForm = document.getElementById('equipement-advisor-form');

advisorForm.addEventListener('submit', async (event) => {
    event.preventDefault();

    const submitButton = document.getElementById('equipement-advisor-submit');
    const errorBox = document.getElementById('equipement-advisor-error');
    const results = document.getElementById('equipement-advisor-results');
    const lang = languageSelect.value;

    errorBox.hidden = true;
    errorBox.textContent = '';
    results.replaceChildren();
    submitButton.disabled = true;
    languageSelect.disabled = true;
    submitButton.textContent = I18N[lang].advisorLoading;

    try {
        const formData = new FormData(advisorForm);
        formData.set('lang', lang);
        const response = await fetch(advisorForm.dataset.url, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': formData.get('_token'),
            },
            body: formData,
        });
        const data = await response.json();

        if (!response.ok) {
            throw new Error(I18N[lang].advisorError);
        }

        const heading = document.createElement('h4');
        heading.className = 'h6';
        heading.textContent = data.source === 'ia'
            ? I18N[lang].aiResults
            : I18N[lang].ruleResults;
        results.appendChild(heading);

        if (data.conseil) {
            const advice = document.createElement('p');
            advice.className = 'text-muted';
            advice.textContent = data.conseil;
            results.appendChild(advice);
        }

        if (!data.recommandations || data.recommandations.length === 0) {
            const empty = document.createElement('p');
            empty.className = 'mb-0';
            empty.textContent = I18N[lang].noRecommendation;
            results.appendChild(empty);
            return;
        }

        const row = document.createElement('div');
        row.className = 'row g-3';

        data.recommandations.forEach((item) => {
            const column = document.createElement('div');
            column.className = 'col-md-6 col-lg-4';

            const card = document.createElement('article');
            card.className = 'card h-100';
            const equipmentCard = document.getElementById(`equipement-card-${item.id}`);
            const equipmentName = equipmentCard?.querySelector('[data-field="nom"]')?.textContent || item.nom;
            const equipmentType = equipmentCard?.querySelector('[data-field="type"]')?.textContent || item.type_equipement;

            if (item.image) {
                const image = document.createElement('img');
                image.src = item.image;
                image.alt = equipmentName;
                image.className = 'card-img-top';
                image.style.height = '180px';
                image.style.objectFit = 'cover';
                card.appendChild(image);
            }

            const body = document.createElement('div');
            body.className = 'card-body';

            const name = document.createElement('h5');
            name.className = 'card-title h6';
            name.textContent = equipmentName;

            const type = document.createElement('p');
            type.className = 'card-text text-muted mb-1';
            type.textContent = equipmentType;

            const price = document.createElement('p');
            price.className = 'fw-bold';
            price.dir = 'ltr';
            price.textContent = `${Number(item.prix_louer).toFixed(2)} ${I18N[lang].currency}`;

            const reason = document.createElement('p');
            reason.className = 'card-text';
            reason.textContent = item.raison;

            const reserve = document.createElement('a');
            reserve.className = 'btn btn-primary';
            reserve.href = item.url;
            reserve.textContent = I18N[lang].reserveRecommended;
            reserve.dataset.equipementCard = `equipement-card-${item.id}`;

            body.append(name, type, price, reason, reserve);
            card.appendChild(body);
            column.appendChild(card);
            row.appendChild(column);
        });

        results.appendChild(row);
    } catch (error) {
        errorBox.textContent = error.message || I18N[lang].advisorError;
        errorBox.hidden = false;
    } finally {
        submitButton.disabled = false;
        languageSelect.disabled = false;
        submitButton.textContent = I18N[languageSelect.value].advisorSubmit;
    }
});

document.getElementById('equipement-advisor-results').addEventListener('click', (event) => {
    const link = event.target.closest('[data-equipement-card]');

    if (!link) {
        return;
    }

    const target = document.getElementById(link.dataset.equipementCard);

    if (target) {
        event.preventDefault();
        const reservationForm = target.querySelector('details');
        if (reservationForm) {
            reservationForm.open = true;
            reservationForm.scrollIntoView({ behavior: 'smooth', block: 'center' });
        } else {
            target.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        window.history.replaceState(null, '', link.href);
    }
});
</script>
@endsection
