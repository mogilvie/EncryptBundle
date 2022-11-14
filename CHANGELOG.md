#Change Log

## 3.0.2 (2022-11-xx) Update
Add attribute support for #[Encrypted] attributes instead of @Encrypted annotations.

## 3.0.1 (2022-03-13) Symfony 6 and PHP 8
Major backward compatibility breaking change to Symfony 6 and PHP 8.

### Encyptor Factory
- Remove logging and event dispatcher constructors
- Change constructor to allow passing of an optional encryptor class name.

Service definition was:
```yaml
    # Factory to create the encryptor/decryptor
    SpecShaper\EncryptBundle\Encryptors\EncryptorFactory:
        arguments: ['@logger', '@event_dispatcher']
        tags:
            - { name: monolog.logger, channel: app }
        
    SpecShaper\EncryptBundle\Encryptors\EncryptorInterface:
        factory: ['@SpecShaper\EncryptBundle\Encryptors\EncryptorFactory','createService']
        arguments:
            - '%spec_shaper_encrypt.method%'
            - '%spec_shaper_encrypt.encrypt_key%'
```
Service definition becomes:
```yaml
    # Factory to create the encryptor/decryptor
    SpecShaper\EncryptBundle\Encryptors\EncryptorFactory:
        arguments: ['@event_dispatcher']
        tags:
            - { name: monolog.logger, channel: app }

    # The encryptor service created by the factory according to the passed method and using the encrypt_key
    SpecShaper\EncryptBundle\Encryptors\EncryptorInterface:
        factory: ['@SpecShaper\EncryptBundle\Encryptors\EncryptorFactory','createService']
        arguments:
            $encryptKey: '%spec_shaper_encrypt.encrypt_key%'
            $encryptorClass: '%spec_shaper_encrypt.encryptor_class%' #optional
```