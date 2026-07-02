<?php
/**
 * Landing homepage organization card (dark theme).
 *
 * @var array $member
 */
$photoUrl = org_photo_url($member['photoPath'] ?? '');
$name = $member['fullName'] ?? '';
$rolePrimary = trim((string) ($member['rolePrimary'] ?? ''));
$roleSecondary = trim((string) ($member['roleSecondary'] ?? ''));
$displayRole = $roleSecondary !== '' ? $roleSecondary : $rolePrimary;
?>
<div class="rc text-center">
    <?php if ($photoUrl !== ''): ?>
    <div class="mx-auto aspect-square rounded-lg mb-3 p-[2px]"
        style="width:165px;max-width:100%;background:linear-gradient(160deg, rgba(150,208,255,.7), rgba(3,85,140,.5));">
        <div class="w-full h-full rounded-[7px] overflow-hidden"
            style="background:linear-gradient(160deg, rgba(150,208,255,.2), rgba(3,85,140,.25));">
            <?php
            $photoPath = $member['photoPath'] ?? '';
            if (preg_match('#^https?://#i', $photoPath)) {
                $classAttr = ' class="w-full h-full object-contain object-center"';
                $lazyAttr = ($lazy ?? true) ? ' loading="lazy"' : '';
                echo '<img src="' . esc($photoUrl) . '" alt="' . esc($name) . '"' . $classAttr . $lazyAttr . '>';
            } else {
                echo responsiveStaticImg($photoPath, 'team-landing', $name, 'w-full h-full object-contain object-center', $lazy ?? true);
            }
            ?>
        </div>
    </div>
    <?php endif; ?>
    <h4 class="font-display text-[.95rem] md:text-[1.05rem] text-off leading-tight"><?= esc($name) ?></h4>
    <?php if ($displayRole !== ''): ?>
    <span class="text-[.62rem] md:text-[.68rem] font-medium tracking-[.12em] uppercase mt-1 block text-gold/90">
        <?= esc($displayRole) ?>
    </span>
    <?php endif; ?>
</div>
