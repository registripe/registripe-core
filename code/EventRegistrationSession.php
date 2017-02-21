<?php

/**
 * Creates, stores, retrieves and deletes the current registration.
 */
class EventRegistrationSession{

	protected $event;

	public function __construct($event) {
		$this->event = $event;
	}

	protected function sessionKey() {
		return "EventRegistration.".$this->event->ID;
	}

	public function get() {
		return EventRegistration::get()->byID(
			Session::get($this->sessionKey())
		);
	}

	public function set($registration) {
		Session::set($this->sessionKey(), $registration->ID);
	}

	public function start() {
		$registration = EventRegistration::create();
		$registration->EventID = $this->event->ID;
		$registration->write();
		$this->set($registration);
		return $registration;
	}

	public function end() {
		Session::set($this->sessionKey(), null);
		Session::clear($this->sessionKey());
	}

}