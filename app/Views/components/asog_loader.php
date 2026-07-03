<?php
$loaderBase = rtrim(base_url('assets/loader'), '/');
$logoUrl = base_url('assets/img/ASOG TBI/WebP/ASOG-TBI_full-colored_stacked.webp');
$importMap = [
    'imports' => [
        'three' => $loaderBase . '/vendor/three.module.min.js',
    ],
];
?>
<link rel="preload" as="image" href="<?= esc($logoUrl) ?>">
<link rel="stylesheet" href="<?= esc($loaderBase) ?>/css/asog-loader.css">

<div
    data-asog-loader-root
    data-loader-base="<?= esc($loaderBase) ?>"
    data-logo-url="<?= esc($logoUrl) ?>"
    aria-live="polite"
    aria-busy="true"
>
    <svg data-asog-loader-filters aria-hidden="true" focusable="false">
        <defs>
            <filter id="asog-loader-threshold">
                <feColorMatrix
                    in="SourceGraphic"
                    type="matrix"
                    values="1 0 0 0 0 0 1 0 0 0 0 0 1 0 0 0 0 0 255 -140"
                />
            </filter>
        </defs>
    </svg>
    <div data-asog-loader-word-morph aria-hidden="true">
        <span data-asog-loader-word-a></span>
        <span data-asog-loader-word-b></span>
    </div>
    <div data-asog-loader-stage aria-hidden="true"></div>
    <div data-asog-loader-shine aria-hidden="true"></div>
    <img
        data-asog-loader-logo
        src="<?= esc($logoUrl) ?>"
        alt="ASOG Technology Business Incubator"
        width="320"
        height="320"
        decoding="async"
    >
</div>

<script type="importmap"><?= json_encode($importMap, JSON_UNESCAPED_SLASHES) ?></script>
<script>
    (() => {
        const root = document.querySelector('[data-asog-loader-root]');
        if (!root) {
            return;
        }

        window.setTimeout(() => {
            if (!root.isConnected || root.dataset.loaderBooted === 'true' || root.dataset.state === 'complete') {
                return;
            }

            root.dataset.fallback = 'true';
            root.dataset.static = 'true';

            window.setTimeout(() => {
                if (!root.isConnected) {
                    return;
                }

                root.dataset.state = 'complete';
                root.setAttribute('aria-busy', 'false');

                window.setTimeout(() => {
                    root.remove();
                }, 900);
            }, 900);
        }, 5000);
    })();
</script>
<script type="module" src="<?= esc($loaderBase) ?>/js/main.js"></script>
