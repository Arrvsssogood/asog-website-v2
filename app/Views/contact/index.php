<!-- ╔══════════════════════════════════════════════════════════════════════╗
     ║  CONTACT PAGE — Matches landing page layout                          ║
     ║  Centered header · Big map left · Form right                         ║
     ╚══════════════════════════════════════════════════════════════════════╝ -->
<link rel="stylesheet" href="<?= base_url('assets/css/guidelinesModal.css') ?>">
<?php
    $contactEmail = 'asogtbi@cspc.edu.ph';
    $contactAddr  = 'Camarines Sur Polytechnic Colleges, San Miguel, Nabua, Camarines Sur, Philippines 4434';
    $fbUrl        = 'https://www.facebook.com/asogtbi';
    $igUrl        = 'https://www.instagram.com/asogtbi';
    $xUrl         = 'https://x.com/asogtbi';
    $threadsUrl   = 'https://www.threads.com/@asogtbi';
    $recaptcha = config('Recaptcha');
    $recaptchaEnabled = $recaptcha->enabled && $recaptcha->siteKey !== '';
?>
<section class="relative overflow-hidden bg-off py-16 md:py-20 px-6 md:px-10 lg:px-14">
    <div class="max-w-[1400px] mx-auto relative z-[2]">

        <!-- ═══════ CENTERED HEADER ═══════
        <div class="text-center mb-10 md:mb-14 reveal">
            <div class="flex items-center justify-center gap-2 mb-3">
                <span class="block w-[18px] h-[1.5px] bg-gold"></span>
                <span class="text-[.58rem] font-semibold tracking-[.2em] uppercase text-gold">Get In Touch</span>
                <span class="block w-[18px] h-[1.5px] bg-gold"></span>
            </div>
            <h2 class="font-display text-3xl md:text-[2.4rem] leading-[1.12] text-dark">
                Reach <em class="italic text-gold">our Team</em>
            </h2>
        </div> -->

        <!-- ═══════ MAP LEFT (wider) + FORM RIGHT ═══════ -->
        <div class="grid grid-cols-1 lg:grid-cols-[1.15fr_0.85fr] gap-8 lg:gap-12">

            <!-- LEFT — Map + Floating Card + Social -->
            <div class="reveal">
                <!-- Map container — maximised height -->
                <div class="relative rounded-lg overflow-hidden mb-5" style="height:420px;">
                    <iframe
                        title="Find ASOG Technology Business Incubator on Google Maps"
                        aria-label="Map showing the location of ASOG Technology Business Incubator in San Miguel, Nabua, Camarines Sur"
                        src="https://maps.google.com/maps?q=ASOG+Technology+Business+Incubator,+San+Miguel,+Nabua,+Camarines+Sur&t=&z=15&ie=UTF8&iwloc=&output=embed"
                        class="absolute inset-0 w-full h-full" style="border:0;" allowfullscreen="" loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade"></iframe>
                </div>
            </div>

            <!-- RIGHT — Contact Form -->
            <div class="reveal reveal-d2">
                <?php $contactErrors = session('errors') ?? []; ?>
                <form action="<?= site_url('contact/send') ?>" method="post" class="space-y-5" id="contactForm" novalidate
                    data-recaptcha-enabled="<?= $recaptchaEnabled ? '1' : '0' ?>"
                    data-recaptcha-site-key="<?= esc($recaptcha->siteKey) ?>"
                    data-recaptcha-action="contact_send">
                    <?= csrf_field() ?>
                    <input type="hidden" name="recaptchaToken" data-recaptcha-token value="">
                    <input type="hidden" name="recaptchaAction" value="contact_send">
                    <div>
                        <label
                            class="text-[.62rem] font-bold tracking-[.16em] uppercase text-dark/60 block mt-.5 mb-2">Name</label>
                        <input type="text" name="name"
                            class="w-full bg-white border border-dark/[.12] rounded-sm px-4 py-3 text-[.85rem] text-dark font-light outline-none transition-colors duration-200 focus:border-gold/50 focus:shadow-sm focus:shadow-gold/10 placeholder:text-dark/30"
                            placeholder="Your full name" required>
                            <span class="contact-field-error text-[.62rem] text-red-500 block mt-1<?= ! empty($contactErrors['name']) ? '' : ' hidden' ?>"><?= esc((string) ($contactErrors['name'] ?? '')) ?></span>
                    </div>
                    <div>
                        <label
                            class="text-[.62rem] font-bold tracking-[.16em] uppercase text-dark/60 block mb-2">Email</label>
                        <input type="email" name="email"
                            class="w-full bg-white border border-dark/[.12] rounded-sm px-4 py-3 text-[.85rem] text-dark font-light outline-none transition-colors duration-200 focus:border-gold/50 focus:shadow-sm focus:shadow-gold/10 placeholder:text-dark/30"
                            placeholder="your@email.com" required>
                            <span class="contact-field-error text-[.62rem] text-red-500 block mt-1<?= ! empty($contactErrors['email']) ? '' : ' hidden' ?>"><?= esc((string) ($contactErrors['email'] ?? '')) ?></span>
                    </div>
                    <div>
                        <label
                            class="text-[.62rem] font-bold tracking-[.16em] uppercase text-dark/60 block mb-2">Message</label>
                        <div class="w-full">
                            <div class="relative w-full bg-white border border-dark/[.12] rounded-sm overflow-hidden">
                            <textarea rows="5" name="message" data-contact-message
                                    class="guidelines-scroll w-full bg-transparent border-none px-4 py-3 pb-9 pr-20 text-[.85rem] text-dark font-light outline-none resize-none transition-colors duration-200 placeholder:text-dark/30"
                            placeholder="How can we help?" required></textarea>
                            </div>
                            <div class="mt-1 flex items-start gap-3 px-1 min-h-[1em]">
                                <span class="contact-field-error min-w-0 flex-1 text-[.62rem] text-red-500 leading-[1.3]<?= ! empty($contactErrors['message']) ? '' : ' hidden' ?>" data-contact-message-error><?= esc((string) ($contactErrors['message'] ?? '')) ?></span>
                                <span data-contact-counter class="ml-auto shrink-0" style="display:inline-block;color:rgba(2,13,24,.6);background:transparent;font-size:10px;line-height:1;font-weight:500;letter-spacing:.08em;white-space:nowrap;">0/5000</span>
                            </div>
                        </div>
                    </div>
                    <button type="submit"
                        class="font-body text-[.72rem] font-medium tracking-[.14em] uppercase text-white bg-sky border border-sky px-8 md:px-10 py-4 rounded-sm cursor-pointer no-underline transition-all duration-200 hover:bg-sky/80 hover:-translate-y-0.5">Send
                        Message</button>
                </form>
            </div>
        </div>
    </div>
</section>

<?php if ($recaptchaEnabled): ?>
    <script src="https://www.google.com/recaptcha/enterprise.js?render=<?= rawurlencode($recaptcha->siteKey) ?>"></script>
<?php endif; ?>
<script src="<?= base_url('assets/js/features/forms/contactForm.js') ?>?v=<?= filemtime(FCPATH . 'assets/js/features/forms/contactForm.js') ?>"></script>
