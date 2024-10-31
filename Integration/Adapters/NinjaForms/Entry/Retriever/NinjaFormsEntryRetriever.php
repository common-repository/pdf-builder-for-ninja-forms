<?php
/**
 * Created by PhpStorm.
 * User: Edgar
 * Date: 3/28/2019
 * Time: 4:30 AM
 */

namespace rednaoformpdfbuilder\Integration\Adapters\NinjaForms\Entry\Retriever;


use rednaoformpdfbuilder\Integration\Adapters\NinjaForms\Entry\NinjaFormsEntryProcessor;
use rednaoformpdfbuilder\Integration\Adapters\NinjaForms\Settings\Forms\NinjaFormsFieldSettingsFactory;
use rednaoformpdfbuilder\Integration\Processors\Entry\EntryItems\MultipleSelectionEntryItem;
use rednaoformpdfbuilder\Integration\Processors\Entry\EntryItems\MultipleSelectionValueItem;
use rednaoformpdfbuilder\Integration\Processors\Entry\EntryProcessorBase;
use rednaoformpdfbuilder\Integration\Processors\Entry\Retriever\EntryRetrieverBase;
use rednaoformpdfbuilder\Integration\Processors\Settings\Forms\FieldSettingsFactoryBase;
use rednaoformpdfbuilder\Integration\Processors\Settings\Forms\FormSettings;

class NinjaFormsEntryRetriever extends EntryRetrieverBase
{


    /**
     * @return FieldSettingsFactoryBase
     */
    public function GetFieldSettingsFactory()
    {
        return new NinjaFormsFieldSettingsFactory();
    }

    /**
     * @return EntryProcessorBase
     */
    protected function GetEntryProcessor()
    {
        return $this->Loader->ProcessorLoader->EntryProcessor;
    }

    public function GetProductItems()
    {
        $items=array();
        foreach($this->EntryItems as $item)
        {
            switch ($item->Field->SubType)
            {
                case 'payment-select':
                case 'payment-multiple':
                    /** @var MultipleSelectionEntryItem $multipleItem */
                    $multipleItem=$item;

                    foreach($multipleItem->Items as $valueItem)
                    {
                        $items[]= array('name'=>$valueItem->Value,'price'=>$valueItem->Amount);
                    }
                break;
                case 'payment-single':
                $items[]=array('name'=>$item->Field->Label,'price'=>$item->Value);
                    break;
            }
        }

        return $items;
    }
}