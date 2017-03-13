# Neontabs 8

**Module Framework For TABS Based Cottage Websites**

*(Note: This set of modules is under active development and will likely change radically come final release)*

The aim of this project is to create a series of modules which, when tied together, will provide a Drupal 8 site author with the ability and flexibility to quickly develop and iterate upon a [TABS](http://www.tabs-software.co.uk/)
 based site.
 
 Currently the core of this project is under development; with the core modules being created currently. Over time this description will expand with more information as the project develops.
 
 ## Neontabs Core Modules
 | Name                                     | Description                              | Introduced |
 | ---------------------------------------- | ---------------------------------------- | ---------- |
 | [Neontabs 8 Property](https://github.com/neontribe/nt8/blob/master/nt8property/nt8property.info.yml)                      | Provides a "Property" content type and all the necessary functionality wrapped in a service in order to load a property (via fixture or the Drupal Batch API) from TABS. | Initial Release        |
 | [Neontabs 8 Search](https://github.com/neontribe/nt8/blob/master/nt8search/nt8search.info.yml)                        | This will provide a service which exposes all the necessary functionality to easily interact with the TABS property search API along with a base search form and block implementation (will be expanded) | Initial Release        |
 | [Neontabs 8 TabsIO](https://github.com/neontribe/nt8/blob/master/nt8tabsio/nt8tabsio.info.yml)                        | Provides the core service used to interact with the tabs api. Handles all requests made to and from the API. | Initial Release        |
 | [Neontabs 8 Images](https://github.com/neontribe/nt8/blob/master/nt8images/nt8images.info.yml)                         | Under Development                        | Initial Release        |
 
 ## Current Installation Procedure
 *(**Could change at any time**)*
 
 ```
    Initial Setup:
      -> Make sure that you have a fresh install of drupal ready.
      -> Make sure that you have a recent version of Drush installed.
      -> Make sure that you have a recent version of Composer installed.
      
    1) Clone this repository into your Drupal installation ./modules directory.
    2) `composer install` in the root of your drupal directory
    3) composer require address && drush en address imagecache_external -y
    3.5) drush cr
    4) drush en nt8tabsio -y
    5) drush en nt8property -y
    6) drush en nt8search -y
    
```
 **Please log any errors or problems which you may encounter as an issue - please remember that this is at a very early stage of development so a lot of things may be broken/not working.**

