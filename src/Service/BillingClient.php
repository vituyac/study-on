<?php

namespace App\Service;

use App\Exception\BillingException;
use App\Exception\BillingUnavailableException;
use App\Exception\BillingValidationException;

class BillingClient
{
    public function __construct(
        private readonly string $billingBaseUrl,
    ) {
    }

    public function auth(string $email, string $password): array
    {
        $data = $this->request('POST', '/api/v1/auth', ['email' => $email, 'password' => $password]);

        return $data;
    }

    public function register(string $email, string $password): array
    {
        return $this->request('POST', '/api/v1/register', ['email' => $email, 'password' => $password]);
    }

    public function getCurrentUser(string $token): array
    {
        return $this->request('GET', '/api/v1/users/current', null, ['Authorization: Bearer ' . $token]);
    }

    private function request(string $method, string $uri, ?array $body = null, array $headers = []): array
    {
        $curl = curl_init();

        $defaultHeaders = ['Content-Type: application/json'];

        curl_setopt_array($curl, [
            CURLOPT_URL => rtrim($this->billingBaseUrl, '/') . $uri,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => array_merge($defaultHeaders, $headers),
        ]);

        if ($body !== null) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($body, JSON_THROW_ON_ERROR));
        }

        $result = curl_exec($curl);

        if ($result === false) {
            $error = curl_error($curl);

            throw new BillingUnavailableException($error);
        }

        $statusCode = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);

        $data = json_decode($result, true, 512, JSON_THROW_ON_ERROR);

        if ($statusCode === 422) {
            $violations = [];

            foreach ($data['violations'] ?? [] as $violation) {
                if (isset($violation['propertyPath'], $violation['title'])) {
                    $violations[$violation['propertyPath']] = $violation['title'];
                }
            }

            throw new BillingValidationException($data['detail'], $violations, $statusCode);
        }

        if ($statusCode >= 400) {
            $message = $data['message'];

            throw new BillingException($message, $statusCode);
        }

        return $data;
    }
}
