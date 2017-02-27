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

	public function testAvailabilityByDate() {
		$this->markTestIncomplete();
		// assert true after sales start or no sales start
		// assert true before sales end or no sales end
		// assert false before sales start
		// assert false after sales end
	}

	public function testAvailabilityByTotalCount() {
		$this->markTestIncomplete();
		// assert true ticket max not reached
		// assert true for no limit
		// assert false ticket count reached
	}

}