<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Forgot password — ASOG TBI</title>
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

    .success {
        font-size: .72rem;
        color: #065f46;
        background: #dcfce7;
        padding: .6rem .7rem;
        border-radius: .25rem;
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
            <h2>Forgot password</h2>
            <p class="sub">Enter your email and we'll send you a reset link</p>

            <?php if (session()->getFlashdata('error')): ?>
            <div class="err"><?= esc(session()->getFlashdata('error')) ?></div>
            <?php endif; ?>

            <?php if (session()->getFlashdata('success')): ?>
            <div class="success"><?= esc(session()->getFlashdata('success')) ?></div>
            <?php endif; ?>

            <form action="<?= site_url('asog-admin/forgot-password') ?>" method="POST">
                <?= csrf_field() ?>

                <div class="field">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?= old('email') ?>" required autofocus>
                </div>

                <button type="submit" class="btn">Send reset link</button>
            </form>

            <a href="<?= site_url('asog-admin') ?>" class="back">&larr; Back to sign in</a>
        </div>
    </div>

</body>

</html>
