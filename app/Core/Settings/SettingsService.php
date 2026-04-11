<?php

namespace App\Core\Settings;

use Illuminate\Support\Facades\Cache;

class SettingsService
{
    protected static array $defaults = [
        'app_name' => 'Case Management System',
        'date_format' => 'Y-m-d',
        'time_format' => 'H:i',
        'items_per_page' => '15',
        'mail_mailer' => 'smtp',
        'smtp_host' => '',
        'smtp_port' => '587',
        'smtp_username' => '',
        'smtp_password' => '',
        'smtp_encryption' => 'tls',
        'smtp_from_address' => '',
        'smtp_from_name' => 'Case Management System',
        'upcoming_notifications_enabled' => '0',
        'upcoming_notifications_days' => '7',
        'upcoming_notifications_time' => '08:00',
    ];

    public function get(string $key, mixed $default = null): mixed
    {
        $value = Cache::remember("setting.{$key}", 3600, function () use ($key) {
            $row = Setting::find($key);
            return $row?->value;
        });
        return $value ?? $default ?? (self::$defaults[$key] ?? null);
    }

    public function set(string $key, string|int|bool|null $value): void
    {
        Setting::updateOrCreate(
            ['key' => $key],
            ['value' => $value === null ? null : (string) $value]
        );
        Cache::forget("setting.{$key}");
    }

    public function all(): array
    {
        $keys = array_keys(self::$defaults);
        $rows = Setting::whereIn('key', $keys)->pluck('value', 'key');
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $rows[$key] ?? self::$defaults[$key];
        }
        return $result;
    }

    public function update(array $data): void
    {
        foreach ($data as $key => $value) {
            if (array_key_exists($key, self::$defaults)) {
                $this->set($key, $value);
            }
        }
    }
}
