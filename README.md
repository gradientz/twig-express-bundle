# KaliopTwigExpressBundle

Browse and render “static” Twig templates in a Symfony project.
This bundle is a port of the [TwigExpress](https://github.com/kaliop/twig-express) tool, and is intended for private front-end prototypes. You might like it if you’re a designer or front-end developer working with Symfony. *Do not use this bundle or its routes in production!*

## Features

1.  Gives access to the Twig templates in the `Resources/views/static` folder of a bundle, showing index pages for this folder and subfolders. (Note that the root folder can be changed for each bundle.)
2.  Renders Twig templates, and reports Twig errors with an extract of the faulty template.

**Important:** this bundle’s controller will *not* be able to render templates that depend on data provided by other existing controllers. It’s intended for “static” prototypes which don’t depend on any data from databases or services.

## Installation

Install with [Composer](https://getcomposer.org/) in your Symfony project:

```
composer require kaliop/twig-express-bundle
```

## Getting started

Add this to your routes (for example in `routing_dev.yml`):

```yaml
twig_express:
    resource: "@KaliopTwigExpressBundle/Resources/config/routing.yml"
```

Then configure `twig_express.bundles` (for example in `config_dev.yml`) with a list of bundles whose “static” templates you want to explore:

```yaml
twig_express:
    bundles: [ MyStaticBundle, AwesomeStaticBundle ]
```

Finally, navigate to `http://[your-hostname]/static/` to see a list your bundles which have `static` views.

## Demo pages

This bundle contains its own demo `static` templates, which demonstrate a few added features. To activate it, add this import to your `config_dev.yml`:

```yaml
imports:
    - { resource: "@KaliopTwigExpressBundle/Resources/config/test.yml" }
```

## Advanced configuration

For each bundle, instead of providing the bundle’s name only, you can change the URL slug (the part that identifies this bundle in the URL) and the path to the bundle’s “static” templates:

```yaml
twig_express:
    bundles:
        # Name is required and must be a valid bundle;
        # root and slug will use fallback values if not defined.
        -   name: MyStaticBundle
            root: Resources/views/static-html
            slug: ohmy
        -   name: AwesomeStaticBundle
            root: Resources/components
            slug: awesome
```

If you would like to use a different URL base than `/static/…`, use the `twig_express.url_base` parameter:

```yaml
parameters:
    twig_express.url_base: something-different
```
