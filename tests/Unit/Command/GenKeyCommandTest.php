<?php
namespace tests\Unit;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use SpecShaper\EncryptBundle\Command\GenKeyCommand ;

class DomBuilderFactoryTest extends KernelTestCase
{
    /**
     * @test
     */
    public function testExecute(){

        $kernel = static::createKernel();
        $kernel->boot();

        $application = new Application($kernel);
        $application->add(new GenKeyCommand());

        $command = $application->find('generate:newsletter');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command' => $command->getName()
        ));

        $output = $commandTester->getOutput();
        $this->assertContains('done',$output);
    }
}
