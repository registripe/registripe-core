<?php

/**
 * Calculate the cost of a given registration
 * 
 * @package silverstripe-eventmanagement
 */
class EventRegistrationCostCalculator{

	protected $registration;
	protected $storeattendeecost = true;

	public function __construct(EventRegistration $registration) {
		$this->registration = $registration;
	}

	public function calculate() {
		$cost = 0;
		foreach($this->registration->Attendees() as $attendee) {
			$attendeecost = $this->calculateAttendeeCost($attendee);
			if($this->storeattendeecost){
				$attendee->Cost = $attendeecost;
				$attendee->write();
			}
			$cost += $attendeecost;
		}

		return $cost;
	}

	public function setStoreAttendeeCost($store = true){
		$this->storeattendeecost = $store;

		return $this;
	}

	protected function calculateAttendeeCost(EventAttendee $attendee) {
		$cost = 0;
		$ticket = $attendee->Ticket();
		if($ticket->hasPrice()){
			$cost += $ticket->obj('Price')->getAmount();
		}

		return $cost;
	}

	public function getDiscountableAttendees() {
		return $this->registration->Attendees()
			->innerJoin("EventTicket", "\"EventAttendee\".\"TicketID\" = \"EventTicket\".\"ID\"")
			->where("PriceAmount > 0");
	}

}
