<?php

namespace EventRegistration\Tests;

class RegistrableEvent_ControllerTest extends \FunctionalTest{
	
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

	public function testRegisterAction() {
		$this->markTestIncomplete();
	}

	// TODO:
	// register
	// unregister
	// registration - view details

}