<?php
/**
 * A task to remove unconfirmed event registrations that are older than the
 * cutoff date to free up the places.
 *
 * @package silverstripe-eventmanagement
 */
class EventRegistrationPurgeTask extends BuildTask {

	public function getTitle() {
		return 'Event Registration Purge Task';
	}

	public function getDescription() {
		return 'Cancels unconfirmed and unsubmitted registrations older than '
			.  'the cut-off date to free up the places.';
	}

	public function run($request) {
		$this->purgeUnsubmittedRegistrations();
		$this->purgeUnconfirmedRegistrations();
	}

	protected function purgeUnsubmittedRegistrations() {		
		$age = DB::getConn()->datetimeDifferenceClause(date('Y-m-d H:i:s'), "\"EventRegistration\".\"Created\"");
		$items = EventRegistration::get()
			->filter("Status", "Unsubmitted")
			->where("($age) > \"RegistrableEvent\".\"RegistrationTimeLimit\"")
			->where('"RegistrableEvent"."RegistrationTimeLimit" > 0')
			->innerJoin("CalendarDateTime", '"TimeID" = "CalendarDateTime"."ID"')
			->innerjoin("RegistrableEvent", '"CalendarDateTime"."EventID" = "RegistrableEvent"."ID"');

		if ($items->exists()) {
			$count = count($items);
			foreach ($items as $registration) {
				$registration->delete();
				$registration->destroy();
			}
		} else {
			$count = 0;
		}

		echo "$count unsubmitted registrations were permantently deleted.\n";
	}

	protected function purgeUnconfirmedRegistrations() {
		$age = DB::getConn()->datetimeDifferenceClause(date('Y-m-d H:i:s'), "\"EventRegistration\".\"Created\"");
		$items = EventRegistration::get()
			->filter("Status", "Unconfirmed")
			->where("($age) > \"RegistrableEvent\".\"ConfirmTimeLimit\"")
			->where('"RegistrableEvent"."ConfirmTimeLimit" > 0')
			->innerJoin("CalendarDateTime", '"TimeID" = "CalendarDateTime"."ID"')
			->innerjoin("RegistrableEvent", '"CalendarDateTime"."EventID" = "RegistrableEvent"."ID"');
			
		if ($items->exists()) {
			$count = count($items);
			foreach($items as $item){
				$item->Status = "Canceled";
				$item->write();
			}
		} else {
			$count = 0;
		}

		echo "$count unconfirmed registrations were canceled.\n";
	}

}