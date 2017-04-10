<?php

namespace EventRegistration\Tests;

class RegistrableEventTest extends \SapphireTest{
	
	protected static $fixture_file = array(
		'../fixtures/EventManagement.yml',
		'../fixtures/Tickets.yml'
	);

	public function setUp() {
		parent::setUp();
		$this->event = $this->objFromFixture('RegistrableEvent', 'event');
		$this->event->publish('Stage', 'Live');
	}

	public function testCMSFields() {
		$fields = $this->event->getCMSFields();
	}

	public function testAvailableTickets() {
		$ticketevent = $this->objFromFixture('RegistrableEvent', 'ticketevent');
		$ticketevent->publish('Stage', 'Live');
		$tickets = $ticketevent->getAvailableTickets();
		$this->assertEquals(2, $tickets->count(), "Two ticket types qualify currently");
	}

	// TODO: Test
	// canRegister
	// getCompletedRegistrations
	// getUnconfirmedRegistrations
	// getIncompleteRegistrations
	// getCancelledRegistrations
	// getValidAttendees
	// getRemainingCapacity

}