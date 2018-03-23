<?php

namespace App;

use Silex\Api\ControllerProviderInterface;
use Silex\Application as App;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\DBAL\Connection;

class ControllerProvider implements ControllerProviderInterface
{
    private $app;

    public function connect(App $app)
    {
        $this->app = $app;

        $app->error([$this, 'error']);

        $controllers = $app['controllers_factory'];

        $controllers
            ->get('/', [$this, 'homepage'])
            ->method('GET|POST')
            ->bind('homepage');


        $controllers
            ->match('/admin/article/{id}/', function ($id, Request $request) use($app) {

                $sql = "SELECT id, type FROM Page WHERE id = :id";
                $sth = $app['db']->executeQuery($sql, array('id' => $id));
                $page = $sth->fetch();

                if (!$page) $app->abort(404, "Страница не найдена");

                extract($page);

                /**
                 * @var $type string
                 * @var $id integer
                 */

                $classFile = __DIR__."/../modules/".$type."/Backend/controllers/".$type.".php";
                if(file_exists ($classFile)) {
                    $classname = 'modules\\'.$type.'\\Backend\\controllers\\'.$type;
                    $handler = new $classname($this->app, $id);
                    return $handler->handle($request);
                }
                $app->abort(404, "Страница не найдена");

            })->method('GET|POST');

        $controllers
            ->match('/admin/gallery/{id}/', function ($id, Request $request) use($app) {

                $sql = "SELECT id, type FROM Page WHERE id = :id";
                $sth = $app['db']->executeQuery($sql, array('id' => $id));
                $page = $sth->fetch();

                if (!$page) $app->abort(404, "Страница не найдена");

                extract($page);

                /**
                 * @var $type string
                 * @var $id integer
                 */

                $classFile = __DIR__."/../modules/Gallery/Backend/controllers/Gallery.php";
                if(file_exists ($classFile)) {
                    $classname = 'modules\\Gallery\\Backend\\controllers\\Gallery';
                    $handler = new $classname($this->app, $id);
                    return $handler->handle($request);
                }
                $app->abort(404, "Страница не найдена");

            })->method('GET|POST');

        $controllers
            ->match('/admin/rightcol/{id}/', function ($id, Request $request) use($app) {

                $sql = "SELECT id, type FROM Page WHERE id = :id";
                $sth = $app['db']->executeQuery($sql, array('id' => $id));
                $page = $sth->fetch();

                if (!$page) $app->abort(404, "Страница не найдена");

                extract($page);

                /**
                 * @var $type string
                 * @var $id integer
                 */

                $classFile = __DIR__."/../modules/RightCol/Backend/controllers/RightCol.php";
                if(file_exists ($classFile)) {
                    $classname = 'modules\\RightCol\\Backend\\controllers\\RightCol';
                    $handler = new $classname($this->app, $id);
                    return $handler->handle($request);
                }
                $app->abort(404, "Страница не найдена");

            })->method('GET|POST');



        $controllers
            ->match('/admin/browser/', function (Request $request) use($app) {

                $classFile = __DIR__."/../Page/Backend/controllers/FileBrowser.php";
                if(file_exists ($classFile)) {
                    $classname = 'Page\\Backend\\controllers\\FileBrowser';
                    $handler = new $classname($app);
                    return $handler->handle($request);
                }
                $app->abort(404, "Страница не найдена");

            })->method('GET|POST');

        $controllers
            ->match('/admin/first_page/', function (Request $request) use($app) {

                $classFile = __DIR__."/../modules/FirstPage/Backend/controllers/FirstPage.php";
                if(file_exists ($classFile)) {
                    $classname = 'modules\\FirstPage\\Backend\\controllers\\FirstPage';
                    $handler = new $classname($app, 4);
                    return $handler->handle($request);
                }
                $app->abort(404, "Страница не найдена");

            })->method('GET|POST');


        $controllers
            ->match('/admin/refs/{pageName}/', function ($pageName, Request $request) use($app) {

                $classTitle = ucfirst($this->camelize($pageName));
                $classFile = __DIR__."/../modules/Refs/Backend/controllers/".$classTitle.".php";
                if(file_exists ($classFile)) {
                    $classname = 'modules\\Refs\\Backend\\controllers\\'.$classTitle;
                    $handler = new $classname($app);
                    return $handler->handle($request);
                }
                $app->abort(404, "Страница не найдена");

            })->method('GET|POST');


        $controllers
            ->match('/admin/{pageName}/', function ($pageName, Request $request) use($app) {
                $classTitle = ucfirst($this->camelize($pageName));
                $classFile = __DIR__."/../Page/Backend/controllers/".$classTitle.".php";
                if(file_exists ($classFile)) {
                    $classname = 'Page\\Backend\\controllers\\'.$classTitle;
                    $handler = new $classname($app);
                    return $handler->handle($request);
                }
                $app->abort(404, "Страница не найдена");

            })->method('GET|POST');

        $controllers
            ->match('/admin/', function (Request $request) use($app) {

                $classFile = __DIR__ . "/../modules/Text/Backend/controllers/Text.php";
                if(file_exists ($classFile)) {
                    $classname = 'modules\\Text\\Backend\\controllers\\Text';
                    $handler = new $classname($app);
                    return $handler->indexAction($request);
                }
                $app->abort(404, "Страница не найдена");

            })->method('GET|POST');

        $controllers->get('/login/', function(Request $request) use ($app) {
            return $app['twig']->render('App/User/templates/login.html.twig', array(
                'error'         => $app['security.last_error']($request),
                'last_username' => $app['session']->get('_security.last_username'),
            ));
        });


        $controllers
            ->match('/{pageName}/', function ($pageName, Request $request) use($app) {

                /**
                 * @var $type string
                 * @var $subtype string
                 * @var $id integer
                 */
                $sql = "SELECT id, type, subtype FROM Page WHERE url = :url";
                $sth = $app['db']->executeQuery($sql, array('url' => '/'.trim($pageName, '\\').'/'));

                $page = $sth->fetch();

                if ($page) {
                    extract($page);

                    $classFile = __DIR__ . "/../modules/" . $type . "/Frontend/controllers/" . $type . ".php";
                    if (file_exists($classFile)) {
                        $classname = 'modules\\'.$type.'\\Frontend\\controllers\\' . $type;
                        $handler = new $classname($id, $app);
                        return $handler->handle($request);
                    }
                }

                $app->abort(404, "Страница не найдена");

        })->assert('pageName', '[\w\-\._/]+')->method('GET|POST');

        return $controllers;
    }

    public function homepage(App $app, Request $request)
    {
        $classname =  'modules\\FirstPage\\Frontend\\controllers\\FirstPage';
        $handler = new $classname(4, $app);
        $app['request'] = $request;
        return $handler->handle($request);
    }


    public function error(\Exception $e, Request $request, $code)
    {
        if ($this->app['debug']) {
            return;
        }

        switch ($code) {
            case 404:
                $message = 'The requested page could not be found.';
                break;
            default:
                $message = 'We are sorry, but something went terribly wrong.';
        }

        return new Response($message, $code);
    }

    function camelize($scored) {
        return lcfirst(
            implode(
                '',
                array_map(
                    'ucfirst',
                    array_map(
                        'strtolower',
                        explode(
                            '_', $scored)))));
    }
}
