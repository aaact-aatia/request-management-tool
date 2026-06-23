<?php

if (!function_exists('rmt_get_admin_csv_tables')) {
	/**
	 * Supported admin tables for CSV export/import.
	 */
	function rmt_get_admin_csv_tables(): array {
		return [
			'tblteams' => [
				'label_key' => 'admin_csv_table_teams',
				'columns' => ['id', 'nameen', 'namefr', 'email', 'contactname', 'contactemail', 'escalationcontactname', 'escalationcontactemail', 'team_lead_user_id', 'status'],
				'order_by' => 'id ASC',
			],
			'tblcatalogue' => [
				'label_key' => 'admin_csv_table_catalogue',
				'columns' => ['id', 'nameen', 'namefr', 'survey', 'status'],
				'order_by' => 'id ASC',
			],
			'tblservices' => [
				'label_key' => 'admin_csv_table_services',
				'columns' => ['id', 'catalogueid', 'nameen', 'namefr', 'sds', 'contactid', 'status'],
				'order_by' => 'id ASC',
			],
			'tblsubservices' => [
				'label_key' => 'admin_csv_table_subservices',
				'columns' => ['id', 'serviceid', 'nameen', 'namefr', 'sds', 'contactid', 'status'],
				'order_by' => 'id ASC',
			],
			'tblsources' => [
				'label_key' => 'admin_csv_table_sources',
				'columns' => ['id', 'nameen', 'namefr', 'status'],
				'order_by' => 'id ASC',
			],
			'tblstatus' => [
				'label_key' => 'admin_csv_table_status',
				'columns' => ['id', 'nameen', 'namefr', 'is_resolved', 'status'],
				'order_by' => 'id ASC',
			],
			'tblholidays' => [
				'label_key' => 'admin_csv_table_holidays',
				'columns' => ['id', 'holiday_date', 'name_en', 'name_fr', 'recurring', 'status'],
				'order_by' => 'holiday_date ASC, id ASC',
			],
		];
	}
}
