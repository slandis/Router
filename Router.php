<?
/**
 * Router.php
 *
 * This is a micro routing class, intended for quick and dirty URI routring. It
 * is by no means expansive or feature-packed. It could easily be expanded upon
 * and I might do that some day. Documentation exists solely in the comments
 * seen below.
 *
 * @author Shaun Landis <slandis@gmail.com>
 * @copyright 2013 Shaun Landis
 * @license MIT License See LICENSE for more details
 *
 * Have fun.
 */

/**
 * Route class
 *
 * A generic wrapper for each instantiated route. The constructor takes three
 * parameters:
 * @var $method 	The allowable action(s) for this URI
 * @var $uri 		The URI to route, including any bound specifiers
 * @var $callback 	The function to call when $uri is requested
 *
 * Functionality for binding simple parameters to routes is included.
 * 
 */
class Route {
	public $method;
	public $uri;
	public $callback;
	private $bindings = [];

	function __construct($method, $uri, $callback) {
		$this->method = $method;
		$this->uri = $uri;
		$this->callback = $callback;
	}

	/**
	 * Creates a named parameter binding for the Route.
	 *
	 * @param $tag 		The name of the binding
	 * @param $pattern 	PCRE pattern describing valid data
	 * @example $route->bind(':name', '[:alpha:]+');
	 */
	public function bind($tag, $pattern) {
		/* Basic sanity check */
		if (!@preg_match("/^:[[:alpha:]]+$/", $tag)) {
			throw new InvalidArgumentException("Invalid parameter name: $tag");
		}

		if (@preg_match("/$pattern/", null) === false) {
			throw new InvalidArgumentException("Invalid RegEx pattern: $pattern");
		}

		$this->bindings[$tag] = $pattern;
	}

	/**
	 * Removes a previously created named binding from the Route.
	 *
	 * @param $tag 		The name of the binding to remove
	 * @example	$route->unbind(':name');
	 */
	public function unbind($tag) {
		if (array_key_exists($tag, $this->bindings)) {
			unset($this->bindings[$key]);
			$this->bindings = array_values($this->bindings);
		}
	}

	/**
	 * Return a list of all named bindings for this Route.
	 */
	public function getBindings() {
		return $this->bindings;
	}
}

/**
 * Router class
 *
 * Create and manage Routes for the current application.
 *
 * @var $default 	Default content to display if the requested URI is not in the Router table.
 */
class Router {
	private $base;
	private $routes = [];
	public $default;

	function __construct($base) {
		$this->base = $base;
		$this->default = function() { header('HTTP/1.1 404 Not Found'); };
	}

	/**
	 * Create a new Route for the application.
	 *
	 * @param $method 	Array of request methods valid for this Route
	 * @param $uri 		Path defined for this Route
	 * @param $callback Function to call when this Route is requested
	 */
	public function add($method, $uri, $callback) {
		$route = new Route($method, $this->base . trim($uri, '/\^$'), $callback);
		$this->routes[] = $route;

		return $route;
	}

	/**
	 * Remove a Route.
	 * @param $uri 	URI of the Route to remove
	 */
	public function remove($uri) {
		foreach ($this->routes as $key => $route) {
			if ($uri == $route->uri) {
				unset($this->routes[$key]);

				$this->routes = array_values($this->routes);
			}
		}
	}

	/**
	 * Execute the routing setup as specified, inserting any named bindings for the Route.
	 */
	public function route() {
		$req = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : $this->uribase;

		foreach ($this->routes as $key => $route) {
			$uri = $route->uri;
			if (strstr($route->uri, ':')) {
				foreach ($route->getBindings() as $tag => $parameter) {
					$uri = str_replace($tag, $parameter, $uri);
				}
			}

			if (preg_match("#^$uri$#", $req)) {
				if (!in_array($_SERVER['REQUEST_METHOD'], $route->method))
					die("<h2>You have requested an invalid resource. Bad dog.</h2>");

				$reqParts = explode('/', $req);
				$uriParts = explode('/', $route->uri);
				$callbackParams = [];

				foreach ($uriParts as $key => $chunk) {
					if (substr($chunk, 0, 1) == ':') {
						$callbackParams[] = $reqParts[$key];
					}
				}

				call_user_func_array($route->callback, $callbackParams);
				return;
			}
		}

		call_user_func($this->default);
	}
}
?>