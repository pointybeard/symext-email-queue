# Email Queue Extension for Symphony CMS

An extensions for [Symphony CMS][ext-Symphony CMS] that adds an email queue system for sending transactions emails via supported 3rd party providers.

-   [Installation](#installation)
    -   [With Git and Composer](#with-git-and-composer)
    -   [With Orchestra](#with-orchestra)
-   [Basic Usage](#basic-usage)
-   [About](#about)
    -   [Requirements](#dependencies)
    -   [Dependencies](#dependencies)
-   [Documentation](#documentation)
-   [Support](#support)
-   [Contributing](#contributing)
-   [License](#license)

## Installation

This is an extension for [Symphony CMS][ext-Symphony CMS]. Add it to the `/extensions` folder of your Symphony CMS installation, then enable it though the interface.

### With Git and Composer

```bash
$ git clone --depth 1 https://github.com/pointybeard/symext-email-queue.git emailqueue
$ composer update -vv --profile --no-dev --no-cache -d ./emailqueue
```
After finishing the steps above, enable "Email Queue" though the administration interface or, if using [Orchestra][ext-Orchestra], with `bin/extension enable emailqueue`.

### With Orchestra

1. Add the following extension definition to your `.orchestra/build.json` file in the `"extensions"` block:

```json
    ...
    {
        "name": "emailqueue",
        "repository": {
            "url": "https://github.com/pointybeard/symext-email-queue.git",
            "target": "emailqueue"
        }
    }
    ...
```

2. If necessary, also add the required extensions. This must go ABOVE the emailqueue definition:

```json
    {
        "repository": {
            "url": "https://github.com/pointybeard/settings.git"
        }
    },
    {
        "repository": {
            "url": "https://github.com/symphonycms/uniqueinputfield.git"
        }
    },
    {
        "repository": {
            "url": "https://github.com/pointybeard/uuidfield.git"
        }
    },
    {
        "repository": {
            "url": "https://github.com/symphonycms/numberfield.git"
        }
    },
    {
        "repository": {
            "url": "https://github.com/symphonists/association_field.git"
        }
    },
```

2. Run the following command to rebuild your Extensions

```bash
$ orchestra build \
    --skip-import-sections \
    --database-skip-import-data \
    --database-skip-import-structure \
    --skip-create-author \
    --skip-seeders \
    --skip-git-reset \
    --skip-postbuild
```

## Basic Usage

@todo

## About

### Requirements

-   This extension works with PHP 7.3 or above.
-   The following Symphony CMS extensions are required:
    -   [Console][req-console]
    -   [Settings][req-settings]
    -   [Number Field][req-numberfield]
    -   [UUID Field][req-uuidfield]
    -   [Unique Text Input Field][req-uniqueinputfield]
    -   [Association Field][req-association_field]

### Dependencies

This extension depends on the following Composer libraries:

-   [PHP Helpers][dep-helpers]
-   [Symphony Section Class Mapper][dep-classmapper]
-   [Symphony CMS: Extended Base Class Library][dep-symphony-extended]
-   [Symphony CMS: Section Builder][dep-section-builder]
-   [Postmark-PHP][dep-postmark]

## Documentation

Read the [full documentation here][ext-docs].

## Support

If you believe you have found a bug, please report it using the [GitHub issue tracker][ext-issues],
or better yet, fork the library and submit a pull request.

## Contributing

We encourage you to contribute to this project. Please check out the [Contributing to this project][doc-CONTRIBUTING] documentation for guidelines about how to get involved.

## Author
-   Alannah Kearney - <https://github.com/pointybeard>
-   See also the list of [contributors][ext-contributor] who participated in this project

## License
"Email Queue Extension for Symphony CMS" is released under the MIT License. See [LICENCE][doc-LICENCE] for details.

[doc-CONTRIBUTING]: https://github.com/pointybeard/symext-email-queue/blob/master/CONTRIBUTING.md
[doc-LICENCE]: http://www.opensource.org/licenses/MIT
[req-console]: https://github.com/pointybeard/console
[req-settings]: https://github.com/pointybeard/settings
[req-numberfield]: https://github.com/symphonycms/numberfield
[req-uuidfield]: https://github.com/pointybeard/uuidfield
[req-uniqueinputfield]: https://github.com/symphonycms/uniqueinputfield
[req-association_field]: https://github.com/symphonists/association_field
[dep-helpers]: https://github.com/pointybeard/helpers
[dep-postmark]: https://github.com/wildbit/postmark-php
[dep-classmapper]: https://github.com/pointybeard/symphony-classmapper
[dep-symphony-extended]: https://github.com/pointybeard/symphony-extended
[dep-section-builder]: https://github.com/pointybeard/symphony-section-builder
[ext-issues]: https://github.com/pointybeard/symext-email-queue/issues
[ext-Symphony CMS]: http://getsymphony.com
[ext-Orchestra]: https://github.com/pointybeard/orchestra
[ext-contributor]: https://github.com/pointybeard/symext-email-queue/contributors
[ext-docs]: https://github.com/pointybeard/symext-email-queue/blob/master/.docs/toc.md
