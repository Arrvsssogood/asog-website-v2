<?php
$logoUrl = trim((string) env('emailBrand.logoUrl', ''));
$logoUrl = $logoUrl !== ''
    ? $logoUrl
    : base_url('assets/img/ASOG TBI/PNG/ASOG-TBI_full-colored_landscape.png');
$logoUrl = str_replace(' ', '%20', $logoUrl);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=DM+Serif+Display:ital@0;1&display=swap" rel="stylesheet">
</head>

<body style="margin:0;padding:0;background:#03558C;font-family:'DM Sans','Aptos','Segoe UI',Tahoma,sans-serif;color:#213142">
    <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background:#03558C;background-image:linear-gradient(135deg,#03558C 0%,#0A6EA4 54%,#43A7DB 100%);padding:34px 12px">
        <tr>
            <td align="center">
                <table width="660" cellpadding="0" cellspacing="0" role="presentation" style="width:100%;max-width:660px;background:#F8F6F2;border-radius:10px;overflow:hidden">
                    <tr>
                        <td style="padding:30px 32px 24px;text-align:center;background:#F8F6F2">
                            <img src="<?= esc($logoUrl) ?>" width="230" alt="ASOG Technology Business Incubator" style="display:block;width:230px;max-width:84%;height:auto;border:0;margin:0 auto 24px">
                            <h1 style="margin:0;font-family:'DM Serif Display',Georgia,serif;font-size:36px;line-height:1.08;font-weight:400;color:#082F49">
                                Reset Your Password
                            </h1>
                            <table cellpadding="0" cellspacing="0" role="presentation" align="center" style="margin:18px auto 0">
                                <tr>
                                    <td style="width:52px;height:3px;background:#F8AF21;font-size:0;line-height:0">&nbsp;</td>
                                    <td style="width:52px;height:3px;background:#43A7DB;font-size:0;line-height:0">&nbsp;</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0 32px 32px">
                            <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background:#FFFFFF;border:1px solid #E4DED2;border-radius:8px">
                                <tr>
                                    <td style="padding:24px">
                                        <p style="margin:0 0 18px;font-size:15px;line-height:1.75;color:#334155">
                                            Hi <strong style="color:#082F49"><?= esc($adminName) ?></strong>, a password reset was requested for your ASOG TBI admin account.
                                        </p>
                                        <p style="margin:0 0 22px;font-size:14px;color:#526577;line-height:1.7">
                                            The reset link expires in <strong style="color:#213142">1 hour</strong>. If you did not request this, you can ignore the email and your password will remain unchanged.
                                        </p>

                                        <table cellpadding="0" cellspacing="0" role="presentation" style="margin:0 0 22px">
                                            <tr>
                                                <td style="background:#03558C;border-radius:4px">
                                                    <a href="<?= esc($resetUrl) ?>" style="display:inline-block;padding:13px 20px;color:#FFFFFF;text-decoration:none;font-size:12px;font-weight:800;letter-spacing:.12em;text-transform:uppercase">
                                                        Reset Password
                                                    </a>
                                                </td>
                                            </tr>
                                        </table>

                                        <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background:#F8F6F2;border:1px solid #E4DED2;border-radius:7px">
                                            <tr>
                                                <td style="padding:16px 18px">
                                                    <p style="margin:0 0 8px;font-size:10px;font-weight:800;letter-spacing:.13em;text-transform:uppercase;color:#03558C">
                                                        Fallback Link
                                                    </p>
                                                    <p style="margin:0 0 10px;font-size:13px;color:#526577;line-height:1.65">
                                                        If the button does not open, copy and paste this URL into your browser.
                                                    </p>
                                                    <p style="margin:0;font-size:12px;color:#03558C;line-height:1.6;word-break:break-all">
                                                        <?= esc($resetUrl) ?>
                                                    </p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <?= view('emails/_footer') ?>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>
