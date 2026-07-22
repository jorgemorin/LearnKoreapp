<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="@yield('description', 'LearnKoreapp — Aprende coreano con repetición espaciada y análisis morfológico con IA')">
    <meta name="theme-color" content="#7c6ef5">

    {{-- Open Graph --}}
    <meta property="og:type"        content="website">
    <meta property="og:site_name"   content="LearnKoreapp">
    <meta property="og:title"       content="@yield('title', 'LearnKoreapp') — Aprende Coreano">
    <meta property="og:description" content="@yield('description', 'Aprende coreano con repetición espaciada y análisis morfológico IA')">

    {{-- Favicon SVG (emoji coreano) --}}
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect width='100' height='100' rx='20' fill='%237c6ef5'/><text y='.9em' font-size='80' text-anchor='middle' x='50' fill='white'>한</text></svg>">

    <title>@yield('title', 'LearnKoreapp') — Aprende Coreano</title>

    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        /* ================================================================
           Sistema de diseño base — LearnKoreapp
           ================================================================ */
        :root {
            --color-bg:          #0f0f1a;
            --color-bg-card:     #1a1a2e;
            --color-bg-glass:    rgba(26, 26, 46, 0.8);
            --color-accent:      #7c6ef5;
            --color-accent-soft: #9b8ff8;
            --color-success:     #34d399;
            --color-danger:      #f87171;
            --color-warning:     #fbbf24;
            --color-text:        #e2e8f0;
            --color-text-muted:  #94a3b8;
            --color-border:      rgba(255,255,255,0.08);
            --radius:            12px;
            --radius-lg:         20px;
            --shadow:            0 4px 24px rgba(0,0,0,0.4);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--color-bg);
            color: var(--color-text);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Navbar */
        .navbar {
            background: var(--color-bg-glass);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--color-border);
            padding: 0 2rem;
            height: 64px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .navbar-brand {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--color-accent-soft);
            text-decoration: none;
            letter-spacing: -0.5px;
        }

        .navbar-brand span { color: var(--color-text); }

        .navbar-nav {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            list-style: none;
        }

        .navbar-nav a {
            color: var(--color-text-muted);
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            transition: color 0.2s;
        }

        .navbar-nav a:hover { color: var(--color-accent-soft); }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.5rem 1.2rem;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
            text-decoration: none;
        }

        .btn-primary {
            background: var(--color-accent);
            color: #fff;
        }

        .btn-primary:hover {
            background: var(--color-accent-soft);
            transform: translateY(-1px);
            box-shadow: 0 4px 16px rgba(124,110,245,0.4);
        }

        .btn-ghost {
            background: transparent;
            color: var(--color-text-muted);
            border: 1px solid var(--color-border);
        }

        .btn-ghost:hover {
            color: var(--color-text);
            border-color: var(--color-accent);
        }

        /* Main content */
        .main-container {
            flex: 1;
            max-width: 1200px;
            margin: 0 auto;
            width: 100%;
            padding: 2rem 1.5rem;
        }

        /* Cards */
        .card {
            background: var(--color-bg-card);
            border: 1px solid var(--color-border);
            border-radius: var(--radius-lg);
            padding: 1.75rem;
            box-shadow: var(--shadow);
        }

        /* Alerts de sesión */
        .alert {
            padding: 0.875rem 1.25rem;
            border-radius: var(--radius);
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }

        .alert-success { background: rgba(52,211,153,0.12); color: var(--color-success); border: 1px solid rgba(52,211,153,0.25); }
        .alert-danger  { background: rgba(248,113,113,0.12); color: var(--color-danger);  border: 1px solid rgba(248,113,113,0.25); }
        .alert-info    { background: rgba(124,110,245,0.12); color: var(--color-accent-soft); border: 1px solid rgba(124,110,245,0.25); }

        /* Footer */
        .footer {
            text-align: center;
            padding: 1.5rem;
            color: var(--color-text-muted);
            font-size: 0.8rem;
            border-top: 1px solid var(--color-border);
        }
    </style>

    @livewireStyles
    @stack('styles')
</head>
<body>
    {{-- Navbar --}}
    <nav class="navbar">
        <a href="{{ route('dashboard') }}" class="navbar-brand">Learn<span>Kore</span></a>
        <ul class="navbar-nav">
            @auth
                <li><a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">Inicio</a></li>
                <li><a href="{{ route('collection') }}" class="{{ request()->routeIs('collection') ? 'active' : '' }}">Mi Colección</a></li>
                <li><a href="{{ route('study') }}" class="{{ request()->routeIs('study') ? 'active' : '' }}">Repasar</a></li>
                <li><a href="{{ route('stats') }}" class="{{ request()->routeIs('stats') ? 'active' : '' }}">Estadísticas</a></li>
                @if(auth()->user()->isAdmin())
                    <li><a href="{{ route('admin.dashboard') }}" style="color: var(--color-warning)">Admin</a></li>
                @endif
                <li style="display:flex; align-items:center; gap:0.5rem;">
                    <a href="{{ route('profile.show') }}" style="
                        display:flex; align-items:center; gap:0.5rem;
                        text-decoration:none; color:var(--color-text-muted);
                        font-size:0.875rem; padding:0.3rem 0.6rem;
                        border-radius:8px; border:1px solid var(--color-border);
                        transition:all 0.2s;
                    " onmouseover="this.style.background='rgba(255,255,255,0.06)'"
                       onmouseout="this.style.background='transparent'">
                        <div style="
                            width:26px; height:26px; border-radius:50%;
                            background:var(--color-accent); color:#fff;
                            display:flex; align-items:center; justify-content:center;
                            font-size:0.75rem; font-weight:700; flex-shrink:0;
                        ">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div>
                        <span>{{ Str::limit(auth()->user()->name, 14) }}</span>
                    </a>
                    <form action="{{ route('logout') }}" method="POST" style="margin:0;">
                        @csrf
                        <button type="submit" title="Salir" style="
                            padding:0.3rem 0.6rem; background:transparent;
                            border:1px solid var(--color-border); border-radius:8px;
                            color:var(--color-text-muted); cursor:pointer; font-size:0.8rem;
                            font-family:inherit; transition:all 0.2s;
                        " onmouseover="this.style.color='var(--color-danger)'; this.style.borderColor='rgba(248,113,113,0.4)'"
                           onmouseout="this.style.color='var(--color-text-muted)'; this.style.borderColor='var(--color-border)'">↩</button>
                    </form>
                </li>
            @else
                <li><a href="{{ route('login') }}">Iniciar sesión</a></li>
                <li><a href="{{ route('register') }}" class="btn btn-primary">Registrarse</a></li>
            @endauth
        </ul>
    </nav>

    {{-- Alertas de sesión flash --}}
    <div class="main-container" style="padding-bottom: 0; margin-bottom: 0; max-width: 100%; padding: 0 1.5rem;">
        @if(session('success'))
            <div class="alert alert-success" style="margin-top: 1rem;">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger" style="margin-top: 1rem;">{{ session('error') }}</div>
        @endif
    </div>

    {{-- Contenido principal --}}
    <main class="main-container">
        @yield('content')
    </main>

    <footer class="footer">
        &copy; {{ date('Y') }} LearnKoreapp — Aprende coreano con IA y repetición espaciada
    </footer>

    @livewireScripts
    @stack('scripts')
</body>
</html>
