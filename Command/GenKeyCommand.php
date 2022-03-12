<?php

namespace SpecShaper\EncryptBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * CLI command to generate a 256-bit encryption key.
 */
class GenKeyCommand extends Command
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'encrypt:genkey';

    protected function configure(): void
    {
        $this->setDescription('Generate a 256-bit encryption key.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $encryption_key_256bit = base64_encode(openssl_random_pseudo_bytes(32));
        $io = new SymfonyStyle($input, $output);
        $io->title('Generated Key');
        $io->success('Key is: ' . $encryption_key_256bit);

        return Command::SUCCESS;
    }
}
