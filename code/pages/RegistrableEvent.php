<?php
/**
 * An event that can people can register to attend.
 */
class RegistrableEvent extends Page {

	private static $db = array(
		'RequireLoggedIn'       => 'Boolean',
		'AfterRegTitle'         => 'Varchar(255)',
		'AfterRegContent'       => 'HTMLText',
		'Capacity'              => 'Int'
	);

	private static $has_many = array(
		'Tickets'     => 'EventTicket',
		'Registrations'   => 'EventRegistration'
	);

	private static $defaults = array(
		'AfterRegTitle'         => 'Thanks For Registering',
		'AfterRegContent'       => '<p>Thanks for registering! We look forward to seeing you.</p>',
	);

	private static $icon = "registripe/images/date_edit.png";

	private static $description = "An event that can be registered for.";

	public function getCMSFields() {
		SiteTree::disableCMSFieldsExtensions();
		$fields = parent::getCMSFields();
		SiteTree::enableCMSFieldsExtensions();

		$fields->insertAfter(
			new ToggleCompositeField(
				'AfterRegistrationContent',
				_t('EventRegistration.AFTER_REG_CONTENT', 'After Registration Content'),
				array(
					new TextField('AfterRegTitle', _t('EventRegistration.TITLE', 'Title')),
					new HtmlEditorField('AfterRegContent', _t('EventRegistration.CONTENT', 'Content'))
				)
			),
			'Content'
		);

		$ticketsconfig = GridFieldConfig_RecordEditor::create();
		$fields->addFieldToTab('Root.Tickets', new GridField(
			'Tickets', 'Ticket Types', $this->Tickets(), $ticketsconfig
		));

		// customise if ticket sub-classes are present
		if (count(ClassInfo::subclassesFor("EventTicket")) > 1 ) {
			$ticketsconfig
				->removeComponentsByType('GridFieldAddNewButton')
				->addComponent(new GridFieldAddNewMultiClass())
				->addComponent(new GridFieldOrderableRows());
		}

		//registrations
		$fields->addFieldToTab('Root.Registrations', $this->getRegistrationsFields());

		//attendees
		if($attendeesfield = $this->getAttendeesGridField()) {
			$fields->addFieldToTab("Root.Attendees", $attendeesfield);
		}

		$this->extend('updateCMSFields', $fields);

		return $fields;
	}

	/**
	 * Get the grid fields for registrations that are:
	 * completed, unconfirmed, incomplete, cancelled
	 * @return FormField
	 */
	protected function getRegistrationsFields() {

		$tabset = new TabSet("Registrations");

		//common config
		$regGridFieldConfig = GridFieldConfig_RecordEditor::create()
			->removeComponentsByType('GridFieldAddNewButton')
			->removeComponentsByType('GridFieldDeleteAction')
			->addComponents(
				new GridFieldButtonRow('after'),
				new GridFieldPrintButton('buttons-after-left'),
				new GridFieldExportButton('buttons-after-left')
			);

		//complete
		$registrationsGrid = new GridField('Registrations',
			_t('Registripe.REGISTRATIONS', 'Registrations'),
			$this->getCompletedRegistrations()
				->sort("LastEdited", "DESC"),
			$regGridFieldConfig
		);
		$tabset->push(new Tab("Completed", $registrationsGrid));

		//incomplete
		$incomplete = $this->getIncompleteRegistrations()
						->sort("LastEdited", "DESC");
		if($incomplete->exists()){
			$incompleteGrid = new GridField('IncompleteRegistrations',
				_t('Registripe.INCOMPLETE', 'Incomplete'),
				$incomplete,
				$regGridFieldConfig
			);
			$tabset->push(new Tab("Incomplete", $incompleteGrid));
		}		

		//cancelled
		$cancelled = $this->getCancelledRegistrations()
						->sort("LastEdited", "DESC");
		if($cancelled->exists()){
			$cancelledGrid = new GridField('CancelledRegistrations',
				_t('Registripe.CANCELLATIONS', 'Cancellations'),
				$cancelled,
				$regGridFieldConfig
			);
			$tabset->push(new Tab("Cancelled", $cancelledGrid));
		}

		return $tabset;
	}

