<?php

namespace EventRegistration\Tests;

class Config {

	/**
	 * Prevent conflicts with extensions so tests can be run within same installation.
	 */
	public static function reset() {
		$config = \Config::inst();
		// remove any extensions that may conflict with base module code
		foreach(array(
			"EventAttendee", "EventRegistration", "EventAttendeeForm", "EventTicket",
			"EventRegisterController", "EventAttendeeController"
		 ) as $class) {
			$config->remove($class, "extensions");
		}
		// add back base extensions
		$config->update("EventRegistration", "extensions", array("Payable"));
 		// just use defaults
		$config->remove("EventRegistration", "calculator_components");
		$config->remove("EventAttendee", "required_fields");

		$config->remove("Injector", "EventRegistration\Calculator");

	}

}