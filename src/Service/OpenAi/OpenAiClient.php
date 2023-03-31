<?php

namespace App\Service\OpenAi;

use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class OpenAiClient
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $apiKey
    ) {
    }

    private function getHeaders(): array
    {
        return [
            'Authorization' => 'Bearer '.$this->apiKey,
        ];
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function embedding(string $input): array
    {
        $response = $this->httpClient->request('POST', 'https://api.openai.com/v1/embeddings', [
            'json' => [
                'model' => 'text-embedding-ada-002',
                'input' => $input,
            ],
            'headers' => $this->getHeaders(),
        ]);

        $json = json_decode($response->getContent());

        return $json->data[0]->embedding;
    }

    /**
     * @param string[] $contents
     *
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function chat(array $contents): string
    {
        $messages = array_map(function (string $content) {
            return ['role' => 'user', 'content' => $content];
        }, $contents);
        $response = $this->httpClient->request('POST', 'https://api.openai.com/v1/chat/completions', [
            'json' => [
                'model' => 'gpt-3.5-turbo',
                'messages' => $messages,
            ],
            'headers' => $this->getHeaders(),
        ]);
        $json = json_decode($response->getContent());

        return $json->choices[0]->message->content;
    }
}
