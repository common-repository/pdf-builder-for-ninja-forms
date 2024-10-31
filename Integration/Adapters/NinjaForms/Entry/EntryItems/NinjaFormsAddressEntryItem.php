<?php
/**
 * Created by PhpStorm.
 * User: Edgar
 * Date: 3/28/2019
 * Time: 5:25 AM
 */

namespace rednaoformpdfbuilder\Integration\Adapters\NinjaForms\Entry\EntryItems;


use rednaoformpdfbuilder\Integration\Processors\Entry\EntryItems\EntryItemBase;
use rednaoformpdfbuilder\Integration\Processors\Entry\HTMLFormatters\MultipleLineFormatter;

class NinjaFormsAddressEntryItem extends EntryItemBase
{
    public $Address1;
    public $Address2;
    public $City;
    public $State;
    public $Postal;
    protected function InternalGetObjectToSave()
    {
        return (object)array(
            'Value'=>$this->Address1.', '.$this->Address2.', '.$this->City.', '.$this->State.', '.$this->Postal,
            'Address1'=>$this->Address1,
            'Address2'=>$this->Address2,
            'City'=>$this->City,
            'State'=>$this->State,
            'Postal'=>$this->Postal
        );
    }

    public function InitializeWithValues($field,$address1,$address2,$city,$state,$postal)
    {
        $this->Initialize($field);
        $this->Address1=$address1;
        $this->Address2=$address2;
        $this->City=$city;
        $this->State=$state;
        $this->Postal=$postal;
        return $this;
    }

    public function InitializeWithOptions($field,$options)
    {
        $this->Address1='';
        $this->Address2='';
        $this->City='';
        $this->State='';
        $this->Postal='';

        $this->Field=$field;
        if(isset($this->Address1))
            $this->Address1=$options->Address1;
        if(isset($this->Address2))
            $this->Address2=$options->Address2;
        if(isset($this->City))
            $this->City=$options->City;
        if(isset($this->State))
            $this->State=$options->State;
        if(isset($this->Postal))
            $this->Postal=$options->Postal;
    }

    public function GetHtml($style='standard',$field=null)
    {
        $formatter=new MultipleLineFormatter();

        if($this->Address1!='')
            $formatter->AddLine($this->Address1);
        if($this->Address2!='')
            $formatter->AddLine($this->Address2);
        if($this->City!='')
            $formatter->AddLine($this->City);
        if($this->State!='')
            $formatter->AddLine($this->State);
        if($this->Postal!='')
            $formatter->AddLine($this->Postal);

        return $formatter;
    }
}