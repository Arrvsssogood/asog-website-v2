<?php
/**
 * Photo member card for organization pages.
 *
 * @var array $member
 * @var string $width CSS width e.g. 220px
 * @var string $gradient Gradient border style
 * @var string $primaryClass CSS classes for primary role
 * @var string $secondaryClass CSS classes for secondary role
 */
$photoUrl = org_photo_url($member['photoPath'] ?? '');
$name = $member['fullName'] ?? '';
$rolePrimary = trim((string) ($member['rolePrimary'] ?? ''));
$roleSecondary = trim((string) ($member['roleSecondary'] ?? ''));
?>
<div class="rc text-center w-full mx-auto" style="max-width:<?= esc($maxWidth ?? '280px') ?>">
    <?php if ($photoUrl !== ''): ?>
    <div class="mx-auto aspect-square rounded-lg mb-4 p-[2px]"
        style="width:<?= esc($width ?? '220px') ?>;max-width:100%;background:<?= esc($gradient ?? 'linear-gradient(160deg, rgba(3,85,140,.58), rgba(3,85,140,.2))') ?>;">
        <div class="w-full h-full rounded-[7px] overflow-hidden">
            <?php
            $photoPath = $member['photoPath'] ?? '';
            if (preg_match('#^https?://#i', $photoPath)) {
                $classAttr = ' class="w-full h-full object-cover object-center"';
                $lazyAttr = ($lazy ?? true) ? ' loading="lazy"' : '';
                echo '<img src="' . esc($photoUrl) . '" alt="' . esc($name) . '"' . $classAttr . $lazyAttr . '>';
            } else {
                echo responsiveStaticImg($photoPath, 'team-org', $name, 'w-full h-full object-cover object-center', $lazy ?? true);
            }
            ?>
        </div>
    </div>
    <?php endif; ?>
    <h4 class="font-display text-[1.05rem] font-semibold text-dark leading-tight"><?= esc($name) ?></h4>
    <?php if ($rolePrimary !== ''): ?>
        <span class="<?= esc($primaryClass ?? 'text-[.68rem] font-semibold tracking-[.08em] uppercase text-dark mt-1.5 block') ?>"><?= esc($rolePrimary) ?></span>
    <?php endif; ?>
    <?php if ($roleSecondary !== ''): ?>
        <span class="<?= esc($secondaryClass ?? 'text-[.68rem] font-semibold tracking-[.08em] uppercase text-dark/70 mt-1 block') ?>"><?= esc($roleSecondary) ?></span>
    <?php endif; ?>
</div>
