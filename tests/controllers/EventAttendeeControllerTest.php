<?php

use EventRegistration\Tests\Config;

class EventAttendeeControllerTest extends FunctionalTest{

	protected static $fixture_file = array(
		'../fixtures/EventManagement.yml'
	);

	protected static $disable_themes = true;

	public function setUp() {
		Config::reset();
		parent::setUp();
		$this->event = $this->objFromFixture('RegistrableEvent', 'event');
		$this->event->publish('Stage', 'Live');
	}
	
	// put existing registration into session
	protected function setUpExistingRegistration() {	
		$this->registration = $this->objFromFixture('EventRegistration', 'reg_a');
		$session = new \EventRegistration\Session($this->event);
		$session->set($this->registration);
	}

	public function testAddAction() {
		$response = $this->get('test-event/register/attendee/add');
		$this->assertEquals(200, $response->getStatusCode());
		// TODO: assert form elements
	}

	public function testAddActionWithTicketSelected() {
		$this->setUpExistingRegistration();
		$ticket = $this->objFromFixture('EventTicket', 'ticket_a');
		$response = $this->get('test-event/register/attendee/add/'.$ticket->ID); // pre-selected ticket
		$this->assertEquals(200, $response->getStatusCode());
		// TODO: assert form elements
	}

	public function testEditAction() {
		$this->setUpExistingRegistration();
		$attendee = $this->objFromFixture('EventAttendee', 'attendee_reg_a_1');
		$response = $this->get('test-event/register/attendee/edit/'.$attendee->ID);
		$this->assertEquals(200, $response->getStatusCode());
	}

	public function testSaveNew() {
		$ticket = $this->objFromFixture("EventTicket", "ticket_a");
		$response = $this->get('test-event/register/attendee/add');
		$response = $this->submitSaveForm($ticket);
		$reg = EventRegistration::get()->sort('ID', 'DESC')->first();
		$attendees = $reg->Attendees();
		$this->assertEquals(1, $attendees->count());
		$latestattendee = $attendees->filter('Email', "foo.bar@example.com")->first();
		$this->assertEquals("Foo", $latestattendee->FirstName);
		$this->assertEquals("Bar", $latestattendee->Surname);
	}

	public function testSaveExisting() {
		$this->setUpExistingRegistration();
		$attendee = $this->objFromFixture('EventAttendee', 'attendee_reg_a_1');
		$this->assertEquals("alice.bob@example.com", $attendee->Email);
		$this->get('test-event/register/attendee/edit/'.$attendee->ID);
		$this->submitSaveData(array(
			"ID" => $attendee->ID,
			"TicketID" => $attendee->TicketID,
			"FirstName" => $attendee->FirstName,
			"Surname" => $attendee->Surname,
			"Email" => "different.email@example.com"
		));
		$attendees = $this->registration->Attendees();	
		$this->assertEquals(1, $attendees->count());
		$attendee = $attendees->first();
		$this->assertEquals("Alice", $attendee->FirstName, "FirstName is same");
		$this->assertEquals("different.email@example.com", $attendee->Email, "Email updated");
	}

	// TODO:
	// assert validation faliulres
	// assert can't choose tickets from other events
	// assert ticket can't be changed

	public function testDelete() {
		// TODO: delete from a multi-attendee rego
		// make sure redirect occurred
		$this->markTestIncomplete();
	}

	public function testDeleteSingle() {
		$this->setUpExistingRegistration();
		$attendee = $this->objFromFixture('EventAttendee', 'attendee_reg_a_1');
		$response = $this->get('test-event/register/attendee/delete/'.$attendee->ID);
		$attendees = $this->registration->Attendees();
		$this->assertEquals(1, $attendees->count(), "Single rego should not be deleted");
	}

	public function testDeleteNotFound() {
		$this->setUpExistingRegistration();
		$attendee = $this->objFromFixture('EventAttendee', 'attendee_reg_a_1');
		$response = $this->get('test-event/register/attendee/delete/999');
		$this->assertEquals(404, $response->getStatusCode());
	}

	// helper for performing submissions
	protected function submitSaveForm($ticket, $attendee = null) {
		$data = array(
			"TicketID" => $ticket->ID, // TicketID is always required by validator
			"FirstName" => "Foo",
			"Surname" => "Bar",
			"Email" => "foo.bar@example.com"
		);
		if ($attendee) {
			$data["ID"] = $attendee->ID;
		}
		return $this->submitSaveData($data);
	}

	protected function submitSaveData($data) {
		return $this->submitForm("EventAttendeeForm_AttendeeForm", "action_save", $data);
	}

}