<?
require('Router.php');

/* Create a new instance of the Router class */
$router = new Router('/');

/* You can add a simple route with a closure-style function */
$route = $router->add(['GET'], '/', function() {
	echo "<h1>This is the home page.</h1>\n";
});

/* You can pass along pre-written functions, and bind parameters into the URI */
$route = $router->add(['POST', 'GET'], '/user/:id', 'getUserID');
$route->bind(':id', "[0-9]+");

/* Do the work */
$router->route();
?>