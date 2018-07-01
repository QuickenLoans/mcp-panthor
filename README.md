# MCP Panthor

[![CircleCI](https://circleci.com/gh/quickenloans-mcp/mcp-panthor.svg?style=svg)](https://circleci.com/gh/quickenloans-mcp/mcp-panthor)
[![Code Climate](https://codeclimate.com/github/quickenloans-mcp/mcp-panthor/badges/gpa.svg)](https://codeclimate.com/github/quickenloans-mcp/mcp-panthor)
[![Codacy Badge](https://api.codacy.com/project/badge/Grade/72c07f5cc84147818e07938970637902)](https://www.codacy.com/app/skluck/mcp-panthor)
[![Codacy Badge](https://api.codacy.com/project/badge/Coverage/72c07f5cc84147818e07938970637902)](https://www.codacy.com/app/skluck/mcp-panthor)
[![Latest Stable Version](https://poser.pugx.org/ql/mcp-panthor/version)](https://packagist.org/packages/ql/mcp-panthor)
[![License](https://poser.pugx.org/ql/mcp-panthor/license)](https://packagist.org/packages/ql/mcp-panthor)

A thin PHP microframework built on Slim and Symfony.

Slim + Symfony = :revolving_hearts:

Panthor uses the simplicity of Slim and provides a bit more structure for applications with additional Symfony
components. Utilities and helpers are provided to simplify template caching and dependency injection using Symfony
Dependency Injection and Slim. It can be used for html applications, APIs, or both.

- [slim/slim](https://github.com/slimphp/Slim) - The core microframework.
- [symfony/config](https://github.com/symfony/config) - Cascading configuration to handle merging multiple config files.
- [symfony/dependency-injection](https://github.com/symfony/dependency-injection) - A robust and flexible dependency injection container.
- [symfony/dotenv](https://github.com/symfony/dotenv) - Use environment variables for configuration
- [symfony/yaml](https://github.com/symfony/yaml) - Use YAML for configuration
- [twig/twig](https://github.com/twig/twig) - The standard in PHP templating engines

Here's a few of the features Panthor provides:

- Standard interfaces for Controllers, Middleware, and Templates
- Error Handling (with Content Negotiation)
- Cookie Encryption with Libsodium
- A simple Session interface to store PHP session data in cookies.
- DI Configuration using Symfony PHP Fluent format and routes with YAML
- Support for [HTTP Problem](https://tools.ietf.org/html/draft-ietf-appsawg-http-problem)
- Utilities for Unit Testing
- Utilities for Templating

## Table of Contents

- [Compatibility](#compatibility)
- [Installation](#installation)
    - [Quick Start](#quick-start)
- [Documentation](#documentation)
- [Dependencies](#dependencies)
    - [Optional Dependencies](#optional-dependencies)

## Compatibility

- **Panthor 1**
    - Slim `~2.0`
    - Symfony `~2.0`
- **Panthor 2**
    - Slim `~2.0`
    - Symfony `~2.0`
- **Panthor 3**
    - Slim `~3.3`
    - Symfony `~3.0 || ~4.0`
    - PHP `~5.6 || ~7.0`
- **Panthor 3.3**
    - Slim `~3.10`
    - Symfony `~4.0`
    - PHP `~7.1`

## Installation

The following command will clone this project and set up a simple skeleton. See the files used in the [starter-kit](starter-kit/).

```
composer create-project ql/mcp-panthor --no-install --remove-vcs
```

Never used Composer, Slim or Symfony before? Here are some resources:
- [Composer - Getting Started](https://getcomposer.org/doc/00-intro.md)
- [Symfony Book - Service Container](http://symfony.com/doc/current/book/service_container.html)
- [Slim Framework v3 documentation](http://www.slimframework.com/docs)

### Quick Start

The following will clone this project, bootstrap your application with the [starter-kit](starter-kit/). Afterwards,
just install dependencies and start the app with the built-in PHP webserver.
```
composer create-project ql/mcp-panthor hello-world --no-install --remove-vcs
cd hello-world
composer install
php -s localhost:8888 -t public
```

Now just visit `localhost:8888` and Panthor should start right up!

## Documentation

- [How To Use](docs/basic-usage.md)
  > Explanations of controllers and middleware, as well as services injected into the DI Container by Panthor.

- [Application Structure](docs/application-structure.md)
  > Details on where code and configuration goes.

- [Error Handling](docs/error-handling.md)
  > How to use the included error handler and logger.
