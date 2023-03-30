<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(
    name: 'app:chat',
    description: 'Add a short description for your command',
)]
class ChatCommand extends Command
{
    public function __construct(private readonly HttpClientInterface $httpClient, private readonly string $openAiApiKey)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('question', InputArgument::OPTIONAL, 'question')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $question = $input->getArgument('question');

        $response = $this->httpClient->request('POST', 'https://api.openai.com/v1/chat/completions', [
            'json' => [
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    ['role' => 'user', 'content' => $question],
                ],
            ],
            'headers' => [
                'Authorization' => 'Bearer '.$this->openAiApiKey,
            ],
        ]);
        $json = json_decode($response->getContent());
        $io->text($json->choices[0]->message->content);
        $io->success('You have a new command! Now make it your own! Pass --help to see your options.');

        return Command::SUCCESS;
    }
}
