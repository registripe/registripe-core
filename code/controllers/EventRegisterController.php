<?php
/**
 * Handles collecting the users details and creating a registration to an event
 * for them.
 *
 * @package registripe
 */
class EventRegisterController extends Page_Controller {

	private static $allowed_actions = array(
		'attendee',
		'review',
		'ReviewForm',
		'payment',
		'complete'
	);

	protected $parent;
	protected $event;
	protected $registration;

	/**
	 * Constructs a new controller for creating a registration.
	 *
	 * @param ContentController $parent
	 * @param RegistrableEvent $event
	 */
	public function __construct($parent, $event) {
		$this->parent = $parent;
		$this->event = $event;
		$this->regSession = new \EventRegistration\Session($this->event);
		parent::__construct($parent->data());
	}

	/**
	 * Perform a security check
	 */
	public function init() {
		parent::init();
		if ($this->event->RequireLoggedIn && !Member::currentUserID()) {
			return Security::permissionFailure($this, array(
				'default' => 'Please log in to register for this event.'
			));
		}
	}

	public function getEvent() {
		return $this->event;
	}

	/**
	 * Select ticket action
	 * @return HTMLText
	 */
	public function index() {
		$exclude  = null;
		if (!$this->event->canRegister()) {
			$data = array(
				'Content' => '<p>This event cannot be registered for.</p>'
			);
		} elseif (!$this->event->getRemainingCapacity($exclude)) {
			$data = array(
				'Title'   => $this->event->Title . ' Is Full',
				'SoldOut' => true,
				'Content' => '<p>There are no more places available at this event.</p>'
			);
		} else {
			$tickets = $this->event->getAvailableTickets();
			$data = new ArrayData(array(
				'Tickets' => $tickets
			));
			$registration = $this->getCurrentRegistration();
			if($registration->Attendees()->exists()){
				$data->NextURL = $this->Link("review");
				$data->NextLink = AnchorField::create("review",
					_t("EventRegisterController.BACKTOREVIEW", "Back to Review"),
					$data->NextURL
				)->Field();
			}
			$template = self::config()->ticket_select_template;
			$content = $this->renderWith($template, $data);
			$data = array(
				'Title' => 'Register For ' . $this->event->Title,
				'Form'  => '',
				'Content' => $content
			);
		}
		$data['Event'] = $this->event;

		return $this->getViewer('index')->process($this->customise($data));
	}

	/**
	 * Create/edit attendee action
	 * @return EventAttendeeController
	 */
	public function attendee($request) {
		$forcewrite = $request->isPOST(); // start rego if form is submitting
		$registration = $this->getCurrentRegistration($forcewrite);
		$nexturl = $this->Link('review');
		$backurl = $this->canReview() ?	$nexturl : $this->Link();
		$record = new Page(array(
			'ID' => -1,
			'Title' => $this->Title,
			'ParentID' => $this->ID,
			'URLSegment' => 'register/attendee',
			'BackURL' => $backurl,
			'NextURL' => $nexturl
		));
		$controller = new EventAttendeeController($record, $registration);
		$this->extend("updateEventAttendeeController", $controller, $record, $registration);
		
		return $controller;
	}

	/**
	 * Review step
	 * @return array
	 */
	public function review() {
		if(!$this->canReview()){
			return $this->redirect($this->Link());
		}
		$registration = $this->getCurrentRegistration();
		$registration = $registration->customise(array(
				'EditLink' => $this->Link('attendee/edit'),
				'DeleteLink' => $this->Link('attendee/delete'),
				'Total' => $registration->obj('calculateTotal')
			))->renderWith("AttendeesReviewTable");

		return array(
			'Title' => 'Review',
			'Content' => $registration,
			'Form' => $this->ReviewForm()
		);
	}

	/**
	 * Check if registration has started, and attendees exist on it.
	 */
	public function canReview(){
		$registration = $this->getCurrentRegistration(false);
		return $registration && $registration->Attendees()->exists();
	}

