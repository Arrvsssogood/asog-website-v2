<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Reset password — ASOG TBI</title>
    <link rel="icon" href="<?= base_url('favicon.ico') ?>" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= base_url('icon.png') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=DM+Serif+Display&display=swap"
        rel="stylesheet">
    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box
    }

    body {
        font-family: 'DM Sans', sans-serif;
        height: 100vh;
        display: flex;
        background: #f4f3f0;
        color: #1e293b
    }

    .brand {
        display: none
    }

    @media(min-width:900px) {
        .brand {
            display: flex;
            flex-direction: column;
            justify-content: center;
            width: 380px;
            flex-shrink: 0;
            background: #03558C;
            padding: 3rem;
            color: #fff
        }

        .brand h1 {
            font-family: 'DM Serif Display', serif;
            font-size: 1.7rem;
            line-height: 1.25;
            margin-bottom: .6rem
        }

        .brand p {
            font-size: .82rem;
            opacity: .7;
            line-height: 1.6
        }
    }

    .form-panel {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 2rem
    }

    .form-box {
        width: 100%;
        max-width: 340px
    }

    .form-box h2 {
        font-family: 'DM Serif Display', serif;
        font-size: 1.35rem;
        margin-bottom: .25rem
    }

    .form-box .sub {
        font-size: .78rem;
        color: #94a3b8;
        margin-bottom: 1.6rem
    }

    .field {
        margin-bottom: .9rem
    }

    .field label {
        display: block;
        font-size: .62rem;
        font-weight: 600;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: #94a3b8;
        margin-bottom: .3rem
    }

    .field input {
        width: 100%;
        font-family: inherit;
        font-size: .82rem;
        color: #1e293b;
        padding: .55rem .7rem;
        border: 1px solid #ddd;
        border-radius: .25rem;
        background: #fff;
        outline: none;
        transition: border .15s
    }

    .field input:focus {
        border-color: #03558C
    }

    .pass-wrap {
        position: relative;
    }

    .pass-wrap input {
        padding-right: 2.4rem;
    }

    .pass-toggle {
        position: absolute;
        right: .55rem;
        top: 50%;
        transform: translateY(-50%);
        width: 1.55rem;
        height: 1.55rem;
        border: none;
        background: transparent;
        color: #94a3b8;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        border-radius: .2rem;
        transition: color .15s, background .15s;
    }

    .pass-toggle:hover {
        color: #03558C;
        background: #f1f5f9;
    }

    .pass-toggle:focus-visible {
        outline: 2px solid #03558C;
        outline-offset: 1px;
    }

    .pass-toggle svg {
        width: 1rem;
        height: 1rem;
    }

    .pass-toggle .icon-eye-off {
        display: none;
    }

    .pass-toggle.is-visible .icon-eye {
        display: none;
    }

    .pass-toggle.is-visible .icon-eye-off {
        display: block;
    }

    .btn {
        width: 100%;
        font-family: inherit;
        font-size: .78rem;
        font-weight: 600;
        color: #fff;
        background: #F8AF21;
        border: none;
        border-radius: .25rem;
        padding: .6rem;
        cursor: pointer;
        margin-top: .4rem;
        transition: background .15s
    }

    .btn:hover {
        background: #e9a01b
    }

    .err {
        font-size: .72rem;
        color: #be123c;
        margin-bottom: .8rem
    }

    .back {
        display: block;
        text-align: center;
        margin-top: 1.2rem;
        font-size: .72rem;
        color: #94a3b8;
        text-decoration: none
    }

    .back:hover {
        color: #03558C
    }
    </style>
</head>

<body>

    <div class="brand">
        <?= responsiveStaticImg('assets/img/ASOG TBI/WebP/ASOG-TBI_full-colored_stacked-white', 'default', 'ASOG TBI', 'block w-[160px] mb-[1.4rem]', false) ?>
        <h1>ASOG Technology<br>Business Incubator</h1>
        <p>Content management system for the ASOG TBI website. Sign in to manage posts and site content.</p>
    </div>

    <div class="form-panel">
        <div class="form-box">
            <h2>Set new password</h2>
            <p class="sub">Choose a strong password for your account</p>

            <?php if (session()->getFlashdata('error')): ?>
            <div class="err"><?= esc(session()->getFlashdata('error')) ?></div>
            <?php endif; ?>

            <form action="<?= site_url('asog-admin/reset-password/' . $token) ?>" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="token" value="<?= esc($token) ?>">

                <div class="field">
                    <label for="password">New password</label>
                    <div class="pass-wrap">
                        <input type="password" id="password" name="password" required minlength="8">
                        <button type="button" class="pass-toggle" id="togglePassword"
                            aria-label="Show password" aria-controls="password" aria-pressed="false">
                            <svg class="icon-eye" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                            <svg class="icon-eye-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M17.94 17.94A10.94 10.94 0 0 1 12 19c-7 0-11-7-11-7a21.77 21.77 0 0 1 5.06-6.94"></path>
                                <path d="M9.9 4.24A10.94 10.94 0 0 1 12 4c7 0 11 7 11 7a21.77 21.77 0 0 1-2.16 3.19"></path>
                                <path d="M14.12 14.12a3 3 0 1 1-4.24-4.24"></path>
                                <path d="M1 1l22 22"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="field">
                    <label for="password_confirm">Confirm new password</label>
                    <div class="pass-wrap">
                        <input type="password" id="password_confirm" name="password_confirm" required minlength="8">
                        <button type="button" class="pass-toggle" id="toggleConfirm"
                            aria-label="Show password" aria-controls="password_confirm" aria-pressed="false">
                            <svg class="icon-eye" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                            <svg class="icon-eye-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M17.94 17.94A10.94 10.94 0 0 1 12 19c-7 0-11-7-11-7a21.77 21.77 0 0 1 5.06-6.94"></path>
                                <path d="M9.9 4.24A10.94 10.94 0 0 1 12 4c7 0 11 7 11 7a21.77 21.77 0 0 1-2.16 3.19"></path>
                                <path d="M14.12 14.12a3 3 0 1 1-4.24-4.24"></path>
                                <path d="M1 1l22 22"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn">Update password</button>
            </form>

            <a href="<?= site_url('asog-admin') ?>" class="back">&larr; Back to sign in</a>
        </div>
    </div>

    <script>
    (function() {
        function initToggle(id) {
            var toggle = document.getElementById(id);
            var input = toggle ? toggle.closest('.pass-wrap').querySelector('input') : null;
            if (!toggle || !input) return;

            toggle.addEventListener('click', function() {
                var show = input.type === 'password';
                input.type = show ? 'text' : 'password';
                toggle.classList.toggle('is-visible', show);
                toggle.setAttribute('aria-pressed', show ? 'true' : 'false');
                toggle.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
            });
        }

        initToggle('togglePassword');
        initToggle('toggleConfirm');
    })();
    </script>

</body>

</html>
