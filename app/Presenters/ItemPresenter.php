<?php

declare(strict_types=1);

namespace App\Presenters;

use DateInterval;
use Nette;
use Nette\Application\UI\Form;
use Nette\Utils\DateTime;
use Nextras\FormComponents\Controls\DateControl;


final class ItemPresenter extends BaseAdminPresenter
{
    const MIME_TYPES = [
        "application/msword", 
        "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
        "text/plain", "application/pdf", "image/*"];


    function actionDefault(bool $hidden = false, int $page = 1, string $search = "") {
        $this->template->hidden = $hidden;

        $docs = $this->database->table("document")->order("show DESC");
        if(!$hidden)
            $docs = $docs->where("hide IS NULL OR hide > DATE_SUB(NOW(), INTERVAL 1 DAY)");

        if($search) {
            $this["searchForm"]->setDefaults(["search" => $search]);
            $ss = "%{$search}%";
            $docs = $docs->where("(name LIKE ? OR description LIKE ?)", $ss, $ss);
        }

        $lastPage = 0;
        $this->template->documents = $docs->page($page, 50, $lastPage);
        $this->template->page = $page;
        $this->template->lastPage = $lastPage;
        $this->template->requestId = $this->storeRequest();

        $this->template->now = new DateTime();
    }

    public function createComponentSearchForm()
    {
        $form = new Form;
        $form->onRender = ["\Nette\Forms\BootstrapForm::makeBootstrap4"];

        $form->addText("search", "Hledaný výraz");
        $form->addSubmit('send', 'Hledat');

        $document = $this->database->table("document")->get($this->getParameter("id"));

        $form->onSuccess[] = function ($form, $values) use ($document) {
            $this->redirect("default", ["hidden" => true, "search" => $values->search]);
        };

        return $form;
    }



    function actionNew() {     
        $this->template->categories = $this->database->table("category")->order("name");
    }

    function actionNewFiles(int $category) {     
        //dump($_FILES);
        //dump(mime_content_type ($_FILES["files"]["tmp_name"][0]));
        $this->template->category = $this->database->table("category")->get($category);
    }

    function actionView(int $id) {

    }


    
    function actionEdit(int  $id, $backlink = "") {
        $this->template->document = $this->database->table("document")->get($id);
        $this["editDocumentForm"]->setDefaults($this->template->document);
    }

    function actionFiles(int  $id, $backlink = "") {
        $this->template->document = $this->database->table("document")->get($id);

        $this->template->files = $this->template->document->related("file")->order("name");
        $this->template->requestId = $this->storeRequest();
        $this->template->backlink = $backlink;
        #$this["editDocumentForm"]->setDefaults($this->template->document);
    }
    function actionBack($backlink = "", $document = null) {
        $this->restoreRequest($backlink);
        if($document)
            $this->redirect("Homepage:detail", $document);

        $this->redirect("default");
    }

    function actionDeleteFile($id, $backlink) {
        $file = $this->database->table("file")->get($id);
        $document = $file->ref("document");
        @unlink("data/$file->filename");
        $file->delete();
        $this->restoreRequest($backlink);
        $this->redirect("files", $document->id);
    }

    function actionDelete($id, $backlink) {
        
        $this->limitRole(self::ROLE_ADMIN);


        $document = $this->database->table("document")->get($id);

        $files = $this->database->table("file")->where("document = ?", $document->id);
        foreach($files as $file) {
            @unlink("data/$file->filename");
            $file->delete();
        }

        $document->delete();
        $this->restoreRequest($backlink);
        $this->redirect("files", $document->id);
    }
    
    
    function actionHide($id, $backlink = "") {
        $document = $this->database->table("document")->get($id);
        $document->update(["hide" => (new DateTime())->sub(new DateInterval("P1D"))]);
        $this->restoreRequest($backlink);
        $this->redirect("default");
    }
    
    function actionShow($id, $backlink = "") {
        $document = $this->database->table("document")->get($id);
        $document->update(["hide" => null]);
        $this->restoreRequest($backlink);
        $this->redirect("default");
    }

