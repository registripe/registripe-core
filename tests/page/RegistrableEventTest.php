<?php

namespace EventRegistration\Tests;

class RegistrableEventTest extends \FunctionalTest{
	
	protected static $fixture_file = array(
		'../fixtures/EventManagement.yml',
		'../fixtures/Tickets.yml'
	);

	public function setUp() {
		parent::setUp();
		$this->event = $this->objFromFixture('RegistrableEvent', 'event');
		$this->event->publish('Stage', 'Live');
	}

	public function testVisitEventPage(){
		$page = $this->get('test-event');
		$this->assertEquals(200, $page->getStatusCode());
	}

	public function testCMSFields() {
		$this->markTestIncomplete("Test CMS Fields");
	}

	public function testAvailableTickets() {
		$ticketevent = $this->objFromFixture('RegistrableEvent', 'ticketevent');
		$ticketevent->publish('Stage', 'Live');
		$tickets = $ticketevent->getAvailableTickets();
		$this->assertEquals(2, $tickets->count(), "Two ticket types qualify currently");
	}

}