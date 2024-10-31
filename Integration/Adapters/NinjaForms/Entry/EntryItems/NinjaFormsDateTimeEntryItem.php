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

class NinjaFormsDateTimeEntryItem extends EntryItemBase
{
    public $Date;
    public $Time;
    public $Unix;
    public $FormattedDateTime;
    protected function InternalGetObjectToSave()
    {
        return (object)array(
            'Value'=>$this->FormattedDateTime,
            'Date'=>$this->Date,
            'Time'=>$this->Time,
            'Unix'=>$this->Unix
        );
    }


    public function InitializeWithValues($field,$formattedDateTime,$date,$time,$unix)
    {
        $this->Initialize($field);
        $this->FormattedDateTime=$formattedDateTime;
        $this->Date=$date;
        $this->Time=$time;
        $this->Unix=$unix;

        return $this;
    }

    public function InitializeWithOptions($field,$options)
    {
        $this->Field=$field;
        if(isset($options->Value))
            $this->FormattedDateTime=$options->Value;
        if(isset($options->Unix))
            $this->Unix=$options->Unix;
        if(isset($options->Time))
            $this->Time=$options->Time;
        if(isset($options->Date))
            $this->Date=$options->Date;
    }

    public function GetHtml($style='standard',$field=null)
    {
        return new BasicPHPFormatter($this->FormattedDateTime);
    }
}