<!--
     ╔══════════════════════════════════════════════════════════════════════╗
     ║  ORGANIZATION — Full-page team listing with grouped sections         ║
     ╚══════════════════════════════════════════════════════════════════════╝
-->

<?php
    $splitFeatured = static function (array $members): array {
        $featured = [];
        $regular = [];
        foreach ($members as $member) {
            if (! empty($member['isFeatured'])) {
                $featured[] = $member;
            } else {
                $regular[] = $member;
            }
        }
        return [$featured, $regular];
    };
?>

<?php if (! empty($coreTeamMembers)): ?>
<?php [$coreFeatured, $coreRegular] = $splitFeatured($coreTeamMembers); ?>
<!-- ── THE CORE TEAM ── -->
<section id="core-team" class="relative bg-off py-12 md:py-16 px-6 md:px-10 lg:px-14">
    <div class="ai-grid"></div>
    <div class="ai-grid-fade"></div>

    <div class="max-w-[1100px] mx-auto relative z-[2]">
        <div class="text-center mb-8 md:mb-10 reveal">
            <div class="flex items-center justify-center gap-2 mb-4">
                <span class="block w-[18px] h-[1.5px] bg-gold"></span>
                <span class="text-[.55rem] font-semibold tracking-[.2em] uppercase text-gold">Leadership</span>
                <span class="block w-[18px] h-[1.5px] bg-gold"></span>
            </div>
            <h2 class="font-display text-[1.8rem] md:text-[2.4rem] leading-[1.15] text-dark">The Core Team</h2>
            <p class="text-[.88rem] font-light leading-[1.8] text-black mt-3 max-w-[520px] mx-auto">The leadership
                guiding ASOG TBI's mission of empowering startups through AI and engineering innovation.</p>
        </div>

        <div class="reveal-group max-w-[980px] mx-auto flex flex-col items-center gap-6 md:gap-8">
            <?php foreach ($coreFeatured as $member): ?>
                <?= view('organization/_photo_card', [
                    'member' => $member,
                    'width' => '220px',
                    'maxWidth' => '280px',
                    'gradient' => 'linear-gradient(160deg, rgba(3,85,140,.58), rgba(3,85,140,.2))',
                    'primaryClass' => 'text-[.68rem] font-semibold tracking-[.08em] uppercase text-dark mt-1.5 block',
                    'lazy' => false,
                ]) ?>
            <?php endforeach; ?>

            <?php if (! empty($coreRegular)): ?>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5 md:gap-8 w-full justify-items-center">
                <?php foreach ($coreRegular as $member): ?>
                    <?= view('organization/_photo_card', [
                        'member' => $member,
                        'width' => '220px',
                        'maxWidth' => '280px',
                        'gradient' => 'linear-gradient(160deg, rgba(3,85,140,.58), rgba(3,85,140,.2))',
                        'primaryClass' => 'text-[.64rem] font-semibold tracking-[.1em] uppercase text-dark mt-1 block',
                        'secondaryClass' => 'text-[.68rem] font-semibold tracking-[.08em] uppercase text-dark/70 mt-1 block',
                    ]) ?>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if (! empty($tbiStaffMembers)): ?>
<?php [$staffFeatured, $staffRegular] = $splitFeatured($tbiStaffMembers); ?>
<!-- ── TBI STAFF ── -->
<section id="tbi-staff" class="relative bg-white py-12 md:py-16 px-6 md:px-10 lg:px-14">
    <div class="ai-grid"></div>
    <div class="ai-grid-fade"></div>

    <div class="max-w-[1100px] mx-auto relative z-[2]">
        <div class="text-center mb-8 md:mb-10 reveal">
            <div class="flex items-center justify-center gap-2 mb-4">
                <span class="block w-[18px] h-[1.5px] bg-navy"></span>
                <span class="text-[.55rem] font-semibold tracking-[.2em] uppercase text-navy">Operations</span>
                <span class="block w-[18px] h-[1.5px] bg-navy"></span>
            </div>
            <h2 class="font-display text-[1.8rem] md:text-[2.4rem] leading-[1.15] text-dark">TBI Staff</h2>
            <p class="text-[.88rem] font-light leading-[1.8] text-black mt-3 max-w-[520px] mx-auto">The dedicated staff
                running the day-to-day operations and supporting our incubatees.</p>
        </div>

        <div class="flex flex-col items-center gap-6 md:gap-8 reveal-group max-w-[800px] mx-auto">
            <?php foreach ($staffFeatured as $member): ?>
                <?= view('organization/_photo_card', [
                    'member' => $member,
                    'width' => '220px',
                    'maxWidth' => '320px',
                    'gradient' => 'linear-gradient(to top, rgba(236,155,64,.45), rgba(236,155,64,.12))',
                    'primaryClass' => 'text-[.64rem] font-semibold tracking-[.08em] uppercase text-navy mt-1.5 block',
                    'secondaryClass' => 'text-[.68rem] font-semibold tracking-[.08em] uppercase text-dark/70 mt-1 block',
                ]) ?>
            <?php endforeach; ?>

            <?php if (! empty($staffRegular)): ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 md:gap-6 w-full max-w-[640px] justify-items-center">
                <?php foreach ($staffRegular as $member): ?>
                    <?= view('organization/_photo_card', [
                        'member' => $member,
                        'width' => '220px',
                        'maxWidth' => '320px',
                        'gradient' => 'linear-gradient(to top, rgba(236,155,64,.45), rgba(236,155,64,.12))',
                        'primaryClass' => 'text-[.64rem] font-semibold tracking-[.08em] uppercase text-navy mt-1.5 block',
                        'secondaryClass' => 'text-[.68rem] font-semibold tracking-[.08em] uppercase text-dark/70 mt-1 block',
                    ]) ?>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if (! empty($showInternsSection) && ! empty($internMembers)): ?>
