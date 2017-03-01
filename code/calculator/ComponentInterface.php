<?php

namespace EventRegistration\Calculator;

use \EventAttendee;
use \EventRegistration;

/**
 * Interface for defining calculator components against
 */
interface ComponentInterface {

	/**
	 * First, each calulator works out and manipulates attendee costs.
	 * @param EventAttendee
	 * @param EventRegistration
	 * @return float
	 */
	public function calculateAttendee(EventAttendee $attendee, EventRegistration $registration, $total);

	/**
	 * Second, each caulator works out and manipulates total registration cost.
	 * @return float
	 */
	public function calculateRegistration(EventRegistration $registration, $total);

}
