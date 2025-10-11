<?php

namespace KamaClickCounter;

class Link_Item {

	public int    $link_id;             /* readonly */
	public int    $attach_id;           /* readonly */
	public int    $in_post;             /* readonly */
	public int    $link_clicks;         /* readonly */
	public string $link_name;           /* readonly */
	public string $link_title;          /* readonly */
	public string $link_description;    /* readonly */
	public string $link_date;           /* readonly */
	public string $last_click_date;     /* readonly */
	public string $link_url;            /* readonly */
	public string $file_size;           /* readonly */
	public string $downloads;           /* readonly */

	public function __construct( object $raw ) {
		$this->link_id          = (int)    $raw->link_id;
		$this->attach_id        = (int)    $raw->attach_id;
		$this->in_post          = (int)    $raw->in_post;
		$this->link_clicks      = (int)    $raw->link_clicks;
		$this->link_name        = (string) $raw->link_name;
		$this->link_title       = (string) $raw->link_title;
		$this->link_description = (string) $raw->link_description;
		$this->link_date        = (string) $raw->link_date;
		$this->last_click_date  = (string) $raw->last_click_date;
		$this->link_url         = (string) $raw->link_url;
		$this->file_size        = (string) $raw->file_size;
		$this->downloads        = (string) $raw->downloads;
	}

}
