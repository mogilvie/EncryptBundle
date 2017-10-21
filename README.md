# SpecShaper Encrypt Bundle

A bundle to handle encoding and decoding of parameters using OpenSSL and Doctrine lifecycle events.  

Features include:

- Written for Symfony verison 3.x.x
- Uses OpenSSL
- Uses Lifecycle event

**Warning**
- This bundle has not been unit tested.
- It has only been running on a Symfony2 v3.0.1 project, and not backward
compatibility tested.

Features road map:

- [ ] Expand parameters to allow selection of encoding method
- [ ] Create CLI commands
- [ ] Handle DateTime data types via the bundle.


## Documentation

The source of the documentation is stored in the `Resources/doc/` folder
in this bundle.

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

Geneate a 256 bit 32 character key and add it to your parameters file.

```
// app/config/parameters.yml

    ...
    encrypt_key: <your_key_here>
    
```

## Step 3: Create the entities
Add the Annotation entity to the declared classes in the entity.


```
use SpecShaper\EncryptBundle\Annotations\Encrypted;
```

Add the annotation '@Encrypted' to the parameters that you want encrypted.
```
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

```
    /**
     * A users date of birth
     
     * @Encrypted
     * @ORM\Column(type="string", nullable=true)
     */
    protected $dateOfBirth;
   
```
