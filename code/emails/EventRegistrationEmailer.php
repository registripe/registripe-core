<?php

class EventRegistrationEmailer{
	
	protected $registration;

	public function __construct(EventRegistration $registration) {
		$this->registration = $registration;
	}

	public function sendConfirmation() {
		$email = EventRegistrationDetailsEmail::factory($this->registration);
		$this->attachTicketFile($email);
		return $email->send();
	}

	/**
	 * Attach a ticket file, if it exists
	 */
	protected function attachTicketFile($email){
		if ($generator = $this->registration->Event()->TicketGenerator) {
			$generator = new $generator();
			$path = $generator->generateTicketFileFor($this->registration);
			$name = $generator->getTicketFilenameFor($this->registration);
			$mime = $generator->getTicketMimeTypeFor($this->registration);
			if ($path) {
				$email->attachFile($path, $name, $mime);
			}
		}
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