	protected function getAttendeesGridField() {
		$attendees = $this->getValidAttendees();
		$config = new GridFieldConfig_RecordViewer();
		$exportcolumns = EventAttendee::config()->export_fields;
		$config->addComponents(
			new GridFieldButtonRow('after'),
			new GridFieldPrintButton('buttons-after-left'),
			$export = new GridFieldExportButton('buttons-after-left')
		);
		$export->setExportColumns($exportcolumns);
		return new GridField("Attendees", "Attendees", $attendees, $config);			
	}

	public function getSettingsFields() {
		$fields = parent::getSettingsFields();
		$fields->addFieldsToTab('Root.Registration', array(
			new CheckboxField(
				'RequireLoggedIn',
				_t('Registripe.REQUIRE_LOGGED_IN', 'Require users to be logged in to register?')
			)
		));
		return $fields;
	}

	/**
	 * Check if this event can currently be registered for.
	 * Checks 
	 * @return boolean
	 */
	public function canRegister(){
		$tickets = $this->getAvailableTickets();
		if($tickets && $tickets->exists()){
			return true;
		}

		return false;
	}

	/**
	 * Get available tickets
	 * @return DataList
	 */
	public function getAvailableTickets() {
		$now = date('Y-m-d H:i:s');
		return $this->Tickets()
			->where("\"StartDate\" <= '".$now."' OR \"StartDate\" IS NULL")
			->where("\"EndDate\" >= '".$now."' OR \"EndDate\" IS NULL");
	}

	/**
	 * Get all the completed registrations
	 * @return DataList
	 */
	public function getCompletedRegistrations() {
		return $this->Registrations()
			->filter("Status", "Valid");
	}

	/**
	 * Get incompleted registrations
	 * Restricts to registrations with an email.
	 * @return DataList
	 */
	public function getIncompleteRegistrations() {
		return $this->Registrations()
				->filter('Status', 'Unsubmitted')
				->filter('Email:not', '');
	}

	/**
	 * Get cancelled registrations
	 * Restricts to registrations with an email.
	 * @return DataList
	 */
	public function getCancelledRegistrations() {
		return $this->Registrations()
				->filter('Status', 'Canceled');
	}
	
	/**
	 * Get valid attendees
	 *
	 * @return DataList
	 */
	public function getValidAttendees() {
		return EventAttendee::get()
			->innerJoin("EventRegistration", "\"EventAttendee\".\"RegistrationID\" = \"EventRegistration\".\"ID\"")
			->filter("EventRegistration.Status", "Valid")
			->filter("EventRegistration.EventID", $this->ID);
	}

	/**
	 * Returns the overall number of places remaining at this event, TRUE if
	 * there are unlimited places or FALSE if they are all taken.
	 *
	 * @param  int $excludeId A registration ID to exclude from calculations.
	 * @return int|bool
	 */
	public function getRemainingCapacity($excludeId = null) {
		if (!$this->Capacity){
			return true;
		}
		$bookings = $this->Registrations()->filter("Status:not", "Canceled");
		if ($excludeId) {
			$bookings = $bookings->filter("ID:not", $excludeId);
		}
		$taken = $bookings->sum("Quantity");
		if($this->Capacity >= $taken){
			return $this->Capacity - $taken;
		}

		return false;
	}

}

class RegistrableEvent_Controller extends Page_Controller {

	public static $allowed_actions = array(
		'register',
		'registration'
	);

	/**
	 * @return EventRegisterController
	 */
	public function register() {
		$record = $this->dataRecord;
		$record->Content = '';
		return new EventRegisterController($this, $record);
	}

	/**
	 * Allows a user to view the details of their registration.
	 *
	 * @param SS_HTTPRequest $request
	 * @return EventRegistrationDetailsController
	 */
	public function registration($request) {
		$id = $request->param('ID');
		if (!ctype_digit($id)) {
			$this->httpError(404);
		}
		$rego = EventRegistration::get()->byID($id);
		if (!$rego || $rego->EventID != $this->ID) {
			$this->httpError(404);
		}
		$request->shift();
		$request->shiftAllParams();

		return new EventRegistrationDetailsController($this, $rego);
	}

	/**
	 * Optionally show register controller on index
	 */
	public function index($request) {
		if (self::config()->register_at_index) {
			return $this->register();
		}
		return array();
	}

}
