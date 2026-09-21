<?php
// core/Ga4Client.php
// Minimal Google Analytics 4 Data API client — no Composer, just cURL + OpenSSL.
//
// Auth:   service-account JSON key → signed JWT → OAuth2 access token
//         (token cached ~50 min, reports cached 15 min via core/Cache.php).
// Config: settings keys `ga4_property_id` and `ga4_service_json` (the full
//         service-account JSON key file contents), editable in
//         Admin → Settings → Social & SEO → Google Analytics.
//
// Usage:
//   $ga4 = Ga4Client::fromSettings();
//   if ($ga4->isConfigured()) {
//       $overview = $ga4->fetchOverview(28);   // ['ok'=>true, ...] or ['ok'=>false,'error'=>...]
//   }

require_once __DIR__ . '/Cache.php';

class Ga4Client
{
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';
    private const API_BASE  = 'https://analyticsdata.googleapis.com/v1beta';
    private const SCOPE     = 'https://www.googleapis.com/auth/analytics.readonly';

    private string $propertyId = '';
    /** @var array<string,mixed>|null Parsed service-account JSON. */
    private ?array $service = null;
    private string $credHash = '';
    private string $configError = '';

    public function __construct(string $propertyId, string $serviceJson)
    {
        $this->propertyId = trim($propertyId);

        $json = trim($serviceJson);
        if ($json !== '') {
            // Accept either the raw JSON key file or base64-encoded JSON.
            if ($json[0] !== '{') {
                $decoded = base64_decode($json, true);
                if (is_string($decoded) && ltrim($decoded)[0] ?? false) {
                    $json = $decoded;
                }
            }
            $parsed = json_decode($json, true);
            if (is_array($parsed) && !empty($parsed['client_email']) && !empty($parsed['private_key'])) {
                $this->service  = $parsed;
                $this->credHash = md5($this->propertyId . '|' . $parsed['client_email'] . '|' . md5($parsed['private_key']));
            } else {
                $this->configError = 'The saved service-account JSON could not be parsed (expected a Google key file with client_email and private_key).';
            }
        }

        if (!function_exists('curl_init') || !function_exists('openssl_sign')) {
            $this->configError = 'The PHP cURL and OpenSSL extensions are required for Google Analytics reporting.';
        }
    }

    /** Build a client from the admin-configured settings keys. */
    public static function fromSettings(): self
    {
        require_once __DIR__ . '/../models/Settings.php';
        $settings = new Settings();
        return new self(
            (string)$settings->get('ga4_property_id', ''),
            (string)$settings->get('ga4_service_json', '')
        );
    }

    /** True when a report fetch can be attempted. */
    public function isConfigured(): bool
    {
        return $this->configError === ''
            && $this->propertyId !== ''
            && $this->service !== null;
    }

    /** Human-readable reason why isConfigured() is false (empty when configured). */
    public function configError(): string
    {
        return $this->configError;
    }

    /** Format a seconds value as "2m 34s". */
    public static function formatDuration(int $seconds): string
    {
        if ($seconds <= 0) return '0s';
        $m = intdiv($seconds, 60);
        $s = $seconds % 60;
        return $m > 0 ? "{$m}m {$s}s" : "{$s}s";
    }

