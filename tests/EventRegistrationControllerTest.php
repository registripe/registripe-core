<?php

class EventRegisterControllerTest extends FunctionalTest{

	protected static $fixture_file = array(
		'fixtures/EventManagement.yml'
	);

	public function setUp() {
		parent::setUp();
		$this->objFromFixture('Calendar', 'calendar')->publish('Stage', 'Live');
		$this->event = $this->objFromFixture('RegistrableEvent', 'event');
		$this->event->publish('Stage', 'Live');
	}

	public function tearDown() {
		parent::tearDown();
		$this->autoFollowRedirection = true;
	}

	public function testIndex() {
		$response = $this->get('calendar/test-event/register');
		$this->assertEquals(200, $response->getStatusCode());
	}

	public function testAttendee() {
		$response = $this->get('calendar/test-event/register/attendee');
		$this->assertEquals(200, $response->getStatusCode());
		$this->markTestIncomplete("Should be a redirect here");
	}

	public function testReviewRedirect() {
		$this->autoFollowRedirection = false;
		$response = $this->get('calendar/test-event/register/review');
		$this->assertEquals(302, $response->getStatusCode(), "Should redirect if no rego started");
		$actual = $response->getHeader('Location');
		$this->assertEquals(Director::baseURL() .'calendar/test-event/register', $actual, "Should redirect to register");
	}

	public function testSubmitReview() {
		$this->markTestIncomplete();
	}

	public function testPayment() {
		$this->markTestIncomplete();
	}

	public function testComplete() {
		$this->markTestIncomplete();
	}

}