<?php

declare(strict_types=1);

namespace App\Presenters;

use Nette;


abstract class BasePresenter extends Nette\Application\UI\Presenter
{
    /** @var Nette\Database\Context @inject */
    public $database;

    /** @var Nette\Caching\Storage @inject */
    public $storage;

    protected $user_db;

    ///** @var Nette\Caching\Cache */
	//public $cache;

	public function __construct(Nette\Caching\Storage $storage)
	{
        //$this->cache = new Nette\Caching\Cache($storage, 'my-namespace');
	}

    public function restoreRequest(string $key): void
    {
        if(\Nette\Utils\Strings::startsWith($key, "_item_")) {
            $document = $this->database->table("document")->get(str_replace("_item_", "", $key));
            $this->redirect("Homepage:detail", $document);
        }
        else
            parent::restoreRequest($key);
    }

    public const ROLE_ADMIN = 2;
    public function limitRole(int $role) {
        if($this->user_db->role < $role) {
            $this->error("Nemáte oprávnění", 403);
        }
    }

    public function beforeRender() {
        parent::beforeRender();
        $this->template->user_db = $this->user_db;
        $this->template->role = $this->user_db->role;
    }


    public function startup()
    {
        /* pokud měníte metodu startup, nezapomeňte zavolat jejího předka */
        parent::startup();
        $user = $this->getUser();

        /* ověření autorizovaného přístupu */
        if (!$user->isLoggedIn()) {
            $this->user_db = (object)[
                "role" => 0
            ];
        }
        else {
            $this->user_db = $this->database->table("users")->get($user->id);
            //if($this->user_db->role < 1) {}

        }
    }
}