    /**
     * Traffic overview for the last $days days, compared with the previous period.
     * Never throws — failures come back as ['ok' => false, 'error' => ...].
     * Successful payloads are cached for 15 minutes per credentials + window.
     */
    public function fetchOverview(int $days = 28): array
    {
        $days = max(1, min(90, $days));
        if (!$this->isConfigured()) {
            return ['ok' => false, 'error' => $this->configError !== '' ? $this->configError : 'Google Analytics is not configured.'];
        }
        $key = 'ga4.overview.' . $this->credHash . '.' . $days;
        try {
            return Cache::remember($key, 900, function () use ($days) {
                return $this->buildOverview($days);
            }, ['ga4']);
        } catch (Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    // ── report assembly ──────────────────────────────────────────

    private function buildOverview(int $days): array
    {
        $start   = date('Y-m-d', strtotime('-' . ($days - 1) . ' days'));
        $pStart  = date('Y-m-d', strtotime('-' . (2 * $days - 1) . ' days'));
        $pEnd    = date('Y-m-d', strtotime('-' . $days . ' days'));

        // 1. Totals for current + previous period in a single call.
        $totalsRes = $this->runReport([
            'dateRanges' => [
                ['startDate' => $start,  'endDate' => date('Y-m-d')],
                ['startDate' => $pStart, 'endDate' => $pEnd],
            ],
            'metrics' => [
                ['name' => 'totalUsers'],
                ['name' => 'newUsers'],
                ['name' => 'sessions'],
                ['name' => 'screenPageViews'],
                ['name' => 'engagedSessions'],
                ['name' => 'averageSessionDuration'],
            ],
            'limit' => 10,
        ]);
        [$cur, $prev] = $this->splitDateRanges($totalsRes, 6);

        // 2. Daily series (current period).
        $dailyRes = $this->runReport([
            'dateRanges' => [['startDate' => $start, 'endDate' => date('Y-m-d')]],
            'dimensions' => [['name' => 'date']],
            'metrics'    => [['name' => 'totalUsers'], ['name' => 'sessions'], ['name' => 'screenPageViews']],
            'orderBys'   => [['dimension' => ['dimensionName' => 'date']]],
            'limit'      => 100,
        ]);
        $daily = [];
        foreach ($dailyRes['rows'] ?? [] as $row) {
            $v = $this->metricFloats($row);
            $d = $row['dimensionValues'][0]['value'] ?? '';
            if (strlen($d) === 8) {
                $daily[] = [
                    'date'      => substr($d, 0, 4) . '-' . substr($d, 4, 2) . '-' . substr($d, 6, 2),
                    'visitors'  => (int)$v[0],
                    'sessions'  => (int)$v[1],
                    'pageviews' => (int)$v[2],
                ];
            }
        }

        // 3. Top pages (current period).
        $pagesRes = $this->runReport([
            'dateRanges' => [['startDate' => $start, 'endDate' => date('Y-m-d')]],
            'dimensions' => [['name' => 'pagePath']],
            'metrics'    => [['name' => 'screenPageViews'], ['name' => 'totalUsers']],
            'orderBys'   => [['metric' => ['metricName' => 'screenPageViews'], 'desc' => true]],
            'limit'      => 8,
        ]);
        $pages = [];
        foreach ($pagesRes['rows'] ?? [] as $row) {
            $v   = $this->metricFloats($row);
            $pages[] = [
                'path'     => $row['dimensionValues'][0]['value'] ?? '/',
                'views'    => (int)$v[0],
                'visitors' => (int)$v[1],
            ];
        }

        // 4. Traffic sources (current period).
        $srcRes = $this->runReport([
            'dateRanges' => [['startDate' => $start, 'endDate' => date('Y-m-d')]],
            'dimensions' => [['name' => 'sessionSource']],
            'metrics'    => [['name' => 'sessions'], ['name' => 'totalUsers']],
            'orderBys'   => [['metric' => ['metricName' => 'sessions'], 'desc' => true]],
            'limit'      => 6,
        ]);
        $sources = [];
        foreach ($srcRes['rows'] ?? [] as $row) {
            $v = $this->metricFloats($row);
            $src = trim((string)($row['dimensionValues'][0]['value'] ?? ''));
            if ($src === '' || $src === '(not set)') $src = '(direct / not set)';
            $sources[] = ['source' => $src, 'sessions' => (int)$v[0], 'visitors' => (int)$v[1]];
        }

        return [
            'ok'       => true,
            'days'     => $days,
            'totals'   => [
                'visitors'     => (int)$cur[0],
                'new_visitors' => (int)$cur[1],
                'sessions'     => (int)$cur[2],
                'pageviews'    => (int)$cur[3],
                'engaged'      => (int)$cur[4],
                'avg_duration' => (int)round($cur[5]),
            ],
            'prev'     => [
                'visitors'  => (int)$prev[0],
                'sessions'  => (int)$prev[2],
                'pageviews' => (int)$prev[3],
            ],
            'daily'    => $daily,
            'pages'    => $pages,
            'sources'  => $sources,
        ];
    }

    /**
     * A totals response with multiple dateRanges returns one row per range,
     * tagged by the implicit `dateRange` dimension. Returns [current, previous]
     * arrays of metric floats (defaulting to zeros for missing ranges).
     */
    private function splitDateRanges(array $res, int $metricCount): array
    {
        $cur = array_fill(0, $metricCount, 0.0);
        $prev = $cur;
        foreach ($res['rows'] ?? [] as $row) {
            $range = $row['dimensionValues'][0]['value'] ?? 'date_range_0';
            $vals  = $this->metricFloats($row);
            if ($range === 'date_range_1') $prev = $vals;
            else                           $cur  = $vals;
        }
        return [$cur, $prev];
    }

    private function metricFloats(array $row): array
    {
        return array_map(
            static fn($m) => (float)($m['value'] ?? 0),
            $row['metricValues'] ?? []
        );
    }

    // ── transport ────────────────────────────────────────────────

    private function runReport(array $body): array
    {
        $ch = curl_init(self::API_BASE . '/properties/' . urlencode($this->propertyId) . ':runReport');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $this->accessToken(),
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS     => json_encode($body),
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT        => 25,
        ]);
        $res = curl_exec($ch);
        if ($res === false) {
            $err = curl_error($ch);
            curl_close($ch);
            throw new RuntimeException('Could not reach Google Analytics: ' . $err);
        }
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $json = json_decode((string)$res, true);
        if ($code >= 400) {
            $msg = is_array($json) ? ($json['error']['message'] ?? '') : '';
            if ($code === 403 && stripos($msg, 'access') !== false) {
                $msg .= ' — grant the service account "Viewer" access to this GA4 property (Admin → Property Access Management).';
            }
            throw new RuntimeException($msg !== '' ? 'GA4: ' . $msg : 'GA4 API returned HTTP ' . $code);
        }
        return is_array($json) ? $json : [];
    }