    public function createComponentEditDocumentForm()
    {
        $form = new Form;
        $form->onRender = ["\Nette\Forms\BootstrapForm::makeBootstrap4"];


        $form->addText('name', 'Jméno')
            ->setMaxLength(150)
            ->setOption('description', 'Jméno podle názvu dokumentu')
            ->setRequired(true);

        $cat = [];
        foreach($this->database->table("category")->order("name") as $c)
            $cat[$c->id] = $c->name;
        $form->addSelect('category', "Kategorie", $cat)
        ->setOption('description', 'Už nemá vliv na datum skrytí!');

        $form->addTextArea('description', "Popis dokumentu")
            ->setOption('description', 'Popis, co je v dokumentu, aby uživatel poznal, co je uvnitř')                  
            ->setRequired(false);

        $form['show'] = (new DateControl('Datum vložení dokumentu'))
            ->setOption('description', 'Kdy byl dokument vložen');

        
        $form['hide'] = (new DateControl('Datum skrytí dokumentu'))
            ->setOption('description', 'Skrytí dokumentu (pokud se nemá skrýt, použijte "delete")')
            ->setNullable();;

        $form->addSubmit('send', 'Uložit');

        $form->onSuccess[] = function ($form, $values) {
            $id = $this->getParameter("id");
            $document = $this->database->table("document")->get($id);
            $document->update($values);
            $this->restoreRequest($this->getParameter("backlink"));
            $this->redirect("Homepage:detail", [$document]);
        };

        return $form;
    }

    
    public function createComponentNewFilesForm()
    {
        $form = new Form;
        $form->onRender = ["\Nette\Forms\BootstrapForm::makeBootstrap4"];

        $form->addMultiUpload("files", "Soubory k přidání")
            ->addRule($form::MIME_TYPE, "Musí se jednat o PDF, obrázek případně dokument Office", array_merge(self::MIME_TYPES, ["application/octet-stream"]))
            ->setHtmlAttribute('accept', implode(",", self::MIME_TYPES))
            ->setOption('description', 'Preferujeme soubory PDF. Můžete nahrát více souborů najednou (stačí použít CTRL)');
        $form->addSubmit('send', 'Uložit');

        $document = $this->database->table("document")->get($this->getParameter("id"));

        $form->onSuccess[] = function ($form, $values) use ($document) {

            foreach($values->files as $file) {
                if(!$file->isOk()) {
                    $form->addError("Nepodařilo se nahrát soubor {$file->name}", "danger");
                    continue;
                }

                $db_file = $this->database->table("file")->insert([
                    "document" => $document->id,
                    "filename" => null,
                    "mime" => $file->getContentType(),
                    "name" => $file->name
                ]);

                $new_name = sprintf("%05d_%s", $db_file->id, $file->getSanitizedName());
                $file->move("./data/$new_name");
                $db_file->update(["filename"=>$new_name]);
                $this->flashMessage("Soubor {$file->name} byl nahrán", "success");

            }
        };

        return $form;
    }


    
    public function createComponentRenameFileForm()
    {
        $form = new Form;
        $form->onRender = ["\Nette\Forms\BootstrapForm::makeBootstrap4"];

        $form->addText("name", "Jméno souboru");
        $form->addInteger("fileid", "ID souboru");
        $form->addText("backlink", "Backlink");
        $form->addSubmit('send', 'Přejmenovat');

        $document = $this->database->table("document")->get($this->getParameter("id"));

        $form->onSuccess[] = function ($form, $values) use ($document) {
            $file = $this->database->table("file")->get($values->fileid);
            $file->update(["name" => $values->name]);
            $this->restoreRequest($values->backlink);
        };

        return $form;
    }

