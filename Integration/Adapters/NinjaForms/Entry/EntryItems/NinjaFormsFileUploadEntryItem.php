<?php
/**
 * Created by PhpStorm.
 * User: Edgar
 * Date: 3/28/2019
 * Time: 5:15 AM
 */

namespace rednaoformpdfbuilder\Integration\Adapters\NinjaForms\Entry\EntryItems;


use rednaoformpdfbuilder\Integration\Processors\Entry\EntryItems\EntryItemBase;
use rednaoformpdfbuilder\Integration\Processors\Entry\HTMLFormatters\BasicPHPFormatter;
use rednaoformpdfbuilder\Integration\Processors\Entry\HTMLFormatters\LinkFormatter;
use rednaoformpdfbuilder\Integration\Processors\Entry\HTMLFormatters\MultipleLinkFormatter;

class NinjaFormsFileUploadEntryItem extends EntryItemBase
{
    public $URLS;
    public $FileName;
    public $Ext;
    public $OriginalName;


    protected function InternalGetObjectToSave()
    {
        return (object)array(
            'Value'=>$this->URLS,
            'FileName'=>'',
            'Ext'=>'',
            'OriginalName'=>''
        );
    }


    public function InitializeWithValues($field,$urls)
    {
        $this->Initialize($field);
        $this->URLS=$urls;
        $this->FileName='';
        $this->Ext='';
        $this->OriginalName='';

        return $this;
    }

    public function InitializeWithOptions($field,$options){
        $this->Field=$field;
        if(isset($options->Value))
            $this->URLS=$options->Value;
        if(isset($options->FileName))
            $this->FileName=$options->FileName;
        if(isset($options->Ext))
            $this->Ext=$options->Ext;
        if(isset($options->OriginalName))
            $this->OriginalName=$options->OriginalName;
    }

    public function GetHtml($style='standard',$field=null)
    {
        $item=new MultipleLinkFormatter();
        foreach($this->URLS as $currentUrl)
            $item->AddItem($currentUrl,$currentUrl);
        return $item;
    }
}