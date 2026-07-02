<!-- ╔══════════════════════════════════════════════════════════════════════╗
     ║  SECTION: NEWS & INSIGHTS                                          ║
     ║  Featured + editorial list · gold hover accents                     ║
     ╚══════════════════════════════════════════════════════════════════════╝ -->
<section id="news" class="relative overflow-hidden bg-off py-16 md:py-24 px-6 md:px-10 lg:px-14">
    <div class="max-w-[1200px] mx-auto relative z-[2]">
        <?php
        $newsExcerpt = static function (?string $text, int $limit): string {
            $decoded = html_entity_decode((string) ($text ?? ''), ENT_QUOTES, 'UTF-8');
            $plain = trim(strip_tags($decoded));

            return character_limiter($plain, $limit, '...');
        };
        $fallbackNewsImage = base_url('assets/img/incubatees.webp');
        ?>

        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-baseline sm:justify-between gap-4 mb-10 md:mb-12 reveal">
            <div>
                <div class="flex items-center gap-2 mb-3">
                    <span class="block w-[18px] h-[1.5px] bg-navy"></span>
                    <span class="text-[.58rem] font-semibold tracking-[.2em] uppercase text-navy">Latest Updates</span>
                </div>
                <h2 class="font-display text-3xl md:text-[2.1rem] leading-[1.12] text-dark">News &amp; <em
                        class="italic text-gold">Insights</em></h2>
            </div>
            <a href="<?= site_url('news') ?>"
                class="text-[.6rem] font-semibold tracking-[.13em] uppercase text-dark no-underline border-b border-dark/40 pb-0.5 transition-colors duration-200 hover:text-gold hover:border-gold shrink-0">View
                All News →</a>
        </div>

        <?php if (! empty($latestPosts)): ?>
            <?php $featured = $latestPosts[0]; $rest = array_slice($latestPosts, 1); ?>

            <div class="grid grid-cols-1 lg:grid-cols-[1.3fr_1fr] gap-8 lg:gap-12 reveal-group">
                <!-- Featured (first post) -->
                <a href="<?= site_url('news/' . $featured['slug']) ?>"
                    class="rc group block no-underline">
                    <div class="aspect-[16/10] bg-[#e5e2dc] overflow-hidden">
                        <?php if (! empty($featured['imagePath'])): ?>
                            <?php if (is_file(FCPATH . $featured['imagePath'])): ?>
                                <?= responsiveUploadImg($featured['imagePath'], 'posts', $featured['title'], 'w-full h-full object-cover', false) ?>
                            <?php else: ?>
                                <img src="<?= esc($fallbackNewsImage) ?>" alt="<?= esc($featured['title']) ?>" width="400" height="267" class="w-full h-full object-cover"/>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="flex items-center justify-center w-full h-full text-[.55rem] font-semibold tracking-[.2em] uppercase text-dark/12">Image</span>
                        <?php endif; ?>
                    </div>
                    <div class="pt-5 pb-5 border-b-2 border-dark/[.06] transition-colors duration-300 group-hover:border-dark/20">
                        <span class="text-[.68rem] font-bold tracking-[.2em] uppercase text-navy/50 mb-2 block">
                            <?= $featured['publishedAt'] ? date('F j, Y', strtotime($featured['publishedAt'])) : esc(ucfirst($featured['category'])) ?>
                        </span>
                        <h3 class="font-display text-[1.2rem] md:text-[1.35rem] text-dark leading-snug mb-2"><?= esc($featured['title']) ?></h3>
                        <?php if (! empty($featured['shortDescription'])): ?>
                            <p class="text-[.8rem] font-light leading-[1.75] text-black"><?= esc($newsExcerpt($featured['shortDescription'], 150)) ?></p>
                        <?php endif; ?>
                    </div>
                </a>

                <!-- Remaining posts — editorial list -->
                <div class="flex flex-col">
                    <?php if (! empty($rest)): ?>
                        <?php foreach ($rest as $i => $post): ?>
                            <a href="<?= site_url('news/' . $post['slug']) ?>"
                                class="rc group flex gap-5 no-underline py-5 border-b border-dark/[.12] last:border-b-0">
                                <div class="flex-1 min-w-0 border-l-2 border-dark/[.04] group-hover:border-dark/20 pl-4 transition-colors duration-200">
                                    <span class="text-[.58rem] font-semibold tracking-[.2em] uppercase text-navy/40 mb-1.5 block">
                                        <?= $post['publishedAt'] ? date('M j, Y', strtotime($post['publishedAt'])) : esc(ucfirst($post['category'])) ?>
                                    </span>
                                    <h3 class="font-display text-[.95rem] text-dark leading-snug mb-1"><?= esc($post['title']) ?></h3>
                                    <?php if (! empty($post['shortDescription'])): ?>
                                        <p class="text-[.74rem] font-light leading-[1.65] text-black line-clamp-2"><?= esc($newsExcerpt($post['shortDescription'], 90)) ?></p>
                                    <?php endif; ?>
                                </div>
                                <div class="w-[100px] h-[78px] shrink-0 bg-[#e5e2dc] overflow-hidden">
                                    <?php if (! empty($post['imagePath'])): ?>
                                        <?php if (is_file(FCPATH . $post['imagePath'])): ?>
                                            <?= responsiveUploadImg($post['imagePath'], 'posts-thumb', $post['title'], 'w-full h-full object-cover', true) ?>
                                        <?php else: ?>
                                            <img src="<?= esc($fallbackNewsImage) ?>" alt="<?= esc($post['title']) ?>" width="100" height="67" class="w-full h-full object-cover"/>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="flex items-center justify-center w-full h-full text-[.45rem] font-semibold tracking-[.15em] uppercase text-dark/10">IMG</span>
                                    <?php endif; ?>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

        <?php else: ?>
            <!-- Fallback — no posts yet -->
            <div class="grid grid-cols-1 lg:grid-cols-[1.3fr_1fr] gap-8 lg:gap-12 reveal-group">
                <!-- Featured placeholder -->
                <div class="rc">
                    <div class="aspect-[16/10] bg-[#e5e2dc] flex items-center justify-center">
                        <span class="text-[.55rem] font-semibold tracking-[.2em] uppercase text-dark/12">Image</span>
                    </div>
                    <div class="pt-5 pb-5 border-b-2 border-dark/[.06]">
                        <span class="text-[.46rem] font-bold tracking-[.2em] uppercase text-navy/40 mb-2.5 block">Coming Soon</span>
                        <h3 class="font-display text-[1.2rem] md:text-[1.35rem] text-dark leading-snug mb-2">Stay Tuned for Updates</h3>
                        <p class="text-[.8rem] font-light leading-[1.75] text-black">News articles and insights will appear here once published through the admin panel.</p>
                    </div>
                </div>

                <!-- List placeholders -->
                <div class="flex flex-col">
                    <div class="rc flex gap-5 py-5 border-b border-dark/[.06]">
                        <div class="flex-1 min-w-0 border-l-2 border-dark/[.04] pl-4">
                            <span class="text-[.44rem] font-bold tracking-[.2em] uppercase text-navy/35 mb-1.5 block">Events</span>
                            <h3 class="font-display text-[.95rem] text-dark leading-snug mb-1">Upcoming Events</h3>
                            <p class="text-[.74rem] font-light leading-[1.65] text-black">Watch this space for workshops, seminars, and incubation updates.</p>
                        </div>
                        <div class="w-[100px] h-[78px] shrink-0 bg-[#e5e2dc] flex items-center justify-center">
                            <span class="text-[.45rem] font-semibold tracking-[.15em] uppercase text-dark/10">IMG</span>
                        </div>
                    </div>
                    <div class="rc flex gap-5 py-5 border-b border-dark/[.06]">
                        <div class="flex-1 min-w-0 border-l-2 border-dark/[.04] pl-4">
                            <span class="text-[.44rem] font-bold tracking-[.2em] uppercase text-navy/35 mb-1.5 block">Features</span>
                            <h3 class="font-display text-[.95rem] text-dark leading-snug mb-1">Feature Stories</h3>
                            <p class="text-[.74rem] font-light leading-[1.65] text-black">In-depth stories about our incubatees and the Bicol innovation ecosystem.</p>
                        </div>
                        <div class="w-[100px] h-[78px] shrink-0 bg-[#e5e2dc] flex items-center justify-center">
                            <span class="text-[.45rem] font-semibold tracking-[.15em] uppercase text-dark/10">IMG</span>
                        </div>
                    </div>
                    <div class="rc flex gap-5 py-5">
                        <div class="flex-1 min-w-0 border-l-2 border-dark/[.04] pl-4">
                            <span class="text-[.44rem] font-bold tracking-[.2em] uppercase text-navy/35 mb-1.5 block">Community</span>
                            <h3 class="font-display text-[.95rem] text-dark leading-snug mb-1">Community Highlights</h3>
                            <p class="text-[.74rem] font-light leading-[1.65] text-black">Milestones from founders, mentors, and partners across the ecosystem.</p>
                        </div>
                        <div class="w-[100px] h-[78px] shrink-0 bg-[#e5e2dc] flex items-center justify-center">
                            <span class="text-[.45rem] font-semibold tracking-[.15em] uppercase text-dark/10">IMG</span>
                        </div>
                    </div>
                    <div class="rc flex gap-5 py-5 border-t border-dark/[.06]">
                        <div class="flex-1 min-w-0 border-l-2 border-dark/[.04] pl-4">
                            <span class="text-[.44rem] font-bold tracking-[.2em] uppercase text-navy/35 mb-1.5 block">Updates</span>
                            <h3 class="font-display text-[.95rem] text-dark leading-snug mb-1">Startup Progress Notes</h3>
                            <p class="text-[.74rem] font-light leading-[1.65] text-black">More incubation milestones and partner activities will be posted here soon.</p>
                        </div>
                        <div class="w-[100px] h-[78px] shrink-0 bg-[#e5e2dc] flex items-center justify-center">
                            <span class="text-[.45rem] font-semibold tracking-[.15em] uppercase text-dark/10">IMG</span>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>
