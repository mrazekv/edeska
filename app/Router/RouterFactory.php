<?php

declare(strict_types=1);

namespace App\Router;

use Nette;
use Nette\Application\Routers\RouteList;
use Nette\Routing\Route;

final class RouterFactory
{
    /** @var Nette\Database\Explorer @inject */
	public $database;

	public function __construct(Nette\Database\Explorer $database)
	{
		$this->database = $database;
	}

	public function createRouter(): RouteList
	{
		$router = new RouteList;
		$router->addRoute('i/<item>', [
			"presenter" => "Homepage",
			"action" => "detail",
			"item" => [
				Route::FILTER_IN => function ($req) {
					return $this->database->table("document")->get(explode("-", $req)[0]); 
				},
				Route::FILTER_OUT => function ($d) {
					return $d->id . "-" . Nette\Utils\Strings::webalize($d->name);
				}
				]
			]);


		
		$router->addRoute('c/<category>', [
			"presenter" => "Homepage",
			"action" => "default",
			"category" => [
				Route::FILTER_IN => function ($req) {
					return $this->database->table("category")->get(explode("-", $req)[0]); 
				},
				Route::FILTER_OUT => function ($c) {
					return $c->id . "-" . Nette\Utils\Strings::webalize($c->name);
				}
				]
			]);

		$router->addRoute('json/<count>', [
			"presenter" => "Homepage",
			"action" => "latestJson"]
			);
		$router->addRoute('json-cat/<category>', [
				"presenter" => "Homepage",
				"action" => "latestCategoryJson",
				"category" => [
					Route::FILTER_IN => function ($req) {
						return $this->database->table("category")->get(explode("-", $req)[0]); 
					},
					Route::FILTER_OUT => function ($c) {
						return $c->id . "-" . Nette\Utils\Strings::webalize($c->name);
					}
					]
				]);

		$router->addRoute('<presenter>/<action>[/<id>]', 'Homepage:default');
		return $router;
	}
}
