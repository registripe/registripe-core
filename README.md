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
*   Invite people to the event, either from member groups or from past events.

## Configuration

When working with multiple attendees, you can carry over specific fields from the previous attendee. Here is an example configuration:
```yaml
EventAttendee:
  prepopulated_fields:
    - Organisation
```

If you extend the available EventAttendee db fields, you can define which ones are required by default in the add/edit form:
```yaml
EventAttendee:
  required_fields:
    - FirstName
    - Surname
    - Email
    - OrganisationID
    - Gender
```
Note that the TicketID will always be required.

## Maintainer Contacts

*   Jeremy Shipman (<jeremy@burnbright.net>)

## Requirements
*   SilverStripe 3.1+
*   [Omnipay](https://github.com/burnbright/silverstripe-omnipay) module for collecting payments with registration.

## Installation

See [Installation](https://github.com/jedateach/silverstripe-eventmanagement/wiki/Installation).

## Project Links
*   [GitHub Project Page](https://github.com/jedateach/silverstripe-eventmanagement)
*   [Issue Tracker](https://github.com/jedateach/silverstripe-eventmanagement/issues)
