<?php
namespace PokerProvide;

class PokerProvide
{
    private string $publicKey;
    private string $secretKey;
    private string $baseUrl;
    public Tournaments $tournaments;

    public function __construct(string $publicKey, string $secretKey, string $baseUrl = 'https://api.pokerprovide.com')
    {
        if (!$publicKey || !$secretKey) throw new \InvalidArgumentException('publicKey and secretKey are required');
        $this->publicKey = $publicKey;
        $this->secretKey = $secretKey;
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->tournaments = new Tournaments($this);
    }

    public function sign(string $rawBody): string
    {
        return hash_hmac('sha256', $rawBody, $this->secretKey);
    }

    public function request(string $method, string $path, ?array $body = null): array
    {
        $raw = $body !== null ? json_encode($body) : '';
        $headers = ['Content-Type: application/json', 'X-Operator-Key: ' . $this->publicKey];
        if ($raw !== '') $headers[] = 'X-Operator-Signature: ' . $this->sign($raw);
        $ch = curl_init($this->baseUrl . $path);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => $raw,
        ]);
        $resp = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $data = json_decode($resp, true) ?: [];
        if ($status >= 400) {
            throw new \RuntimeException(($data['detail'] ?? $data['message'] ?? "HTTP $status"), $status);
        }
        return $data;
    }

    public function createSession(string $playerId, ?string $displayName = null, string $currency = 'EUR'): array
    {
        return $this->request('POST', '/api/v1/session',
            ['player_id' => $playerId, 'display_name' => $displayName, 'currency' => $currency]);
    }

    public function walletBalance(string $operatorId, string $playerId): array
    {
        return $this->request('POST', '/api/wallet/balance', ['operator_id' => $operatorId, 'player_id' => $playerId]);
    }

    public function walletDebit(string $operatorId, string $playerId, float $amount, string $transactionId, string $currency = 'EUR', string $reason = 'POKER_BUYIN'): array
    {
        return $this->request('POST', '/api/wallet/debit',
            ['operator_id' => $operatorId, 'player_id' => $playerId, 'amount' => $amount,
             'currency' => $currency, 'transaction_id' => $transactionId, 'reason' => $reason]);
    }

    public function walletCredit(string $operatorId, string $playerId, float $amount, string $transactionId, string $currency = 'EUR', string $reason = 'POKER_CASHOUT'): array
    {
        return $this->request('POST', '/api/wallet/credit',
            ['operator_id' => $operatorId, 'player_id' => $playerId, 'amount' => $amount,
             'currency' => $currency, 'transaction_id' => $transactionId, 'reason' => $reason]);
    }

    public function verifyWebhook(string $rawBody, string $signatureHeader, string $webhookSecret): bool
    {
        $expected = hash_hmac('sha256', $rawBody, $webhookSecret);
        return hash_equals($expected, $signatureHeader);
    }
}

class Tournaments
{
    private PokerProvide $c;
    public function __construct(PokerProvide $c) { $this->c = $c; }

    public function list(?string $state = null): array
    {
        return $this->c->request('GET', '/api/v1/tournaments' . ($state ? '?state=' . urlencode($state) : ''));
    }
    public function get(string $id): array { return $this->c->request('GET', '/api/v1/tournaments/' . rawurlencode($id)); }
    public function structure(string $id): array { return $this->c->request('GET', '/api/v1/tournaments/' . rawurlencode($id) . '/structure'); }
    public function payouts(string $id): array { return $this->c->request('GET', '/api/v1/tournaments/' . rawurlencode($id) . '/payouts'); }
    public function players(string $id): array { return $this->c->request('GET', '/api/v1/tournaments/' . rawurlencode($id) . '/players'); }
    public function results(string $id): array { return $this->c->request('GET', '/api/v1/tournaments/' . rawurlencode($id) . '/results'); }
    public function register(string $id, string $playerId, ?string $displayName = null, ?string $operatorOrigin = null, ?string $idempotencyKey = null, ?string $ticketId = null): array
    {
        return $this->c->request('POST', '/api/v1/tournaments/' . rawurlencode($id) . '/register',
            ['player_id' => $playerId, 'display_name' => $displayName, 'operator_origin' => $operatorOrigin,
             'idempotency_key' => $idempotencyKey, 'ticket_id' => $ticketId]);
    }
    public function unregister(string $id, string $playerId): array
    {
        return $this->c->request('POST', '/api/v1/tournaments/' . rawurlencode($id) . '/unregister?player_id=' . urlencode($playerId));
    }
}
