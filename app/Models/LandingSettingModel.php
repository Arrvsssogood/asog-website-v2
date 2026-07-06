<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * LandingSettingModel stores key-value settings for landing page sections.
 */
class LandingSettingModel extends Model
{
    public const KEY_INCUBATEES_FILTER = 'landingIncubateesCohortFilter';
    public const KEY_GUESS_STARTUP_ENABLED = 'guessStartupEnabled';
    public const KEY_GUESS_STARTUP_VISIBLE = 'guessStartupVisible';
    public const KEY_SHOW_INTERNS = 'show_interns_section';
    public const KEY_APPLY_FAQ_TITLE = 'apply_faq_title';
    public const KEY_APPLY_FAQ_INTRO = 'apply_faq_intro';
    public const KEY_APPLY_ALLOW_DUPLICATE_EMAILS = 'apply_allow_duplicate_emails';
    public const KEY_APPLY_START_DATE = 'apply_start_date';
    public const KEY_APPLY_END_DATE = 'apply_end_date';

    protected $table            = 'landing_settings';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $useTimestamps    = true;
    protected $createdField     = 'createdAt';
    protected $updatedField     = 'updatedAt';

    protected $allowedFields = [
        'settingKey',
        'settingValue',
    ];

    public function getValue(string $key, ?string $default = null): ?string
    {
        $row = $this->where('settingKey', $key)->first();

        if (! is_array($row) || ! array_key_exists('settingValue', $row)) {
            return $default;
        }

        return $row['settingValue'] !== null ? (string) $row['settingValue'] : $default;
    }

    public function setValue(string $key, ?string $value): bool
    {
        $existing = $this->where('settingKey', $key)->first();

        if (is_array($existing) && isset($existing['id'])) {
            return (bool) $this->update((int) $existing['id'], ['settingValue' => $value]);
        }

        return (bool) $this->insert([
            'settingKey'   => $key,
            'settingValue' => $value,
        ]);
    }
}
