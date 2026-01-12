<?php

declare(strict_types=1);

namespace SpecShaper\EncryptBundle\Tests\Unit\Encryptors;

use SpecShaper\EncryptBundle\Encryptors\AesGcmEncryptor;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * @author David Dadon <david.dadon@neftys.fr>
 */
class AesGcmEncryptorTest extends \PHPUnit\Framework\TestCase
{
    private const TEST_KEY = 'YBmNcBGfrZoayB+V254wdYa/abvxSUWJsjCtlMc1tRI=';

    public function testEncryptException(): void
    {
        $this->expectException(\TypeError::class);

        // Given
        $object = new \stdClass();
        $object->test = 'Test';

        $encryptor = new AesGcmEncryptor(new EventDispatcher());
        $encryptor->setSecretKey(self::TEST_KEY);

        // When
        $encryptor->encrypt($object);
    }

    public function testEncryptNullReturnsNull(): void
    {
        // Given
        $encryptor = new AesGcmEncryptor(new EventDispatcher());
        $encryptor->setSecretKey(self::TEST_KEY);
        $encryptor->setDefaultAssociatedData(null);

        // When
        $result = $encryptor->encrypt(null);

        // Then
        $this->assertTrue($result === null);
    }

    public function testEncryptOnlySuffix(): void
    {
        // Given
        $encryptor = new AesGcmEncryptor(new EventDispatcher());
        $encryptor->setSecretKey(self::TEST_KEY);
        $encryptor->setDefaultAssociatedData(null);

        // When
        $result = $encryptor->encrypt('<ENC>');

        // Then
        $this->assertTrue($result === '<ENC>');
    }

    public function testEncryptAndDecryptReturnsOriginalValue(): void
    {
        // Given
        $encryptor = new AesGcmEncryptor(new EventDispatcher());
        $encryptor->setSecretKey(self::TEST_KEY);
        $encryptor->setDefaultAssociatedData(null);
        $value = 'Honey, where are my pants?';

        // When
        $encryptedValue = $encryptor->encrypt($value);

        // Then
        $this->assertTrue($encryptedValue !== $value);

        // When
        $decrypted = $encryptor->decrypt($encryptedValue);

        // Then
        $this->assertTrue($decrypted === $value);
    }

    /**
     * @throws \Exception
     */
    public function testDecryptException(): void
    {
        $this->expectException(\TypeError::class);
        // or for PHPUnit < 5.2
        // $this->setExpectedException(InvalidArgumentException::class);

        // Given
        $object = new \stdClass();
        $object->test = 'Test';

        // ...and then add your test code that generates the exception
        $encryptor = new AesGcmEncryptor(new EventDispatcher());
        $encryptor->setSecretKey(self::TEST_KEY);

        // When
        $encryptor->decrypt($object);
    }

    /**
     * @throws \Exception
     */
    public function testDecryptNullReturnsNull(): void
    {
        // Given
        $encryptor = new AesGcmEncryptor(new EventDispatcher());
        $encryptor->setSecretKey(self::TEST_KEY);
        $encryptor->setDefaultAssociatedData(null);

        // When
        $result = $encryptor->decrypt(null);

        // Then
        $this->assertTrue($result === null);
    }

    public function testDecryptWithoutSuffixReturnsOrignialValue(): void
    {
        // Given
        $encryptor = new AesGcmEncryptor(new EventDispatcher());
        $encryptor->setSecretKey(self::TEST_KEY);
        $encryptor->setDefaultAssociatedData(null);

        // When
        $result = $encryptor->decrypt('Test value <ENC');

        // Then
        $this->assertTrue($result === 'Test value <ENC');
    }

    public function testDecryptReturnsExpectedValue(): void
    {
        // Given
        $encryptor = new AesGcmEncryptor(new EventDispatcher());
        $encryptor->setSecretKey(self::TEST_KEY);
        $encryptor->setDefaultAssociatedData(null);

        // When
        $decrypted = $encryptor->decrypt('g5wofClWz/wG44umXsUw+wAHQiqhTmo0eGIcODXvV6bjU3xDR8paa7wzu8EoJh0xGOJPD+Ue<ENC>');

        // Then
        $this->assertTrue($decrypted === 'Honey, where are my pants?');
    }

    public function testEncryptWithColumnName(): void
    {
        // Given
        $encryptor = new AesGcmEncryptor(new EventDispatcher());
        $encryptor->setSecretKey(self::TEST_KEY);
        $value = 'Honey, where are my pants?';

        // When
        $encryptedValue = $encryptor->encrypt($value, 'columnName');

        // Then
        $this->assertFalse($encryptedValue === $value);

        // When
        $decrypted = $encryptor->decrypt($encryptedValue, 'columnName');

        // Then
        $this->assertTrue($decrypted === $value);
    }

    public function testEncryptWithDefaultAssociatedData(): void
    {
        // Given
        $encryptor = new AesGcmEncryptor(new EventDispatcher());
        $encryptor->setSecretKey(self::TEST_KEY);
        $encryptor->setDefaultAssociatedData('DefaultAssociatedData');
        $value = 'Honey, where are my pants?';

        // When
        $encryptedValue = $encryptor->encrypt($value);

        // Then
        $this->assertFalse($encryptedValue === $value);

        // When
        $decrypted = $encryptor->decrypt($encryptedValue);

        // Then
        $this->assertTrue($decrypted === $value);
    }
}
