<?php

namespace KamaClickCounter;

class Month_Clicks_Updater {

	public const OPTION_NAME = 'kcc_updated_month';

	private string $curr_month;
	private string $prev_month;

	public function __construct() {
		$this->curr_month = date( 'Y-m' );
		$this->prev_month = date( 'Y-m', strtotime( '-1 month' ) );
	}

	public function init(): void {
		$db_month = get_option( self::OPTION_NAME, '' );
		if( $db_month !== $this->curr_month ){
			$updated_items = $this->update_all_links();
			update_option( self::OPTION_NAME, $this->curr_month );
		}
	}

	public function need_update_single_link( Link_Item $item ): bool {
		return
			! str_contains( $item->clicks_history, $this->prev_month )
			&&
			// not update if link was added in the current month (because counting for the month is in progress)
			! str_starts_with( $item->link_date, $this->curr_month );
	}

	public function update_single_link( Link_Item $item ): bool {
		global $wpdb;

		$clicks_history = array_filter( explode( "\n", trim( $item->clicks_history ) ) );
		array_unshift( $clicks_history, "$this->prev_month = $item->clicks_in_month" ); // place to top

		$new_data = [
			'clicks_in_month'   => 0,
			'clicks_prev_month' => $item->clicks_in_month,
			'clicks_history'    => implode( "\n", $clicks_history ),
		];

		return (bool) $wpdb->update( $wpdb->kcc_clicks, $new_data, [ 'link_id' => $item->link_id ] );
	}

	/**
	 * @return int Number of updated links
	 */
	private function update_all_links(): int {
		global $wpdb;

		$total = 0;
		$batch = 10000;

		// NOTE: Do the update in batches to avoid long-running queries.
		// NOTE: Do the update using SQL, because in PHP it will be too slow for large datasets.
		while( true ){
			$rows = $wpdb->query( $wpdb->prepare( <<<SQL
				UPDATE {$wpdb->kcc_clicks}
				SET
					clicks_prev_month = clicks_in_month,
					clicks_in_month   = 0,
					clicks_history    = CONCAT( %s, ' = ', clicks_prev_month, CHAR(10), clicks_history )
				WHERE clicks_history NOT LIKE %s
				ORDER BY link_id ASC
				LIMIT %d
				SQL,
				$this->prev_month,
				'%' . $wpdb->esc_like( $this->prev_month ) . '%',
				$batch
			) );

			if( ! $rows ){
				break;
			}

			$total += (int) $rows;
		}

		return $total;
	}

}
