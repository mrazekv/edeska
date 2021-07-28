<?php

declare(strict_types=1);

namespace App\Presenters;

use Nette;
use Nette\Application\UI\Form;

final class UserPresenter extends BaseAdminPresenter
{
    /** @var Nette\Security\Passwords @inject */
    public $passwords;
    
    
      /**
	 * @var \App\ProjectAuth @inject
     */
    public $authenticator;

    function actionDefault() {
        $this->limitRole(self::ROLE_ADMIN);
        $this->template->users = $this->database->table("users")->order("role, username");
    }

    function actionEdit(string $id) {
        $this->limitRole(self::ROLE_ADMIN);
        $this->template->user = $this->database->table("users")->get($id);
        $this["userForm"]->setDefaults($this->template->user);
    }  
    
    function actionEditPassword(string $id) {
        $this->limitRole(self::ROLE_ADMIN);
        $this->template->user = $this->database->table("users")->get($id);
    }

    function actionNew() {
        $this->limitRole(self::ROLE_ADMIN);
        $this["userForm"]->setDefaults(["role"=>1]);
    }

    function actionPassword() {
        $this->template->user = $this->user_db;
    }


    protected function createComponentUserForm()
    {
        $this->limitRole(self::ROLE_ADMIN);
        $form = new Form;
        $form->onRender = ["\Nette\Forms\BootstrapForm::makeBootstrap4"];

        $id = $this->getParameter("id");



        if(!$id)
        $form->addText('username', 'Login')
            ->setMaxLength(100)
            ->setRequired(true);

        $form->addText('name', 'Jméno')
            ->setMaxLength(200)
            ->setRequired(true);

        if(!$id) {
            $form->addPassword('password', 'Heslo')
                ->addRule($form::MIN_LENGTH, 'Heslo musí mít alespoň %d znaků', 8);
            $form->addPassword('password2', 'Heslo (znovu)');
        }
        $form->addSelect('role', 'Role', [0=>"nefunkční", 1=>"normální", 2=>"admin"]);

        $form->addSubmit('send', 'Uložit');
        

        $form->onSuccess[] = function ($form, $values) use ($id) {
            if($id) {
                $this->database->table("users")->get($id)->update($values);
            }
            else {
                if($values->password != $values->password2) {
                    $form->addError("Hesla se neshodují");
                    return $form;
                }
                $values->password = $this->passwords->hash($values->password);
                unset($values->password2);
                $this->database->table("users")->insert($values);
            }
            $this->redirect("default");
        };

        return $form;
    }



    
    protected function createComponentPasswordForm()
    {
        $form = new Form;
        $form->onRender = ["\Nette\Forms\BootstrapForm::makeBootstrap4"];
        
        $id = $this->getParameter("id");
        if($id)
            $this->limitRole(self::ROLE_ADMIN);

        if(!$id) {
            $form->addPassword('password_old', 'Heslo (staré)');    
        }

        $form->addPassword('password', 'Heslo (nové)')
            ->addRule($form::MIN_LENGTH, 'Heslo musí mít alespoň %d znaků', 8);
        $form->addPassword('password2', 'Heslo (znovu)');

        $form->addSubmit('send', 'Uložit');
        

        $form->onSuccess[] = function ($form, $values) use ($id) {
            if($values->password != $values->password2) {
                $form->addError("Hesla se neshodují");
                return $form;
            }

            
            if($id) {
                $this->limitRole(self::ROLE_ADMIN);
                $values->password = $this->passwords->hash($values->password);
                unset($values->password2);
                // administratorska zmena
                $this->database->table("users")->get($id)->update($values);
            }
            else {
                try 
                {
                    $this->authenticator->changePassword($this->user_db->username, $values->password_old, $values->password);
                    $this->flashMessage("Heslo bylo změněno");
                }
                catch(Nette\Security\AuthenticationException $e) {
                    $form->addError("Špatné původní heslo");
                    return $form;
                }
                //$this->user_db->update($values);
            }
            $this->redirect("default");
        };

        return $form;
    }


    
    protected function createComponentMessageForm()
    {
        $this->limitRole(self::ROLE_ADMIN);
        $form = new Form;
        $form->onRender = ["\Nette\Forms\BootstrapForm::makeBootstrap4"];

        $form->addTextArea('message', 'Zpráva (včetně HTML)');

        $form->addSubmit('send', 'Uložit');
        
        $form->setDefaults($this->database->table("messages")->fetch());

        $form->onSuccess[] = function ($form, $values)  {
            $m = $this->database->table("messages")->fetch();
            if($m) $m->update($values);
            else $this->database->table("messages")->insert($values);
            $this->redirect("message");
        };

        return $form;
    }
}
