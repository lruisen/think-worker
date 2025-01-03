<?php

namespace ThinkWorker\Middlewares;

use Closure;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;
use Symfony\Component\VarDumper\VarDumper;
use think\Response;
use ThinkWorker\Request;

class InteractsWithVarDumper
{
	/**
	 * @param \app\Request|Request $request
	 * @param Closure $next
	 * @return Response
	 */
	public function handle(Request|\app\Request $request, Closure $next): Response
	{
		if (class_exists(VarDumper::class)) {
			$cloner = new VarCloner();
			$dumper = new HtmlDumper();

			$prevHandler = VarDumper::setHandler(function ($var) use ($dumper, $cloner) {
				$dumper->dump($cloner->cloneVar($var));
			});

			/** @var Response $response */
			$response = $next($request);

			VarDumper::setHandler($prevHandler);
			return $response;
		}

		return $next($request);
	}
}