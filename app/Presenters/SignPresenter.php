<?php

declare(strict_types=1);

namespace App\Presenters;

use Nette;
use Nette\Application\UI\Form;


final class SignPresenter extends Nette\Application\UI\Presenter
{
    



      /**
	 * @var \App\ProjectAuth @inject
     */
    public $authenticator;

    /** 
     * @var Nette\Database\Context @inject
     */
    public $database;

    /** @var Nette\Security\Passwords @inject */
	private $passwords;

    
    /**
	 * @var \Nette\Mail\IMailer @inject
	 */
    public $mailer;

    public function __construct(Nette\Security\Passwords $passwords) {
        $this->passwords = $passwords;
    }

    public function actionIn(string $backlink = "")
    {
        
    }

    protected function createComponentSignInForm()
    {
        $form = new Form;
        $form->addText('username', 'Uživatelské jméno:')
            ->setRequired('Prosím vyplňte své uživatelské jméno.');

        $form->addPassword('password', 'Heslo:')
            ->setRequired('Prosím vyplňte své heslo.');

        $form->addSubmit('send', 'Přihlásit');

        $form->onSuccess[] = [$this, 'signInFormSucceeded'];
        return $form;
    }
    
    public function actionOut()
    {
        $user = $this->getUser();
        $user->setAuthenticator($this->authenticator);
        $user->logout();
        $this->redirect('Homepage:');
        
        $this->flashMessage("Byl jste odhlášen pro další práci se přihlaste znovu.", "success");
    }
    
    public function signInFormSucceeded($form, $values)
    {
        $user = $this->getUser();
        $user->setAuthenticator($this->authenticator);

        try {
            $user->login($values->username, $values->password);
            if($this->getParameter('backlink'))
                $this->restoreRequest($this->getParameter('backlink'));
            $this->redirect('Homepage:default');

        } catch (Nette\Security\AuthenticationException $e) {
            $form->addError('Nesprávné přihlašovací jméno nebo heslo.' . $this->passwords->hash("123"));
        }
    }
}