<?php

class EventRegistrationCostCalculator{

	protected $registration;

	public function __construct(EventRegistration $registration) {
		$this->registration = $registration;
	}

	public function calculate(){
		$amount = 0;
		$currency = "";
		$tickets = $this->registration->getTicketQuantities();
		if ($tickets) {
			foreach ($tickets as $id => $quantity) {
				$ticket = EventTicket::get()->byID($id);
				$price  = $ticket->obj('Price');
				if ($ticket->Type == 'Free' || !$quantity) {
					continue;
				}
				$amount  += $price->getAmount() * $quantity;
				$currency = $price->getCurrency();
			}
		}

		return $amount;
	}

}