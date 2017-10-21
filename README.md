# SpecShaper Tax Bundle

The SpecShaperVatBundle provides system for logging, assigning and responding to user issues.

Features include:

- Written for Symfony verison 3.x.x
- Provides a service for validating VAT numbers.
- Provides a javascript file and ajax route for validating.

**Warning**
- This bundle has not been unit tested.
- It has only been running on a Symfony2 v3.0.1 project, and not backward
compatibility tested.

Features road map:

- [ ] Generate events
- [ ] Add tests

Possible wish list:

- Translation files are required for frontend 
- Support for MongoDB/CouchDB ODM or Propel

## Documentation

The source of the documentation is stored in the `Resources/doc/` folder
in this bundle.

## License

This bundle is under the MIT license. See the complete license in the bundle:

    Resources/meta/LICENSE

## About

TokenBundle has been written for the [SpecShaper](http://about.specshaper.com) and [Parolla](http://parolla.ie) websites to keep track of user issues and debugging.

## Reporting an issue or a feature request

Issues and feature requests are tracked in the [Github issue tracker](https://github.com/mogilvie/HelpBundle/issues).

When reporting a bug, it may be a good idea to reproduce it in a basic project
built using the [Symfony Standard Edition](https://github.com/symfony/symfony-standard)
to allow developers of the bundle to reproduce the issue by simply cloning it
and following some steps.

# Installation

## Step 1: Download the bundle

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```bash
$ composer require specshaper/ticket-bundle dev-master
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
            new SpecShaper\VatBundle\SpecShaperVatBundle(),
        );
        // ...
    }
    // ...
}
```

## Step 2: Install and Enalbe SOAP

This bundle requires SOAP to query the VIES service for EU VAT codes.

Find extension=php_soap.dll in php.ini and remove the semicolon(;)

## Step 2: Configure the bundle

To configure the minimum settings you need to define the orm. Only Doctrine is
supported at the moment.

Configure doctrine for an additional connection, make sure that the parameters are defined in parameters.yml:
```
// app/config/config.yml

# Doctrine Configuration
doctrine:
    dbal:
        default_connection: default
        connections:
            default:
                # Your AppBundle connection here.
            ticket:
                driver:   pdo_mysql
                host:     '%database_host_ticket%'
                port:     '%database_host_ticket%'
                dbname:   '%database_host_ticket%'
                user:     '%database_host_ticket%'
                password: '%database_host_ticket%'
                charset:  utf8mb4
                logging: false
    orm:
            auto_generate_proxy_classes: "%kernel.debug%"
            default_entity_manager: default
            entity_managers:
                default:
                    naming_strategy: doctrine.orm.naming_strategy.underscore
                    connection: default
                    auto_mapping: true
                ticket:
                    naming_strategy: doctrine.orm.naming_strategy.underscore
                    connection: ticket
                    mappings:
                        SpecShaperVatBundle: ~
```
                
Configure the TicketBundle and define your bundle user class.

```yml
// app/config/config.yml

# Ticket bundle
spec_shaper_vat:
    model_manager_name: doctrine.orm.ticket_entity_manager
    user_class: AppBundle\Entity\User
```

You will also need to enable translations if not already configured:

```yml
// app/config/config.yml
framework:
    translator:      { fallbacks: ["%locale%"] }
```
## Step 3: Create the entities

The bundle requires entities to interact with the database and store information.
- Ticket
- TicketReply

Create the database using the commands:
```
$ bin/console doctrine:database:create --connection=ticket

$ bin/console doctrine:schema:update --force --em=ticket
```

Entities must extend the base superclass, and implement the relevant interface.
The entities must use the provided repositories within the bundle. Unless you wish
to override the repositories.

## Step 4: Define the routes

Define any routing that you prefer. The controller can be placed behind a firewall
by defining a prefix protected by your security firewalls.

```yml
// app/config/routing.yml

spec_shaper_notification:
    resource: "@SpecShaperNotificationBundle/Controller/"
    type:   annotation

```

## Step 5: Integrate into one of your twig templates

The bundle requires:
- Jquery
- Bootstrap 
- DatePicker

The links to css and js files are generated by including twig templates.

A typical twig template extending a bundle base.html.twig.

```twig
{# app/Resources/views/calendar/calendar.html.twig #}

{% extends 'base.html.twig' %}


{% block stylesheets %}
    
    {% include 'SpecShaperVatBundle:Calendar:styles.html.twig' %}    
    <style>
      #calendar-holder{
          width: 50%;
          height: 200px;
      }
    </style>
    
{% endblock %}

{% block body %}

    {% include 'SpecShaperCalendarBundle:Calendar:calendar.html.twig' %}    

{% endblock %}

{% block javascripts %}

    {% include 'SpecShaperCalendarBundle:Calendar:javascript.html.twig' %} 
 
    <script>
        Calendar.init({
            loader: "{{ url('calendar_loader') }}",
            new: "{{url('event_new')}}",
            update: "{{ url('event_update', {'id' : 'PLACEHOLDER'} ) }}",
            updateDateTime: "{{ url('event_updatedatetime', {'id' : 'PLACEHOLDER'} ) }}",
            delete: "{{ url('event_delete', {'id' : 'PLACEHOLDER'} ) }}",
            deleteSeries: "{{ url('event_deleteseries', {'id' : 'PLACEHOLDER'} ) }}",
        });
    </script>

{% endblock %}
```

## Step 7: Customise

### Change appearance

Menu trees may not match the style of your liking. Override the menu twig file
in app\Resources\SpecSaperHelpBundle\views\Doc\helpMenu.html.twig

More substantial style changes may need css overridden
in app\Resources\SpecSaperHelpBundle\views\Doc\helpMenu.html.twig


### Open Help Document At The Current Page

If you're using KnpMenuBundle then you might create a link to the menu page in
the navbar

```
    $menu['User']->addChild('UserGuide', array(
            'label' => 'nav.userGuide',
            'route' => 'help_page',
            'routeParameters' => array('page' => $request->get('_route');),
        ))
        ->setAttribute('id', 'helpDocumentButton')
    ;
```

Alternatively, as a link in a twig template:

```
<a href="{{ path( 'help_page', {'page': app.request.attributes.get('_route')}) }}" target="_blank">Click to open help</a>
```

note that "target="_blank" opens a new tab.

### Context Modal Help Pop-ups

Best to locate these items inside the layout.html.twig or equivalent top level
twig file for your website.

Insert css for context help cusor

```css
    .context-help  {
        cursor:help;
    } 
```

Any dom element with a help topic requires an additional class and data attribute

```
    <a class="context-help" data-helpid="cat_show.funny">Funny cats found here.</a>
```

where "cat_show" might be the controller route and "funny" is a field on the rendered page.


Include the help modal somewhere near the top of the page body content.

```
    <!-- Include New Ticket modal -->
      {{ include('@SpecShaperTax/Ticket/newTicketModal.html.twig') }}
```

You can override the modal in app\Resources\SpecSaperHelpBundle\views\Doc\helpModal.html.twig

Include javascript for bootstrap, and SpecShaperHelpBundle (this method uses assetic)
Note that jQuery is also required to be called before bootstrap.

```
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
    {% javascripts
        '@AppBundle/Resources/public/js/bootstrap.min.js'
        '@SpecShaperHelpBundle/Resources/public/js/specshaperhelpbundle-inlinehelp.js'

    %}
    <script src="{{ asset_url }}" type="text/javascript"></script>
```

Initialise the inline help js class. with the id for the helpModal and your
path to the help controller modal action.

```
        InlineHelp.init({
            helpModal: $('#helpModal'),
            loadPageUrl: "{{ path('help_modal', {'page': 'PLACEHOLDER'}) }}",
        });

```

### Create a new help topic based on current route

If you're using KnpMenuBundle then you might create a link to draft new help pages
wrapped in a security role admin check of course!

```
    if ($security->isGranted('ROLE_PREVIOUS_ADMIN')) {
        $menu['User']->addChild('DraftHelp', array(
                'label' => 'nav.createHelpPage',
                'route' => 'help_draft',
                'routeParameters' => array('page' => $request->get('_route');),
            ))
            ->setAttribute('id', 'helpDocumentButton')
        ;
    }
```

Note that you will need to define $this->security somehow from the container.

```
    $security = $this->container->get('security.authorization_checker');
```

Alternatively, write the route directly into the url address bar as:

"http:\\your.domain\help\controller_action\draft"

Where the "controller_action" is the route for the pages action.
Or controller_action.field if you want a field type on the page.