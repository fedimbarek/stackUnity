<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>HeatAlert — @yield('title', 'Anticipez la canicule et les coupures')</title>

    <link href="https://fonts.googleapis.com/css?family=Nunito:400,600,700,800,900&display=swap" rel="stylesheet">
    <link href="{{ asset('vendor/fontawesome-free/css/all.min.css') }}" rel="stylesheet" type="text/css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        html { scroll-behavior: smooth; }
        body { font-family: 'Nunito', sans-serif; color: #2b2b2b; }

        :root {
            --heat-orange: #ff6b35;
            --heat-red: #e63946;
            --heat-navy: #14213d;
            --heat-navy-light: #1b2a4a;
        }

        /* ---- Navbar ---- */
        .front-nav {
            background: var(--heat-navy);
            padding: 14px 0;
        }
        .front-nav .navbar-brand { color: #fff; font-weight: 800; letter-spacing: 1px; font-size: 1.3rem; }
        .front-nav .navbar-brand i { color: var(--heat-orange); margin-right: 6px; }
        .front-nav .nav-link { color: rgba(255,255,255,0.85) !important; font-weight: 600; font-size: 0.9rem; margin: 0 6px; }
        .front-nav .nav-link:hover { color: var(--heat-orange) !important; }
        .btn-heat {
            background: linear-gradient(90deg, var(--heat-orange), var(--heat-red));
            color: #fff !important; border: none; font-weight: 700; border-radius: 30px;
            padding: 8px 22px; font-size: 0.85rem;
        }
        .btn-heat:hover { opacity: 0.9; color: #fff; }
        .btn-heat-outline {
            border: 1.5px solid rgba(255,255,255,0.6); color: #fff !important; border-radius: 30px;
            padding: 7px 20px; font-size: 0.85rem; font-weight: 600;
        }
        .btn-heat-outline:hover { background: rgba(255,255,255,0.1); }

        /* ---- Masthead ---- */
        .masthead {
            background: linear-gradient(135deg, var(--heat-navy) 0%, #3a1f52 45%, var(--heat-red) 85%, var(--heat-orange) 120%);
            min-height: 92vh; display: flex; align-items: center; justify-content: center;
            text-align: center; color: #fff; position: relative; overflow: hidden;
        }
        .masthead::before {
            content: ""; position: absolute; width: 500px; height: 500px; border-radius: 50%;
            background: rgba(255,255,255,0.05); top: -150px; right: -100px;
        }
        .masthead h1 { font-size: 3.2rem; font-weight: 900; letter-spacing: 2px; text-transform: uppercase; }
        .masthead p { font-size: 1.15rem; color: rgba(255,255,255,0.85); max-width: 640px; margin: 18px auto 34px; }

        /* ---- Stats ---- */
        .stats-bar { background: #fff; padding: 40px 0; margin-top: -1px; }
        .stat-item { text-align: center; }
        .stat-item .num { font-size: 2.2rem; font-weight: 800; color: var(--heat-red); }
        .stat-item .label { font-size: 0.85rem; color: #777; text-transform: uppercase; letter-spacing: 1px; }

        /* ---- About / dark sections ---- */
        .section-dark { background: var(--heat-navy-light); color: #fff; padding: 90px 0; }
        .section-dark h2 { font-weight: 800; margin-bottom: 22px; }
        .section-dark p { color: rgba(255,255,255,0.75); font-size: 1.02rem; }

        .section-light { padding: 90px 0; background: #fff; }
        .section-light h2 { font-weight: 800; text-align: center; margin-bottom: 50px; color: var(--heat-navy); }

        /* ---- Feature cards ---- */
        .feature-card {
            text-align: center; padding: 34px 20px; border-radius: 14px; height: 100%;
            border: 1px solid #eee; transition: transform .2s, box-shadow .2s;
        }
        .feature-card:hover { transform: translateY(-6px); box-shadow: 0 18px 40px rgba(0,0,0,0.08); }
        .feature-icon {
            width: 64px; height: 64px; border-radius: 50%; display: flex; align-items: center; justify-content: center;
            margin: 0 auto 18px; background: linear-gradient(135deg, var(--heat-orange), var(--heat-red)); color: #fff; font-size: 1.4rem;
        }
        .feature-card h5 { font-weight: 800; margin-bottom: 10px; }
        .feature-card p { color: #777; font-size: 0.92rem; }
        .badge-live { background: #2ecc71; color: #fff; font-size: 0.65rem; padding: 3px 8px; border-radius: 20px; margin-left: 6px; vertical-align: middle; }

        /* ---- Steps ---- */
        .step-num {
            width: 46px; height: 46px; border-radius: 50%; background: var(--heat-navy); color: var(--heat-orange);
            display: flex; align-items: center; justify-content: center; font-weight: 800; margin: 0 auto 14px; font-size: 1.1rem;
        }

        /* ---- CTA ---- */
        .cta-section {
            background: linear-gradient(90deg, var(--heat-red), var(--heat-orange));
            color: #fff; text-align: center; padding: 70px 0;
        }
        .cta-section h2 { font-weight: 800; margin-bottom: 10px; }

        /* ---- Contact cards ---- */
        .contact-card { background: #f8f9fc; border-radius: 14px; padding: 30px 20px; text-align: center; height: 100%; }
        .contact-card i { font-size: 1.5rem; color: var(--heat-red); margin-bottom: 10px; }

        /* ---- Footer ---- */
        .front-footer { background: var(--heat-navy); color: rgba(255,255,255,0.6); text-align: center; padding: 26px 0; font-size: 0.85rem; }
        .front-footer a { color: rgba(255,255,255,0.8); }
    </style>
</head>
<body>
    @include('layouts.partials.front-nav')

    @yield('content')

    @include('layouts.partials.front-footer')

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>