<?php
/**
 * Created by PhpStorm.
 * User: Edgar
 * Date: 3/28/2019
 * Time: 4:26 AM
 */

namespace rednaoformpdfbuilder\Integration\Adapters\NinjaForms\Settings\Forms;


use Exception;
use rednaoformpdfbuilder\Integration\Adapters\NinjaForms\Settings\Forms\Fields\NinjaFormsAddressFieldSettings;
use rednaoformpdfbuilder\Integration\Adapters\NinjaForms\Settings\Forms\Fields\NinjaFormsDateFieldSettings;
use rednaoformpdfbuilder\Integration\Adapters\NinjaForms\Settings\Forms\Fields\NinjaFormsNameFieldSettings;
use rednaoformpdfbuilder\Integration\Processors\Settings\Forms\Fields\FieldSettingsBase;
use rednaoformpdfbuilder\Integration\Processors\Settings\Forms\FieldSettingsFactoryBase;

class NinjaFormsFieldSettingsFactory extends FieldSettingsFactoryBase
{
    /**
     * @param $options
     * @return FieldSettingsBase
     * @throws Exception
     */
    public function GetFieldByOptions($options)
    {
        $field= parent::GetFieldByOptions($options);
        if($field!=null)
            return $field;

        switch ($options->Type)
        {
            case 'Address':
                $field=new NinjaFormsAddressFieldSettings();
                break;
            case 'Date':
                $field=new NinjaFormsDateFieldSettings();
                break;
            case 'Name':
                $field=new NinjaFormsNameFieldSettings();
                break;
        }

        if($field==null)
            throw new Exception('Invalid field settings type '.$options->Type);

        $field->InitializeFromOptions($options);
        return $field;
    }


}