    private function accessToken(): string
    {
        // Cache::remember memoizes per request and persists to disk; the JWT
        // flow itself is deterministic, so wrap it in a 50-minute cache entry.
        $key = 'ga4.token.' . $this->credHash;
        try {
            return Cache::remember($key, 3000, function () {
                return $this->requestAccessToken();
            }, ['ga4']);
        } catch (Throwable $e) {
            // Token failures must not be cached — rethrow for the caller.
            throw $e;
        }
    }

    private function requestAccessToken(): string
    {
        $jwt = $this->signJwt();
        $ch = curl_init(self::TOKEN_URL);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query([
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion'  => $jwt,
            ]),
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT        => 25,
        ]);
        $res = curl_exec($ch);
        if ($res === false) {
            $err = curl_error($ch);
            curl_close($ch);
            throw new RuntimeException('OAuth token request failed: ' . $err);
        }
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $json = json_decode((string)$res, true);
        if ($code >= 400 || empty($json['access_token'])) {
            $detail = $json['error_description'] ?? ($json['error'] ?? '');
            if ($code === 400 && stripos((string)$detail, 'invalid') !== false) {
                $detail .= ' — check that the service-account JSON key is valid and has not been revoked.';
            }
            throw new RuntimeException('Google rejected the service account credentials' . ($detail !== '' ? ': ' . $detail : '.'));
        }
        return (string)$json['access_token'];
    }

    private function signJwt(): string
    {
        $now    = time();
        $header = $this->b64(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $claims = $this->b64(json_encode([
            'iss'   => $this->service['client_email'],
            'scope' => self::SCOPE,
            'aud'   => self::TOKEN_URL,
            'iat'   => $now - 10,
            'exp'   => $now + 3500,
        ]));
        $input = $header . '.' . $claims;

        $pkey = openssl_pkey_get_private($this->service['private_key']);
        if ($pkey === false) {
            throw new RuntimeException('The stored service-account private key could not be read — re-paste the JSON key file.');
        }
        openssl_sign($input, $sig, $pkey, OPENSSL_ALGO_SHA256);
        if ($sig === false) {
            throw new RuntimeException('Failed to sign the Google auth request.');
        }
        return $input . '.' . $this->b64($sig);
    }

    private function b64(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
