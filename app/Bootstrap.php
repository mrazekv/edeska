<?php

declare(strict_types=1);

namespace App;

use Nette\Bootstrap\Configurator;


class Bootstrap
{
	public static function boot(): Configurator
	{
		$configurator = new Configurator;
		$appDir = dirname(__DIR__);
		$configurator->setDebugMode(["vmsecret@46.149.118.154", 'vmsecret@147.229.13.71', "vmsecret@2001:67c:1220:80c:86b:9037:ba35:7ab5"]); // enable for your remote IP, cookies nette-debug
		$configurator->enableTracy($appDir . '/log');

		$configurator->setTimeZone('Europe/Prague');
		$configurator->setTempDirectory($appDir . '/temp');


		$configurator->createRobotLoader()
			->addDirectory(__DIR__)
			->register();

		$configurator->addConfig($appDir . '/config/common.neon');
		
		if($_SERVER["SERVER_NAME"] == "edeska.olomucany.cz") {
			$configurator->addConfig($appDir . '/config/remote.neon');
		}
		else {
			$configurator->addConfig($appDir . '/config/local.neon');
		}

		return $configurator;
	}
}
