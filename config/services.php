<?php

$yandexHttpSslOptions = (static function (): array {
    $ca = env('YANDEX_HTTP_CAFILE') ?: env('YANDEX_OAUTH_CAFILE');
    if (is_string($ca) && $ca !== '') {
        $normalized = str_replace('\\', '/', $ca);
        if (is_file($normalized)) {
            return ['verify' => $normalized];
        }
    }
    if (! filter_var(env('YANDEX_HTTP_SSL_VERIFY', env('YANDEX_OAUTH_SSL_VERIFY', 'true')), FILTER_VALIDATE_BOOLEAN)) {
        return ['verify' => false];
    }

    return [];
})();

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'yandex' => [
        'client_id' => env('YANDEX_CLIENT_ID'),
        'client_secret' => env('YANDEX_CLIENT_SECRET'),
        'redirect' => env('YANDEX_REDIRECT_URI', 'http://localhost:8000/auth/yandex/callback'),
        /** Ключ для HTTP Геокодера на сервере (может совпадать с JS или быть отдельным «API Геокодера»). */
        'maps_http_geocode_key' => trim((string) (env('YANDEX_MAPS_HTTP_GEOCODE_KEY') ?: env('YANDEX_MAPS_API_KEY') ?: '')),
        /** Ключ для загрузки JS API карты в браузере (продукт «JavaScript API …»). Если пусто — берётся YANDEX_MAPS_API_KEY. */
        'maps_js_api_key' => trim((string) (env('YANDEX_MAPS_JS_API_KEY') ?: env('YANDEX_MAPS_API_KEY') ?: '')),
        /** @deprecated используйте maps_js_api_key; оставлено для обратной совместимости */
        'maps_api_key' => env('YANDEX_MAPS_API_KEY'),
        'scopes' => ['login:email', 'login:info'],
        'guzzle' => $yandexHttpSslOptions,
        'http_client_options' => $yandexHttpSslOptions,
    ],

    'yookassa' => [
        'shop_id' => env('YOOKASSA_SHOP_ID'),
        'secret_key' => env('YOOKASSA_SECRET_KEY'),
        'enabled' => filter_var(env('YOOKASSA_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
        /** Путь к cacert.pem (на Windows часто нужен тот же, что для YANDEX_OAUTH_CAFILE) */
        'cafile' => (static function (): ?string {
            $ca = env('YOOKASSA_CAFILE') ?: env('YANDEX_OAUTH_CAFILE');
            if (! is_string($ca) || $ca === '') {
                return null;
            }
            $normalized = str_replace('\\', '/', $ca);

            return is_file($normalized) ? $normalized : null;
        })(),
        'ssl_verify' => filter_var(env('YOOKASSA_SSL_VERIFY', env('YANDEX_HTTP_SSL_VERIFY', 'true')), FILTER_VALIDATE_BOOLEAN),
    ],

];
