<?php
/**
 * @package   WPEmerge
 * @author    Atanas Angelov <atanas.angelov.dev@gmail.com>
 * @copyright 2018 Atanas Angelov
 * @license   https://www.gnu.org/licenses/gpl-2.0.html GPL-2.0
 * @link      https://wpemerge.com/
 */

namespace WPEmerge\Routing;

use WPEmerge\Exceptions\ConfigurationException;
use WPEmerge\Requests\RequestInterface;

/**
 * Represent an object which has a WordPress query filter.
 */
interface HasQueryFilterInterface {
	/**
	 * Get the main WordPress query vars filter, if any.
	 *
	 * @return callable|null
	 */
	public function getQueryFilter();

	/**
	 * Set the main WordPress query vars filter.
	 *
	 * @param  callable|null $query_filter
	 * @return void
	 */
	public function setQueryFilter( $query_filter );

	/**
	 * Apply the query filter, if any.
	 *
	 * @internal
	 * @throws ConfigurationException
	 * @param RequestInterface            $request
	 * @param  array<string, mixed>       $query_vars
	 * @return array<string, mixed>|false
	 */
	public function applyQueryFilter( $request, $query_vars );

	/**
	 * Fluent alias for setQueryFilter().
	 *
	 * @param  callable $query_filter
	 * @return static   $this
	 */
	public function query( $query_filter );
}
