<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Acceso' }} — LearnKoreapp</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --color-bg:         #0f0f1a;
            --color-bg-card:    #1a1a2e;
            --color-accent:     #7c6ef5;
            --color-accent-soft:#9b8ff8;
            --color-success:    #34d399;
            --color-danger:     #f87171;
            --color-text:       #e2e8f0;
            --color-text-muted: #94a3b8;
            --color-border:     rgba(255,255,255,0.08);
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--color-bg);
            color: var(--color-text);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-image: radial-gradient(ellipse at 20% 50%, rgba(124,110,245,0.08) 0%, transparent 60%),
                              radial-gradient(ellipse at 80% 20%, rgba(155,143,248,0.05) 0%, transparent 50%);
        }

        .auth-container {
            width: 100%;
            max-width: 420px;
            padding: 1rem;
        }

        .auth-brand {
            text-align: center;
            margin-bottom: 2rem;
        }

        .auth-brand h1 {
            font-size: 2rem;
            font-weight: 700;
            color: var(--color-accent-soft);
            letter-spacing: -1px;
        }

        .auth-brand h1 span { color: var(--color-text); }

        .auth-brand p {
            color: var(--color-text-muted);
            font-size: 0.9rem;
            margin-top: 0.4rem;
        }

        .auth-card {
            background: var(--color-bg-card);
            border: 1px solid var(--color-border);
            border-radius: 20px;
            padding: 2.5rem;
            box-shadow: 0 8px 40px rgba(0,0,0,0.5);
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-group label {
            display: block;
            font-size: 0.85rem;
            font-weight: 500;
            color: var(--color-text-muted);
            margin-bottom: 0.4rem;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            background: rgba(255,255,255,0.04);
            border: 1px solid var(--color-border);
            border-radius: 10px;
            color: var(--color-text);
            font-size: 0.95rem;
            font-family: inherit;
            transition: border-color 0.2s, box-shadow 0.2s;
            outline: none;
        }

        .form-control:focus {
            border-color: var(--color-accent);
            box-shadow: 0 0 0 3px rgba(124,110,245,0.15);
        }

        .form-control.is-invalid { border-color: var(--color-danger); }

        .invalid-feedback {
            color: var(--color-danger);
            font-size: 0.8rem;
            margin-top: 0.3rem;
        }

        .btn-submit {
            width: 100%;
            padding: 0.875rem;
            background: var(--color-accent);
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            font-family: inherit;
            transition: all 0.2s;
            margin-top: 0.5rem;
        }

        .btn-submit:hover {
            background: var(--color-accent-soft);
            transform: translateY(-1px);
            box-shadow: 0 4px 20px rgba(124,110,245,0.4);
        }

        .btn-submit:active { transform: translateY(0); }

        .auth-footer {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.875rem;
            color: var(--color-text-muted);
        }

        .auth-footer a {
            color: var(--color-accent-soft);
            text-decoration: none;
            font-weight: 500;
        }

        .auth-footer a:hover { text-decoration: underline; }

        .alert {
            padding: 0.75rem 1rem;
            border-radius: 10px;
            margin-bottom: 1.25rem;
            font-size: 0.875rem;
        }

        .alert-danger {
            background: rgba(248,113,113,0.12);
            color: var(--color-danger);
            border: 1px solid rgba(248,113,113,0.25);
        }

        .alert-success {
            background: rgba(52,211,153,0.12);
            color: var(--color-success);
            border: 1px solid rgba(52,211,153,0.25);
        }
    </style>
    @livewireStyles
</head>
<body>
    <div class="auth-container">
        <div class="auth-brand">
            <h1>Learn<span>Kore</span></h1>
            <p>Aprende coreano con IA y repetición espaciada</p>
        </div>

        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        {{-- Slot de Livewire v4: el componente se inyecta aquí --}}
        {{ $slot }}
    </div>
    @livewireScripts
</body>
</html>
