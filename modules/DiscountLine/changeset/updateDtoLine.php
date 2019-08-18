<?php
/*************************************************************************************************
 * Copyright 2019 JPL TSolucio, S.L. -- This file is a part of TSOLUCIO coreBOS Customizations.
* Licensed under the vtiger CRM Public License Version 1.1 (the "License"); you may not use this
* file except in compliance with the License. You can redistribute it and/or modify it
* under the terms of the License. JPL TSolucio, S.L. reserves all rights not expressly
* granted by the License. coreBOS distributed by JPL TSolucio S.L. is distributed in
* the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied
* warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. Unless required by
* applicable law or agreed to in writing, software distributed under the License is
* distributed on an "AS IS" BASIS, WITHOUT ANY WARRANTIES OR CONDITIONS OF ANY KIND,
* either express or implied. See the License for the specific language governing
* permissions and limitations under the License. You may obtain a copy of the License
* at <http://corebos.org/documentation/doku.php?id=en:devel:vpl11>
*************************************************************************************************/

class updateDtoLine extends cbupdaterWorker {

	public function applyChange() {
		if ($this->hasError()) {
			$this->sendError();
		}
		if ($this->isApplied()) {
			$this->sendMsg('Changeset '.get_class($this).' already applied!');
		} else {
			global $adb;
			$modname = 'DiscountLine';
			if ($this->isModuleInstalled($modname)) {
				$module = Vtiger_Module::getInstance($modname);
				$ev = new VTEventsManager($adb);
				$ev->registerHandler('corebos.entity.link.after', 'modules/DiscountLine/CheckDuplicateRelatedRecords.php', 'CheckDuplicateRelatedRecords');
				$ev->registerHandler('corebos.filter.inventory.getprice', 'modules/DiscountLine/GetPriceHandler.php', 'PriceCalculationGetPriceEventHandler');
				// Gets all the uitype10 accountid and adds them to crmentityrel
				$query_result = $adb->pquery('SELECT discountlineid, accountid FROM vtiger_discountline', array());
				while ($_rows = $adb->fetch_array($query_result)) {
					$crmid_discountline = $_rows['discountlineid'];
					$crmid_accounts = $_rows['accountid'];
					$this->ExecuteQuery(
						'INSERT INTO `vtiger_crmentityrel` (`crmid`, `module`, `relcrmid`, `relmodule`) VALUES (?,?,?,?)',
						array($crmid_discountline, 'DiscountLine', $crmid_accounts, 'Accounts')
					);
				}
			}
			$this->sendMsg('Changeset '.get_class($this).' applied!');
			$this->markApplied();
		}
		$this->finishExecution();
	}

	public function undoChange() {
		if ($this->isBlocked()) {
			return true;
		}
		if ($this->hasError()) {
			$this->sendError();
		}
		if ($this->isApplied()) {
			global $adb;
			$ev = new VTEventsManager($adb);
			$ev->unregisterHandler('CheckDuplicateRelatedRecords');
			$ev->unregisterHandler('PriceCalculationGetPriceEventHandler');
			$this->sendMsg('Changeset '.get_class($this).' undone!');
			$this->markUndone();
		} else {
			$this->sendMsg('Changeset '.get_class($this).' not applied!');
		}
		$this->finishExecution();
	}
}