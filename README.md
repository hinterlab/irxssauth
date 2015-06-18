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

Simply install this module using the composer and run the dev/build, as well as flush the site. 

## irxssauth.yml

The internetrix staff records are stored on www.internetrix.net. The IRXDBUser is database username, IRXDBPassword is the database password, IRXDBName is the name of the database, IRXServerIP is the IP address of www.internetrix.net (currently hosted on delta350), IRXSiteDomain is the domain and has to use the https to encrupt the data sent, IRXSiteAPIURL is the API url. Change the variables in the irxssauth.myl file when there are any changes with the hosting details or the API of RESTfulAPI module on www.internetrix.net.

