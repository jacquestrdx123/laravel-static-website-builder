<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class HostAfricaClient
{
    /**
     * @throws RuntimeException
     */
    public function lookup(string $searchTerm, array $options = []): array
    {
        return $this->request('POST', '/domains/lookup', $this->lookupParams($searchTerm, $options));
    }

    /**
     * @throws RuntimeException
     */
    public function suggestions(string $searchTerm, array $options = []): array
    {
        return $this->request('POST', '/domains/lookup/suggestions', $this->lookupParams($searchTerm, $options));
    }

    /**
     * @throws RuntimeException
     */
    public function tlds(): array
    {
        return $this->request('GET', '/tlds');
    }

    /**
     * @throws RuntimeException
     */
    public function tldsPricing(): array
    {
        return $this->request('GET', '/tlds/pricing');
    }

    /**
     * @param  'register'|'renew'|'transfer'  $type
     *
     * @throws RuntimeException
     */
    public function pricing(string $type, string $domain): array
    {
        return $this->request('GET', '/order/pricing/domains/'.$type, [
            'domain' => $domain,
        ]);
    }

    /**
     * @throws RuntimeException
     */
    public function credits(): array
    {
        return $this->request('GET', '/billing/credits');
    }

    /**
     * @throws RuntimeException
     */
    public function version(): array
    {
        return $this->request('GET', '/version');
    }

    /**
     * @throws RuntimeException
     */
    public function register(array $params): array
    {
        return $this->request('POST', '/order/domains/register', $params);
    }

    /**
     * @throws RuntimeException
     */
    public function transfer(array $params): array
    {
        return $this->request('POST', '/order/domains/transfer', $params);
    }

    /**
     * @throws RuntimeException
     */
    public function renew(array $params): array
    {
        return $this->request('POST', '/order/domains/renew', $params);
    }

    /**
     * @throws RuntimeException
     */
    public function information(string $domain): array
    {
        return $this->request('GET', $this->domainPath($domain, '/information'));
    }

    /**
     * @throws RuntimeException
     */
    public function getContacts(string $domain): array
    {
        return $this->request('GET', $this->domainPath($domain, '/contact'));
    }

    /**
     * @throws RuntimeException
     */
    public function saveContacts(string $domain, array $contactDetails): array
    {
        return $this->request('POST', $this->domainPath($domain, '/contact'), [
            'contactdetails' => $contactDetails,
        ]);
    }

    /**
     * @throws RuntimeException
     */
    public function getLock(string $domain): array
    {
        return $this->request('GET', $this->domainPath($domain, '/lock'));
    }

    /**
     * @throws RuntimeException
     */
    public function saveLock(string $domain, string $lockStatus): array
    {
        return $this->request('POST', $this->domainPath($domain, '/lock'), [
            'lockstatus' => $lockStatus,
        ]);
    }

    /**
     * @throws RuntimeException
     */
    public function getDns(string $domain): array
    {
        return $this->request('GET', $this->domainPath($domain, '/dns'));
    }

    /**
     * @throws RuntimeException
     */
    public function saveDns(string $domain, array $dnsRecords): array
    {
        return $this->request('POST', $this->domainPath($domain, '/dns'), [
            'dnsrecords' => $dnsRecords,
        ]);
    }

    /**
     * @throws RuntimeException
     */
    public function getNameservers(string $domain): array
    {
        return $this->request('GET', $this->domainPath($domain, '/nameservers'));
    }

    /**
     * @throws RuntimeException
     */
    public function saveNameservers(string $domain, array $nameservers): array
    {
        return $this->request('POST', $this->domainPath($domain, '/nameservers'), $nameservers);
    }

    /**
     * @throws RuntimeException
     */
    public function registerNs(string $domain, string $nameserver, string $ipAddress): array
    {
        return $this->request('POST', $this->domainPath($domain, '/nameservers/register'), [
            'nameserver' => $nameserver,
            'ipaddress' => $ipAddress,
        ]);
    }

    /**
     * @throws RuntimeException
     */
    public function modifyNs(string $domain, string $nameserver, string $currentIpAddress, string $newIpAddress): array
    {
        return $this->request('POST', $this->domainPath($domain, '/nameservers/modify'), [
            'nameserver' => $nameserver,
            'currentipaddress' => $currentIpAddress,
            'newipaddress' => $newIpAddress,
        ]);
    }

    /**
     * @throws RuntimeException
     */
    public function deleteNs(string $domain, string $nameserver): array
    {
        return $this->request('POST', $this->domainPath($domain, '/nameservers/delete'), [
            'nameserver' => $nameserver,
        ]);
    }

    /**
     * @throws RuntimeException
     */
    public function eppCode(string $domain): array
    {
        return $this->request('GET', $this->domainPath($domain, '/eppcode'));
    }

    /**
     * @throws RuntimeException
     */
    public function release(string $domain, string $transferTag): array
    {
        return $this->request('POST', $this->domainPath($domain, '/release'), [
            'transfertag' => $transferTag,
        ]);
    }

    /**
     * @throws RuntimeException
     */
    public function requestDeletion(string $domain): array
    {
        return $this->request('POST', $this->domainPath($domain, '/delete'));
    }

    /**
     * @throws RuntimeException
     */
    public function sync(string $domain): array
    {
        return $this->request('POST', $this->domainPath($domain, '/sync'));
    }

    /**
     * @throws RuntimeException
     */
    public function transferSync(string $domain): array
    {
        return $this->request('POST', $this->domainPath($domain, '/transfersync'));
    }

    /**
     * @throws RuntimeException
     */
    public function getEmailForwarding(string $domain): array
    {
        return $this->request('GET', $this->domainPath($domain, '/email'));
    }

    /**
     * @throws RuntimeException
     */
    public function saveEmailForwarding(string $domain, array $prefixes, array $forwardTo): array
    {
        return $this->request('POST', $this->domainPath($domain, '/email'), [
            'prefix' => $prefixes,
            'forwardto' => $forwardTo,
        ]);
    }

    /**
     * @throws RuntimeException
     */
    public function protectId(string $domain, int $status): array
    {
        return $this->request('POST', $this->domainPath($domain, '/protectid'), [
            'status' => $status,
        ]);
    }

    /**
     * @throws RuntimeException
     */
    private function request(string $method, string $action, array $params = []): array
    {
        $this->ensureConfigured();

        $endpoint = rtrim((string) config('services.hostafrica.endpoint'), '/');
        $url = str_starts_with($action, '/')
            ? $endpoint.$action
            : $endpoint.'/'.$action;

        $headers = [
            'username' => (string) config('services.hostafrica.username'),
            'token' => $this->buildToken(),
        ];

        $http = Http::withHeaders($headers)->timeout(30);

        $response = strtoupper($method) === 'GET'
            ? $http->get($url, $params)
            : $http->asForm()->post($url, $params);

        if (! $response->successful()) {
            throw new RuntimeException(
                'HostAfrica API request failed ('.$response->status().'): '.$response->body()
            );
        }

        $json = $response->json();

        return is_array($json) ? $json : ['raw' => $response->body()];
    }

    private function buildToken(): string
    {
        $username = (string) config('services.hostafrica.username');
        $apiKey = (string) config('services.hostafrica.api_key');
        $timestamp = gmdate('y-m-d H');

        return base64_encode(hash_hmac('sha256', $apiKey, $username.':'.$timestamp));
    }

    private function domainPath(string $domain, string $suffix): string
    {
        return '/domains/'.rawurlencode(strtolower($domain)).$suffix;
    }

    /**
     * HostAfrica requires array params (e.g. tldsToInclude) — never null.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    private function lookupParams(string $searchTerm, array $options = []): array
    {
        $tlds = $options['tldsToInclude'] ?? null;
        unset($options['tldsToInclude']);

        if (! is_array($tlds) || $tlds === []) {
            $tlds = config('services.hostafrica.default_tlds', ['co.za', 'com', 'net', 'org']);
        }

        return array_merge([
            'searchTerm' => $searchTerm,
            'tldsToInclude' => array_values($tlds),
            'isIdnDomain' => (bool) ($options['isIdnDomain'] ?? false),
            'premiumEnabled' => (bool) ($options['premiumEnabled'] ?? false),
        ], $options);
    }

    /**
     * @throws RuntimeException
     */
    private function ensureConfigured(): void
    {
        if (blank(config('services.hostafrica.username')) || blank(config('services.hostafrica.api_key'))) {
            throw new RuntimeException('HostAfrica API credentials are not configured.');
        }
    }
}