	/**
	 * Review attendees
	 * @return Form
	 */
	public function ReviewForm() {
		$registration = $this->getCurrentRegistration();
		$attendees = $registration->Attendees();

		// registrant fields
		$fields = $registration->getFrontEndFields();
		
		$actions = new FieldList(
			new AnchorField("addticket",
				_t("EventRegisterController.ADDANOTHER", "Add Another Ticket"),
				$this->Link()
			),
			$nextaction = new FormAction(
				"submitreview", _t("EventRegisterController.NEXTSTEP", "Next Step")
			)
		);
		if($registration->getTotalOutstanding() > 0){
			$nextaction->setTitle(_t("EventRegisterController.MAKEPAYMENT", "Make Payment"));
		}
		
		$validator = new RequiredFields("RegistrantAttendeeID");
		
		$this->extend("updateReviewForm", $fields, $actions);
		$form = new EventRegisterForm($this, "ReviewForm", $fields, $actions, $validator);
		$form->setRegistrantValidator(new RequiredFields(
			$registration->stat('registrant_fields')
		), $registration->contactableAttendees()->count() === 0);
		$form->loadDataFrom($registration);

		return $form;
	}

	/**
	 * Submit review action
	 * @param data
	 */
	public function submitreview($data, $form) {
		$registration = $this->getCurrentRegistration();
		//save registrant
		$registrantid = isset($data['RegistrantAttendeeID']) ? (int)$data['RegistrantAttendeeID'] : null;
		if($registrantid && $attendee = $registration->Attendees()->byID($registrantid)) {
			$registration->update(array(
				'FirstName' => $attendee->FirstName,
				'Surname' => $attendee->Surname,
				'Email' => $attendee->Email
				// TODO: make this extensible
			));
		}
		$form->saveInto($registration);
		$registration->calculateTotal(); // final calculation
		$registration->write();
		//redirect to appropriate place, based on total cost
		$action = $registration->canPay() ? 'payment' : 'complete';
		return $this->redirect($this->Link($action));
	}

	/**
	* Payment handling action
	* @return PaymentController
	*/
	public function payment() {
		$registration = $this->getCurrentRegistration(false);
		if(!$registration || !$registration->canPay()){
			return $this->redirect($this->Link());
		}
		$controller = new PaymentController($this, "payment", $registration, $registration->Total);
		$controller->setSuccessURL($this->Link('complete'));
		$controller->setCancelURL($this->Link('payment'));
		//hack the url segment until the parent controller of this works properly
		$controller->data()->URLSegment = "register/payment";
		return $controller;
	}

	/**
	 * Completed registration action
	 * @return HTTPResponse
	 */
	public function complete() {
		$registration = $this->getCurrentRegistration(false);
		if(!$registration){
			return $this->redirect($this->Link());
		}
		if(!$registration->canSubmit()){
			return $this->redirect($this->Link('review'));
		}

		//update registration status
		$registration->Status = "Valid";
		$registration->write();
		//email registration
		$mailer = new EventRegistrationEmailer($registration);
		$mailer->sendConfirmation();
		$mailer->notifyAdmin();

		//end session
		$this->regSession->end();
		
		//redirect to registration details
		return $this->redirect($registration->Link());
	}

	/**
	 * Find or make the current registration in the session.
	 * @param boolean $write
	 * @return EventRegistration
	 */
	public function getCurrentRegistration($write = true) {
		$registration = null;
		// local reference
		if($this->registration && !$this->registration->isSubmitted()) {
			$registration = $this->registration;
		}
		if(!$registration){
			// get from session
			$registration = $this->regSession->get();
		}
		
		if (!$registration) {
			// create a new
			if($write){
				// persist immediately
				$registration = $this->regSession->start();
			} else {
				// create new in memory
				$registration = EventRegistration::create();
				$registration->EventID = $this->event->ID;	
			}
		}
		// store locally
		$this->registration = $registration;
		return $this->registration;
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

}
