<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
</head>

<body style="margin:0;padding:0;background:#f4f3f0;font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif">
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f3f0;padding:32px 16px">
        <tr>
            <td align="center">
                <table width="580" cellpadding="0" cellspacing="0"
                    style="background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,.06)">

                    <!-- Header -->
                    <tr>
                        <td style="background:#03558C;padding:28px 32px;text-align:center">
                            <h1 style="margin:0;font-size:22px;font-weight:700;color:#fff;letter-spacing:.02em">ASOG TBI</h1>
                            <p style="margin:6px 0 0;font-size:12px;color:rgba(255,255,255,.7);letter-spacing:.08em;text-transform:uppercase">
                                Application Status Update
                            </p>
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td style="padding:32px 32px 24px">

                            <p style="margin:0 0 20px;font-size:15px;color:#1e293b;line-height:1.6">
                                Hi <strong><?= esc($applicantName) ?></strong>,
                            </p>

                            <p style="margin:0 0 24px;font-size:15px;color:#1e293b;line-height:1.6">
                                <?= esc($message) ?>
                            </p>

                            <!-- Application summary card -->
                            <table width="100%" cellpadding="0" cellspacing="0"
                                style="margin-bottom:24px;border:1px solid #eceae6;border-radius:6px;overflow:hidden">
                                <tr>
                                    <td style="background:#fafaf9;padding:10px 16px;border-bottom:1px solid #eceae6">
                                        <span style="font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#94a3b8">
                                            Your Application
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:0">
                                        <table width="100%" cellpadding="0" cellspacing="0">
                                            <?= view('emails/_row', ['label' => 'Applicant',    'value' => esc($applicantName)]) ?>
                                            <?= view('emails/_row', ['label' => 'Startup Name', 'value' => esc($startupName)]) ?>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <!-- Status badge -->
                            <table cellpadding="0" cellspacing="0" style="margin:0 0 24px">
                                <tr>
                                    <td style="font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#64748b;padding-bottom:8px">
                                        Application Status
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <span style="display:inline-block;background:<?= esc($badgeBg) ?>;color:<?= esc($badgeColor) ?>;font-size:13px;font-weight:700;padding:7px 18px;border-radius:4px;letter-spacing:.04em">
                                            <?= esc($statusLabel) ?>
                                        </span>
                                    </td>
                                </tr>
                            </table>

                            <?php if (! empty($statusRemark)): ?>
                            <table width="100%" cellpadding="0" cellspacing="0"
                                style="margin-bottom:24px;background:#fafaf9;border:1px solid #eceae6;border-radius:6px">
                                <tr>
                                    <td style="padding:14px 16px">
                                        <p style="margin:0 0 6px;font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#64748b">
                                            Remark From The Review Team
                                        </p>
                                        <p style="margin:0;font-size:14px;color:#334155;line-height:1.7;white-space:pre-line">
                                            <?= esc($statusRemark) ?>
                                        </p>
                                    </td>
                                </tr>
                            </table>
                            <?php endif; ?>

                            <?php if (! empty($revalidationUrl)): ?>
                            <table width="100%" cellpadding="0" cellspacing="0"
                                style="margin-bottom:24px;background:#fff7ed;border:1px solid #fed7aa;border-radius:6px">
                                <tr>
                                    <td style="padding:18px 16px;text-align:center">
                                        <p style="margin:0 0 14px;font-size:14px;color:#334155;line-height:1.6">
                                            Use the secure link below to update your existing application.
                                        </p>
                                        <a href="<?= esc($revalidationUrl) ?>"
                                            style="display:inline-block;background:#03558C;color:#fff;text-decoration:none;font-size:12px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;padding:11px 18px;border-radius:4px">
                                            Update Application
                                        </a>
                                        <p style="margin:12px 0 0;font-size:12px;color:#9a3412;line-height:1.5">
                                            This link is valid for 14 days<?= ! empty($revalidationExpiresAt) ? ', until ' . esc(date('M j, Y g:i A', strtotime((string) $revalidationExpiresAt))) : '' ?>.
                                        </p>
                                    </td>
                                </tr>
                            </table>
                            <?php endif; ?>

                            <!-- What happens next -->
                            <table width="100%" cellpadding="0" cellspacing="0"
                                style="margin-bottom:24px;background:#f8fbff;border-left:3px solid #03558C;border-radius:0 4px 4px 0">
                                <tr>
                                    <td style="padding:14px 16px">
                                        <p style="margin:0 0 6px;font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#03558C">
                                            What Happens Next
                                        </p>
                                        <p style="margin:0;font-size:14px;color:#334155;line-height:1.6">
                                            <?= esc($nextSteps) ?>
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:0;font-size:14px;color:#64748b;line-height:1.6">
                                Warm regards,<br>
                                <strong style="color:#1e293b">ASOG TBI Team</strong>
                            </p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background:#fafaf9;border-top:1px solid #eceae6;padding:20px 32px;text-align:center">
                            <p style="margin:0;font-size:11px;color:#94a3b8;line-height:1.5">
                                This is an automated notification from the ASOG TBI application portal.<br>
                                Please do not reply directly to this email.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>

</html>
