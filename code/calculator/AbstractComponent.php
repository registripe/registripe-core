<?php

namespace EventRegistration\Calculator;

abstract class AbstractComponent implements ComponentInterface{

	protected $registration;

	public function __construct(\EventRegistration $registration) {
		$this->registration = $registration;
	}

	/**
	 * Runnig total for given attendee
	 */
	public function calculateAttendee(\EventAttendee $attendee, $total) {
		return $total;
	}

	/**
	 * Running total for registration
	 */
	public function calculateRegistration($total) {
		return $total;
	}

}