<?php
/**
 * Nette Forms & Bootstap v4 rendering example.
 */
declare(strict_types=1);


namespace Nette\Forms;

use Nette\Forms\Form;


class BootstrapForm {
    public static function makeBootstrap4(Form $form): void
    {
        $renderer = $form->getRenderer();
        $renderer->wrappers['controls']['container'] = null;
        $renderer->wrappers['controls']['error'] = "ul class='alert alert-danger'";
        $renderer->wrappers['pair']['container'] = 'div class="mb3"';
        $renderer->wrappers['pair']['.error'] = 'has-danger';
        $renderer->wrappers['control']['container'] = null;// 'div class=col-sm-9';
        $renderer->wrappers['label']['container'] = 'div class="col-form-label"';
        $renderer->wrappers['control']['description'] = 'span class=form-text';
        $renderer->wrappers['control']['errorcontainer'] = 'span class=form-control-feedback';
        $renderer->wrappers['control']['.error'] = 'is-invalid';
        
        //dump($renderer);
        foreach ($form->getControls() as $control) {
            $type = $control->getOption('type');
            if ($type === 'button') {
                $control->getControlPrototype()->addClass(empty($usedPrimary) ? 'btn btn-primary' : 'btn btn-secondary');
                $usedPrimary = true;

            } elseif (in_array($type, ['text', 'textarea', 'select'], true)) {
                $control->getControlPrototype()->addClass('form-control');

            } elseif ($type === 'file') {
                $control->getControlPrototype()->addClass('form-control');

            } elseif (in_array($type, ['checkbox', 'radio'], true)) {
                if ($control instanceof \Nette\Forms\Controls\Checkbox) {
                    $control->getLabelPrototype()->addClass('form-check-label');
                } else {
                    $control->getItemLabelPrototype()->addClass('form-check-label');
                }
                //$control->getControlPrototype()->addClass('form-check-input');
                $control->getSeparatorPrototype()->setName('div')->addClass('form-check');
            }
        }
    }
}