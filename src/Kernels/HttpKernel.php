<?php
/**
 * @package   WPEmerge
 * @author    Atanas Angelov <atanas.angelov.dev@gmail.com>
 * @copyright 2018 Atanas Angelov
 * @license   https://www.gnu.org/licenses/gpl-2.0.html GPL-2.0
 * @link      https://wpemerge.com/
 */

namespace WPEmerge\Kernels;

use Exception;
use WPEmerge\Application\Application;
use WPEmerge\Exceptions\ErrorHandlerInterface;
use WPEmerge\Requests\RequestInterface;
use WPEmerge\Routing\HasQueryFilterInterface;
use WPEmerge\Routing\Router;

/**
 * Describes how a request is handled.
 */
class HttpKernel implements HttpKernelInterface {
	/**
	 * Application.
	 *
	 * @var Application
	 */
	protected $app = null;

	/**
	 * Request.
	 *
	 * @var RequestInterface
	 */
	protected $request = null;

	/**
	 * Router.
	 *
	 * @var Router
	 */
	protected $router = null;

	/**
	 * Error handler.
	 *
	 * @var ErrorHandlerInterface
	 */
	protected $error_handler = null;

	/**
	 * Middleware available to the application.
	 *
	 * @var array<string, string>
	 */
	protected $middleware = [];

	/**
	 * Middleware groups.
	 *
	 * @var array<string, array<string>>
	 */
	protected $middleware_groups = [];

	/**
	 * Global middleware that will be applied to all routes.
	 *
	 * @var array
	 */
	protected $global_middleware = [
		\WPEmerge\Flash\FlashMiddleware::class,
		\WPEmerge\Input\OldInputMiddleware::class,
	];

	/**
	 * Middleware sorted in order of execution.
	 *
	 * @var array<string>
	 */
	protected $middleware_priority = [];

	/**
	 * Constructor.
	 *
	 * @codeCoverageIgnore
	 * @param RequestInterface      $request
	 * @param Router                $router
	 * @param ErrorHandlerInterface $error_handler
	 */
	public function __construct( Application $app, RequestInterface $request, Router $router, ErrorHandlerInterface $error_handler ) {
		$this->app = $app;
		$this->request = $request;
		$this->router = $router;
		$this->error_handler = $error_handler;
	}

	/**
	 * {@inheritDoc}
	 * @codeCoverageIgnore
	 */
	public function bootstrap() {
		$this->router->setMiddleware( $this->middleware );
		$this->router->setMiddlewareGroups( $this->middleware_groups );
		$this->router->setGlobalMiddleware( $this->global_middleware );
		$this->router->setMiddlewarePriority( $this->middleware_priority );

		// Web.
		add_action( 'request', [$this, 'filterRequest'], 1000 );
		add_action( 'template_include', [$this, 'filterTemplateInclude'], 1000 );

		// Ajax.
		add_action( 'admin_init', [$this, 'registerAjaxAction'] );

		// Admin.
	}

	/**
	 * {@inheritDoc}
	 */
	public function handle( RequestInterface $request, $arguments = [] ) {
		$this->error_handler->register();

		try {
			$response = $this->router->execute( $request, $arguments );
		} catch ( Exception $exception ) {
			$response = $this->error_handler->getResponse( $request, $exception );
		}

		$this->error_handler->unregister();

		return $response;
	}

	/**
	 * Filter the main query vars.
	 *
	 * @param  array $query_vars
	 * @return array
	 */
	public function filterRequest( $query_vars ) {
		$routes = $this->router->getRoutes();

		foreach ( $routes as $route ) {
			if ( ! $route instanceof HasQueryFilterInterface ) {
				continue;
			}

			if ( ! $route->isSatisfied( $this->request ) ) {
				continue;
			}

			$query_vars = $route->applyQueryFilter( $this->request, $query_vars );
			break;
		}

		return $query_vars;
	}

	/**
	 * Filter the main template file.
	 *
	 * @param  string $view
	 * @return string
	 */
	public function filterTemplateInclude( $view ) {
		global $wp_query;

		$response = $this->handle( $this->request, [$view] );

		if ( $response instanceof \Psr\Http\Message\ResponseInterface ) {
			$container = $this->app->getContainer();
			$container[ WPEMERGE_RESPONSE_KEY ] = $response;

			if ( $response->getStatusCode() === 404 ) {
				$wp_query->set_404();
			}

			return WPEMERGE_DIR . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'view.php';
		}

		return $view;
	}

	/**
	 * Register ajax action to hook into current one.
	 *
	 * @return void
	 */
	public function registerAjaxAction() {
		if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) {
			return;
		}

		$action = $this->request->post( 'action', $this->request->get( 'action' ) );
		$action = sanitize_text_field( $action );

		add_action( "wp_ajax_{$action}", [$this, 'actionAjax'] );
		add_action( "wp_ajax_nopriv_{$action}", [$this, 'actionAjax'] );
	}

	/**
	 * Act on ajax action.
	 *
	 * @return void
	 */
	public function actionAjax() {
		$response = $this->handle( $this->request, [''] );

		if ( $response instanceof \Psr\Http\Message\ResponseInterface ) {
			$this->app->respond( $response );
			wp_die( '', '', ['response' => null] );
		}
	}
}
