<?php

namespace App\Command;

use App\Dto\Weapon;
use App\Service\OpenAi\OpenAiClient;
use App\Service\Pinecone\PineconeWeaponClient;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:recommend',
    description: 'Add a short description for your command',
)]
class RecommendCommand extends Command
{
    public function __construct(
        private readonly OpenAiClient $openAiClient,
        private readonly PineconeWeaponClient $pineconeWeaponClient
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $question = $io->ask('どんなブキが欲しいでしか？');

        $io->section('ChatGPTに問い合わせてるでし');
        $answer = $this->openAiClient->chat([
            'スプラトゥーン3のブキについてお尋ねします',
            'ブキチっぽくしゃべってください',
            $question,
            '以上を考慮しておすすめのブキ、サブウェポン、スペシャルを教えてください',
        ]);
        $io->text($answer);
        $io->section('Pineconeに問い合わせてるでし');

        $embedding = $this->openAiClient->embedding($answer);
        $weapons = $this->pineconeWeaponClient->query($embedding);

        $io->text('あなたにおすすめのブキはこちらでし！');
        $io->table(
            ['ブキ名', 'タイプ', 'サブ', 'スペシャル', 'スコア'],
            array_map(function (Weapon $weapon) {
                return [
                    $weapon->name,
                    $weapon->type,
                    $weapon->subWeapon,
                    $weapon->specialWeapon,
                    $weapon->score,
                ];
            }, $weapons)
        );

        return Command::SUCCESS;
    }
}
