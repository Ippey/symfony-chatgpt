<?php

namespace App\Service\Pinecone;

use App\Dto\Weapon;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class PineconeWeaponClient
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $apiKey,
        private readonly string $envName,
        private readonly string $projectName,
        private readonly string $projectId
    ) {
    }

    private function getBaseUrl(): string
    {
        return sprintf('https://%s-%s.svc.%s.pinecone.io', $this->projectName, $this->projectId, $this->envName);
    }

    private function getHeaders(): array
    {
        return [
            'Api-Key' => $this->apiKey,
        ];
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function upsert(Weapon $weapon, array $embedding): int
    {
        $url = $this->getBaseUrl().'/vectors/upsert';
        $response = $this->httpClient->request('POST', $url, [
            'json' => [
                'vectors' => [
                    [
                        'id' => $weapon->key,
                        'values' => $embedding,
                        'metadata' => [
                            'name' => $weapon->name,
                            'type' => $weapon->type,
                            'subWeapon' => $weapon->subWeapon,
                            'specialWeapon' => $weapon->specialWeapon,
                        ],
                    ],
                ],
            ],
            'headers' => $this->getHeaders(),
        ]);

        return $response->getStatusCode();
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function query(array $embedding): array
    {
        $url = $this->getBaseUrl().'/query';
        $response = $this->httpClient->request('POST', $url, [
            'json' => [
                'topK' => 5,
                'vector' => $embedding,
                'includeMetadata' => true,
                'includeValues' => false,
                'namespace' => '',
            ],
            'headers' => $this->getHeaders(),
        ]);

        $json = json_decode($response->getContent());

        $weapons = [];
        foreach ($json->matches as $match) {
            $weapon = new Weapon(
                key: $match->id,
                name: $match->metadata->name,
                type: $match->metadata->type,
                subWeapon: $match->metadata->subWeapon,
                specialWeapon: $match->metadata->specialWeapon,
                score: $match->score,
            );
            $weapons[] = $weapon;
        }

        return $weapons;
    }
}
