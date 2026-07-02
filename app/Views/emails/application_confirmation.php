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
                            <h1 style="margin:0;font-size:22px;font-weight:700;color:#fff;letter-spacing:.02em">ASOG TBI
                            </h1>
                            <p
                                style="margin:6px 0 0;font-size:12px;color:rgba(255,255,255,.7);letter-spacing:.08em;text-transform:uppercase">
                                Application Confirmation
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
                                <?= ! empty($isUpdate)
                                    ? 'Thank you for updating your ASOG TBI Incubation Program application! Here is a copy of your updated responses for your records.'
                                    : 'Thank you for applying to the ASOG TBI Incubation Program! Here is a copy of your submitted responses for your records.' ?>
                            </p>

                            <!-- Responses table -->
                            <table width="100%" cellpadding="0" cellspacing="0"
                                style="margin-bottom:24px;border:1px solid #eceae6;border-radius:6px;overflow:hidden">
                                <tr>
                                    <td style="background:#fafaf9;padding:10px 16px;border-bottom:1px solid #eceae6">
                                        <span
                                            style="font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#94a3b8">Applicant
                                            Information</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:0">
                                        <table width="100%" cellpadding="0" cellspacing="0">
                                            <?= view('emails/_row', ['label' => 'Full Name', 'value' => $applicantName]) ?>
                                            <?= view('emails/_row', ['label' => 'Email Address', 'value' => $applicantEmail]) ?>
                                            <?= view('emails/_row', ['label' => 'Contact Number', 'value' => $contactNumber]) ?>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <table width="100%" cellpadding="0" cellspacing="0"
                                style="margin-bottom:24px;border:1px solid #eceae6;border-radius:6px;overflow:hidden">
                                <tr>
                                    <td style="background:#fafaf9;padding:10px 16px;border-bottom:1px solid #eceae6">
                                        <span
                                            style="font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#94a3b8">Startup
                                            Details</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:0">
                                        <table width="100%" cellpadding="0" cellspacing="0">
                                            <?= view('emails/_row', ['label' => 'Startup Name', 'value' => $startupName]) ?>
                                            <?= view('emails/_row', ['label' => 'Description', 'value' => $startupDescription]) ?>
                                            <?php if (! empty($mainRisk)): ?>
                                            <?= view('emails/_row', ['label' => 'Main Risk', 'value' => $mainRisk]) ?>
                                            <?php endif; ?>
                                            <?php if (! empty($shortTermGoals)): ?>
                                            <?= view('emails/_row', ['label' => 'Short-term Goals', 'value' => $shortTermGoals]) ?>
                                            <?php endif; ?>
                                            <?php if (! empty($videoPresentationLink)): ?>
                                            <?= view('emails/_row', ['label' => 'Video Presentation', 'value' => '<a href="' . esc($videoPresentationLink) . '" style="color:#03558C;text-decoration:none">' . esc($videoPresentationLink) . '</a>']) ?>
                                            <?php endif; ?>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <!-- Status note -->
                            <table cellpadding="0" cellspacing="0" style="margin:0 0 24px">
                                <tr>
                                    <td
                                        style="font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#64748b;padding-bottom:6px">
                                        Application Status
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <span
                                            style="display:inline-block;background:#f0f4ff;color:#3730a3;font-size:13px;font-weight:700;padding:6px 16px;border-radius:4px;letter-spacing:.04em">
                                            For Review
                                        </span>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:0 0 20px;font-size:14px;color:#64748b;line-height:1.6">
                                <?= ! empty($isUpdate)
                                    ? 'Your updated application is now back in the review queue. Our team will review it and get back to you. Please keep this email for your reference.'
                                    : 'Our team will review your application and get back to you. Please keep this email for your reference.' ?>
                            </p>

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
                                This is an automated confirmation from the ASOG TBI application portal.<br>
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
