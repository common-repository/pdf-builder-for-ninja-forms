<?php
/**
 * Created by PhpStorm.
 * User: Edgar
 * Date: 3/21/2019
 * Time: 6:19 AM
 */

namespace rednaoformpdfbuilder\Integration\Adapters\NinjaForms\Settings\Forms\Fields;
use rednaoformpdfbuilder\Integration\Processors\Settings\Forms\Fields\FieldSettingsBase;

class NinjaFormsNameFieldSettings extends FieldSettingsBase
{

    public function GetType()
    {
        return 'Name';
    }
}