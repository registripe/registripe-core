<?php
/**
 * A form for registering for events which collects the desired tickets and
 * basic user details, then requires the user to pay if needed, then finally
 * displays a registration summary.
 *
 * @package silverstripe-eventmanagement
 */
class EventRegisterForm extends MultiForm {

	//public static $start_step = 'EventRegisterTicketsStep';
	public static $start_step = 'EventRegistrationDetailsStep';

	public function __construct($controller, $name) {
		$this->controller = $controller;
		$this->name       = $name;

		parent::__construct($controller, $name);

		if ($expiryfield = $this->getExpiryField()) {	
			$this->fields->push($expiryfield);
		}
	}

	protected function getExpiryField(){
		if($expires = $this->getExpiryDateTime()){
			Requirements::javascript(THIRDPARTY_DIR . '/jquery/jquery.js');
			Requirements::add_i18n_javascript('eventmanagement/javascript/lang');
			Requirements::javascript('eventmanagement/javascript/EventRegisterForm.js');

			$message = _t('EventManagement.PLEASECOMPLETEREGWITHIN',
				'Please complete your registration within %s. If you do not,'
				. ' the places that are on hold for you will be released to'
				. ' others. You have %s remaining');

			$remain = strtotime($expires->getValue()) - time();
			$hours  = floor($remain / 3600);
			$mins   = floor(($remain - $hours * 3600) / 60);
			$secs   = $remain - $hours * 3600 - $mins * 60;

			$remaining = sprintf(
				'<span id="registration-countdown">%s:%s:%s</span>',
				str_pad($hours, 2, '0', STR_PAD_LEFT),
				str_pad($mins, 2, '0', STR_PAD_LEFT),
				str_pad($secs, 2, '0', STR_PAD_LEFT)
			);

			return new LiteralField('CompleteRegistrationWithin', sprintf(
				"<p id=\"complete-registration-within\">$message</p>",
				$expires->TimeDiff(), $remaining));
		}
	}

	/**
	 * @return SS_Datetime
	 */
	public function getExpiryDateTime() {
		if ($this->getSession()->RegistrationID) {
			$created = strtotime($this->getSession()->Registration()->Created);
			$limit = $this->controller->getEvent()->RegistrationTimeLimit;
			if ($limit){
				return DBField::create_field('SS_Datetime', $created + $limit);	
			} 
		}
	}

	/**
	 * Handles validating the final step and writing the tickets data to the
	 * registration object.
	 */
	public function finish($data, $form) {
		$result = parent::finish($data, $form);

		// support validation of the parent
		if($result === false) {
			return;
		}

		$registration = $this->session->getRegistration();
		if(!$tickets || !isset($tickets['tickets'])) {
			$validate = $registration->getTicketQuantities();
		} else {
			$validate = $tickets['Tickets'];
		}

		// Check that the requested tickets are still available.
		if (!$this->validateTickets($validate, $form)) {
			Session::set("FormInfo.{$form->FormName()}.data", $form->getData());
			return $this->controller->redirectBack();
		}

		$this->session->delete();

		// If the registrations is already valid, then send a details email.
		if ($registration->Status == 'Valid') {
			$this->emailRegistration($registration);
		}

		$this->extend('onRegistrationComplete', $registration);

		return $this->controller->redirect(Controller::join_links(
			$this->getController()->getEvent()->Link(),
			'registration',
			$registration->ID,
			'?token=' . $registration->Token
		));
	}

	protected function emailRegistration($registration){
		$email = EventRegistrationDetailsEmail::factory($registration);
		$this->attachTicketFile($email, $registration);
		$email->send();
		$adminemail = EventRegistration::config()->admin_notification_email;
		if($adminemail){
			$email = EventAdminNotificationEmail::factory($registration);
			$email->setTo($adminemail);
			$email->send();
		}
	}

