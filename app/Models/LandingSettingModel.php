<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * LandingSettingModel stores key-value settings for landing page sections.
 */
class LandingSettingModel extends Model
{
    private const CACHE_TTL = 300;

    public const KEY_INCUBATEES_FILTER = 'landingIncubateesCohortFilter';
    public const KEY_GUESS_STARTUP_ENABLED = 'guessStartupEnabled';
    public const KEY_GUESS_STARTUP_VISIBLE = 'guessStartupVisible';
    public const KEY_SHOW_INTERNS = 'show_interns_section';
    public const KEY_APPLY_FAQ_TITLE = 'apply_faq_title';
    public const KEY_APPLY_FAQ_INTRO = 'apply_faq_intro';
    public const KEY_APPLY_ALLOW_DUPLICATE_EMAILS = 'apply_allow_duplicate_emails';
    public const KEY_APPLY_START_DATE = 'apply_start_date';
    public const KEY_APPLY_END_DATE = 'apply_end_date';
    public const KEY_APPLY_SHOW_DEADLINE = 'apply_show_deadline';
    public const KEY_APPLY_LEAN_CANVAS_TEMPLATE = 'apply_lean_canvas_template';
    public const KEY_LANDING_LOADER_ENABLED = 'landing_loader_enabled';

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
        $cache = service('cache');
        $cacheKey = $this->cacheKey($key);
        $cached = $cache->get($cacheKey);

        if (is_array($cached) && array_key_exists('value', $cached)) {
            return $cached['value'] !== null ? (string) $cached['value'] : $default;
        }

        $row = $this->where('settingKey', $key)->first();

        if (! is_array($row) || ! array_key_exists('settingValue', $row)) {
            $cache->save($cacheKey, ['value' => null], self::CACHE_TTL);
            return $default;
        }

        $value = $row['settingValue'] !== null ? (string) $row['settingValue'] : null;
        $cache->save($cacheKey, ['value' => $value], self::CACHE_TTL);

        return $value !== null ? $value : $default;
    }

    public function setValue(string $key, ?string $value): bool
    {
        $existing = $this->where('settingKey', $key)->first();

        if (is_array($existing) && isset($existing['id'])) {
            $saved = (bool) $this->update((int) $existing['id'], ['settingValue' => $value]);
            $this->clearValueCache($key);

            return $saved;
        }

        $saved = (bool) $this->insert([
            'settingKey'   => $key,
            'settingValue' => $value,
        ]);
        $this->clearValueCache($key);

        return $saved;
    }

    private function cacheKey(string $key): string
    {
        return 'asog_landing_setting_' . hash('sha256', $key);
    }

    private function clearValueCache(string $key): void
    {
        service('cache')->delete($this->cacheKey($key));
    }
}
