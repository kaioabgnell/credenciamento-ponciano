<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Acesso — Credenciamento Eventos Ponciano</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="shortcut icon" href="{{{ URL::to('images/favicon.png') }}}">
    <style>
        .login-bg-shapes {
            position: absolute;
            inset: 0;
            overflow: hidden;
            pointer-events: none;
        }

        .shape {
            position: absolute;
            border-radius: 50%;
            background: rgba(2, 143, 208, .1);
            animation: float 8s ease-in-out infinite;
        }

        .shape-1 {
            width: 300px;
            height: 300px;
            top: -80px;
            left: -80px;
            animation-delay: 0s;
        }

        .shape-2 {
            width: 200px;
            height: 200px;
            bottom: -60px;
            right: -60px;
            animation-delay: 2s;
        }

        .shape-3 {
            width: 150px;
            height: 150px;
            top: 40%;
            right: 10%;
            animation-delay: 4s;
        }

        @keyframes float {

            0%,
            100% {
                transform: translate(0, 0) scale(1);
            }

            50% {
                transform: translate(12px, -18px) scale(1.05);
            }
        }

        .login-card {
            animation: fade-up .5s ease;
        }

        @keyframes fade-up {
            from {
                opacity: 0;
                transform: translateY(24px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-input-wrap {
            position: relative;
        }

        .login-input-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 16px;
            color: var(--cinza-400);
            pointer-events: none;
        }

        .login-input-wrap .form-control {
            padding-left: 42px;
        }

        .btn-login {
            width: 100%;
            padding: 13px;
            background: linear-gradient(135deg, var(--azul-primario), var(--azul-escuro));
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 700;
            font-family: var(--font-body);
            cursor: pointer;
            transition: all .2s;
            box-shadow: 0 4px 16px rgba(2, 143, 208, .35);
        }

        .btn-login:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 24px rgba(2, 143, 208, .45);
        }

        .login-divider {
            text-align: center;
            font-size: 12px;
            color: var(--cinza-400);
            margin: 20px 0;
            position: relative;
        }

        .login-divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: var(--cinza-300);
        }

        .login-divider span {
            position: relative;
            background: #fff;
            padding: 0 12px;
        }

        .lembrar-wrap {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13.5px;
            color: var(--cinza-600);
            cursor: pointer;
        }

        .lembrar-wrap input {
            accent-color: var(--azul-primario);
        }

        .error-box {
            background: var(--vermelho-light);
            border: 1px solid #f5c2c7;
            border-radius: 8px;
            padding: 10px 14px;
            color: #9b1c1c;
            font-size: 13px;
            margin-bottom: 16px;
        }

        .login-footer {
            text-align: center;
            margin-top: 24px;
            font-size: 11.5px;
            color: rgba(255, 255, 255, .3);
        }
        .login-logo img{
          height: 100px;
          margin: 0 auto;
          margin-bottom: 20px;
        }
    </style>
</head>

<body>

    <div class="login-page">

        <div class="login-bg-shapes">
            <div class="shape shape-1"></div>
            <div class="shape shape-2"></div>
            <div class="shape shape-3"></div>
        </div>

        <div class="login-card">

            <div class="login-logo">
                <img class="logo-login" src="{{ url('images/logo-ponciano.png')}}" alt="Ponciano Credenciamento">
                <div class="login-title">Credenciamento</div>
                <div class="login-subtitle">Sistema de Gestão Operacional para Eventos</div>
            </div>

            {{-- Erros --}}
            @if ($errors->any())
                <div class="error-box">
                    ⚠ {{ $errors->first() }}
                </div>
            @endif

            <form action="{{ route('login.post') }}" method="POST">
                @csrf

                <div class="form-group">
                    <label class="form-label" for="email">E-mail</label>
                    <div class="login-input-wrap">
                        <span class="login-input-icon">@</span>
                        <input type="email" id="email" name="email"
                            class="form-control {{ $errors->has('email') ? 'is-invalid' : '' }}"
                            value="{{ old('email') }}" placeholder="seu@email.com.br" autofocus autocomplete="email">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="senha">Senha</label>
                    <div class="login-input-wrap">
                        <span class="login-input-icon">🔒</span>
                        <input type="password" id="senha" name="senha"
                            class="form-control {{ $errors->has('senha') ? 'is-invalid' : '' }}" placeholder="••••••••"
                            autocomplete="current-password">
                    </div>
                </div>

                <div class="d-flex justify-between align-center mb-16">
                    <label class="lembrar-wrap">
                        <input type="checkbox" name="lembrar" value="1">
                        Manter conectado
                    </label>
                </div>

                <button type="submit" class="btn-login">
                    Acessar o Sistema
                </button>

            </form>

            <div class="login-divider"><span>Credenciamento Eventos Ponciano</span></div>

            <div style="text-align:center; font-size:12px; color:var(--cinza-400);">
                Problemas de acesso? Entre em contato com o administrador.
            </div>

        </div>

    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
</body>

</html>
