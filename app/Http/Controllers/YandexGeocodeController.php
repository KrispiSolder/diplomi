<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * HTTP API Геокодера (geocode-maps.yandex.ru). Используется из браузера через same-origin fetch,
 * чтобы не зависеть от JSONP внутри ymaps.geocode (CORB в Chrome).
 * Пробует ключ HTTP-геокодера, затем JS API-ключ, если они различаются.
 */
class YandexGeocodeController extends Controller
{
    public function reverse(Request $request)
    {
        $validated = $request->validate([
            'lat' => 'required|numeric|between:40,85',
            'lon' => 'required|numeric|between:15,185',
        ]);

        $keys = $this->geocodeApiKeys();
        if ($keys === []) {
            return response()->json(['address' => null, 'error' => 'missing_api_key'], 503);
        }

        $lat = (float) $validated['lat'];
        $lon = (float) $validated['lon'];

        foreach ($keys as $key) {
            $address = $this->requestReverse($key, $lon, $lat, true);
            if ($address !== null && $address !== '') {
                return response()->json(['address' => $address]);
            }
            $address = $this->requestReverse($key, $lon, $lat, false);
            if ($address !== null && $address !== '') {
                return response()->json(['address' => $address]);
            }
        }

        return response()->json([
            'address' => null,
        ]);
    }

    public function forward(Request $request)
    {
        $validated = $request->validate([
            'q' => 'required|string|min:3|max:500',
        ]);

        $keys = $this->geocodeApiKeys();
        if ($keys === []) {
            return response()->json(['lat' => null, 'lon' => null, 'error' => 'missing_api_key'], 503);
        }

        foreach ($keys as $key) {
            try {
                $response = $this->geocodeGet([
                    'apikey' => $key,
                    'geocode' => $validated['q'],
                    'format' => 'json',
                    'results' => 1,
                    'lang' => 'ru_RU',
                ]);
            } catch (\Throwable $e) {
                Log::warning('yandex_forward_geocode_failed', ['message' => $e->getMessage()]);

                return response()->json(['lat' => null, 'lon' => null, 'error' => 'upstream'], 502);
            }

            if (! $response->successful()) {
                Log::warning('yandex_forward_geocode_http', [
                    'status' => $response->status(),
                    'body' => mb_substr($response->body(), 0, 400),
                ]);

                continue;
            }

            $pos = data_get($response->json(), 'response.GeoObjectCollection.featureMember.0.GeoObject.Point.pos');
            if (! is_string($pos) || trim($pos) === '') {
                continue;
            }

            $parts = preg_split('/\s+/', trim($pos));
            if (count($parts) < 2) {
                continue;
            }

            $lon = (float) $parts[0];
            $lat = (float) $parts[1];

            return response()->json(['lat' => $lat, 'lon' => $lon]);
        }

        return response()->json(['lat' => null, 'lon' => null]);
    }

    /**
     * @return list<string>
     */
    private function geocodeApiKeys(): array
    {
        $http = trim((string) config('services.yandex.maps_http_geocode_key'));
        $js = trim((string) config('services.yandex.maps_js_api_key'));
        $out = [];
        foreach ([$js, $http] as $k) {
            if ($k !== '' && ! in_array($k, $out, true)) {
                $out[] = $k;
            }
        }

        return $out;
    }

    private function requestReverse(string $key, float $lon, float $lat, bool $withKindHouse): ?string
    {
        $params = [
            'apikey' => $key,
            'geocode' => $lon.','.$lat,
            'format' => 'json',
            'results' => 10,
            'lang' => 'ru_RU',
        ];
        if ($withKindHouse) {
            $params['kind'] = 'house';
        }

        try {
            $response = $this->geocodeGet($params);
        } catch (\Throwable $e) {
            Log::warning('yandex_reverse_geocode_failed', ['message' => $e->getMessage()]);

            return null;
        }
        if (! $response->successful()) {
            Log::warning('yandex_reverse_geocode_http', [
                'status' => $response->status(),
                'body' => mb_substr($response->body(), 0, 400),
            ]);

            return null;
        }

        $address = $this->pickAddressFromGeocoderJson($response->json());

        return $address !== null && $address !== '' ? $address : null;
    }

