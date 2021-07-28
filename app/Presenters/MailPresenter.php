<?php

declare(strict_types=1);

namespace App\Presenters;

use Nette;
use Nette\Application\UI\Form;

final class MailPresenter extends BasePresenter
{
	/** @var Nette\Application\LinkGenerator @inject */
    public $linkGenerator;
    
    /** @var Nette\Mail\Mailer @inject */
    public $mailer;

    public function createComponentAddUserForm()
    {
        $form = new Form;
        $form->onRender = ["\Nette\Forms\BootstrapForm::makeBootstrap4"];


        $form->addText('mail', 'Email')
            ->setMaxLength(200)
            ->setOption('description', 'Emailová adresa')
            ->addRule(Form::EMAIL, 'Email nemá správný formát')
            ->setRequired(true);

        $form->addCheckbox("accept", "Souhlasím s tím, aby byla tato emailová adresa uložena a byly na ni posílány novinky z těchto stránek. Odběr novinek můžete kdykoliv odhlásit pomocí odkazu v emailové zprávě, která vám bude chodit, nebo kontaktováním administrátorů. Případným odhlášením bude vaše adresa ze systému smazána. Tyto adresy nebudou použity k jinému účelu než obce Olomučany.")
            ->setRequired(true);

        $form->addReCaptcha(
            'captcha', // control name
            '', // label
            "Potvrďte prosím, že nejste robot." // error message
        );

        $form->addSubmit('send', 'Uložit');

        $form->onSuccess[] = function ($form, $values) {
            $mail = strtolower($values->mail);
            $cnt = $this->database->table("mail")->where("mail = ?", $mail)->count();
            if($cnt) {
                $this->flashMessage("Adresa {$mail} již v systému je.", "warning");
            }
            else {
                $this->database->table("mail")->insert(["mail" => $mail]);
                $this->flashMessage("Adresa {$mail} byla vložena do systému.");
            }
            $this->redirect("Homepage:default");
        };

        return $form;
    }

    public function actionList() {
        $this->limitRole(self::ROLE_ADMIN);
        $this->template->mail=$this->database->table("mail")->order("mail");
        $this->template->request= $this->storeRequest();
    }

    public function actionDeleteMail($mail, $backlink = "", $yes=false) {
        $this->template->mail = $mail;
        if($yes) {
            $this->database->table("mail")->where("mail = ?", $mail)->limit(1)->delete();
            $this->flashMessage("Operace smazání byla provedena");
            $this->restoreRequest($backlink);
            $this->redirect("Homepage:");
        }
    }

    public function actionSend() {

        $data_orig = $this->database->table("document")->where("alert = 0 AND (hide IS NULL OR hide >=  DATE_SUB(NOW(), INTERVAL 1 DAY)) AND (show >=  DATE_SUB(NOW(), INTERVAL 30 DAY))");
        $data = $this->database->table("document")->where("alert = 0 AND (hide IS NULL OR hide >=  DATE_SUB(NOW(), INTERVAL 1 DAY)) AND (show >=  DATE_SUB(NOW(), INTERVAL 30 DAY))")->order("show DESC");


        $msg = "Na stránkách úřední desky Olomučany došlo k vytvoření následujících záznamů:";

        $cnt = 0;
        foreach($data as $d) {
            $l = $this->linkGenerator->link("Homepage:detail", [$d]);
            $msg .= "<h2>{$d->name}</h2>{$d->description}<br><a href=\"$l\">$l</a><hr>" ;
            $cnt ++;
        }

        if(!$cnt) {
            $this->sendJson(["send"=>"0"]);
            return;
        }


        //Toto je vyžádaná zpráva systému. Pokud je již nechcete dostávat, pokračujte na https://www.olomucany.cz/maillist/mrazek.v%40gmail.com/delete.

        foreach($this->database->table("mail") as $m) {
            $mail = new Nette\Mail\Message;
            //$m = (object)["mail"=>'mrazek@fit.vutbr.cz'];
            $del = $this->linkGenerator->link("Mail:deleteMail", [$m->mail]);
            $mail->setFrom('Úřední deska Olomučany <webmaster@olomucany.cz>')
                ->addTo($m->mail)
                ->setSubject('Novinky na úřední desce (' . date("j. n. Y") . ')')
                ->setHtmlBody($msg . 
                "<p style=\"font-size: 10px; color: gray\">Toto je vyžádaná zpráva systému. Pokud je již nechcete dostávat, pokračujte na <a href=\"$del\">$del</a>");

            //echo "<pre>{$mail->htmlBody}</pre>";
            $this->mailer->send($mail);
        }

        $data_orig->update(["alert" => 1]);

        $this->sendJson(["send"=>$cnt]);
    }
}
