<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class DomainCheckerService
{
    private const HTTP_TIMEOUT = 5;
    private const MAX_RETRIES = 2;
    private const VALID_STATUS_CODE = 200; // Нас интересуют оние 200 домены

    /**
     * Комплексная проверка домена (ОПТИМИЗИРОВАННАЯ)
     *
     * 1. Сначала HTTP проверка - если не 200, то домен мертвой
     * 2. При HTTP 200 - проверяем DNS
     * 3. Если DNS есть, то WHOIS для точного статуса
     */
    public function comprehensiveCheck(string $domain): array
    {
        $domain = $this->normalizeDomain($domain);

        try {
            // ШАГ 1: HTTP ПРОВЕРКА (ВАЖНО: Нас интересуют нАЛИЧНЫЕ домены 200)
            $httpCheck = $this->checkHttpStatus($domain);

            // Определяем домен как доступный только если HTTP 200
            if ($httpCheck['http_status'] !== self::VALID_STATUS_CODE) {
                // Сразу возвращаем - не тратим лимиты
                return [
                    'available' => false,
                    'http_status' => $httpCheck['http_status'],
                    'ip_address' => $httpCheck['ip_address'] ?? null,
                    'availability_data' => [
                        'status' => 'dead_or_occupied',
                        'reason' => 'HTTP status not 200: ' . $httpCheck['http_status'],
                    ]
                ];
            }

            // ШАГ 2: DNS ПРОВЕРКА
            $dnsRecords = $this->checkDnsRecords($domain);

            // ШАГ 3: WHOIS (ТОЛЬКО ЕСЛИ DNS существует)
            $whoisData = [];
            if (!empty($dnsRecords['nameservers'])) {
                $whoisData = $this->getWhoisInfo($domain);
            }

            return [
                'available' => true,  // HTTP 200 = домен занят и работает
                'http_status' => $httpCheck['http_status'],
                'ip_address' => $httpCheck['ip_address'] ?? null,
                'availability_data' => array_merge($dnsRecords, $whoisData, [
                    'status' => 'active',
                    'checked_at' => now()->toDateTimeString(),
                ])
            ];
        } catch (Exception $e) {
            Log::warning("Domain check error for {$domain}: " . $e->getMessage());

            return [
                'available' => false,
                'http_status' => 0,
                'availability_data' => [
                    'status' => 'error',
                    'reason' => $e->getMessage(),
                ]
            ];
        }
    }

    /**
     * HTTP проверка (только HTTP 200 = важны)
     */
    private function checkHttpStatus(string $domain): array
    {
        $result = [
            'http_status' => 0,
            'ip_address' => null,
        ];

        for ($i = 0; $i < self::MAX_RETRIES; $i++) {
            try {
                $response = Http::timeout(self::HTTP_TIMEOUT)
                    ->withoutRedirecting()
                    ->get("http://{$domain}");

                $result['http_status'] = $response->status();

                // Получаем IP адрес
                $ip = gethostbyname($domain);
                if ($ip && filter_var($ip, FILTER_VALIDATE_IP)) {
                    $result['ip_address'] = $ip;
                }

                // Иесли достали ответ, то выкодим
                break;
            } catch (Exception $e) {
                if ($i === self::MAX_RETRIES - 1) {
                    $result['http_status'] = 0; // Таймаут или ошибка
                }
                usleep(500000); // 0.5 sec delay before retry
            }
        }

        return $result;
    }

    /**
     * DNS проверка
     */
    private function checkDnsRecords(string $domain): array
    {
        $result = [
            'nameserver_1' => null,
            'nameserver_2' => null,
            'nameserver_3' => null,
            'has_dns' => false,
        ];

        try {
            // Получаем NS рекорды
            $records = dns_get_record($domain, DNS_NS);

            if (is_array($records) && count($records) > 0) {
                $result['has_dns'] = true;
                $nameservers = [];

                foreach ($records as $index => $record) {
                    if (isset($record['target'])) {
                        $nameservers[] = $record['target'];
                    }
                    if (count($nameservers) >= 3) break;
                }

                // Наполняем nameserver fields
                for ($i = 0; $i < count($nameservers); $i++) {
                    $result['nameserver_' . ($i + 1)] = $nameservers[$i];
                }
            }
        } catch (Exception $e) {
            Log::debug("DNS check error for {$domain}: " . $e->getMessage());
        }

        return $result;
    }

    /**
     * WHOIS информация (только если DNS есть)
     */
    private function getWhoisInfo(string $domain): array
    {
        $result = [
            'registrar' => null,
            'created_date' => null,
            'updated_date' => null,
            'expiration_date' => null,
        ];

        try {
            // Можно использовать бесплатные WHOIS серверы
            $whoisServers = [
                'com' => 'whois.verisign-grs.com',
                'net' => 'whois.verisign-grs.com',
                'org' => 'whois.pir.org',
                'info' => 'whois.afilias.net',
                'biz' => 'whois.neulevel.biz',
                'ru' => 'whois.tcinet.ru',
                'su' => 'whois.tcinet.ru',
            ];

            $tld = $this->getTld($domain);
            $whoisServer = $whoisServers[$tld] ?? 'whois.com';

            // Сработает только бесплатные серверы
            $whoisData = $this->queryWhoisServer($whoisServer, $domain);
            if ($whoisData) {
                $result = $this->parseWhoisResponse($whoisData, $tld);
            }
        } catch (Exception $e) {
            Log::debug("WHOIS check error for {$domain}: " . $e->getMessage());
        }

        return $result;
    }

    /**
     * Обращение к WHOIS серверу
     */
    private function queryWhoisServer(string $server, string $domain): ?string
    {
        try {
            $connection = fsockopen($server, 43, $errno, $errstr, 3);
            if (!$connection) {
                return null;
            }

            fwrite($connection, $domain . "\r\n");
            $response = '';

            while (!feof($connection)) {
                $response .= fgets($connection, 128);
            }
            fclose($connection);

            return $response ?: null;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Парсинг WHOIS респонса
     */
    private function parseWhoisResponse(string $response, string $tld): array
    {
        $result = [
            'registrar' => null,
            'created_date' => null,
            'updated_date' => null,
            'expiration_date' => null,
        ];

        // Обработка разных форматов WHOIS
        $patterns = [
            'registrar' => ['Registrar:', 'registrar:', 'Регистратор:'],
            'created_date' => ['Creation Date:', 'created:', 'Date de cr'],
            'updated_date' => ['Updated Date:', 'Last Updated:', 'Updated:'],
            'expiration_date' => ['Registry Expiry Time:', 'Expiry Date:', 'expiration date:'],
        ];

        foreach ($patterns as $key => $keywords) {
            foreach ($keywords as $keyword) {
                if (preg_match('/' . $keyword . '\s+(.+)$/im', $response, $matches)) {
                    $value = trim($matches[1]);
                    if ($key !== 'registrar') {
                        // Парсим даты
                        $timestamp = strtotime($value);
                        if ($timestamp) {
                            $result[$key] = date('Y-m-d H:i:s', $timestamp);
                        }
                    } else {
                        $result[$key] = substr($value, 0, 100); // Ограничиваем длину
                    }
                    break;
                }
            }
        }

        return $result;
    }

    /**
     * Получить TLD домена
     */
    private function getTld(string $domain): string
    {
        $parts = explode('.', $domain);
        return strtolower(end($parts));
    }

    /**
     * Нормализация домена
     */
    private function normalizeDomain(string $domain): string
    {
        return strtolower(preg_replace('/^(https?:\/\/)?(www\.)/', '', $domain));
    }
}
