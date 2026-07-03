<?php
$logoUrl = trim((string) env('emailBrand.logoUrl', ''));
$logoUrl = $logoUrl !== ''
    ? $logoUrl
    : base_url('assets/img/ASOG TBI/PNG/ASOG-TBI_full-colored_landscape.png');
$logoUrl = str_replace(' ', '%20', $logoUrl);

$isUpdated = ! empty($isUpdate);
$emailTitle = $isUpdated ? 'Updated Application Received' : 'Application Received';
$introCopy = $isUpdated
    ? 'Your updated application is back with the review team. The details below reflect the latest version we received.'
    : 'Your application is now with the ASOG TBI review team. The details below are included for your records.';
$closingCopy = $isUpdated
    ? 'We will review the updated information and reach out once there is a new decision or request.'
    : 'We will review your application and reach out once there is a new decision or request.';
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
                                <?= esc($emailTitle) ?>
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
                                    <td style="padding:24px 24px 6px">
                                        <p style="margin:0 0 18px;font-size:15px;line-height:1.75;color:#334155">
                                            Hi <strong style="color:#082F49"><?= esc($applicantName) ?></strong>, <?= esc(lcfirst($introCopy)) ?>
                                        </p>

                                        <table cellpadding="0" cellspacing="0" role="presentation" style="margin:0 0 20px">
                                            <tr>
                                                <td style="background:#FFF6DF;border:1px solid #E9C66F;border-radius:999px;padding:7px 12px">
                                                    <span style="font-size:11px;font-weight:800;letter-spacing:.12em;text-transform:uppercase;color:#7C4A03">For Review</span>
                                                </td>
                                            </tr>
                                        </table>

                                        <p style="margin:0 0 10px;font-family:'DM Serif Display',Georgia,serif;font-size:22px;line-height:1.2;color:#03558C">
                                            Applicant details
                                        </p>
                                        <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
                                            <?= view('emails/_row', ['label' => 'Full Name', 'value' => esc($applicantName)]) ?>
                                            <?= view('emails/_row', ['label' => 'Email Address', 'value' => esc($applicantEmail)]) ?>
                                            <?= view('emails/_row', ['label' => 'Contact Number', 'value' => esc($contactNumber)]) ?>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin-top:18px;background:#FFFFFF;border:1px solid #E4DED2;border-radius:8px">
                                <tr>
                                    <td style="padding:24px 24px 6px">
                                        <p style="margin:0 0 10px;font-family:'DM Serif Display',Georgia,serif;font-size:22px;line-height:1.2;color:#03558C">
                                            Startup snapshot
                                        </p>
                                        <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
                                            <?= view('emails/_row', ['label' => 'Startup Name', 'value' => esc($startupName)]) ?>
                                            <?= view('emails/_row', ['label' => 'Description', 'value' => esc($startupDescription)]) ?>
                                            <?php if (! empty($mainRisk)): ?>
                                                <?= view('emails/_row', ['label' => 'Main Risk', 'value' => esc($mainRisk)]) ?>
                                            <?php endif; ?>
                                            <?php if (! empty($shortTermGoals)): ?>
                                                <?= view('emails/_row', ['label' => 'Short-term Goals', 'value' => esc($shortTermGoals)]) ?>
                                            <?php endif; ?>
                                            <?php if (! empty($videoPresentationLink)): ?>
                                                <?= view('emails/_row', ['label' => 'Video Presentation', 'value' => '<a href="' . esc($videoPresentationLink) . '" style="color:#03558C;text-decoration:none;font-weight:800">' . esc($videoPresentationLink) . '</a>']) ?>
                                            <?php endif; ?>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin-top:18px;background:#EEF7FC;border:1px solid #CAE6F3;border-radius:8px">
                                <tr>
                                    <td style="padding:18px 20px">
                                        <p style="margin:0;font-size:14px;color:#29445A;line-height:1.7">
                                            <?= esc($closingCopy) ?> Keep this email as your copy of the submitted information.
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
