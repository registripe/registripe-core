<?php

class EventMoneyExtension extends DataExtension{
	
	/**
	 * Temporary way of improving price rendering,
	 * until core is updated.
	 * @see https://github.com/silverstripe/silverstripe-framework/issues/1388
	 * 
	 */
	function Nicer(){
		$value = $this->owner->getAmount();
		if($value == 0){
			return _t("Currency.NICEZERO", "Free");
		}

		return $this->owner->Nice();
	}

}