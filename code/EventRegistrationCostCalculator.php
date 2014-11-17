<?php

/**
 * Calculate the cost of a given registration
 * 
 * @package silverstripe-eventmanagement
 */
class EventRegistrationCostCalculator{

	protected $registration;

	public function __construct(EventRegistration $registration) {
		$this->registration = $registration;
	}

	public function calculate() {
		$cost = 0;
		foreach($this->registration->Attendees() as $attendee) {
			$cost += $this->calculateAttendeeCost($attendee);
		}

		return $cost;
	}

	protected function calculateAttendeeCost(EventAttendee $attendee) {
		$cost = 0;
		$ticket = $attendee->Ticket();
		if($ticket->hasPrice()){
			$cost += $ticket->obj('Price')->getAmount();
		}

		return $cost;
	}

}