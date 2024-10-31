<?php
/**
 * Created by PhpStorm.
 * User: Edgar
 * Date: 3/21/2019
 * Time: 6:15 AM
 */

namespace rednaoformpdfbuilder\Integration\Adapters\NinjaForms\Settings\Forms\Fields;
use rednaoformpdfbuilder\Integration\Processors\Settings\NinjaForms\Fields\FieldSettingsBase;

class NinjaFormsAddressFieldSettings extends FieldSettingsBase
{

    public function GetType()
    {
        return 'Address';
    }
}