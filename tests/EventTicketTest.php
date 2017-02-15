<?php
/**
 * Contains tests for the {@link EventTicket} class.
 *
 * @package    silverstripe-eventmanagement
 * @subpackage tests
 */
class EventTicketTest extends SapphireTest {

	public function testCMSFields() {
		$this->markTestIncomplete("Test CMS Fields");
	}

	/**
	 * @covers EventTicket::getAvailableForDateTime()
	 */
	public function testGetAvailableForDatetimeWithDates() {
		$ticket = new EventTicket();
		$time   = new CalendarDateTime();

		// First test making the ticket unavailable with a fixed start date in
		// the past.
		$ticket->StartType = 'Date';
		$ticket->StartDate = $startDate = date('Y-m-d H:i:s', time() + 60);
		$avail = $ticket->getAvailableForDateTime($time);

		$this->assertFalse($avail['available']);
		$this->assertEquals(strtotime($startDate), $avail['available_at']);

		// Make it beyond the end date.
		$ticket->EndType = 'Date';
		$ticket->EndDate = date('Y-m-d H:i:s');
		$avail = $ticket->getAvailableForDateTime($time);
		$this->assertFalse($avail['available']);
	}

	/**
	 * @covers EventTicket::getAvailableForDateTime()
	 */
	public function testGetAvailableForDatetimeWithQuantity() {
		$ticket = new EventTicket();
		$ticket->StartType = 'Date';
		$ticket->StartDate = date('Y-m-d', time() - (3600 * 24));
		$ticket->EndType   = 'Date';
		$ticket->EndDate   = date('Y-m-d', time() + (3600 * 24));
		$ticket->write();

		$time = new CalendarDateTime();
		$time->write();

		$ticket->Available = 50;
		$avail = $ticket->getAvailableForDateTime($time);
		$this->assertEquals(50, $avail['available']);

		// Create a registration that consumes some of the tickets.
		$rego = new EventRegistration();
		$rego->Status = 'Valid';
		$rego->TimeID = $time->ID;
		$rego->write();
		$rego->Tickets()->add($ticket, array('Quantity' => 49));

		$avail = $ticket->getAvailableForDateTime($time);
		$this->assertEquals(1, $avail['available']);

		// Then check we can exclude it.
		$avail = $ticket->getAvailableForDateTime($time, $rego->ID);
		$this->assertEquals(50, $avail['available']);

		// Then bump up the quantity so there are no more available.
		$rego->Tickets()->remove($ticket);
		$rego->Tickets()->add($ticket, array('Quantity' => 50));

		$avail = $ticket->getAvailableForDateTime($time);
		$this->assertFalse($avail['available']);
	}

	/**
	 * @covers EventTicket::getSaleEndForDateTime()
	 */
	public function testGetSaleEndForDateTime() {
		$ticket = new EventTicket();
		$time   = new CalendarDateTime();
		$now    = time();

		$ticket->EndType = 'Date';
		$ticket->EndDate = date('Y-m-d H:i:s', $now);
		$this->assertEquals(
			$now,
			$ticket->getSaleEndForDateTime($time),
			'The correct end time is returned with a fixed date.'
		);
	}

}