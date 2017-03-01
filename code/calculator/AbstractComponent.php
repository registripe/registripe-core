<?php

namespace EventRegistration\Calculator;

abstract class AbstractComponent implements ComponentInterface{

	public function calculateAttendee(\EventAttendee $attendee, \EventRegistration $registration, $total) {
		return $total;
	}

	public function calculateRegistration(\EventRegistration $registration, $total) {
		return $total;
	}

}