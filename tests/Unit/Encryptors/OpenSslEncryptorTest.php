<?php
/**
 * Created by PhpStorm.
 * User: mark
 * Date: 20/06/19
 * Time: 22:55.
 */

namespace SpecShaper\EncryptBundle\tests\Unit\Encryptors;

use SpecShaper\EncryptBundle\Encryptors\OpenSslEncryptor;
use Symfony\Component\EventDispatcher\EventDispatcher;

class OpenSslEncryptorTest extends \PHPUnit\Framework\TestCase
{
    private const TEST_KEY = 'YBmNcBGfrZoayB+V254wdYa/abvxSUWJsjCtlMc1tRI=';

    public function testEncryptException()
    {
        $this->expectException(\TypeError::class);

        $object = new \stdClass();
        $object->test = 'Test';

        $encryptor = new OpenSslEncryptor(new EventDispatcher());
        $encryptor->setSecretKey(self::TEST_KEY);

        $encryptor->encrypt($object);
    }

    /**
     * @throws \Exception
     */
    public function testEncrypt()
    {
        $encryptor = new OpenSslEncryptor(new EventDispatcher());
        $encryptor->setSecretKey(self::TEST_KEY);

        // Assert that empty value returns an empty value.
        $result = $encryptor->encrypt(null);
        $this->assertTrue(null === $result);

        // Assert that "<ENC>" returns an empty value;
        $result = $encryptor->encrypt('<ENC>');
        $this->assertTrue('<ENC>' === $result);

        // Assert that an encrypted then decrypted value returns the original value;
        $value = 'Honey, where are my pants?';
        $encryptedValue = $encryptor->encrypt($value);
        $decrypted = $encryptor->decrypt($encryptedValue);
        $this->assertTrue($value === $decrypted);
    }

    /**
     * @throws \Exception
     */
    public function testDecryptException()
    {
        $this->expectException(\TypeError::class);
        // or for PHPUnit < 5.2
        // $this->setExpectedException(InvalidArgumentException::class);

        $object = new \stdClass();
        $object->test = 'Test';

        // ...and then add your test code that generates the exception
        $encryptor = new OpenSslEncryptor(new EventDispatcher());
        $encryptor->setSecretKey(self::TEST_KEY);
        $encryptor->decrypt($object);
    }

    /**
     * @throws \Exception
     */
    public function testDecrypt()
    {
        $encryptor = new OpenSslEncryptor(new EventDispatcher());
        $encryptor->setSecretKey(self::TEST_KEY);

        // Assert that empty value returns an empty value.
        $result = $encryptor->decrypt(null);
        $this->assertTrue(null === $result);

        // Assert that string without "<ENC>" returns an same string;
        $result = $encryptor->decrypt('Test value <ENC');
        $this->assertTrue('Test value <ENC' === $result);

        // Assert that an encrypted value returns the expected decrypted value;
        $decrypted = $encryptor->decrypt('5hhCphjZSgXvZgAu9t3O99fnFsdDgHr67QR7lf8NVZdgHTH8Dj/gsfQ+AI2agJOc<ENC>');
        $this->assertTrue('Honey, where are my pants?' === $decrypted);
    }
}
