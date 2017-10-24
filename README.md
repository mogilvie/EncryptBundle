# SpecShaper Encrypt Bundle

A bundle to handle encoding and decoding of parameters using OpenSSL and Doctrine lifecycle events.  

Features include:

- Written for Symfony verison 3.x.x
- Uses OpenSSL
- Uses Lifecycle events

**Warning**
- This bundle has not been unit tested.
- It has only been running on a Symfony2 v3.0.1 project, and not backward
compatibility tested.

Features road map:

- [x] Create a factory method to expand for different encryptors
- [x] Create a twig function to decrypt encoded values
- [ ] Expand parameters to allow selection of encoding method
- [ ] Create CLI commands
- [ ] Handle DateTime data types via the bundle.

## License

This bundle is under the MIT license. See the complete license in the bundle:

    Resources/meta/LICENSE

## About

EncryptBundle has been written for the [SpecShaper](http://about.specshaper.com) and [Parolla](http://parolla.ie) websites
to encode users private data. The bundle will be expanded as part of a larger EU GDPR data management bundle.

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
 
Copy the key into your parameters file.

```yaml
// app/config/parameters.yml

    ...
    encrypt_key: <your_key_here>
    
```

A config file entry is not required, however there are some options that
can be configured to extend the bundle.

```yaml
// app/config/config.yml

    ...
    spec_shaper_encrypt:
        subscriber_class: 'AppBundle\Subscribers\OtherSubscriber'
        annotation_classes:
            - 'SpecShaper\EncryptBundle\Annotations\Encrypted'
            - 'AppBundle\Annotations\CustomAnnotation'
```   

## Step 3: Create the entities
Add the Annotation entity to the declared classes in the entity.

```php
<?php
...
use SpecShaper\EncryptBundle\Annotations\Encrypted;
```

Add the annotation '@Encrypted' to the parameters that you want encrypted.
```php
<?php

    /**
     *
     * A PPS number is always 7 numbers followed by either one or two letters.
     *
     * @Encrypted
     * @ORM\Column(type="string", nullable=true)
     */
    protected $taxNumber;
   
```

For DateTime parameters store the date as a string, and use the getters and setters
to convert that string.
You may also need to create a DataTransformer if you are using the parameter in a form
with the DateType formtype.

```php
<?php

    /**
     * A users date of birth    
     * 
     * @Encrypted
     * @ORM\Column(type="string", nullable=true)
     */
    protected $dateOfBirth;
   
```
## Step 4: Decrypt in templates

If you query a repository using a select method, or get an array result 
then the doctrine onLoad event subscriber will not decyrpt any encrypted
values.

In this case, use the twig filter to decrypt your value when rendering.

```
{{ employee.bankAccountNumber | decrypt }}
```

## Step 5: Call the Encryptor service directly

You can of course inject the encryptor service any time into classes
either by using autowiring or defining the injection in your service definitions.

```
/**
 * @var SpecShaper\EncryptBundle\Encryptors\EncryptorInterface;
 */
private $encryptor;

public function __construct(EncryptorInterface $encryptor)
{
    $this->encryptor = $encryptor;
}
```
