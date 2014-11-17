<?php
/**
 * Handles collecting the users details and creating a registration to an event
 * for them.
 *
 * @package silverstripe-eventmanagement
 */
class EventRegisterController extends Page_Controller {

	public static $url_handlers = array(
		'' => 'index'
	);

	public static $allowed_actions = array(
		'RegisterForm',
		'confirm'
	);

	protected $parent;
	protected $event;

	/**
	 * Constructs a new controller for creating a registration.
	 *
	 * @param ContentController $parent
	 * @param RegistrableEvent $event
	 */
	public function __construct($parent, $event) {
		$this->parent   = $parent;
		$this->event = $event;
		parent::__construct($parent->data());
	}

	public function init() {
		parent::init();
		if ($this->event->RequireLoggedIn && !Member::currentUserID()) {
			return Security::permissionFailure($this, array(
				'default' => 'Please log in to register for this event.'
			));
		}
		if($this->checkRegistrationExpired()){
			return $this->redirect($this->Link());
		}
	}

	public function index() {
		$exclude  = null;
		// If we have a current multiform ID, then exclude the linked
		// registration from the capacity calculation.
		if (isset($_GET['MultiFormSessionID'])) {
			$exclude = $this->RegisterForm()->getSession()->RegistrationID;
		}
		if (!$this->event->canRegister()) {
			$data = array(
				'Content' => '<p>This event cannot be registered for.</p>'
			);
		} elseif ($this->event->getRemainingCapacity($exclude)) {
			$data = array(
				'Title' => 'Register For ' . $this->event->Title,
				'Form'  => $this->RegisterForm(),
				'Content' => ''
			);
		} else {
			$data = array(
				'Title'   => $this->event->Title . ' Is Full',
				'SoldOut' => true,
				'Content' => '<p>There are no more places available at this event.</p>'
			);
		}

		$data['Event'] = $this->event;

		return $this->getViewer('index')->process($this->customise($data));
	}

	/**
	 * @return RegistrableEvent
	 */
	public function getEvent() {
		return $this->event;
	}

	/**
	 * @param  string $action
	 * @return string
	 */
	public function Link($action = null) {
		return Controller::join_links(
			$this->parent->Link(), 'register', $action
		);
	}

	/**
	 * @return EventRegisterForm
	 */
	public function RegisterForm() {
		static $form; //only build the form once
		if(!$form){
			$form = new EventRegisterForm($this, 'RegisterForm');
			$this->extend('updateEventRegisterForm', $form);
		}

		return $form;
	}

	/**
	 * Check if the current registration has expired.
	 * @return boolean
	 */
	public function checkRegistrationExpired() {
		$form   = $this->RegisterForm();
		$expiry = $form->getExpiryDateTime();
		if ($expiry && $expiry->InPast()) {
			$form->getSession()->Registration()->delete();
			$form->getSession()->delete();
			$message = _t('EventManagement.REGSESSIONEXPIRED', 'Your'
				. ' registration expired before it was completed. Please'
				. ' try ordering your tickets again.');
			$form->sessionMessage($message, 'bad');

			return true;
		}
	}

	/**
	 * Handles a user clicking on a registration confirmation link in an email.
	 */
	public function confirm($request) {
		$id    = $request->param('ID');
		$token = $request->getVar('token');

		if (!$rego = EventRegistration::get()->byID($id)) {
			return $this->httpError(404);
		}
		if ($rego->Token != $token) {
			return $this->httpError(403);
		}
		if ($rego->Status != 'Unconfirmed') {
			return $this->redirect($rego->Link());
		}
		try {
			$rego->Status = 'Valid';
			$rego->write();

			EventRegistrationDetailsEmail::factory($rego)->send();
		} catch (ValidationException $e) {
			return array(
				'Title'   => 'Could Not Confirm Registration',
				'Content' => '<p>' . $e->getResult()->message() . '</p>'
			);
		}

		return array(
			'Title'   => $this->event->AfterConfirmTitle,
			'Content' => $this->event->obj('AfterConfirmContent')
		);
	}

}
