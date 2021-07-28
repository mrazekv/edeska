<?php

declare(strict_types=1);

namespace App\Presenters;

use Nette;

// lisi se tim, ze vyzaduje prihlaseni
abstract class BaseAdminPresenter extends BasePresenter
{

    public function startup()
    {

        /* pokud měníte metodu startup, nezapomeňte zavolat jejího předka */
        parent::startup();
        $user = $this->getUser();

        /* ověření autorizovaného přístupu */
        if (!$user->isLoggedIn()) {
            $this->flashMessage("Nejste přihlášen", "danger");
            $this->redirect("Sign:in", ['backlink' => $this->storeRequest()]);
            //$this->error('Nemáte oprávnění pro přístup na tuto stránku.', 403);
        }
        else {
            $this->user_db = $this->database->table("users")->get($user->id);
            if($this->user_db->role < 1) {
                $this->flashMessage("Účet byl deaktivován", "danger");
                $this->redirect("Sign:in", ['backlink' => $this->storeRequest()]);
            }

        }
    }
}
