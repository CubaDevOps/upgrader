# PHP Upgrader

A PHP script to manage upgrades for your project using different repository providers.

[![Automatic Versioning and Release](https://github.com/CubaDevOps/upgrader/actions/workflows/semver.yml/badge.svg)](https://github.com/CubaDevOps/upgrader/actions/workflows/semver.yml)

## Table of Contents

- [State of the Art](#state-of-the-art)
- [How it Works](#how-it-works)
- [Requirements](#requirements)
- [Installation](#installation)
- [Features](#features)
- [Configuration](#configuration)
- [Usage](#usage)
- [Contributing](#contributing)
- [License](#license)

## State of the Art

Composer is the most widely used package manager for PHP projects. However, while it allows you to update your project's
dependencies, it doesn't offer a way to upgrade the project itself. This script is designed to address that limitation
by providing a method to upgrade your project's root to a specific version, the latest version, or the highest minor
version available.

## How it Works

The script connects to the API of a configured provider to fetch the latest release information for a specified
repository. It then downloads the release's artifact files and extracts them into the project directory. Additionally,
the script allows users to exclude certain resources from the upgrade process, giving them more control and helping to
prevent conflicts with existing files.

## Requirements

- PHP 7.4 or higher
- Composer
- Zip extension
- Json extension
- Curl extension

## Installation

To install the project, use Composer:

```bash
composer require cubadevops/upgrader
```

## Features

- Show available update versions
- Upgrade safely to the highest minor version
- Upgrade to the latest version
- Upgrade to a specific version

## Configuration

The script requires a configuration file to be present in the root of your project. The configuration file should be
named `upgrader.json` or `upgrader.json.dist` and should contain the following:

```json
{
  "repository_provider": "github",
  "repository_identifier": "owner/repository",
  "project_dir": "/var/www/html",
  "has_root_directory": true,
  "excluded_resources": [
    "tests",
    ".github"
  ]
}
```

_repository_provider_: The repository provider to use. Currently, only `github` is supported but more providers will be
added soon.

_repository_identifier_: The owner and repository name separated by a slash.

_project_dir_: The directory where the project is located. Usually this is the directory where the `composer.json` file
is located.

_has_root_directory_: `true` if the artifact files are compressed into a root directory that acts as a container for all
files, `false` otherwise.

_excluded_resources_: An array of resources to exclude from the upgrade process. This can be directories or files.

## Usage

Run the script from the command line. Below are the available commands:

__Help__

`vendor/bin/upgrader --help|-h`

__Show Available Versions__

`vendor/bin/upgrader show-candidates`

__Upgrade to a Specific Version__

`vendor/bin/upgrader upgrade <version>`

__Upgrade to the Latest Version__

`vendor/bin/upgrader upgrade-to-latest`

__Upgrade Safely to the Highest Minor Version__

`vendor/bin/upgrader upgrade-safely`

## Contributing

Contributions are always welcome! Please open an issue or a pull request.

## License

This project is licensed under the MIT License - see the [LICENSE](./LICENSE) file for details.