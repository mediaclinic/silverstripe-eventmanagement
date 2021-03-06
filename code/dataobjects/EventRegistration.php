<?php
/**
 * Represents a registration to an event.
 *
 * @package silverstripe-eventmanagement
 */
class EventRegistration extends DataObject {

	private static $db = array(
		'Name'   => 'Varchar(255)',
		'Email'  => 'Varchar(255)',
		'Status' => 'Enum("Unsubmitted, Unconfirmed, Valid, Canceled")',
		'Total'  => 'Money',
		'Token'  => 'Varchar(40)'
	);

	private static $has_one = array(
		'Time'   => 'RegistrableDateTime',
		'Member' => 'Member'
	);

	private static $many_many = array(
		'Tickets' => 'EventTicket'
	);

	private static $many_many_extraFields = array(
		'Tickets' => array('Quantity' => 'Int')
	);

	private static $summary_fields = array(
		'Name'          => 'Name',
		'Email'         => 'Email',
		'Time.Title'    => 'Event',
		'TotalQuantity' => 'Places'
	);

	protected function onBeforeWrite() {
		if (!$this->isInDB()) {
			$generator = new RandomGenerator();
			$this->Token = substr($generator->randomToken(), 0,40);
		}

		parent::onBeforeWrite();
	}

	public function getCMSFields() {
		$fields = parent::getCMSFields();

		$fields->removeByName('Tickets');
		$fields->removeByName('Total');
		$fields->removeByName('Token');
		$fields->removeByName('TimeID');

		$fields->addFieldToTab('Root.Tickets', $tickets = new TableListField(
			'Tickets',
			'EventTicket',
			array(
				'Title'        => 'Ticket Title',
				'PriceSummary' => 'Price',
				'Quantity'     => 'Quantity'
			)));
		$tickets->setCustomSourceItems($this->Tickets());

		if (class_exists('Payment')) {
			$fields->addFieldToTab('Root.Tickets', new ReadonlyField(
				'TotalNice', 'Total', $this->Total->Nice()
			));
		}

		return $fields;
	}

	/**
	 * @see EventRegistration::EventTitle()
	 */
	public function getTitle() {
		return $this->Time()->Title;
	}

	/**
	 * @return int
	 */
	public function TotalQuantity() {
		return $this->Tickets()->sum('Quantity');
	}

	/**
	 * @return SS_Datetime
	 */
	public function ConfirmTimeLimit() {
		$unconfirmed = $this->Status == 'Unconfirmed';
		$limit       = $this->Time()->Event()->ConfirmTimeLimit;

		if ($unconfirmed && $limit) {
			return DBField::create_field('SS_Datetime', strtotime($this->Created) + $limit);
		}
	}

	/**
	 * Generate a desicrption of the tickets in the registration
	 * @return string
	 */
	public function getDescription() {
		$parts = array();
		foreach($this->Tickets() as $ticket){
			$parts[] = $ticket->Quantity."x".$ticket->Title;
		}

		return $this->Time()->Event()->Title.": ".implode(",", $parts);
	}

	/**
	 * @return string
	 */
	public function Link($action = '') {
		return Controller::join_links(
			$this->Time()->Event()->Link(), 'registration', $this->ID, $action, '?token=' . $this->Token
		);
	}

	public function canCreate($member = null) {
		return Permission::check("CMS_ACCESS_CMSMain");
	}

	public function canEdit($member = null) {
		return Permission::check("CMS_ACCESS_CMSMain");
	}

	public function canDelete($member = null) {
		return Permission::check("CMS_ACCESS_CMSMain");
	}

	public function canView($member = null) {
		return Permission::check("CMS_ACCESS_CMSMain");
	}

}