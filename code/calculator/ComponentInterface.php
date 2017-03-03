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
	 * @return float
	 */
	public function calculateAttendee(EventAttendee $attendee, $total);

	/**
	 * Second, each caulator works out and manipulates total registration cost.
   * @param float incoming total
	 * @return float updated total
	 */
	public function calculateRegistration($total);

}
