<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName

namespace Jcore\DynamicArchive;

/**
 * Class RenderBlocks
 *
 * @package Jcore\DynamicArchive
 */
class RenderBlocks {

	/**
	 * Handles intercepting a request and rerender only the dynamic archive block.
	 *
	 * @return void
	 */
	public static function intercept() {
		return;
	}

	public static function init() {
		add_action( 'parse_request', __CLASS__ . '::intercept' );
	}
}
