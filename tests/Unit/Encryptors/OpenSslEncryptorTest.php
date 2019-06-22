<?php
/**
 * Created by PhpStorm.
 * User: mark
 * Date: 20/06/19
 * Time: 22:55
 */

namespace SpecShaper\EncryptBundle\tests\Unit\Encryptors;

use SpecShaper\EncryptBundle\Encryptors\OpenSslEncryptor;
use SpecShaper\EncryptBundle\Exception\EncryptException;

class OpenSslEncryptorTest extends \PHPUnit\Framework\TestCase
{

    private $encrypt_key = "YBmNcBGfrZoayB+V254wdYa/abvxSUWJsjCtlMc1tRI=";

    public function testEncryptException()
    {
        $this->expectException(EncryptException::class);

        $object = new \stdClass();
        $object->test = 'Test';

        $encryptor  = new OpenSslEncryptor($this->encrypt_key);

        $encryptor->encrypt($object);
    }

    /**
     * @throws \Exception
     */
    public function testEncrypt()
    {
        $encryptor  = new OpenSslEncryptor($this->encrypt_key);

        // Assert that empty value returns an empty value.
        $result = $encryptor->encrypt(null);
        $this->assertTrue($result === null);

        // Assert that "<ENC>" returns an empty value;
        $result = $encryptor->encrypt("<ENC>");
        $this->assertTrue($result === "<ENC>");

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
        $this->expectException(EncryptException::class);
        // or for PHPUnit < 5.2
        // $this->setExpectedException(InvalidArgumentException::class);

        $object = new \stdClass();
        $object->test = 'Test';

        //...and then add your test code that generates the exception
        $encryptor  = new OpenSslEncryptor($this->encrypt_key);
        $encryptor->decrypt($object);
    }

    /**
     * @throws \Exception
     */
    public function testDecrypt()
    {

        $encryptor  = new OpenSslEncryptor($this->encrypt_key);

        // Assert that empty value returns an empty value.
        $result = $encryptor->decrypt(null);
        $this->assertTrue($result === null);

        // Assert that string without "<ENC>" returns an same string;
        $result = $encryptor->decrypt("Test value <ENC");
        $this->assertTrue($result === "Test value <ENC");

        // Assert that an encrypted value returns the expected decrypted value;
        $decrypted = $encryptor->decrypt('5hhCphjZSgXvZgAu9t3O99fnFsdDgHr67QR7lf8NVZdgHTH8Dj/gsfQ+AI2agJOc<ENC>');
        $this->assertTrue($decrypted === 'Honey, where are my pants?');
    }
}
