# Digital Skills and Jobs Platform sample install

<p>An installation profile for a Digital Skills and Jobs Platform installation, a one-stop-shop for digital skills and jobs in the EU.

Provides custom modules, a custom theme and a configuration for all the functionality of DSJP.
</p>

## 1. Development

We recommend that you install this profile as part of the DSJP project distribution  using Composer.

If the profile will be installed on a different Drupal project, please follow the instructions below:


In your project’scomposer.json file make sure the following properties are set as shown:
```bash
"minimum-stability": "dev",
"prefer-stable": true,
```

and that you are using "drupal/core": "^9.4"

Add the following to the repositories section of composer.json:

```bash
  "repositories": {
        "drupal": {
            "type": "composer",
            "url": "https://packages.drupal.org/8"
        },
        "assets": {
            "type": "composer",
            "url": "https://asset-packagist.org"
        },
        "chosen": {
            "type": "package",
            "package": {
                "name": "harvesthq/chosen",
                "version": "1.8.7",
                "type": "drupal-library",
                "source": {
                    "url": "https://github.com/harvesthq/chosen-package.git",
                    "type": "git",
                    "reference": "v1.8.7"
                }
            }
        },
        "jquery-timepicker": {
            "type": "package",
            "package": {
                "name": "bower-asset/timepicker",
                "version": "1.11.14",
                "type": "drupal-library",
                "source": {
                    "url": "https://github.com/jonthornton/jquery-timepicker.git",
                    "type": "git",
                    "reference": "1.11.14"
                }
            }
        },
        "slick": {
            "type": "package",
            "package": {
                "name": "kenwheeler/slick",
                "version": "1.8.1",
                "type": "drupal-library",
                "dist": {
                    "url": "https://github.com/kenwheeler/slick/archive/v1.8.1.zip",
                    "type": "zip"
                }
            }
        },
        "blazy": {
            "type": "package",
            "package": {
                "name": "dinbror/blazy",
                "version": "1.8.2",
                "type": "drupal-library",
                "extra": {
                    "installer-name": "blazy"
                },
                "source": {
                    "type": "git",
                    "url": "https://github.com/dinbror/blazy",
                    "reference": "1.8.2"
                }
            }
        },
        "flag-icons": {
            "type": "package",
            "package": {
                "name": "alexsobolenko/flag-icons",
                "version": "1.0",
                "type": "drupal-library",
                "dist": {
                    "url": "https://github.com/alexsobolenko/flag-icons/archive/master.zip",
                    "type": "zip"
                }
            }
        },
        "jquery-ui-touch-punch": {
            "type": "package",
            "package": {
                "name": "furf/jquery-ui-touch-punch",
                "version": "master",
                "type": "drupal-library",
                "dist": {
                    "url": "https://github.com/furf/jquery-ui-touch-punch/archive/refs/heads/master.zip",
                    "type": "zip"
                }
            }
        },
        "colorbutton": {
            "type": "package",
            "package": {
                "name": "ckeditor/colorbutton",
                "version": "4.16.1",
                "type": "drupal-library",
                "dist": {
                    "url": "https://download.ckeditor.com/colorbutton/releases/colorbutton_4.16.1.zip",
                    "type": "zip"
                }
            }
        },
        "panelbutton": {
            "type": "package",
            "package": {
                "name": "ckeditor/panelbutton",
                "version": "4.16.1",
                "type": "drupal-library",
                "dist": {
                    "url": "https://download.ckeditor.com/panelbutton/releases/panelbutton_4.16.1.zip",
                    "type": "zip"
                }
            }
        },
        "sparql_entity_storage": {
            "type": "git",
            "url": "https://git.drupalcode.org/project/sparql_entity_storage.git"
        },
        "editorplaceholder": {
            "type": "package",
            "package": {
                "name": "ckeditor/editorplaceholder",
                "version": "4.16.2",
                "type": "drupal-library",
                "dist": {
                    "url": "https://download.ckeditor.com/editorplaceholder/releases/editorplaceholder_4.16.2.zip",
                    "type": "zip"
                }
            }
        },
        "oe_dashboard_agent": {
            "type": "vcs",
            "url": "https://github.com/openeuropa/oe_dashboard_agent"
        }
    },
```
```bash
Add extra patches:
        "patches": {
            "openeuropa/oe_theme": {
                "Infinite loop - Maximum call stack size exceeded.": "./web/profiles/contrib/dsjp/patches/oe_theme_ecl_datepicker_infinite_loop.patch",
                "Pager breaks on ajax requests": "./web/profiles/contrib/dsjp/patches/oe_theme_pager_crash_ajax_pages.patch"
            },
            "drupal/legal": {
                "Separate extra fields from the main additional checkboxes and add update trigger": "./web/profiles/contrib/dsjp/patches/legal_extra_checkboxes.patch",
                "Preserve CAS module returnto destiantion": "./web/profiles/contrib/dsjp/patches/legal_cas_returnto.patch"
            },
            "openeuropa/webtools-geocoding-provider": {
                "Change webtools geocoding provider endpoint": "./web/profiles/contrib/dsjp/patches/change_geocoding_url.patch"
            },
            "drupal/group_permissions": {
                "Fix issue when group is missing but there are permissions set.": "./web/profiles/contrib/dsjp/patches/group_permissions_deleted_group.patch"
            },
            "drupal/views_infinite_scroll": {
                "Views infinite scroll VBO workaround": "./web/profiles/contrib/dsjp/patches/views_infinite_scroll_vbo.patch"
            },
            "drupal/private_message": {
                "Fix view mode class wrapper": "./web/profiles/contrib/dsjp/patches/private_message_view_mode.patch"
            },
            "openeuropa/oe_authentication": {
                "Show delete account actions on cancel account page": "./web/profiles/contrib/dsjp/patches/oe_authentication_show_cancel_action.patch"
            },
            "drupal/mentions": {
                "Null argument on MentionsFilter getMentions()": "./web/profiles/contrib/dsjp/patches/mentions_null_argument.patch"
            },
            "easyrdf/easyrdf": {
                "https://github.com/easyrdf/easyrdf/issues/390": "./web/profiles/contrib/dsjp/patches/update_easyrdf.patch",
            }
        }
```

Require the digit/dsjp package for downloading all the modules, themes and configuration that come with the installation profile:

```bash
docker-compose exec web composer require digit/dsjp --update-with-dependencies

docker-compose exec web ./vendor/bin/run toolkit:build-dev

docker-compose exec web ./vendor/bin/drush site-install dsjp_profile
```

What does the installation profile do?

Drupal is installed in the web directory.

The profile is placed in web/profiles/contrib/dsjp

Installation:

Request access to

Add SSH key to your Github account.

Steps

Run the installation
```bash
docker-compose up -d

docker-compose exec web composer install
```
Make sure the profile is installed:
```bash
docker-compose exec web composer require digit/dsjp --update-with-dependencies

docker-compose exec web ./vendor/bin/run toolkit:build-dev

docker-compose exec web ./vendor/bin/drush site-install dsjp_profile
```
Done! Visit  http://test:8080/web
