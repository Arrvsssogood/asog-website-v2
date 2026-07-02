<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gmail API Refresh Token</title>
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            background: #f4f3f0;
            color: #102033;
            font-family: "DM Sans", Arial, sans-serif;
        }

        main {
            width: min(720px, calc(100vw - 32px));
            border: 1px solid rgba(16, 32, 51, .12);
            border-radius: 8px;
            background: #fff;
            padding: 28px;
            box-shadow: 0 24px 70px rgba(16, 32, 51, .12);
        }

        h1 {
            margin: 0 0 10px;
            font-size: 1.25rem;
        }

        p {
            margin: 0 0 18px;
            color: rgba(16, 32, 51, .68);
            line-height: 1.6;
        }

        code {
            display: block;
            overflow-wrap: anywhere;
            border: 1px solid rgba(3, 85, 140, .16);
            border-radius: 6px;
            background: #f8fafc;
            padding: 14px;
            color: #03558c;
            font-size: .9rem;
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <main>
        <h1>Gmail API refresh token generated</h1>
        <p>Copy this token into <strong>gmailApi.refreshToken</strong> in your environment file, then set <strong>gmailApi.setupEnabled = false</strong>. This page does not save the token for you.</p>
        <code><?= esc($refreshToken) ?></code>
    </main>
</body>
</html>
