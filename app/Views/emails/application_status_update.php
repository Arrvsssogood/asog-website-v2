<?php
$logoUrl = trim((string) env('emailBrand.logoUrl', ''));
$logoUrl = $logoUrl !== ''
    ? $logoUrl
    : base_url('assets/img/ASOG TBI/PNG/ASOG-TBI_full-colored_landscape.png');
$logoUrl = str_replace(' ', '%20', $logoUrl);
$hasRevalidation = ! empty($revalidationUrl);
$title = $hasRevalidation ? 'Application Needs Updates' : 'Application Review Update';
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
                                <?= esc($title) ?>
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
                                        <table cellpadding="0" cellspacing="0" role="presentation" style="margin:0 0 18px">
                                            <tr>
                                                <td style="background:<?= esc($badgeBg) ?>;border-radius:999px;padding:7px 12px">
                                                    <span style="font-size:11px;font-weight:800;letter-spacing:.12em;text-transform:uppercase;color:<?= esc($badgeColor) ?>"><?= esc($statusLabel) ?></span>
                                                </td>
                                            </tr>
                                        </table>

                                        <p style="margin:0 0 18px;font-size:15px;line-height:1.75;color:#334155">
                                            Hi <strong style="color:#082F49"><?= esc($applicantName) ?></strong>, <?= esc(lcfirst($message)) ?>
                                        </p>

                                        <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background:#F8F6F2;border:1px solid #E4DED2;border-radius:7px">
                                            <tr>
                                                <td style="padding:18px 20px 4px">
                                                    <p style="margin:0 0 10px;font-family:'DM Serif Display',Georgia,serif;font-size:22px;line-height:1.2;color:#03558C">
                                                        Application
                                                    </p>
                                                    <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
                                                        <?= view('emails/_row', ['label' => 'Applicant', 'value' => esc($applicantName)]) ?>
                                                        <?= view('emails/_row', ['label' => 'Startup Name', 'value' => esc($startupName)]) ?>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <?php if (! empty($statusRemark)): ?>
                                <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin-top:18px;background:#FFFFFF;border:1px solid #E4DED2;border-radius:8px">
                                    <tr>
                                        <td style="padding:22px 24px">
                                            <p style="margin:0 0 10px;font-family:'DM Serif Display',Georgia,serif;font-size:22px;line-height:1.2;color:#03558C">
                                                Review note
                                            </p>
                                            <p style="margin:0;font-size:14px;color:#334155;line-height:1.75;white-space:pre-line">
                                                <?= esc($statusRemark) ?>
                                            </p>
                                        </td>
                                    </tr>
                                </table>
                            <?php endif; ?>

                            <?php if ($hasRevalidation): ?>
                                <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin-top:18px;background:#FFF6DF;border:1px solid #E9C66F;border-radius:8px">
                                    <tr>
                                        <td style="padding:22px 24px">
                                            <p style="margin:0 0 10px;font-family:'DM Serif Display',Georgia,serif;font-size:22px;line-height:1.2;color:#7C4A03">
                                                Update your application
                                            </p>
                                            <p style="margin:0 0 16px;font-size:14px;color:#3F3421;line-height:1.7">
                                                Use the private link below to update the same application. The link is valid for 14 days<?= ! empty($revalidationExpiresAt) ? ', until ' . esc(date('M j, Y g:i A', strtotime((string) $revalidationExpiresAt))) : '' ?>.
                                            </p>
                                            <table cellpadding="0" cellspacing="0" role="presentation">
                                                <tr>
                                                    <td style="background:#03558C;border-radius:4px">
                                                        <a href="<?= esc($revalidationUrl) ?>" style="display:inline-block;padding:12px 18px;color:#FFFFFF;text-decoration:none;font-size:12px;font-weight:800;letter-spacing:.12em;text-transform:uppercase">
                                                            Update Application
                                                        </a>
                                                    </td>
                                                </tr>
                                            </table>
                                            <p style="margin:14px 0 0;font-size:12px;color:#705B31;line-height:1.6;word-break:break-all">
                                                <?= esc($revalidationUrl) ?>
                                            </p>
                                        </td>
                                    </tr>
                                </table>
                            <?php endif; ?>

                            <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin-top:18px;background:#EEF7FC;border:1px solid #CAE6F3;border-radius:8px">
                                <tr>
                                    <td style="padding:18px 20px">
                                        <p style="margin:0;font-size:14px;color:#29445A;line-height:1.7">
                                            <?= esc($nextSteps) ?>
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:24px 0 0;font-size:14px;color:#526577;line-height:1.7">
                                Warm regards,<br>
                                <strong style="color:#082F49">ASOG TBI Team</strong>
                            </p>
                        </td>
                    </tr>
                    <?= view('emails/_footer') ?>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>
