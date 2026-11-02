<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Contacts d'urgence — HeatAlert</title>
    <style>
        /* DejaVu Sans est fournie avec dompdf : elle gère les accents (é, à, ç…) */
        @page { margin: 90px 40px 60px 40px; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10.5px; color: #2b2b2b; }

        header { position: fixed; top: -70px; left: 0; right: 0; height: 55px;
                 border-bottom: 3px solid #ff6b35; }
        header .brand { font-size: 20px; font-weight: bold; color: #14213d; }
        header .brand span { color: #e63946; }
        header .meta { position: absolute; right: 0; top: 8px; text-align: right; font-size: 9px; color: #777; }

        footer { position: fixed; bottom: -40px; left: 0; right: 0; height: 25px;
                 font-size: 8.5px; color: #888; text-align: center; border-top: 1px solid #ddd; padding-top: 5px; }

        .pagenum:before { content: counter(page); }

        h1 { font-size: 16px; color: #14213d; margin: 0 0 4px 0; }
        .subtitle { color: #777; margin-bottom: 14px; }

        .national { width: 100%; border-collapse: separate; border-spacing: 6px; margin: 0 -6px 14px -6px; }
        .national td { border: 2px solid #ff6b35; border-radius: 8px; text-align: center; padding: 8px 4px; width: 25%; }
        .national .num { font-size: 20px; font-weight: bold; color: #e63946; }
        .national .lbl { font-size: 9px; color: #333; }

        .cat-title { font-size: 12.5px; font-weight: bold; color: #14213d; padding: 5px 8px;
                     background: #f4f5f9; border-left: 5px solid #999; margin: 14px 0 0 0; }
        table.list { width: 100%; border-collapse: collapse; page-break-inside: auto; }
        table.list tr { page-break-inside: avoid; }
        table.list th { background: #14213d; color: #fff; font-size: 9px; text-transform: uppercase;
                        text-align: left; padding: 5px 6px; }
        table.list td { padding: 5px 6px; border-bottom: 1px solid #e6e6e6; vertical-align: top; }
        table.list tr:nth-child(even) td { background: #fafafa; }
        .phone { font-weight: bold; color: #e63946; white-space: nowrap; font-size: 11px; }
        .badge { display: inline-block; padding: 1px 6px; border-radius: 8px; font-size: 8px; color: #fff; }
        .b-24 { background: #198754; }
        .b-off { background: #6c757d; }
        .b-nat { background: #e63946; }
        .muted { color: #888; }
        .warning { margin-top: 18px; padding: 8px 10px; background: #fff3ec; border: 1px solid #ffb38f;
                   border-radius: 6px; font-size: 9.5px; }
    </style>
</head>
<body>

<header>
    <div class="brand">Heat<span>Alert</span></div>
    <div style="font-size: 9px; color:#777;">Annuaire des contacts d'urgence</div>
    <div class="meta">
        Généré le {{ $generatedAt->format('d/m/Y à H:i') }}<br>
        {{ $total }} contact(s) · {{ $categories->count() }} catégorie(s)
    </div>
</header>

<footer>
    HeatAlert — En cas de danger immédiat : SAMU 190 · Protection civile 198 · Police 197
    · Page <span class="pagenum"></span>
</footer>

<main>
    <h1>Contacts d'urgence</h1>
    <div class="subtitle">Liste complète des numéros actifs dans l'application, classés par catégorie.</div>

    {{-- NUMÉROS NATIONAUX (4 par ligne) --}}
    @if ($priorityContacts->isNotEmpty())
        <table class="national">
            @foreach ($priorityContacts->chunk(4) as $row)
                <tr>
                    @foreach ($row as $p)
                        <td>
                            <div class="num">{{ $p->phone }}</div>
                            <div class="lbl">{{ $p->name }}</div>
                        </td>
                    @endforeach
                    @for ($i = $row->count(); $i < 4; $i++)
                        <td style="border: none;"></td>
                    @endfor
                </tr>
            @endforeach
        </table>
    @endif

    {{-- CONTACTS PAR CATÉGORIE --}}
    @foreach ($categories as $category)
        <div class="cat-title" style="border-left-color: {{ $category->color }};">
            {{ $category->name }} ({{ $category->contacts->count() }})
        </div>
        <table class="list">
            <thead>
                <tr>
                    <th style="width: 28%;">Nom</th>
                    <th style="width: 15%;">Téléphone</th>
                    <th style="width: 14%;">Ville</th>
                    <th style="width: 31%;">Adresse</th>
                    <th style="width: 12%;">Dispo.</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($category->contacts as $contact)
                    <tr>
                        <td>
                            {{ $contact->name }}
                            @if ($contact->is_priority) <span class="badge b-nat">National</span> @endif
                        </td>
                        <td class="phone">{{ $contact->phone }}</td>
                        <td>{{ $contact->city ?? '—' }}</td>
                        <td class="muted">{{ $contact->address ?? '—' }}</td>
                        <td>
                            @if ($contact->is_24h)
                                <span class="badge b-24">24h/24</span>
                            @else
                                <span class="badge b-off">Bureau</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endforeach

    <div class="warning">
        <strong>Conseil :</strong> imprimez cette page et gardez-la près de votre téléphone.
        En cas de coupure de courant prolongée, votre téléphone peut se décharger : une liste papier reste toujours accessible.
    </div>
</main>

</body>
</html>
