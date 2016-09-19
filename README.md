# TwigExpressBundle

Browse and render “static” Twig templates in a Symfony project. This bundle is a port of the [TwigExpress](https://github.com/gradientz/twig-express) tool, and is intended for private front-end prototypes. You might like it if you’re a designer or front-end developer working with Symfony.

**Warning:** do not use this bundle or its routes in production (`prod` environment)!

## Features

1.  Gives access to a bundle’s `Resources/views/static` folder.
2.  Shows index pages for this folder and subfolders.
3.  Renders Twig templates, and reports Twig errors with an extract of the faulty template.

Note that this bundle’s routes will *not* be able to render templates that depend on data provided by existing controllers. It’s intended for “static” prototypes which don’t depend on any data from databases or services.

## Installation

*This part assumes that you have some basic knowledge about [Composer](https://getcomposer.org/) and `composer.json` config.*

Add `gradientz/twig-express-bundle` to your dependencies:

```json
{
	"repositories": [
        { "type": "vcs", "url": "https://github.com/gradientz/twig-express-bundle" }
    ],
    "require": {
        "gradientz/twig-express-bundle": "~1.0"
    }
}
```

## Getting started

(1) Put your “static” templates in the `Resources/views/static` folder of your bundle(s).

(2) Add this to your routes (for example in `routing_dev.yml`):

```
twig_express:
    resource: "@TwigExpressBundle/Resources/config/routing.yml"
```

(3) Make sure your bundle(s) with `static` views are listed in Assetic's configuration:

```
assetic:
    bundles:
        - AcmeDefaultBundle
        - MyStaticBundle
        - AwesomeStaticBundle
```

## Demo pages

This bundle contains its own demo `static` templates. To activate the demo, add this import to your config:

```
imports:
    - { resource: "@TwigExpressBundle/Resources/config/demo.yml" }
```

(It’s a simple config file that adds this bundle to `assetic.bundles`, and declares a Twig global variable. Feel free to imitate this pattern to create static bundle-specific config that is easy to plug in.)

Then navigate to `http://hostname/static/` to see a list your bundles which have `static` views.
