<?php declare(strict_types=1);

namespace Circli\WebCore;

use Circli\Contracts\ExtensionInterface;
use Circli\Contracts\PathContainer;
use function DI\autowire;
use function DI\create;
use function DI\get;
use FastRoute\DataGenerator\GroupCountBased as DataGeneratorGroupCountBased;
use FastRoute\Dispatcher\GroupCountBased;
use FastRoute\RouteCollector;
use FastRoute\RouteParser\Std;
use Polus\Adr\Interfaces\ResolverInterface;
use Polus\Adr\Interfaces\ResponseHandlerInterface;
use Polus\Adr\ResponseHandler\HttpResponseHandler;
use Polus\MiddlewareDispatcher\DispatcherInterface as MiddlewareDispatcherInterface;
use Polus\MiddlewareDispatcher\Relay\Dispatcher as RelayDispatcher;
use Polus\Router\FastRoute\Dispatcher as FastRouteDispatcher;
use Polus\Router\FastRoute\RouterCollection;
use Polus\Router\RouterCollectionInterface;
use Polus\Router\RouterDispatcherInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Zend\Diactoros\RequestFactory;
use Zend\Diactoros\ResponseFactory;

class Extension implements ExtensionInterface
{
	/** @var PathContainer */
	private $paths;

	public function __construct(PathContainer $paths)
	{
		$this->paths = $paths;
	}

	public function configure(): array
	{
		return [
			'adr.relay_resolver' => function (ContainerInterface $container) {
				return function ($middleware) use ($container) {
					if ($middleware instanceof MiddlewareInterface) {
						return $middleware;
					}

					return $container->get($middleware);
				};
			},
			ResponseHandlerInterface::class => autowire(HttpResponseHandler::class),
			ResponseFactoryInterface::class => autowire(ResponseFactory::class),
			RequestFactoryInterface::class => create(RequestFactory::class),
			MiddlewareDispatcherInterface::class => create(RelayDispatcher::class)->constructor(
				get(ResponseFactoryInterface::class),
				get('adr.relay_resolver')
			),
			RouteCollector::class => create(RouteCollector::class)->constructor(
				get(Std::class),
				get(DataGeneratorGroupCountBased::class)
			),
			RouterCollectionInterface::class => autowire(RouterCollection::class),
			RouterDispatcherInterface::class => function (ContainerInterface $container) {
				return new FastRouteDispatcher(
					GroupCountBased::class,
					$container->get(RouteCollector::class)
				);
			},
			ResolverInterface::class => function (ContainerInterface $container) {
				return new ActionResolver($container);
			},
		];
	}
}
