<?php

declare(strict_types=1);

namespace App\Presenters;

use Nette;
use Nette\Application\UI\Form;


final class CategoryPresenter extends BaseAdminPresenter
{

    function actionDefault() {
        $this->template->categories = $this->database->table("category")->order("name");
    }
    function actionNew() {
        $this->limitRole(self::ROLE_ADMIN);
     
    }

    
    function actionEdit(int  $id) {
        $this->limitRole(self::ROLE_ADMIN);
        $this->template->category = $this->database->table("category")->get($id);
        $this["categoryForm"]->setDefaults($this->template->category);
     
    }

    public function createComponentCategoryForm()
    {
        $this->limitRole(self::ROLE_ADMIN);
        $form = new Form;
        $form->onRender = ["\Nette\Forms\BootstrapForm::makeBootstrap4"];

        $id = $this->getParameter("id");

        $form->addText('name', 'Jméno')
            ->setMaxLength(150)
            ->setRequired(true);

        $form->addText('color', 'Barva');

        $form->addTextArea("description", "Popis pro vytváření");
        $form->addInteger("hide", "Skrytí (0 = trvalý typ)");

        $form->addSubmit('send', 'Uložit');
        

        $form->onSuccess[] = function ($form, $values) use ($id) {
            if($id) {
                $this->database->table("category")->get($id)->update($values);
            }
            else {
              
                $this->database->table("category")->insert($values);
            }
            $this->redirect("default");
        };

        return $form;
    }

}
