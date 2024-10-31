<?php
/**
 * Created by PhpStorm.
 * User: Edgar
 * Date: 3/19/2019
 * Time: 11:38 AM
 */

namespace rednaoformpdfbuilder\Integration\Adapters\NinjaForms\Loader;


use rednaoformpdfbuilder\Integration\Adapters\NinjaForms\Entry\NinjaFormsEntryProcessor;
use rednaoformpdfbuilder\Integration\Adapters\NinjaForms\FormProcessor\NinjaFormsFormProcessor;
use rednaoformpdfbuilder\Integration\Processors\Loader\ProcessorLoaderBase;

class NinjaFormsProcessorLoader extends ProcessorLoaderBase
{

    public function Initialize()
    {
        $this->FormProcessor=new NinjaFormsFormProcessor($this->Loader);
        $this->EntryProcessor=new NinjaFormsEntryProcessor($this->Loader);
    }
}