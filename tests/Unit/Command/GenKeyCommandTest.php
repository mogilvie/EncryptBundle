<?php
namespace tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use SpecShaper\EncryptBundle\Command\GenKeyCommand;

class DomBuilderFactoryTest extends TestCase
{
    /**
     * @test
     */
    public function testExecute(){

        $application = new Application();
        $application->add(new GenKeyCommand());

        $command = $application->find('encrypt:genkey');
        
        $commandTester = new CommandTester($command);
        
        $commandTester->execute(array(
            'command' => $command->getName()
        ));

        $output = $commandTester->getOutput();
        echo($commandTester->getDisplay());
        $this->assertNotEmpty($commandTester->getDisplay());
    }
   
}
