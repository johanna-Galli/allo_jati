
<?php
require_once 'vendor/autoload.php';

$router = new Router($_GET['url']);

// listes des routes
$router->get('/Films/Film/:id_movie', 'Movies.showOneMovie');
$router->get('/Films', 'Movies.showAllMovies');

$router->get('/Artists/show', 'Artists.setByArtists');
$router->get('/Artists', 'Artists.index');

$router->get('/Users/:slug', 'Users.index');
$router->get('/Users', 'Users.index');

$router->get('/', 'Home.index');


$router->run();
