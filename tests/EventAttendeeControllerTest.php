<?php

class EventAttendeeControllerTest extends FunctionalTest{

	protected static $fixture_file = array(
		'fixtures/EventManagement.yml'
	);

	public function setUp() {
		parent::setUp();
		$this->objFromFixture('Calendar', 'calendar')->publish('Stage', 'Live');
		$this->event = $this->objFromFixture('RegistrableEvent', 'event');
		$this->event->publish('Stage', 'Live');
	}

	public function testAddAttendeeTicket(){
		$ticket = $this->objFromFixture('EventTicket', 'ticket_a');
		$page = $this->get('test-event/register/attendee/add/'.$ticket->ID); // pre-selected ticket
		$this->assertEquals(200, $page->getStatusCode());
	}

	// make sure you can't choose tickets from other events
	

}