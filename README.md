# SpecShaper Encrypt Bundle

A bundle to handle encoding and decoding of parameters using OpenSSL and Doctrine lifecycle events.

Features include:
- V3 is Symfony 5.4|6 PHP 8
- V2 is Symfony 5.
- v1 is Symfony 3.4 and not active any more.
- Uses OpenSSL
- Uses Lifecycle events

**Warning**
- This bundle has not been unit tested.

Features road map:

- [x] Create a factory method to expand for different encryptors
- [x] Create a twig function to decrypt encoded values
- [x] Expand parameters to allow selection of encoding method
- [ ] Create CLI commands to encrypt and decrypt the entire database
- [ ] Handle DateTime data types via the bundle.

## License

This bundle is under the MIT license. See the complete license in the bundle:

    Resources/meta/LICENSE

## About

EncryptBundle has been written for the [SpecShaper](http://about.specshaper.com) and [Parolla](https://www.parolla.ie) websites
to encode users private data. The bundle is expanded in a larger [gdpr-bundle](https://github.com/mogilvie/GdprBundle).

## Reporting an issue or a feature request

Issues and feature requests are tracked in the [Github issue tracker](https://github.com/mogilvie/HelpBundle/issues).

When reporting a bug, it may be a good idea to reproduce it in a basic project
built using the [Symfony Standard Edition](https://github.com/symfony/symfony-standard)
to allow developers of the bundle to reproduce the issue by simply cloning it
and following some steps.

# Installation

## Step 1: Download the bundle

Open a command console, enter your project directory and execute the
following command to download the latest version of this bundle:

```
$ composer require specshaper/encrypt-bundle dev-master
```

This command requires you to have Composer installed globally, as explained
in the [installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

## Step 2: Enable the bundle

Then, enable the bundle by adding it to the list of registered bundles
in the `app/AppKernel.php` file of your project:

```php
<?php
// app/AppKernel.php

// ...
class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = array(
            // ...
            new SpecShaper\EncryptBundle\SpecShaperEncryptBundle(),
        );
        // ...
    }
    // ...
}
```

## Step 2: Configure the bundle

Generate a 256 bit key using the command provided in the bundle.

```
$ bin/console encrypt:genkey
```
 
Copy the key into your .env file.
```
###> encrypt-bundle ###
ENCRYPT_KEY=<YOUR KEY HERE>
###< encrypt-bundle ###
```

And resolve in your parameters file.
```yaml
// app/config/parameters.yml
    ...
    encrypt_key: '%env(resolve:ENCRYPT_KEY)%'
```

A config file entry is not required, however there are some options that
can be configured to extend the bundle.

```yaml
# The encryptor service created by the factory according to the passed method and using the encrypt_key
SpecShaper\EncryptBundle\Encryptors\EncryptorInterface:
  factory: ['@SpecShaper\EncryptBundle\Encryptors\EncryptorFactory','createService']
  arguments:
    $encryptKey: '%spec_shaper_encrypt.encrypt_key%'
    $encryptorClass: '%spec_shaper_encrypt.encryptor_class%' #optional
```
You can disable encryption by setting the 'is_disabled' option to true. Decryption still continues if any values
contain the \<ENC> suffix.

You can pass the class name of your own encyptor service using the optional encryptorClass option.

You can extend the EncryptBundle default Subscriber and override its methods. Use the 'subscriber_class' option
to point the bundle at your custom subscriber.

If you want to define your own annotation/attribute, then this can be used to trigger encryption by adding the annotation 
class name to the 'annotation_classes' option array.

### Alternative EncryptKeyEvent
The EncryptKey can be set via a dispatched event listener, which overrides any .env or param.yml defined key.
Create a listener for the EncryptKeyEvents::LOAD_KEY event and set your encryption key at that point.

## Step 3: Create the entities
Add the Annotation entity to the declared classes in the entity.

```php
<?php
...
use SpecShaper\EncryptBundle\Annotations\Encrypted;
```

Add the attribute #[Encrypted] to the properties you want encrypted.

Note that the legacy annotation '@Encrypted' in the parameters is deprecated and
will be discontinued in the next major update.
```php
<?php

    /**
     * A PPS number is always 7 numbers followed by either one or two letters.
     * 
     * @ORM\Column(type="string")
     */
    #[Encrypted]
    protected string $taxNumber;
    
    /**
     * True if the user is self employed.
     * 
     * @ORM\Column(type="string", nullable=true)
     */
    #[Encrypted]
    protected ?bool $isSelfEmployed;
    
    /**
     * Date of birth
     * 
     * @Encrypted
     * Note that the above Encrypted property is a legacy annotation, and while
     * it still is supported, it will be deprecated in favour of Attributes .
     * 
     * @ORM\Column(type="string", nullable=true)
     */
    protected ?String $dob;
   
```
Where encrypting a field you will need to set the column type as string.  

Your getters and setters may also need to be type declared.  

For example, boolean should either be return declared bool, or return a bool using a ternary method.  

```php
<?php
    /**
     * Get isSelfEmployed
     *
     * @return boolean
     */
    public function isSelfEmployed(): bool
    {
        return $this->isSelfEmployed;
    }

    /**
     * Get isSelfEmployed
     *
     * @return boolean
     */
    public function isSelfEmployed(): bool
    {
        return ($this->isSelfEmployed == 1 ? true: false);
    }

```

For DateTime parameters store the date as a string, and use the getters and setters
to convert that string.

You may also need to create a DataTransformer if you are using the parameter in a form
with the DateType form type.

## Step 4: General Use

The bundle comes with an DoctrineEncryptSubscriber. This subscriber catches the doctrine events
onLoad, onFlush and postFlush.

The onLoad event subscriber will decrypt your entity parameter at loading. This means that your forms
and form fields will already be decrypted.

The onFlush and postFlush event subscribers will check if encryption is enabled, and encrypt the data
before entry to the database.

So, in normal CRUD operation you do not need to do anything in the controller for encrypting or decrypting
the data.

## Step 5: Decrypt in services and controllers

You can of course inject the EncryptorInterface service any time into classes
either by using autowiring or defining the injection in your service definitions.

```php
<?php
    use SpecShaper\EncryptBundle\Encryptors\EncryptorInterface;
    ...
    /**
     * @var SpecShaper\EncryptBundle\Encryptors\EncryptorInterface;
     */
    private $encryptor;
    ...
    
    // Inject the Encryptor from the service container at class construction
    public function __construct(EncryptorInterface $encryptor)
    {
        $this->encryptor = $encryptor;
    }
    
    // Inject the Encryptor in controller actions.
    public function editAction(EncryptorInterface $encryptor)
    {
        ...
        // An example encrypted value, you would get this from your database query.
        $encryptedValue = "3DDOXwqZAEEDPJDK8/LI4wDsftqaNCN2kkyt8+QWr8E=<ENC>";
        
        $decrypted = $encryptor->decrypt($encryptedValue);
        ...
    }


```

Or you can dispatch the EncryptEvent.

```php
<?php
    ...
    use SpecShaper\EncryptBundle\Event\EncryptEvent;
    use SpecShaper\EncryptBundle\Event\EncryptEvents;
    use Symfony\Component\EventDispatcher\EventDispatcherInterface;
    ...
    
    public function indexAction(EventDispatcherInterface $dispatcher)
    {
        ...
        // An example encrypted value, you would get this from your database query.
        $event = new EncryptEvent("3DDOXwqZAEEDPJDK8/LI4wDsftqaNCN2kkyt8+QWr8E=<ENC>");

        $dispatcher->dispatch(EncryptEvents::DECRYPT, $event);
        
        $decrypted = $event->getValue();
    }
```

## Step 5: Decrypt in templates

If you query a repository using a select with an array result 
then the doctrine onLoad event subscriber will not decyrpt any encrypted
values.

In this case, use the twig filter to decrypt your value when rendering.

```
{{ employee.bankAccountNumber | decrypt }}
```


