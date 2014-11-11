# SilverStripe Event Management Module
The event management module allows you to manage event details and registrations from within the CMS.

[![Build Status](https://travis-ci.org/jedateach/silverstripe-eventmanagement.svg?branch=1.2)](https://travis-ci.org/jedateach/silverstripe-eventmanagement)

## Features

*   Allow people to register, view their registration details and un-register
    for events.
*   Attach multiple ticket types to each event. Each ticket can have its own
    price, go on sale in different time ranges and have different quantities
    available.
*   Hold event tickets during the registration process to ensure they are
    available.
*   Require email confirmation for confirming free event registrations, or
    canceling a registration.
*   Send registered users a notification email when event details change.
*   Generate ticket files that a user can download and print.
*   Invite people to the event, either from member groups or from past events.
*   Send reminder emails a fixed time before an event starts.

## Maintainer Contacts

*   Andrew Short (<andrew@silverstripe.com.au>)
*   Jeremy Shipman (<jeremy@burnbright.net>)

## Requirements
*   SilverStripe 3.1+
*   The [Event Calendar](https://github.com/unclecheese/EventCalendar) module.
*   The [MultiForm](https://github.com/silverstripe/silverstripe-multiform) module.
*   The [ItemSetField](https://github.com/ajshort/silverstripe-itemsetfield) module.
*   Optionally requires the [Omnipay](https://github.com/burnbright/silverstripe-omnipay) module for collecting payments with registration.
*   Optionally requires the [Queued Jobs](https://github.com/nyeholt/silverstripe-queuedjobs) module for sending out event reminder emails.

## Installation

See [Installation](https://github.com/ajshort/silverstripe-eventmanagement/wiki/Installation).

## Project Links
*   [GitHub Project Page](https://github.com/ajshort/silverstripe-eventmanagement)
*   [Issue Tracker](https://github.com/ajshort/silverstripe-eventmanagement/issues)
