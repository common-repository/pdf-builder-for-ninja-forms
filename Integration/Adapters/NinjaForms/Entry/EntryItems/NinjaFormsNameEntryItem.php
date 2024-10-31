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

class NinjaFormsNameEntryItem extends EntryItemBase
{
    public $FirstName;
    public $LastName;
    protected function InternalGetObjectToSave()
    {
        return (object)array(
            'Value'=>$this->FirstName.' '.$this->LastName,
            'FirstName'=>$this->FirstName,
            'LastName'=>$this->LastName
        );
    }


    public function InitializeWithValues($field,$firstName,$lastName)
    {
        $this->Initialize($field);
        $this->FirstName=$firstName;
        $this->LastName=$lastName;
        return $this;
    }

    public function InitializeWithOptions($field,$options)
    {
        $this->Field=$field;
        if(isset($options->FirstName))
            $this->FirstName=$options->FirstName;
        if(isset($options->LastName))
            $this->LastName=$options->LastName;
    }

    public function GetHtml($style='standard',$field=null)
    {
        return new BasicPHPFormatter($this->FirstName.' '.$this->LastName);
    }
}