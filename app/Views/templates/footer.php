<!-- ╔══════════════════════════════════════════════════════════════════════╗
     ║  FOOTER — Classic columnar footer in navy / gold theme               ║
     ╚══════════════════════════════════════════════════════════════════════╝ -->
<?php if (empty($hideSiteFooter)): ?>
<link rel="stylesheet" href="<?= base_url('assets/css/footer.css') ?>">

<footer class="site-footer relative overflow-hidden">
    <div class="relative z-[1] max-w-[1400px] mx-auto px-6 md:px-10 lg:px-14">

        <!-- ── MAIN COLUMNS ────────────────────────────────────── -->
        <div class="ft-grid">

            <!-- COL 1: Brand -->
            <div class="ft-col ft-col-brand">
                <?= responsiveStaticImg('assets/img/ASOG TBI/WebP/asog logo variations_full-colored_landscape-light', 'footer-logo', 'ASOG TBI', 'ft-logo') ?>
                <p class="ft-tagline">
                    Empowering startups &amp; MSMEs in Bicol through engineering, AI, and food value chain innovation.
                </p>

                <!-- Social icons -->
                <div class="ft-social">
                    <a href="https://www.facebook.com/asogtbi" target="_blank" rel="noopener" class="ft-social-link" aria-label="Facebook">
                        <svg fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                    </a>
                    <a href="https://www.instagram.com/asogtbi" target="_blank" rel="noopener" class="ft-social-link" aria-label="Instagram">
                        <svg fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
                    </a>
                    <a href="https://x.com/asogtbi" target="_blank" rel="noopener" class="ft-social-link" aria-label="X / Twitter">
                        <svg fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                    </a>
                    <a href="https://www.threads.com/@asogtbi" target="_blank" rel="noopener" class="ft-social-link" aria-label="Threads">
                        <svg fill="currentColor" viewBox="0 0 24 24"><path d="M12.186 24h-.007c-3.581-.024-6.334-1.205-8.184-3.509C2.35 18.44 1.5 15.586 1.472 12.01v-.017c.03-3.579.879-6.43 2.525-8.482C5.845 1.205 8.6.024 12.18 0h.014c2.746.02 5.043.725 6.826 2.098 1.677 1.29 2.858 3.13 3.509 5.467l-2.04.569c-1.104-3.96-3.898-5.984-8.304-6.015-2.91.022-5.11.936-6.54 2.717C4.307 6.504 3.616 8.914 3.59 12c.025 3.086.718 5.496 2.057 7.164 1.43 1.783 3.631 2.698 6.54 2.717 2.623-.02 4.358-.631 5.8-2.045 1.647-1.613 1.618-3.593 1.09-4.798-.31-.71-.873-1.3-1.634-1.75-.192 1.352-.622 2.446-1.29 3.276-.776.965-1.872 1.599-3.263 1.885-1.254.258-2.47.148-3.43-.312-1.09-.522-1.815-1.396-2.04-2.46-.346-1.636.307-3.217 1.743-4.227 1.108-.78 2.558-1.168 4.312-1.156.888.006 1.739.096 2.547.27-.062-.784-.225-1.44-.493-1.96-.384-.743-1.009-1.189-1.857-1.327-1.237-.2-2.646.152-3.322.95l-1.54-1.317c1.046-1.232 2.97-1.793 4.746-1.505 1.34.217 2.412.914 3.1 2.015.53.85.838 1.89.935 3.117.516.17.993.394 1.424.674 1.206.783 2.073 1.933 2.51 3.332.587 1.885.382 4.532-1.694 6.565-1.842 1.804-4.15 2.619-7.268 2.643zM11.29 12.666c-1.16-.007-2.136.244-2.833.73-.787.55-1.066 1.29-.93 1.932.122.575.558 1.025 1.194 1.33.72.345 1.62.413 2.544.222 1.017-.21 1.8-.654 2.327-1.31.42-.524.725-1.203.893-2.03-.7-.267-1.497-.423-2.385-.475-.266-.02-.537-.025-.81-.025v-.374z"/></svg>
                    </a>
                </div>
            </div>

            <!-- COL 2: Quick Links -->
            <div class="ft-col">
                <h4 class="ft-heading">Quick Links</h4>
                <ul class="ft-links">
                    <li><a href="<?= site_url('about') ?>">About Us</a></li>
                    <li><a href="<?= site_url('programs') ?>">Programs &amp; Services</a></li>
                    <li><a href="<?= site_url('facilities') ?>">Facilities</a></li>
                    <li><a href="<?= site_url('incubatees') ?>">Incubatees</a></li>
                    <li><a href="<?= site_url('news') ?>">News &amp; Insights</a></li>
                </ul>
            </div>

            <!-- COL 3: Get Involved -->
            <div class="ft-col">
                <h4 class="ft-heading">Get Involved</h4>
                <ul class="ft-links">
                    <li><a href="<?= site_url('apply') ?>">Apply as Incubatee</a></li>
                    <li><a href="<?= site_url('organization') ?>">Organization</a></li>
                    <li><a href="<?= site_url('contact') ?>">Contact Us</a></li>
                </ul>
            </div>

            <!-- COL 4: Contact Info -->
            <div class="ft-col">
                <h4 class="ft-heading">Contact</h4>
                <ul class="ft-contact-list">
                    <li>
                        <svg class="ft-contact-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <span>Camarines Sur Polytechnic Colleges, San Miguel, Nabua, Camarines Sur 4434</span>
                    </li>
                    <li>
                        <svg class="ft-contact-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        <a href="mailto:asogtbi@cspc.edu.ph">asogtbi@cspc.edu.ph</a>
                    </li>
                </ul>
            </div>
        </div>

        <!-- ── BOTTOM BAR ──────────────────────────────────────── -->
        <div class="ft-bottom">
            <span class="ft-copyright">© <?= date('Y') ?> ASOG Technology Business Incubator · CSPC</span>
            <span class="ft-funded">Funded by DOST-PCIEERD</span>
        </div>
    </div>
</footer>
<?php endif; ?>

<button type="button" class="ft-return-top" id="returnToTop" aria-label="Return to top">
    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
        <path d="M12 5l-7 7 1.41 1.41L11 8.83V19h2V8.83l4.59 4.58L19 12z"></path>
    </svg>
</button>

<!-- Toast notifications -->
<?= function_exists('renderToast') ? renderToast() : '' ?>

<!-- Hero slideshow (landing page only) -->
<?php if (! empty($isLanding)): ?>
<script src="<?= base_url('assets/js/features/layout/hero.js') ?>" defer></script>
<?php endif; ?>

<!-- Scroll-reveal animation observer -->
<script src="<?= base_url('assets/js/features/layout/scroll-reveal.js') ?>" defer></script>

<!-- TOC scroll-spy (loaded only if sidebar exists on page) -->
<script src="<?= base_url('assets/js/features/layout/toc.js') ?>" defer></script>

<!-- Footer GSAP animations -->
<script src="<?= base_url('assets/js/features/layout/footer.js') ?>" defer></script>

</body>
</html>