    public function createComponentNewItemsForm()
    {
        $form = new Form;
        $form->onRender = ["\Nette\Forms\BootstrapForm::makeBootstrap4"];


        $form->addText('name', 'Jméno')
            ->setMaxLength(150)
            ->setOption('description', 'Jméno podle názvu dokumentu')
            ->setRequired(true);
        $form->addTextArea('description', "Popis dokumentu")
            ->setOption('description', 'Popis, co je v dokumentu, aby uživatel poznal, co je uvnitř')      
            
            ->setRequired(false);
        
        $form->addMultiUpload("files", "Soubory k přidání")
            ->addRule($form::MIME_TYPE, "Musí se jednat o PDF, obrázek případně dokument Office", array_merge(self::MIME_TYPES, ["application/octet-stream"]))
            ->setHtmlAttribute('accept', implode(",", self::MIME_TYPES))
            ->setOption('description', 'Preferujeme soubory PDF. Můžete nahrát více souborů najednou (stačí použít CTRL)');

        $form['date_insert'] = (new DateControl('Datum vložení dokumentu'))
            ->setOption('description', 'Kdy byl dokument vložen, od této doby se počítá lhůta pro skrytí')
            ->setDefaultValue(new DateTime()) ;

             

        $form->addSubmit('send', 'Uložit');
        $category = $this->database->table("category")->get($this->getParameter("category"));

        $form->onSuccess[] = function ($form, $values) use ($category) {
            $hide = null;
            if($category->hide) {
                $hide = $values->date_insert->add(new DateInterval("P{$category->hide}D"));
            }

            $document = $this->database->table("document")->insert([
                "name"=>$values->name,
                "category" => $category->id,
                "description" => $values->description,
                "show" => $values->date_insert,
                "hide" => $hide
                ]);


            foreach($values->files as $file) {
                if(!$file->isOk()) {
                    $form->addError("Nepodařilo se nahrát soubor {$file->name}", "danger");
                    continue;
                }

                $db_file = $this->database->table("file")->insert([
                    "document" => $document->id,
                    "filename" => null,
                    "mime" => $file->getContentType(),
                    "name" => $file->name
                ]);

                $new_name = sprintf("%05d_%s", $db_file->id, $file->getSanitizedName());
                $file->move("./data/$new_name");
                $db_file->update(["filename"=>$new_name]);
                $this->flashMessage("Soubor {$file->name} byl nahrán", "success");

            }

            $this->redirect("default");
        };

        return $form;
    }

    public function actionImport() {
        // update `document` set hide = NULL where hide="0000-00-00"

        // upraveno 21. 4. 2021
        //die("only once");
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $db2 = new \mysqli("olomucan2.dbaserver.net", "olomucan2", "HKwZgZLo", "olomucan2");
        $db2->query("set names utf8");
        echo "<pre>";
        $all = $db2->query("SELECT * from dp_document where nid > 2072");

        while($upload = $all->fetch_object()) {
            $node = $db2->query("select * from dp_node where nid = {$upload->nid}")->fetch_object();
            if(!$node) continue;

            if(!file_exists("../../old/{$upload->filename}")) continue;


            

            dump($node);
            dump($upload);
            //continue;

            // category:
            // 0: vyhlaska - 3
            // 1: oznameni - 6
            // 2: ozv - 1
            switch((int)($upload->category)) {
                case 0: $category = 3; break;
                case 1: $category = 6; break;
                case 2: $category = 1; break;
            }

            $document = $this->database->table("document")->insert([
                "name" => $node->title,
                "show" => DateTime::from($node->created),
                "hide" => (new DateTime($upload->hide)),
                "category" => $category,
                "description" => ""
            ]
            );

            echo $node->title . " ... " . DateTime::from($node->created) . " - " . (new DateTime($upload->hide)) . " ... cat" . $upload->category . "\n";
            foreach([$upload->filename, $upload->filename2, $upload->filename3, $upload->filename4, $upload->filename5] as $fn) {
                if(!$fn) continue;
                @ $fs = filesize("../../$fn");
                echo $fs;
                if(!$fs) continue;
                echo $fn . " .. " . strtolower($this->sanitize(basename($fn))) . "\n";
                $file = $this->database->table("file")->insert([
                    "document" => $document->id,
                    "mime" => mime_content_type("../../$fn"),
                    "name" => basename($fn),
                    "filename" => null
                ]);
                
                $fn2 = sprintf("%05d_%s", $file->id, strtolower($this->sanitize(basename($fn))));
                copy("../../old/$fn", "data/$fn2");
                $file->update(["filename"=>$fn2]);
            }
        }


        echo "</pre>";


        exit();
    }

    private function sanitize($name) {
        return trim(Nette\Utils\Strings::webalize($name, '.', false), '.-');
    }

}