	/**
	 * Attach a ticket file, if it exists
	 */
	protected function attachTicketFile($email, $registration){
		if ($generator = $registration->Event()->TicketGenerator) {
			$generator = new $generator();

			$path = $generator->generateTicketFileFor($registration);
			$name = $generator->getTicketFilenameFor($registration);
			$mime = $generator->getTicketMimeTypeFor($registration);

			if ($path) {
				$email->attachFile($path, $name, $mime);
			}
		}
	}

	/**
	 * Validates that the tickets requested are available and valid.
	 *
	 * @param  array $tickets A map of ticket ID to quantity.
	 * @param  Form  $form
	 * @return bool
	 */
	public function validateTickets($tickets, $form) {
		$event = $this->controller->getEvent();
		$session  = $this->getSession();

		// Loop through each ticket and check that the data entered is valid
		// and they are available.
		foreach ($tickets as $id => $quantity) {
			$ticket = $event->Tickets()->byID($id);
			$avail = $ticket->getAvailability($session->RegistrationID);
			$avail = $avail['available'];
			if (!$avail) {
				$form->addErrorMessage(
					'Tickets',
					sprintf(
						_t('EventRegisterForm.NONEAVAILABLE', 
							'%s is currently not available.'),
						$ticket->Title
					),
					'required');
				return false;
			}
			if (is_int($avail) && $avail < $quantity) {
				$form->addErrorMessage(
					'Tickets',
					sprintf(
						_t('EventRegisterForm.NOTENOUGHAVAILABLE', 
							'There are only %d of "%s" available.'),
						$avail,
						$ticket->Title
					),
					'required');
				return false;
			}
			if ($ticket->MinTickets && $quantity < $ticket->MinTickets) {
				$form->addErrorMessage('Tickets',sprintf(
					_t('EventRegisterForm.UNDERMINIMUMQUANTITY', 
						'You must purchase at least %d of "%s".'),
					$ticket->MinTickets, $ticket->Title), 'required');
				return false;
			}
			if ($ticket->MaxTickets && $quantity > $ticket->MaxTickets) {
				$form->addErrorMessage('Tickets', sprintf(
					_t('EventRegisterForm.OVERMAXIMUMQUANTITY', 
						'You can only purchase at most %d of "%s".'),
					$ticket->MaxTickets, $ticket->Title), 'required');
				return false;
			}
		}
		// Then check the sum of the quantities does not exceed the overall
		// event capacity.
		if ($event->Capacity) {
			$avail = $event->getRemainingCapacity($session->RegistrationID);
			$totalquantity = array_sum($tickets);

			if ($totalquantity > $avail) {
				$message = sprintf(
					_t(
						'EventRegisterForm.OVERTOTALCAPACITY', 
						'The event only has %d overall places remaining, but you '
						. 'have requested a total of %d places. Please select a '
						. 'lower number.'
					),
					$avail, $request
				);
				$form->addErrorMessage('Tickets', $message, 'required');
				return false;
			}
		}

		return true;
	}

	/**
	 * Override the MultiForm setSession function so we can use our own session.
	 */
	protected function setSession() {
		$this->session = $this->getCurrentSession();

		// If there was no session found, create a new one instead
		if(!$this->session) {
			$this->session = new EventRegisterFormSession();
			$this->session->setForm($this);
			$this->session->Hash = $this->controller->request->getVar('MultiFormSessionID');
			$this->session->write();
		} else {
			$this->session->setForm($this);
		}

		// Create encrypted identification to the session instance if it doesn't exist
		if(!$this->session->Hash) {
			$this->session->Hash = sha1($this->session->ID . '-' . microtime());
			$this->session->write();
		}

		// after generating the hash, if the url does not contain the hash then
		// redirect them to it
		if($this->controller->request->requestVar('MultiFormSessionID') !== $this->session->Hash) {
			return $this->controller->redirect(Controller::join_links(
				$_SERVER['REQUEST_URI'],
				'?MultiFormSessionID='. $this->session->Hash
			));
		}
	}

}
