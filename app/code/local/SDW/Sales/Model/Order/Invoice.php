<?php

class SDW_Sales_Model_Order_Invoice extends Mage_Sales_Model_Order_Invoice
{
	const INVOICE_STATUS_TABLE_NAME         = 'mgt_sdw_invoice_status';
	const INVOICE_STATUS_TO_BE_EXPORTED     = 'TO_BE_EXPORTED';
	const INVOICE_STATUS_NOT_TO_BE_EXPORTED = 'NOT_TO_BE_EXPORTED';
	const INVOICE_STATUS_EXPORTED           = 'EXPORTED';

	public static function getValidInvoiceStatuses()
	{
		return array(
			static::INVOICE_STATUS_TO_BE_EXPORTED,
			static::INVOICE_STATUS_NOT_TO_BE_EXPORTED,
			static::INVOICE_STATUS_EXPORTED,
		);
	}

	public function getCurrentStatus()
	{
		$db_read = Mage::getSingleton('core/resource')->getConnection('core_read');

		// Try to get current invoice status
		$invoice_status = $db_read->fetchOne(
			vsprintf(
				'
				SELECT `invoice_status`
				FROM %s
				WHERE `invoice_id` = %d
				LIMIT 1
				',
				array(
					$db_read->quoteIdentifier(static::INVOICE_STATUS_TABLE_NAME),
					$this->getId(),
				)
			)
		);

		// Set default fallback status
		if (!$invoice_status) {
			$invoice_status = static::INVOICE_STATUS_NOT_TO_BE_EXPORTED;
		}

		return $invoice_status;
	}

	public function updateCurrentStatus($new_status)
	{
		// Sanity checks
		if (!in_array($new_status, static::getValidInvoiceStatuses(), true)) {
			throw new InvalidArgumentException('New invoice status is not valid.');
		}
		if (!$this->getId()) {
			throw new Exception('Invoice is not persisted in the database yet.');
		}

		$db_write = Mage::getSingleton('core/resource')->getConnection('core_write');
		$query    = vsprintf(
			'
			INSERT INTO %3$s (`invoice_id`, `invoice_status`)
			VALUES (%1$d, %2$s)
			ON DUPLICATE KEY UPDATE `invoice_status` = %2$s
			',
			array(
				$this->getId(),
				$db_write->quote($new_status),
				$db_write->quoteIdentifier(static::INVOICE_STATUS_TABLE_NAME),
			)
		);
		$db_write->query($query);

		if ($new_status === static::INVOICE_STATUS_TO_BE_EXPORTED) {
			$this->updateExportStatus('header', 0);
			$this->updateExportStatus('detail', 0);
		}

		return true;
	}

	public function getExportStatus($export_key)
	{
		// Sanity check
		$this->_checkExportKey($export_key);

		$db_read = Mage::getSingleton('core/resource')->getConnection('core_read');
		$query   = vsprintf(
			'
			SELECT %s
			FROM %s
			WHERE `invoice_id` = %d
			LIMIT 1
			',
			array(
				$db_read->quoteIdentifier($export_key.'_has_been_exported'),
				$db_read->quoteIdentifier(static::INVOICE_STATUS_TABLE_NAME),
				$this->getId(),
			)
		);

		return intval($db_read->fetchOne($query));
	}

	public function updateExportStatus($export_key, $value)
	{
		// Sanity check
		$this->_checkExportKey($export_key);

		$db_write = Mage::getSingleton('core/resource')->getConnection('core_write');
		$query    = vsprintf(
			'
			UPDATE %s
			SET %s = %d
			WHERE `invoice_id` = %d
			LIMIT 1
			',
			array(
				$db_write->quoteIdentifier(static::INVOICE_STATUS_TABLE_NAME),
				$db_write->quoteIdentifier($export_key.'_has_been_exported'),
				$value,
				$this->getId(),
			)
		);

		return $db_write->query($query);
	}

	protected function _afterSaveCommit()
	{
		$parent_commit = parent::_afterSaveCommit();

		$db_write = Mage::getSingleton('core/resource')->getConnection('core_write');
		$db_write->insert(
			static::INVOICE_STATUS_TABLE_NAME,
			array(
				'invoice_id'     => $this->getId(),
				'invoice_status' => static::INVOICE_STATUS_TO_BE_EXPORTED,
			)
		);

		return $parent_commit;
	}

	protected function _checkExportKey($export_key)
	{
		$authorized_keys = array('header', 'detail');
		if (!in_array($export_key, $authorized_keys, true)) {
			throw new InvalidArgumentException('Invalid value for argument $export_key (must be \'header\' or \'detail\').');
		}
	}
}
