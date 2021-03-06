# WCB, the Bootstrapper and Apache docker container for Winter CMS 

`wcb` is a command line tool and a docker container that enables you to reconstruct an Winter CMS installation
from a single configuration file and run it in place.

It can be used to quickly bootstrap a local development environment for a project.

## Credits
`wcb` is a fork of [ocb](https://github.com/foerster-werbung/ocb)
which is a fork of [OFFLINE-GmbH/oc-bootstrapper](https://github.com/OFFLINE-GmbH/oc-bootstrapper).

`oc-bootstrap`'s `october.yaml` is compatible with `wcb`'s `winter-cms.yaml`. No changes required.

The docker container is build on top of [aspendigital/docker-octobercms](https://github.com/aspendigital/docker-octobercms).

## Features

### wcb
* Installs and updates private and public plugins (via Git or Marketplace)
* Makes sure only necessary files are in your git repo by intelligently managing your `.gitignore` file
* Built in support for GitLab CI deployments
* Built in support for shared configuration file templates
* Sets sensible configuration defaults using `.env` files for production and development environments
* Enable to seed/migrate database and storage folder

### Container
* Runs wcb in docker container 
* Automatic install/update/seed the project during startup of the container
* Makes sure that everything inside of WinterCMS is in a correct state, and if not it reports you back.


## Docker image

An official Docker image that bundles `wcb`, `composer` and `Envoy` is available on [hub.docker.com](https://hub.docker.com/r/foersterwerbung/wcb) as `foersterwerbung/wcb`.


## Usage

### Quickstart

You can find some examples in the `examples` folder.

#### 1. Create a `docker-compose.yaml` file in your project root:
```
version: '2.2'
services:
  web:
    image: foersterwerbung/wcb:latest
    ports:
      - 80:80
    environment:
      - WCB_INSTALL=true
    depends_on:
      - mysql
    volumes:
      - "./:/var/www/html"

  mysql:
    image: mysql:5.7
    ports:
      - 3306:3306
    environment:
      - MYSQL_ROOT_PASSWORD=wintercms
      - MYSQL_DATABASE=wintercms
```
#### 2. Start the web container.

In your project directory you'll find an `winter-cms.yaml` file. Edit its contents.

Simply add a theme name and your ready to go.

Start the `web` container again. Check the output of the container to see whats happening.

If `WCB_INSTALL=true` is set, it will download a fresh copy of Winter Cms.
It will also install the defined plugins and themes that you have 
configured in your `winter-cms.yaml`.

After the init phase, an apache server runs in the foreground.


## Configuration

### Theme and Plugin syntax

`wcb` enables you to install plugins and themes from your own git repo. Simply
append your repo's address in `()` to tell `wcb` to check it out for you.
If no repo is defined the plugins are loaded from the Winter Marketplace.


#### Examples

```yaml
# Install a plugin from the official Winter Marketplace
- Offline.Mall 

# Install a plugin from a git repository. The plugin will be cloned
# into your local repository and become part of it. You can change the
# plugin and modify it to your needs. It won't be checked out again (no updates).
- Offline.Mall (https://github.com/Offline-GmbH/oc-mall-plugin.git)

# The ^ marks this plugin as updateable. It will be removed and checked out again
# during each call to `winter install`. Local changes will be overwritten.
# This plugin will stay up to date with the changes of your original plugin repo.
- ^Offline.Mall (https://github.com/Offline-GmbH/oc-mall-plugin.git)

# Install a specific branch of a plugin. Keep it up-to-date.
- ^Offline.Mall (https://github.com/Offline-GmbH/oc-mall-plugin.git#develop)
```

### Project seeding

You can seed a project with 
```
wcb seed
```
You have to set a database or a storage folder that should be applied.
```
seed:
    database: dev/migrations
    storage: dev/storage
```
It executes all `.sql` files in the database folder and copies the storage folder into the current project root

You can set 
```
    environment:
      - WCB_SEED=true
```
to apply the `odb seed` command once during the init phase.
 
 
### Scheduler and Queues
 
 To enable a scheduler for Winter CMS just add `WCB_CRON=true` to your `docker-composer.yaml` file.
 
 ```
     environment:
       - WCB_CRON=true
 ```
 
 Make sure that your commands configured in the `Plugin.php`
 
 ```
 class Plugin extends PluginBase {

    public function registerSchedule($schedule)
    {
        $schedule->command('queue:work --tries=25 --once')->everyMinute();
    }
}
 ```
 
 
### Variables for the container
 
Following environment variables can be used from [docker-octobercms](https://github.com/aspendigital/docker-octobercms#docker-entrypoint).

Do not use the `Winter CMS app environment config` from [docker-octobercms](https://github.com/aspendigital/docker-octobercms#october-cms-app-environment-config)
since the project is init and handled by wcb.

#### WCB Variables
| Variable | Default | Action |
| -------- | ------- | ------ |
| WCB_INSTALL | false | `true` installs winter cms and dependencies during the first startup. |
| WCB_SEED | false | `true` executes `wcb seed` during the startup once. |
| WCB_CRON | false | `true` starts a cron process within the container which enables the scheduler for Winter CMS |


#### Docker Winter CMS Variables
| Variable | Default | Action |
| -------- | ------- | ------ |
| ENABLE_CRON | false | use `WCB_CRON` instead |
| FWD_REMOTE_IP | false | `true` enables remote IP forwarding from proxy (Apache) |
| GIT_CHECKOUT |  | Checkout branch, tag, commit within the container. Runs `git checkout $GIT_CHECKOUT` |
| GIT_MERGE_PR |  | Pass GitHub pull request number to merge PR within the container for testing |
| INIT_OCTOBER | false | `true` runs october up on container start |
| INIT_PLUGINS | false | `true` runs composer install in plugins folders where no 'vendor' folder exists. `force` runs composer install regardless. Helpful when using git submodules for plugins. |
| PHP_DISPLAY_ERRORS | off | Override value for `display_errors` in docker-oc-php.ini |
| PHP_MEMORY_LIMIT | 128M | Override value for `memory_limit` in docker-oc-php.ini |
| PHP_POST_MAX_SIZE | 32M | Override value for `post_max_size` in docker-oc-php.ini |
| PHP_UPLOAD_MAX_FILESIZE | 32M | Override value for `upload_max_filesize` in docker-oc-php.ini |
| UNIT_TEST |  | `true` runs all Winter CMS unit tests. Pass test filename to run a specific test. |
| VERSION_INFO | false | `true` outputs container current commit, php version, and dependency info on start |
| XDEBUG_ENABLE | false | `true` enables the Xdebug PHP extension |
| XDEBUG_REMOTE_HOST | host.docker.internal | Override value for `xdebug.remote_host` in docker-xdebug-php.ini |



#### Install additional plugins

If at any point in time you need to install additional plugins, simply add them to your `winter-cms.yaml` and re-run 
`wcb install`. Missing plugins will be installed.

You can also restart the container.
  
### Update Winter CMS

If you want to update the installation you can run

```
wcb update
```

### Push changes to remote git repo

To push local changes to the current git remote run 

```
wcb push
```

## Files that should be tracked with git
This is the minimal set that you need for your team:
```
.gitignore
docker-compose.yaml
october.yaml
```

If you have custom plugins or themes with migrations and storage folder:
```
dev
    migrations
    storage
plugins
    custom-vendor  
        custom-plugin
storage
    app
    cms
    framework
    logs
    .gitignore   
themes
    my-theme
.gitignore
docker-compose.yaml
winter-cms.yaml
```
with this `.gitignore`:
```
*
!.gitignore
!composer.*
composer.phar
!.env.*
!winter-cms.yaml
!Envoy.blade.php
!.gitlab-ci.yml
!themes
!themes/**/*
!plugins
!plugins/custom-vendor
!plugins/custom-vendor/custom-plugin
!plugins/custom-vendor/custom-plugin/*
!plugins/custom-vendor/custom-plugin/**/*
!dev
!dev/**/*
!docker-compose.yaml
!storage
!storage/**/*
```


 

## TODO

* Deployments 
* `wcb` as composer binary that can be used outside of this docker container.
