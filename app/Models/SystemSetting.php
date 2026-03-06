<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;

class SystemSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
    ];

    private const CACHE_KEY = 'system_settings.map.v1';

    public static function getValue(string $key, ?string $default = null): ?string
    {
        $map = static::getMap();

        return array_key_exists($key, $map) ? $map[$key] : $default;
    }

    public static function setValue(string $key, ?string $value): void
    {
        static::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );

        Cache::forget(self::CACHE_KEY);
    }

    public static function getMap(): array
    {
        try {
            if (! Schema::hasTable('system_settings')) {
                return [];
            }
        } catch (\Throwable) {
            return [];
        }

        return Cache::rememberForever(self::CACHE_KEY, static function (): array {
            return static::query()
                ->pluck('value', 'key')
                ->map(fn ($value) => $value !== null ? (string) $value : null)
                ->all();
        });
    }

    public static function applyRuntimeConfig(): void
    {
        $map = static::getMap();

        $configMap = [
            'asaas.base_url' => 'services.asaas.base_url',
            'asaas.api_key' => 'services.asaas.api_key',
            'asaas.webhook_token' => 'services.asaas.webhook_token',
            'apibrasil.base_url' => 'services.apibrasil.base_url',
            'apibrasil.token' => 'services.apibrasil.token',
            'apibrasil.token_header' => 'services.apibrasil.token_header',
            'apibrasil.token_prefix' => 'services.apibrasil.token_prefix',
            'apibrasil.cpf_path' => 'services.apibrasil.cpf_path',
            'apibrasil.cnpj_path' => 'services.apibrasil.cnpj_path',
            'apibrasil.cpf_method' => 'services.apibrasil.cpf_method',
            'apibrasil.cnpj_method' => 'services.apibrasil.cnpj_method',
            'zapi.instance' => 'zapi.instance',
            'zapi.token' => 'zapi.token',
            'zapi.client_token' => 'zapi.client_token',
            'cpfclean.whatsapp_number' => 'services.cpfclean.whatsapp_number',
        ];

        foreach ($configMap as $settingKey => $configKey) {
            if (array_key_exists($settingKey, $map) && $map[$settingKey] !== null && $map[$settingKey] !== '') {
                Config::set($configKey, $map[$settingKey]);
            }
        }
    }
}
