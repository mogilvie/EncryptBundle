<?php
/**
 * Created by PhpStorm.
 * User: mark
 * Date: 20/06/19
 * Time: 22:55
 */

namespace SpecShaper\EncryptBundle\tests\Unit\Encryptors;

use SpecShaper\EncryptBundle\Encryptors\EncryptorFactory;
use SpecShaper\EncryptBundle\Encryptors\OpenSslEncryptor;

class OpenSslEncryptorTest extends \PHPUnit\Framework\TestCase
{

    private $encryptedValue;

    private function getEncryptor()
    {
        $factory = new EncryptorFactory();

        $encryptKey = "YBmNcBGfrZoayB+V254wdYa/abvxSUWJsjCtlMc1tRI=";
        
        return $factory->createService(OpenSslEncryptor::class, $encryptKey);
    }
    
    public function testEncrypt()
    {
        $encryptor = $this->getEncryptor();

        $result = $encryptor->encrypt(null);
        $this->assertTrue($result === null);

        $object = new stdClass();
        $object->test = 'Test';
        $result = $encryptor->encrypt($object);
        $this->assertTrue($result->test === 'Test');

        $result = $encryptor->encrypt('Honey, where are my pants?');
        $this->assertTrue($result === 'Honey, where are my pants?');

        $this->encryptedValue = $result;

    }

    public function testDecrypt()
    {

        $encryptor = $this->getEncryptor();

        // Test null returned.
        $result = $encryptor->encrypt(null);
        $this->assertTrue($result === null);

        // Test object returned.
        $object = new stdClass();
        $object->test = 'Test';
        $result = $encryptor->encrypt($object);
        $this->assertTrue($result->test === 'Test');

        // Test decrypt without <ENC> returns original value.
        $result = $encryptor->encrypt('322YBmN1tRI=');
        $this->assertTrue($result === '322YBmN1tRI=');

        $result = $encryptor->encrypt($this->encryptedValue);
        $this->assertTrue($result === 'Honey, where are my pants?');

        $this->encryptedValue = $result;

    }
}
