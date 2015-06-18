# SilverStripe IRXSSAuth

## Requirements

* SilverStripe 3.1
* [RESTful API](https://github.com/colymba/silverstripe-restfulapi) on the https://www.internetrix.net/ website.

## Maintainers

* Yuchen Liu (yuchen.liu@internetrix.com.au)

## Description

This module replaces the default member login authenticator with an customized authenticaor. It implements irx staff member login authentication when a user is trying to use a @internetrix.com.au email, and the user is either an irxstaff or doesn't exist in the production website.

## Installation with [Composer](https://getcomposer.org/)

```composer require "silverstripe-modules/irxssauth"```

## Usage

Install this module using the composer then run the dev/build and flush the website. 

## irxssauth.yml

The internetrix staff records are stored on www.internetrix.net. The access details are configued in irxssauth.yml file. IRXDBUser is the database username, IRXDBPassword is the database password, IRXDBName is the name of the database, IRXServerIP is the IP address of www.internetrix.net (currently hosted on delta350), IRXSiteDomain is the domain and has to use the https to encrupt the data sent, IRXSiteAPIURL is the API url. Change the variables in the irxssauth.yml file should there any changes of the hosting server or the API of RESTfulAPI module are made on www.internetrix.net.

