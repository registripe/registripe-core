<?php
/**
 * Allows a user to cancel their registration by entering their email address.
 *
 * @package registripe
 */
class EventUnregisterController extends Page_Controller {

	public static $allowed_actions = array(
		'UnregisterForm',
		'afterunregistration',
		'confirm'
	);

	protected $parent;
	protected $event;

	/**
	 * Constructs a new controller for deleting a registration.
	 *
	 * @param Controller $parent
	 * @param RegistrableEvent $event
	 */
	public function __construct($parent, $event) {
		$this->parent = $parent;
		$this->event   = $event;

		parent::__construct($parent->data());
	}

	/**
	 * @return Form
	 */
	public function UnregisterForm() {
		return new Form(
			$this,
			'UnregisterForm',
			new FieldList(new EmailField(
				'Email', _t('Registripe.EMAIL_ADDRESS', 'Email address')
			)),
			new FieldList(new FormAction(
				'doUnregister', _t('Registripe.UN_REGISTER', 'Un-register')
			)),
			new RequiredFields('Email')
		);
	}

	/**
	 * @param array $data
	 * @param Form  $form
	 */
	public function doUnregister($data, $form) {
		$regos = $this->event->Registrations()->filter('Email', $data['Email']);

		if (!$regos || !count($regos)) {
			$form->sessionMessage(_t(
				'EventManager.NOREGFOREMAIL',
				'No registrations for the email you entered could be found.'),
				'bad');
			return $this->redirectBack();
		}

		if ($this->event->UnRegEmailConfirm) {
			$addr         = $data['Email'];
			$email        = new Email();
			$registration = $regos->First();

			$email->setTo($addr);
			$email->setSubject(sprintf(
				_t('Registripe.CONFIRMUNREGFOR', 'Confirm Un-Registration For %s (%s)'),
				$this->event->Title, SiteConfig::current_site_config()->Title));

			$email->setTemplate('EventUnregistrationConfirmationEmail');
			$email->populateTemplate(array(
				'Registration' => $registration,
				'Event'         => $this->event,
				'SiteConfig'   => SiteConfig::current_site_config(),
				'ConfirmLink'  => Director::absoluteURL(Controller::join_links(
					$this->Link(), 'confirm',
					'?email=' . urlencode($addr), '?token=' . $registration->Token))
			));

			$email->send();
		} else {
			foreach ($regos as $rego) {
				$rego->Status = 'Canceled';
				$rego->write();
			}
		}

		$this->redirect($this->Link('afterunregistration'));
	}

	/**
	 * @return array
	 */
	public function afterunregistration() {
		return array(
			'Title'   => $this->event->AfterUnregTitle,
			'Content' => $this->event->obj('AfterUnregContent')
		);
	}

	/**
	 * @return array
	 */
	public function confirm($request) {
		$email = $request->getVar('email');
		$token = $request->getVar('token');

		// Attempt to get at least one registration with the email and token,
		// and if we do then cancel all the other ones as well.
		$registration = EventRegistration::get()
					->filter("Email", $email)
					->filter("Token", $token)
					->first();

		if (!$registration) {
			return $this->httpError(404);
		}

		// Now cancel all registrations with the same email.
		$regos = EventRegistration::get()
					->filter("Email", $email)
					->filter("ID:not", $registration->ID);
		foreach ($regos as $rego) {
			$rego->Status = 'Canceled';
			$rego->write();
		}

		return array(
			'Title'   => $this->event->AfterConfUnregTitle,
			'Content' => $this->event->obj('AfterConfUnregContent')
		);
	}

	/**
	 * @param  string $action
	 * @return string
	 */
	public function Link($action = null) {
		return Controller::join_links(
			$this->parent->Link(), 'unregister', $action
		);
	}

}