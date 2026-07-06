<?php
$siteUrl = rtrim((string) env('emailBrand.siteUrl', 'https://asogtbi.com'), '/');
$socialLinks = [
    ['label' => 'Facebook', 'mark' => 'f', 'url' => (string) env('emailBrand.facebookUrl', 'https://www.facebook.com/asogtbi')],
    ['label' => 'Instagram', 'mark' => 'IG', 'url' => (string) env('emailBrand.instagramUrl', 'https://www.instagram.com/asogtbi')],
    ['label' => 'X', 'mark' => 'X', 'url' => (string) env('emailBrand.xUrl', 'https://x.com/asogtbi')],
    ['label' => 'Threads', 'mark' => '@', 'url' => (string) env('emailBrand.threadsUrl', 'https://www.threads.com/@asogtbi')],
];
?>
<tr>
    <td style="background:#03558C;padding:24px 28px;text-align:center">
        <table cellpadding="0" cellspacing="0" role="presentation" align="center" style="margin:0 auto 16px">
            <tr>
                <?php foreach ($socialLinks as $item): ?>
                    <td style="padding:0 5px">
                        <a href="<?= esc($item['url']) ?>" target="_blank" style="display:inline-block;width:34px;height:34px;line-height:34px;border-radius:17px;background:#F8AF21;color:#03558C;text-decoration:none;font-family:'DM Sans','Aptos','Segoe UI',Tahoma,sans-serif;font-size:11px;font-weight:800;text-align:center">
                            <?= esc($item['mark']) ?>
                        </a>
                    </td>
                <?php endforeach; ?>
            </tr>
        </table>
        <p style="margin:0 0 8px;font-family:'DM Sans','Aptos','Segoe UI',Tahoma,sans-serif;font-size:12px;line-height:1.6;color:#D8EBF5">
            ASOG Technology Business Incubator · Camarines Sur Polytechnic Colleges
        </p>
        <p style="margin:0;font-family:'DM Sans','Aptos','Segoe UI',Tahoma,sans-serif;font-size:11px;line-height:1.6;color:#BBD8E7">
            <a href="<?= esc($siteUrl) ?>" target="_blank" style="color:#FFFFFF;text-decoration:none;font-weight:700"><?= esc($siteUrl) ?></a>
            &nbsp;·&nbsp;
            <a href="mailto:asogtbi@cspc.edu.ph" style="color:#FFFFFF;text-decoration:none;font-weight:700">asogtbi@cspc.edu.ph</a>
        </p>
        <p style="margin:14px 0 0;font-family:'DM Sans','Aptos','Segoe UI',Tahoma,sans-serif;font-size:10px;line-height:1.6;color:#93C3DB">
            Automated message from the ASOG TBI website. Please do not reply directly to this email.
        </p>
    </td>
</tr>
