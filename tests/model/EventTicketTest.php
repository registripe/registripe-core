<?php

namespace EventRegistration\Tests;

use \EventTicket;

class EventTicketTest extends \SapphireTest {

	protected static $fixture_file = array(
		'../fixtures/Tickets.yml'
	);

	public function testCMSFields() {
		// smoke test
		EventTicket::create()->getCMSFields();
	}
	
	public function testAvailabilityByDate() {
		$ticket = EventTicket::create();
		$ticket->populateDefaults();

		$ticket->StartDate = date("Y-m-d", strtotime("+1 day"));
		$this->assertFalse($ticket->isAvailable(), "Start date tomorrow means unavailable");
		$ticket->StartDate = date("Y-m-d", strtotime("-1 day"));
		$this->assertTrue($ticket->isAvailable(), "Start date yesterday means available");
		$ticket->StartDate = null;

		$ticket->EndDate = date("Y-m-d", strtotime("-1 day"));
		$this->assertFalse($ticket->isAvailable(), "Finish date yesterday means unavailable");
		$ticket->EndDate = date("Y-m-d", strtotime("+1 day"));
		$this->assertTrue($ticket->isAvailable(), "Finish date tomorrow means available");
	}

}