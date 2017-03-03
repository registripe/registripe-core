<?php

namespace EventRegistration\Calculator;

/**
 * Calculate the cost of a given registration
 */
class CostComponent extends AbstractComponent{

	public function calculateAttendee(\EventAttendee $attendee, $cost) {
		$ticket = $attendee->Ticket();
		if($ticket->hasPrice()){
			$cost += $ticket->obj('Price')->getAmount();
		}
		return $cost;
	}

}
