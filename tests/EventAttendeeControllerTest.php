<?php

class EventAttendeeControllerTest extends FunctionalTest{

	protected static $fixture_file = array(
		'fixtures/EventManagement.yml'
	);

	protected static $disable_themes = true;

	
	protected function resetExtensions() {
		// remove any extensions that may conflict with base module code
		foreach(array(
			"EventAttendee", "EventRegistration", "EventTicket",
			"EventRegisterController", "EventAttendeeController"
		 ) as $class) {
			Config::inst()->remove($class, 'extensions');
		}
		// add back base extensions
		Config::inst()->update('EventRegistration', 'extensions', ['Payable']);
	}

	public function setUp() {
		$this->resetExtensions();
		parent::setUp();
		$this->objFromFixture('Calendar', 'calendar')->publish('Stage', 'Live');
		$this->event = $this->objFromFixture('RegistrableEvent', 'event');
		$this->event->publish('Stage', 'Live');

		// force session registration
		$this->registration = $this->objFromFixture('EventRegistration', 'reg_a');
		Session::set("EventRegistration.".$this->event->ID, $this->registration->ID);
	}

	public function testAddWithTicket() {
		$ticket = $this->objFromFixture('EventTicket', 'ticket_a');
		$page = $this->get('calendar/test-event/register/attendee/add/'.$ticket->ID); // pre-selected ticket
		$this->assertEquals(200, $page->getStatusCode());

		$response = $this->submitForm("EventAttendeeForm_AttendeeForm", "action_save", array(
			"FirstName" => "Foo",
			"Surname" => "Bar",
			"Email" => $email = "foo.bar@example.com"
		));

		$attendees = $this->registration->Attendees();
		$this->assertEquals(2, $attendees->count());
		$latestattendee = $attendees->filter('Email', $email)->first();
		$this->assertEquals("Foo", $latestattendee->FirstName);
		$this->assertEquals("Bar", $latestattendee->Surname);

		// assert registration has started with attndee details
		// assert validation faliulres?
		// make sure you can't choose tickets from other events
	}

	public function testAddWithoutTicket() {
		$this->markTestIncomplete();
	}

	public function testEditAttendee() {
		$attendee = $this->objFromFixture('EventAttendee', 'attendee_reg_a_1');
		$page = $this->get('calendar/test-event/register/attendee/edit/'.$attendee->ID);
		$this->assertEquals(200, $page->getStatusCode());
	}

	public function testSave() {
		$this->markTestIncomplete();
	}

	public function testDelete() {
		$this->markTestIncomplete();
	}

}