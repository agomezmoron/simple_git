# simple_git

CONTENTS OF THIS FILE ---------------------
   
 * Introduction Requirements Recommended modules Installation Configuration
 * Troubleshooting FAQ Maintainers

INTRODUCTION ------------ Drupal module to have a simple interface to connect
your Git services accounts.

REQUIREMENTS ------------

* This module requires REST capabilities enabled. CORS should be enabled if you
* will use from an external domain.

RECOMMENDED MODULES -------------------

* [REST UI](https://www.drupal.org/project/restui) module to handle it easily.

INSTALLATION ------------
 
 * Install as you would normally install a contributed Drupal module.

CONFIGURATION -------------
 
 * Configure the GitHub & GitLab apps on  user permissions in Administration »
 * Configuration » Web services » Simple Git. Enable the REST resources of the
 * module: Git Account Resource Git Connector Resource
  
When enabled, the module will register some permissions & some resources, so
remember refreshing the cache.

You can get more information about the REST interfaces in the following
[link](REST_INTERFACES.md).

TROUBLESHOOTING ---------------

If you have issues connecting to the Git services, ensure all the needed
information is properly configured.

MAINTAINERS -----------

Current maintainers: Aleandro Gómez (agomezmoron) -
https://drupal.org/u/agomezmoron Carlos Raigada (craigada) -
https://drupal.org/u/craigada Estefanía Barrera (ebarrera) -
https://www.drupal.org/u/ebarrera Mª Ángeles Villalba (mavillalba) -
https://www.drupal.org/u/mavillalba

This project has been sponsored by: 3Emergya
