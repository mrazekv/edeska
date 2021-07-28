<?php

declare(strict_types=1);

namespace App\Presenters;

use Nette\Utils\DateTime;
use Nette;


final class HomepagePresenter extends BasePresenter
{
    public function actionDefault($category = null, bool $hidden = false, int $tempPage = 1, int $longPage=1)  {
        $this->template->category = $category;
        $this->template->hidden = $hidden;
        $this->template->categories = $this->database->table("category")->order("name");

        $all_temp = $this->database->table("document")->where("hide IS NOT NULL")->order("show DESC");
        $all_long = $this->database->table("document")->where("hide IS NULL")->order("show DESC");

        if(!$hidden)
            $all_temp = $all_temp->where("hide >=  DATE_SUB(NOW(), INTERVAL 1 DAY)");

        if($category) {
            $all_temp = $all_temp->where("category", $category);
            $all_long = $all_long->where("category", $category);
        }

        $this->template->temp_count = $all_temp->count();
        $this->template->long_count = $all_long->count();


        $tempLastPage = 0;
		$this->template->all_temp = $all_temp->page($tempPage, 10, $tempLastPage);
		$this->template->tempPage = $tempPage;
        $this->template->tempLastPage = $tempLastPage;
        

        
        $longLastPage = 0;
        $this->template->all_long = $all_long->page($longPage, 10, $longLastPage);
        $this->template->longPage = $longPage;
        $this->template->longLastPage = $longLastPage;
        

        $this->template->now = DateTime::from(mktime(0, 0, 0));

        #->where("hide >= NOW()")
    }

    public function actionDetail($item) {
        $this->template->now = DateTime::from(mktime(0, 0, 0));
        $this->template->document = $item;
        //if($this->user_db->role >= 1)
        $this->template->requestId = "_item_{$item->id}"; // hack pro uredni desku
        //dump($id);
    }

    private function getDocumentObject($d) {
        $c = $d->ref("category");
        $ret = (object)[
            "name" => $d->name,
            "description" => $d->description,
            "show" => $d->show,
            "hide" => $d->hide,
            "category" => $c->name,
            "color" => $c->color,
            "files" => [],
            "link" => $this->link("//Homepage:detail", $d)
        ];

        foreach($d->related("file")->order("name") as $f) {
            $ret->files[] = (object)[
                "name" => $f->name,
                "link" => $this->template->baseUrl . "/data/" . $f->filename,
                "mime" => $f->mime
            ];
        }

        return $ret;
    }
    public function actionLatestJson($count = 3) {
        $all = $this->database->table("document")
            ->where("hide IS NULL OR (hide >=  DATE_SUB(NOW(), INTERVAL 1 DAY))")
            ->order("show DESC")
            ->limit($count);
        
        $data = [];
        foreach($all as $d) {
            $data[] = $this->getDocumentObject($d);
        }

        $this->sendJson($data);
    }


    public function actionLatestCategoryJson($category, $count = 3) {
        $all = $this->database->table("document")
            ->where("hide IS NULL OR (hide >=  DATE_SUB(NOW(), INTERVAL 1 DAY))")
            ->where("category = ?", $category->id)
            ->order("show DESC")
            ->limit($count);
        
        $data = [];
        foreach($all as $d) {
            $data[] = $this->getDocumentObject($d);
        }

        $this->sendJson($data);
        $this->sendJson($all);
    }

    public function actionTest() {
        $bl = $this->storeRequest();
        echo $this->link("//test2", [$bl]);
        die();
    }

    public function actionTest2($backlink) {
        $this->restoreRequest($backlink);
        die("fail");
    }


}
