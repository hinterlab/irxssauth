# SilverStripe IRXSSAuth

## Requirements

* SilverStripe 3.1
* HTTP_AUTHORIZATION enabled for the CGI version of PHP
* [RESTful API](https://github.com/colymba/silverstripe-restfulapi) on the https://www.internetrix.net/ website.

## Maintainers

* Yuchen Liu (yuchen.liu@internetrix.com.au)

## Description

This module replaces the default member login authenticator with an customized authenticaor. It implements irx staff member login authentication when a user is trying to use a @internetrix.com.au email, and the user is either an irxstaff or doesn't exist in the production website. It also optionally adds BasicAuth to staging sites to prevent indexing and other unwanted visitors.

## Installation with [Composer](https://getcomposer.org/)

```composer require "silverstripe-modules/irxssauth"```

Install this module using the composer then run a dev/build and flush the website. 

Optionally add the following two lines to the top of the root .htaccess file for staging site protection:

`## Enable FCGI HTTP Authorization Header ###`
`SetEnvIf Authorization .+ HTTP_AUTHORIZATION=$0`

Or put this redirect rule as a workaround
`RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]`

## Usage

To enable protection on staging site domains from access by external visitors, put the following in _ss_environment.php file: 

`define('IRX_USE_STAGE_AUTH', true);`
IMPORTANT: Remove or set it to be false when you visit the site for the first time of this session and need to do dev/build or a flush.


This can also trigger a "noindex" tag to be added to pages and prevent staging sites from indexing by search engines like Google, just put this in the < head > of the main Page.ss template:

`<% if $protect_site_from_indexing %><meta name="robots" content="noindex"><% end_if %>`


## irxssauth.yml

The access details are configued in irxssauth.yml file. IRXSiteDomain is the domain to connect to and it has to use https to encrypt the data sent.
It also defines the Staging Domain Featured Strings, these are matched aginst the domain to see if protection should be applied.

## Auth Remember
Successful HTTP auth on the authenticated device will be remembered for 7 days of a certain domain. Any succeful HTTP Auth from another device or in an incognito window will automatically invalid the previously authenticated device.