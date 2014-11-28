<?php
/**
 * A calendar event that can people can register to attend.
 */
class RegistrableEvent extends CalendarEvent {

	private static $db = array(
		'TicketGenerator'       => 'Varchar(255)',
		'OneRegPerEmail'        => 'Boolean',
		'RequireLoggedIn'       => 'Boolean',
		'RegistrationTimeLimit' => 'Int',
		'RegEmailConfirm'       => 'Boolean',
		'EmailConfirmMessage'   => 'Varchar(255)',
		'ConfirmTimeLimit'      => 'Int',
		'AfterConfirmTitle'     => 'Varchar(255)',
		'AfterConfirmContent'   => 'HTMLText',
		'UnRegEmailConfirm'     => 'Boolean',
		'AfterConfUnregTitle'   => 'Varchar(255)',
		'AfterConfUnregContent' => 'HTMLText',
		'EmailNotifyChanges'    => 'Boolean',
		'NotifyChangeFields'    => 'Text',
		'AfterRegTitle'         => 'Varchar(255)',
		'AfterRegContent'       => 'HTMLText',
		'AfterUnregTitle'       => 'Varchar(255)',
		'AfterUnregContent'     => 'HTMLText',
		'Capacity'              => 'Int',
		'EmailReminder'         => 'Boolean',
		'RemindDays'            => 'Int'
	);

	private static $has_many = array(
		'Tickets'     => 'EventTicket',
		'Registrations'   => 'EventRegistration'
	);

	private static $defaults = array(
		'RegistrationTimeLimit' => 900,
		'AfterRegTitle'         => 'Thanks For Registering',
		'AfterRegContent'       => '<p>Thanks for registering! We look forward to seeing you.</p>',
		'EmailConfirmMessage'   => 'Important: You must check your emails and confirm your registration before it is valid.',
		'ConfirmTimeLimit'      => 21600,
		'AfterConfirmTitle'     => 'Registration Confirmed',
		'AfterConfirmContent'   => '<p>Thanks! Your registration has been confirmed</p>',
		'AfterUnregTitle'       => 'Registration Canceled',
		'AfterUnregContent'     => '<p>Your registration has been canceled.</p>',
		'AfterConfUnregTitle'   => 'Un-Registration Confirmed',
		'AfterConfUnregContent' => '<p>Your registration has been canceled.</p>',
		'NotifyChangeFields'    => 'StartDate,EndDate,StartTime,EndTime'
	);

	private static $icon = "eventmanagement/images/date_edit.png";

	private static $description = "An event that can be registered for.";

