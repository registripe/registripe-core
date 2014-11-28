<?php
/**
 * Handles collecting the users details and creating a registration to an event
 * for them.
 *
 * @package silverstripe-eventmanagement
 */
class EventRegisterController extends Page_Controller {

	public static $allowed_actions = array(
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
	}

	/**
	 * Select ticket step
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
				'Tickets' => $tickets,
				'Link' => $this->event->Link()
			));
			$content = $data->renderWith("EventTicketSelector");
			$registration = $this->getCurrentRegistration();
			if($registration->Attendees()->exists()){
				$link = $this->Link("review");
				$content .= "<a href=\"$link\">Back to review</a>";
			}
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
	 * Create/edit attendee step
	 */
	public function attendee() {
		$registration = $this->getCurrentRegistration();
		$nexturl = $this->Link('review');
		$backurl = $this->canReview() ?	$nexturl : $this->Link();
		$record = new Page(array(
			'ID' => -1,
			'Title' => $this->Title,
			'ParentID' => $this->ID,
			'URLSegment' => 'register/attendee',
			'BackURL' => $backurl,
			'NextURL' => $this->Link('review')
		));

		return new EventAttendeeController($record, $registration);
	}

	/**
	 * Review step
	 */
	public function review() {
		if(!$this->canReview()){
			return $this->redirect($this->Link());
		}
		$registration = $this->getCurrentRegistration()
			->customise(array(
				'EditLink' => $this->Link('attendee/edit'),
				'DeleteLink' => $this->Link('attendee/delete')
			))->renderWith("AttendeesReviewTable");

		return array(
			'Title' => 'Review',
			'Content' => $registration,
			'Form' => $this->ReviewForm()
		);
	}

	public function canReview(){
		$registration = $this->getCurrentRegistration(false);
		return $registration && $registration->Attendees()->exists();
	}

	public function ReviewForm() {
		$registration = $this->getCurrentRegistration();
		$fields = new FieldList(
			new DropdownField("RegistrantAttendeeID", "You are",
				$registration->Attendees()
					->map()->toArray()
			)
		);
		$actions = new FieldList(
			new LiteralField(
				"addticket",
				sprintf("<a href=\"%s\">%s</a>",
					$this->Link(),
					"Add another ticket"
				)
				
			),
			$nextaction = new FormAction("submitreview", "Next Step")
		);
		if($registration->getTotalOutstanding() > 0){
			$nextaction->setTitle("Make Payment");
		}

		$form = new Form($this, "ReviewForm", $fields, $actions);
		return $form;
	}

	public function submitreview($data, $form) {
		$registration = $this->getCurrentRegistration();
		//save registrant
		$registrantid = isset($data['RegistrantAttendeeID']) ? (int)$data['RegistrantAttendeeID'] : null;
		if($registrantid && $attendee = $registration->Attendees()->byID($registrantid)) {
			$registration->update(array(
				'FirstName' => $attendee->FirstName,
				'Surname' => $attendee->Surname,
				'Email' => $attendee->Email
			));
		}
		$form->saveInto($registration);
		$registration->write();

		//redirect to appropriate place, based on total cost
		if($registration->canPay()){
			return $this->redirect($this->Link('payment'));
		}
		return $this->redirect($this->Link('complete'));
	}

	public function payment() {
		$registration = $this->getCurrentRegistration(false);
		if(!$registration){
			return $this->redirect($this->Link());
		}

		$controller = new PaymentController($this, "payment", $registration, $registration->Total);
		$controller->setSuccessURL($this->Link('complete'));
		//hack the url segment until the parent controller of this works properly
		$controller->data()->URLSegment = "register/payment";
		return $controller;
	}

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
		$this->endRegistrationSession();
		
		//redirect to registration details
		return $this->redirect($registration->Link());
	}

	/**
	 * @return RegistrableEvent
	 */
	public function getEvent() {
		return $this->event;
	}

	/**
	 * Find or make the current regisratrion.
	 * Store reference in the session.
	 * @return EventRegistration
	 */
	public function getCurrentRegistration($forcestart = true) {
		$registration = $this->registration;
		//look for regisration in session
		if(!$registration){
			$registration = EventRegistration::get()->byID(
				Session::get("EventRegistration.".$this->event->ID)
			);
		}
		//end any submitted registrations
		if($registration && $registration->isSubmitted()){
			$this->endRegistrationSession();
			$registration = null;
		}

		//start a new registration
		if(!$registration && $forcestart){
			$registration = $this->startRegistrationSession();
		}
		$this->registration = $registration;
		return $this->registration;
	}

	public function startRegistrationSession() {
		$registration =  new EventRegistration();
		$registration->EventID = $this->event->ID;
		$registration->write();
		Session::set("EventRegistration.".$this->event->ID, $registration->ID);
		return $registration;
	}

	public function endRegistrationSession() {
		Session::set("EventRegistration.".$this->event->ID, null);
		Session::clear("EventRegistration.".$this->event->ID);
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
