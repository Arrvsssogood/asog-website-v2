<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\LandingSettingModel;
use App\Models\OrganizationMemberModel;

class Organization extends BaseController
{
    public function index(): string
    {
        $settings = new LandingSettingModel();
        $memberModel = new OrganizationMemberModel();

        $coreTeam = $memberModel->getPublishedBySection(OrganizationMemberModel::SECTION_CORE_TEAM);
        $tbiStaff = $memberModel->getPublishedBySection(OrganizationMemberModel::SECTION_TBI_STAFF);
        $interns = $memberModel->getPublishedBySection(OrganizationMemberModel::SECTION_INTERN);
        $mentors = $memberModel->getPublishedBySection(OrganizationMemberModel::SECTION_MENTOR);
        helper('image');
        
        $settingModel = new LandingSettingModel();
        $raw = trim((string) $settingModel->getValue(LandingSettingModel::KEY_SHOW_INTERNS, '1'));

        $data = [
            'title'               => 'Organization - ASOG TBI',
            'heroSubtitle'        => 'Our People',
            'heroTitle'           => 'Organization',
            'heroDesc'            => 'The team behind ASOG TBI — leadership, staff, and mentors driving innovation forward.',
            'showInternsSection'  => trim((string) $settings->getValue(LandingSettingModel::KEY_SHOW_INTERNS, '1')) !== '0',
            'coreTeamMembers'     => $coreTeam,
            'tbiStaffMembers'     => $tbiStaff,
            'internMembers'       => $interns,
            'mentorGroups'        => $this->groupMentors($mentors),
        ];

        return view('templates/header', $data)
            . view('templates/page_hero', $data)
            . view('organization/list', $data)
            . view('templates/footer');
    }

    /**
     * @param list<array<string, mixed>> $mentors
     * @return list<array{category: string, members: list<array<string, mixed>>}>
     */
    private function groupMentors(array $mentors): array
    {
        $order = OrganizationMemberModel::MENTOR_CATEGORIES;

        $grouped = [];
        foreach ($mentors as $mentor) {
            $category = trim((string) ($mentor['mentorCategory'] ?? 'Other'));
            if ($category === '') {
                $category = 'Other';
            }
            $grouped[$category][] = $mentor;
        }

        foreach ($grouped as $category => $members) {
            usort($members, static function (array $a, array $b): int {
                $order = ((int) ($a['sortOrder'] ?? 0)) <=> ((int) ($b['sortOrder'] ?? 0));
                return $order !== 0 ? $order : ((int) ($a['id'] ?? 0)) <=> ((int) ($b['id'] ?? 0));
            });
            $grouped[$category] = $members;
        }

        $result = [];
        foreach ($order as $category) {
            if (! empty($grouped[$category])) {
                $result[] = ['category' => $category, 'members' => $grouped[$category]];
                unset($grouped[$category]);
            }
        }

        foreach ($grouped as $category => $members) {
            $result[] = ['category' => $category, 'members' => $members];
        }

        return $result;
    }
}