	public function getCMSFields() {
		SiteTree::disableCMSFieldsExtensions();
		$fields = parent::getCMSFields();
		SiteTree::enableCMSFieldsExtensions();

		//remove recursion options from RegistrableEvents
		$fields->removeByName(_t('CalendarEvent.RECURSION','Recursion'));

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

		$fields->insertAfter(
			new ToggleCompositeField(
				'AfterUnRegistrationContent',
				_t('EventRegistration.AFTER_UNREG_CONTENT', 'After Un-Registration Content'),
				array(
					new TextField('AfterUnregTitle', _t('EventRegistration.TITLE', 'Title')),
					new HtmlEditorField('AfterUnregContent', _t('EventRegistration.CONTENT', 'Content'))
				)
			),
			'AfterRegistrationContent'
		);

		if ($this->RegEmailConfirm) {
			$fields->addFieldToTab('Root.Main', new ToggleCompositeField(
				'AfterRegistrationConfirmation',
				_t('EventRegistration.AFTER_REG_CONFIRM_CONTENT', 'After Registration Confirmation Content'),
				array(
					new TextField('AfterConfirmTitle', _t('EventRegistration.TITLE', 'Title')),
					new HtmlEditorField('AfterConfirmContent', _t('EventRegistration.CONTENT', 'Content'))
				)
			));
		}

		if ($this->UnRegEmailConfirm) {
			$fields->addFieldToTab('Root.Main', new ToggleCompositeField(
				'AfterUnRegistrationConfirmation',
				_t('EventRegistration.AFTER_UNREG_CONFIRM_CONTENT', 'After Un-Registration Confirmation Content'),
				array(
					new TextField('AfterConfUnregTitle', _t('EventRegistration.TITLE', 'Title')),
					new HtmlEditorField('AfterConfUnregContent', _t('EventRegistration.CONTENT', 'Content'))
				)
			));
		}

		$fields->addFieldToTab('Root.Tickets', new GridField(
			'Tickets',
			'Ticket Types',
			$this->Tickets(),
			GridFieldConfig_RecordEditor::create()
		));

		$generators = ClassInfo::implementorsOf('EventRegistrationTicketGenerator');
		if (self::config()->generate_ticket_files && $generators) {
			foreach ($generators as $generator) {
				$instance = new $generator();
				$generators[$generator] = $instance->getGeneratorTitle();
			}
			$generator = new DropdownField(
				'TicketGenerator',
				_t('EventRegistration.TICKET_GENERATOR', 'Ticket generator'),
				$generators
			);
			$generator->setEmptyString(_t('EventManagement.NONE', '(None)'));
			$generator->setDescription(_t(
				'EventManagement.TICKET_GENERATOR_NOTE',
				'The ticket generator is used to generate a ticket file for the user to download.'
			));

			$fields->addFieldToTab('Root.Tickets', $generator);
		}

		//registrations
		$regGridFieldConfig = GridFieldConfig_RecordEditor::create()
			->removeComponentsByType('GridFieldAddNewButton')
			->removeComponentsByType('GridFieldDeleteAction')
			->addComponents(
				new GridFieldButtonRow('after'),
				new GridFieldPrintButton('buttons-after-left'),
				new GridFieldExportButton('buttons-after-left')
			);
		$regGrids = array(
			new GridField('Registrations',
				_t('EventManagement.REGISTRATIONS', 'Registrations'),
				$this->Registrations()->filter('Status', 'Valid'),
				$regGridFieldConfig
			)
		);
		$cancelled = $this->Registrations()
			->filter('Status', 'Canceled');
		if($cancelled->exists()){
			$regGrids[] = new GridField('CanceledRegistrations',
				_t('EventManagement.CANCELLATIONS', 'Cancellations'),
				$cancelled,
				$regGridFieldConfig
			);
		}
		$fields->addFieldsToTab('Root.Registrations', $regGrids);

		if ($this->RegEmailConfirm) {
			$fields->addFieldToTab('Root.Registrations', new ToggleCompositeField(
				'UnconfirmedRegistrations',
				_t('EventManagement.UNCONFIRMED_REGISTRATIONS', 'Unconfirmed Registrations'),
				array(
					new GridField(
						'UnconfirmedRegistrations',
						'',
						$this->Registrations()->filter('Status', 'Unconfirmed')
					)
				)
			));
		}

		//attendees
		if($attendeesfield = $this->getAttendeesGridField()) {
			$fields->addFieldToTab("Root.Attendees", $attendeesfield);
		}

		$this->extend('updateCMSFields',$fields);

		return $fields;
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

		Requirements::javascript('eventmanagement/javascript/cms.js');

		$fields->addFieldsToTab('Root.Registration', array(
			new CheckboxField(
				'OneRegPerEmail',
				_t('EventManagement.ONE_REG_PER_EMAIL', 'Limit to one registration per email address?')
			),
			new CheckboxField(
				'RequireLoggedIn',
				_t('EventManagement.REQUIRE_LOGGED_IN', 'Require users to be logged in to register?')
			),
			$limit = new NumericField(
				'RegistrationTimeLimit',
				_t('EventManagement.REG_TIME_LIMIT', 'Registration time limit')
			),
		));

		$limit->setDescription(_t(
			'EventManagement.REG_TIME_LIMIT_NOTE',
			'The time limit to complete registration, in seconds. Set to 0 to disable place holding.'
		));

		$fields->addFieldsToTab('Root.Email', array(
			new CheckboxField(
				'RegEmailConfirm',
				_t('EventManagement.REQ_EMAIL_CONFIRM', 'Require email confirmation to complete free registrations?')
			),
			$info = new TextField(
				'EmailConfirmMessage',
				_t('EventManagement.EMAIL_CONFIRM_INFO', 'Email confirmation information')
			),
			$limit = new NumericField(
				'ConfirmTimeLimit',
				_t('EventManagement.EMAIL_CONFIRM_TIME_LIMIT', 'Email confirmation time limit')
			),
			new CheckboxField(
				'UnRegEmailConfirm',
				_t('EventManagement.REQ_UN_REG_EMAIL_CONFIRM', 'Require email confirmation to un-register?')
			),
			new CheckboxField(
				'EmailNotifyChanges',
				_t('EventManagement.EMAIL_NOTIFY_CHANGES', 'Notify registered users of event changes via email?')
			),
			new CheckboxSetField(
				'NotifyChangeFields',
				_t('EventManagement.NOTIFY_CHANGE_IN', 'Notify of changes in'),
				singleton('RegistrableDateTime')->fieldLabels(false)
			)
		));

		$info->setDescription(_t(
			'EventManagement.EMAIL_CONFIRM_INFO_NOTE',
			'This message is displayed to users to let them know they need to confirm their registration.'
		));

		$limit->setDescription(_t(
			'EventManagement.CONFIRM_TIME_LIMIT_NOTE',
			'The time limit to conform registration, in seconds. Set to 0 for no limit.'
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

	public function getAvailableTickets() {
		$now = date('Y-m-d H:i:s');

		return $this->Tickets()
			->filter("StartDate:LessThan", $now)
			->filter("EndDate:GreaterThan", $now);
	}

	public function getCompletedRegistrations() {
		return $this->Registrations()
			->filter("Status","Valid");
	}

	public function getValidAttendees() {
		return EventAttendee::get()
			->innerJoin("EventRegistration", "\"EventAttendee\".\"RegistrationID\" = \"EventRegistration\".\"ID\"")
			->filter("EventRegistration.Status", "Valid")
			->filter("EventRegistration.EventID", $this->ID);
	}

	public function validate() {
		$result   = parent::validate();
		$currency = null;

		// Ensure that if we are sending a reminder email it has an interval
		// to send at.
		if ($this->EmailReminder && !$this->RemindDays) {
			$result->error('You must enter a time to send the reminder at.');
		}

		// Ensure that we only have tickets in one currency, since you can't
		// make a payment across currencies.
		foreach ($this->Tickets() as $ticket) {
			if ($ticket->Type == 'Price') {
				$ticketCurr = $ticket->Price->getCurrency();
				if ($ticketCurr && $currency && $ticketCurr != $currency) {
					$result->error(sprintf(
						'You cannot attach tickets with different currencies '
						. 'to one event. You have tickets in both "%s" and "%s".',
						$currency, $ticketCurr));
					return $result;
				}
				$currency = $ticketCurr;
			}
		}

		return $result;
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

class RegistrableEvent_Controller extends CalendarEvent_Controller {

	public static $allowed_actions = array(
		'register',
		'unregister',
		'registration'
	);

	/**
	 * @return EventRegisterController
	 */
	public function register() {
		$record = $this->dataRecord;
		$record->Content = "";
		return new EventRegisterController($this, $record);
	}

	/**
	 * @return EventUnregisterController
	 */
	public function unregister() {
		return new EventUnregisterController($this, $this->dataRecord);
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

}
