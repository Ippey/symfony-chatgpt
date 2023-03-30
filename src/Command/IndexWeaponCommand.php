<?php

namespace App\Command;

use App\Dto\Weapon;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(
    name: 'app:index-weapon',
    description: 'Add a short description for your command',
)]
class IndexWeaponCommand extends Command
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $openAiApiKey,
        private readonly string $pineconeEnvName,
        private readonly string $pineconeApiKey
    ) {
        parent::__construct();
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $response = $this->httpClient->request('GET', 'https://stat.ink/api/v3/weapon');
        $json = json_decode($response->getContent());

        foreach ($json as $weapon) {
            $weapon = new Weapon(
                key: $weapon->key,
                name: $weapon->name->ja_JP,
                type: $weapon->type->name->ja_JP,
                subWeapon: $weapon->sub->name->ja_JP,
                specialWeapon: $weapon->special->name->ja_JP,
            );
            $embedding = $this->embedding($weapon);
            $this->createIndex($weapon, $embedding);
        }

        $io->success('You have a new command! Now make it your own! Pass --help to see your options.');

        return Command::SUCCESS;
    }

    /**
     * @return mixed
     *
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    private function embedding(Weapon $weapon)
    {
        $response = $this->httpClient->request('POST', 'https://api.openai.com/v1/embeddings', [
            'json' => [
                'model' => 'text-embedding-ada-002',
                'input' => $weapon->__toString(),
            ],
            'headers' => [
                'Authorization' => 'Bearer '.$this->openAiApiKey,
            ],
        ]);

        $json = json_decode($response->getContent());

        return $json->data[0]->embedding;
    }

    /**
     * @throws TransportExceptionInterface
     */
    private function createIndex(Weapon $weapon, array $embedding): void
    {
        $response = $this->httpClient->request('POST', 'https://weapons-083e838.svc.eu-west1-gcp.pinecone.io/vectors/upsert', [
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
            'headers' => [
                'Api-Key' => $this->pineconeApiKey,
            ],
        ]);
    }
}
