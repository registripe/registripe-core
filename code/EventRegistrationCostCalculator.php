<?php

/**
 * Calculate the cost of a given registration
 * 
 * @package silverstripe-eventmanagement
 */
class EventRegistrationCostCalculatorComponent extends EventRegistrationCalculatorBaseComponent{

	protected $storeattendeecost = true;

	public function setStoreAttendeeCost($store = true){
		$this->storeattendeecost = $store;
		return $this;
	}

	public function calculateAttendee(EventAttendee $attendee, EventRegistration $registration, $cost) {
		$ticket = $attendee->Ticket();
		if($ticket->hasPrice()){
			$cost += $ticket->obj('Price')->getAmount();
		}
		if($this->storeattendeecost){
				$attendee->Cost = $cost;
				$attendee->write();
			}
		return $cost;
	}

}
