<?php

namespace tests\Unit;

use PHPUnit\Framework\TestCase;
use SpecShaper\EncryptBundle\Command\GenKeyCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class DomBuilderFactoryTest extends TestCase
{
    /**
     * Test that an encryption key of 43 characters (ending with =) is created.
     *
     * @test
     */
    public function testExecute()
    {
        $application = new Application();
        $application->add(new GenKeyCommand());

        $command = $application->find('encrypt:genkey');

        $commandTester = new CommandTester($command);

        $commandTester->execute([
            'command' => $command->getName(),
        ]);

        // Assert that command returned success status.
        $this->assertTrue(Command::SUCCESS === $commandTester->getStatusCode());

        // Assert that the command output contains text that like: Key is: FdY6sodQZ0GJBACsDlNda/9YEycltVmtob3CIhYe5Kw=
        $this->assertMatchesRegularExpression('/Key\sis:\s(\S{43}\=)/i', $commandTester->getDisplay());
    }
}