<!-- ── INTERNS ── -->
<section id="interns" class="relative bg-off py-12 md:py-16 px-6 md:px-10 lg:px-14">
    <div class="ai-grid"></div>
    <div class="ai-grid-fade"></div>

    <div class="max-w-[1100px] mx-auto relative z-[2]">
        <div class="text-center mb-8 md:mb-10 reveal">
            <div class="flex items-center justify-center gap-2 mb-4">
                <span class="block w-[18px] h-[1.5px] bg-sky"></span>
                <span class="text-[.55rem] font-semibold tracking-[.2em] uppercase text-sky">Support Team</span>
                <span class="block w-[18px] h-[1.5px] bg-sky"></span>
            </div>
            <h2 class="font-display text-[1.8rem] md:text-[2.4rem] leading-[1.15] text-dark">Interns</h2>
            <p class="text-[.88rem] font-light leading-[1.8] text-black mt-3 max-w-[520px] mx-auto">The interns
                supporting ongoing projects and helping the team deliver creative, technical, and operational work.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 md:gap-8 w-full max-w-[1020px] mx-auto justify-items-center reveal-group">
            <?php foreach ($internMembers as $member): ?>
                <?= view('organization/_photo_card', [
                    'member' => $member,
                    'width' => '220px',
                    'maxWidth' => '300px',
                    'gradient' => 'linear-gradient(160deg, rgba(150,208,255,.7), rgba(3,85,140,.5))',
                    'primaryClass' => 'text-[.62rem] font-semibold tracking-[.1em] uppercase text-sky mt-1.5 block',
                ]) ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if (! empty($mentorGroups)): ?>
<!-- ── MENTORS ── -->
<section id="mentors" class="relative bg-off py-12 md:py-16 px-6 md:px-10 lg:px-14">
    <div class="ai-grid"></div>
    <div class="ai-grid-fade"></div>

    <div class="max-w-[1100px] mx-auto relative z-[2]">
        <div class="text-center mb-8 md:mb-10 reveal">
            <div class="flex items-center justify-center gap-2 mb-4">
                <span class="block w-[18px] h-[1.5px] bg-sky"></span>
                <span class="text-[.55rem] font-semibold tracking-[.2em] uppercase text-sky">Experts</span>
                <span class="block w-[18px] h-[1.5px] bg-sky"></span>
            </div>
            <h2 class="font-display text-[1.8rem] md:text-[2.4rem] leading-[1.15] text-dark">Mentors</h2>
            <p class="text-[.88rem] font-light leading-[1.8] text-black mt-3 max-w-[520px] mx-auto">Mentors who provide
                domain expertise, industry connections, and ecosystem access to every incubation journey.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 md:gap-10 reveal-group justify-items-center">
            <?php foreach ($mentorGroups as $group): ?>
            <div class="rc w-full max-w-[340px] text-center mx-auto">
                <h3 class="font-display text-[1.05rem] font-semibold text-dark mb-4"><?= esc($group['category']) ?></h3>
                <ul class="space-y-2 text-[.92rem] text-dark/85 leading-[1.7] list-none p-0 m-0">
                    <?php foreach ($group['members'] as $mentor): ?>
                        <li><?= esc($mentor['fullName']) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>
