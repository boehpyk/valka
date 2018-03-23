<?php

namespace App;

use Silex\Application as SilexApplication;
use Silex\Provider\DoctrineServiceProvider;
use Silex\Provider\FormServiceProvider;
use Silex\Provider\SecurityServiceProvider;
use Silex\Provider\SessionServiceProvider;
use Silex\Provider\HttpCacheServiceProvider;
use Silex\Provider\ValidatorServiceProvider;
use Silex\Provider\MonologServiceProvider;
use Silex\Provider\WebProfilerServiceProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Encoder\PlaintextPasswordEncoder;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Silex\Provider\TwigServiceProvider;
use Silex\Provider\ServiceControllerServiceProvider;
use Silex\Provider\HttpFragmentServiceProvider;
use App\User\UserProvider;
use Twig\boehpykExtension;

class Application extends SilexApplication
{
    private $rootDir;
    private $env;

    public function __construct($env)
    {
        $this->rootDir = __DIR__.'/../../';
        $this->env = $env;

        parent::__construct();

        $app = $this;
        // Override these values in resources/config/prod.php file
        $app['var_dir'] = $this->rootDir.'var';
        $app['locale'] = 'ru';
        $app['http_cache.cache_dir'] = function (Application $app) {
            return $app['var_dir'].'/cache/http';
        };
        $app['monolog.options'] = [
            'monolog.logfile' => $app['var_dir'].'/logs/app.log',
            'monolog.name' => 'app',
            'monolog.level' => 300, // = Logger::WARNING
        ];
        $app['security.users'] = array('boehpyk' => array('ROLE_USER', 'qqq123'));

        $configFile = sprintf('%s/config/%s.php', $this->rootDir, $env);

        if (!file_exists($configFile)) {
            throw new \RuntimeException(sprintf('The file "%s" does not exist.', $configFile));
        }
        require $configFile;


        $app->register(new DoctrineServiceProvider());
        $app->register(new FormServiceProvider());
        $app->register(new HttpCacheServiceProvider());
        $app->register(new HttpFragmentServiceProvider());
        $app->register(new ServiceControllerServiceProvider());
        $app->register(new SessionServiceProvider());
        $app->register(new ValidatorServiceProvider());
        $app->register(new SecurityServiceProvider(), array(
            'security.firewalls' => array(
                'secured' => array(
                    'pattern' => '^/admin/',
                    'form' => array(
                        'login_path' => '/login/',
                        'check_path' => '/admin/login_check'
                    ),
                    'logout' => array('logout_path' => '/admin/logout', 'invalidate_session' => true),
                    'users' => function() use ($app) {
                        // Specific class App\User\UserProvider is described below
                        return new UserProvider($app['db']);
                    },
                ),
            ),
        ));

//        $app['security.default_encoder'] = function ($app) {
//            return new PlaintextPasswordEncoder();
//        };
        $app['security.utils'] = function ($app) {
            return new AuthenticationUtils($app['request_stack']);
        };

        $app->register(new MonologServiceProvider(), $app['monolog.options']);
        $app->register(new TwigServiceProvider(), array(
            'twig.options' => array(
                'cache' => $app['var_dir'].'/cache/twig',
                'strict_variables' => $app['debug'],
                'debug' => $app['debug'],
                'charset' => $app['charset'],
            ),
            'twig.form.templates' => array('bootstrap_3_horizontal_layout.html.twig'),
            'twig.path' => array($this->rootDir.'src'),
        ));
//        $app['twig'] = $app->extend('twig', function ($twig, $app) {
//            $twig->addFunction(new \Twig_SimpleFunction('asset', function ($asset) use ($app) {
//                $base = $app['request_stack']->getCurrentRequest()->getBasePath();
//                return sprintf($base.'/'.$asset, ltrim($asset, '/'));
//            }));
//            return $twig;
//        });
        $app["twig"] = $app->extend("twig", function (\Twig_Environment $twig, $app) {
            $twig->addExtension(new \App\Twig\boehpykExtension($app));

            return $twig;
        });
        if ('dev' === $this->env) {
            $app->register(new WebProfilerServiceProvider(), array(
                'profiler.cache_dir' => $app['var_dir'].'/cache/profiler',
                'profiler.mount_prefix' => '/_profiler', // this is the default
            ));
        }
        $app['request'] = function() use($app) {
            return $app['request_stack']->getCurrentRequest();
        };

        $app->mount('', new ControllerProvider());
    }
    public function getRootDir()
    {
        return $this->rootDir;
    }
    public function getEnv()
    {
        return $this->env;
    }
}