    private function pickAddressFromGeocoderJson(?array $json): ?string
    {
        $members = data_get($json, 'response.GeoObjectCollection.featureMember');
        if (! is_array($members)) {
            return null;
        }
        if (! array_is_list($members)) {
            $members = array_values($members);
        }

        foreach ($members as $member) {
            $geo = $member['GeoObject'] ?? null;
            if (! is_array($geo)) {
                continue;
            }

            $text = data_get($geo, 'metaDataProperty.GeocoderMetaData.text');
            if (is_string($text) && $text !== '') {
                return $this->stripRussiaPrefix($text);
            }

            $formatted = data_get($geo, 'metaDataProperty.GeocoderMetaData.Address.formatted');
            if (is_string($formatted) && $formatted !== '') {
                return $this->stripRussiaPrefix($formatted);
            }

            $components = data_get($geo, 'metaDataProperty.GeocoderMetaData.Address.Components')
                ?? data_get($geo, 'metaDataProperty.GeocoderMetaData.Address.Component');
            if (is_array($components)) {
                if (! array_is_list($components)) {
                    $components = array_values($components);
                }
                $line = $this->formatComponentsLine($components);
                if ($line !== '') {
                    return $line;
                }
            }

            $nameOnly = data_get($geo, 'name');
            if (is_string($nameOnly) && $nameOnly !== '') {
                return $this->stripRussiaPrefix($nameOnly);
            }
        }

        return null;
    }

    /**
     * @param  array<int, array{kind?: string, name?: string}>  $components
     */
    private function formatComponentsLine(array $components): string
    {
        $byKind = [];
        foreach ($components as $c) {
            if (! is_array($c)) {
                continue;
            }
            $kind = $c['kind'] ?? null;
            $name = $c['name'] ?? null;
            if (is_string($kind) && is_string($name) && $name !== '') {
                $byKind[$kind] = $name;
            }
        }

        $city = $byKind['locality'] ?? $byKind['area'] ?? $byKind['district'] ?? $byKind['province'] ?? '';
        $street = $byKind['street'] ?? '';
        $house = $byKind['house'] ?? '';

        return implode(', ', array_filter([$city, $street, $house], fn ($s) => is_string($s) && $s !== ''));
    }

    private function stripRussiaPrefix(string $text): string
    {
        $t = trim($text);
        $t = preg_replace('/^Россия,\s*/iu', '', $t) ?? $t;
        $t = preg_replace('/^Russia,\s*/iu', '', $t) ?? $t;

        return trim($t);
    }

    /**
     * @return \Illuminate\Http\Client\Response
     */
    private function geocodeGet(array $query)
    {
        try {
            return $this->http(false)->get('https://geocode-maps.yandex.ru/1.x/', $query);
        } catch (\Throwable $e) {
            if (! $this->isSslCertificateFailure($e)) {
                throw $e;
            }
            Log::notice('yandex_geocode_ssl_retry_without_verify');

            return $this->http(true)->get('https://geocode-maps.yandex.ru/1.x/', $query);
        }
    }

    private function isSslCertificateFailure(\Throwable $e): bool
    {
        $m = $e->getMessage();

        return str_contains($m, 'cURL error 60') || str_contains($m, 'SSL certificate');
    }

    private function http(bool $disableSslVerify)
    {
        $opts = config('services.yandex.http_client_options', []);
        if (! is_array($opts)) {
            $opts = [];
        }
        if ($disableSslVerify) {
            $opts['verify'] = false;
        }

        return Http::withOptions($opts)->timeout(12);
    }
}
