<?php

class EventRegistrationEmailer{
	
	protected $registration;

	public function __construct(EventRegistration $registration) {
		$this->registration = $registration;
	}

	public function sendConfirmation() {
		$email = EventRegistrationDetailsEmail::factory($this->registration);
		return $email->send();
	}

	public function notifyAdmin() {
		$adminemail = EventRegistration::config()->admin_notification_email;
		if($adminemail) {
			$email = EventAdminNotificationEmail::factory($this->registration);
			$email->setTo($adminemail);
			$email->send();
		}
	}

}
