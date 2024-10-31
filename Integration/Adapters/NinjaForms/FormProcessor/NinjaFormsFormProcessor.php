<?php
/**
 * Created by PhpStorm.
 * User: Edgar
 * Date: 3/19/2019
 * Time: 11:39 AM
 */

namespace rednaoformpdfbuilder\Integration\Adapters\NinjaForms\FormProcessor;



use rednaoformpdfbuilder\Integration\Adapters\NinjaForms\Settings\Forms\Fields\NinjaFormsAddressFieldSettings;
use rednaoformpdfbuilder\Integration\Adapters\NinjaForms\Settings\Forms\Fields\NinjaFormsDateFieldSettings;
use rednaoformpdfbuilder\Integration\Adapters\NinjaForms\Settings\Forms\Fields\NinjaFormsNameFieldSettings;
use rednaoformpdfbuilder\Integration\Processors\FormProcessor\FormProcessorBase;
use rednaoformpdfbuilder\Integration\Processors\Settings\Forms\EmailNotification;
use rednaoformpdfbuilder\Integration\Processors\Settings\Forms\Fields\FileUploadFieldSettings;
use rednaoformpdfbuilder\Integration\Processors\Settings\Forms\Fields\MultipleOptionsFieldSettings;
use rednaoformpdfbuilder\Integration\Processors\Settings\Forms\Fields\NumberFieldSettings;
use rednaoformpdfbuilder\Integration\Processors\Settings\Forms\Fields\FieldSettingsBase;
use rednaoformpdfbuilder\Integration\Processors\Settings\Forms\Fields\TextFieldSettings;
use rednaoformpdfbuilder\Integration\Processors\Settings\Forms\FormSettings;
use stdClass;
use Svg\Tag\Text;

class NinjaFormsFormProcessor extends FormProcessorBase
{
    public function __construct($loader)
    {
        parent::__construct($loader);
        \add_action('ninja_forms_save_form',array($this,'FormIsSaving'),10);
    }

    public function FormIsSaving($formId){
        if(isset($forms['post_content']))
        {
            $forms['post_content'] = \stripslashes($forms['post_content']);
            $forms = $this->SerializeForm($forms);
        }
        else
        {
            $forms = \json_decode(\stripslashes($_POST['form']));
            $forms = $this->SerializeFormV2($forms);
        }
        $this->SaveOrUpdateForm($forms);
    }

    public function SerializeFormV2($form){

        $formSettings=new FormSettings();
        if(isset($form->actions))
        {
            foreach($form->actions as $currentAction)
            {
                if($currentAction->settings->type=='email')
                {
                    $formSettings->EmailNotifications[]=new EmailNotification($currentAction->id,$currentAction->settings->label);
                }
            }
        }



        $formSettings->OriginalId=$form->id;
        $formSettings->Name=$form->settings->title;
        $formSettings->Fields=$this->SerializeFieldsV2($form->fields);


        return $formSettings;
    }


    public function SerializeFieldsV2($fieldList)
    {
        /** @var FieldSettingsBase[] $fieldSettings */
        $fieldSettings=array();

        foreach($fieldList as $field)
        {
            switch($field->settings->type)
            {
                case 'textarea':
                case 'email':
                case 'textbox':
                case 'firstname':
                case 'lastname':
                case 'phone':
                case 'address':
                case 'city':
                case 'liststate':
                case 'listcountry':
                case 'zip':
                case 'date':
                case 'checkbox':
                case 'confirm':
                case 'hidden':
                case 'number':
                case 'starrating':
                case 'firstname':
                case 'lastname':

                    $fieldSettings[]=(new TextFieldSettings())->Initialize($field->id,$field->settings->label,$field->settings->type);
                    break;

                case 'listcheckbox':
                case 'listmultiselect':
                case 'listradio':
                case 'listselect':
                    $settings=(new MultipleOptionsFieldSettings())->Initialize($field->id,$field->settings->label,$field->settings->type);
                    if(isset($field->settings->options))
                    {
                        foreach($field->settings->options as $option)
                        {
                            $settings->AddOption($option->label,$option->value,'');
                        }
                    }
                    $fieldSettings[]=$settings;
                    break;
                case 'file_upload':
                    $fieldSettings[]=(new FileUploadFieldSettings())->Initialize($field->id,$field->settings->label,$field->settings->type);
                    break;
            }
        }

        return $fieldSettings;
    }


    public function SerializeForm($form){

        $formSettings=new FormSettings();
        $formSettings->OriginalId=$form->Id;
        $formSettings->Name=$form->Name;
        $formSettings->Fields=$this->SerializeFields($form->Fields);

        if(isset($form->actions))
        {
            foreach($form->actions as $currentAction)
            {
                if($currentAction->settings->type=='email')
                {
                    $formSettings->EmailNotifications[]=new EmailNotification($currentAction->id,$currentAction->settings->label);
                }
            }
        }


        return $formSettings;
    }

    public function SerializeFields($fieldList)
    {
        /** @var FieldSettingsBase[] $fieldSettings */
        $fieldSettings=array();
        foreach($fieldList as $field)
        {
            switch($field->Type)
            {
                case 'textarea':
                case 'email':
                case 'textbox':
                case 'firstname':
                case 'lastname':
                case 'phone':
                case 'address':
                case 'city':
                case 'liststate':
                case 'listcountry':
                case 'zip':
                case 'date':
                case 'checkbox':
                case 'confirm':
                case 'hidden':
                case 'number':
                case 'starrating':
                case 'firstname':
                case 'lastname':
                    $fieldSettings[]=(new TextFieldSettings())->Initialize($field->Id,$field->Label,$field->Type);
                    break;

                case 'listcheckbox':
                case 'listmultiselect':
                case 'listselect':
                case 'listradio':
                    $settings=(new MultipleOptionsFieldSettings())->Initialize($field->Id,$field->Label,$field->Type);

                    global $wpdb;
                    $options=$wpdb->get_var($wpdb->prepare('select value from '.$wpdb->prefix.'nf3_field_meta meta where parent_id=%d and meta.key="options"',$field->Id));
                    if($options!==null)
                    {
                        $options=unserialize($options);
                        if($options!==false)
                        {
                            foreach($options as $currentOption)
                            {
                                if(isset($currentOption['label'])&&isset($currentOption['value']))
                                    $settings->AddOption($currentOption['label'],$currentOption['value']);
                            }
                        }

                    }


                    $fieldSettings[]=$settings;
                    break;
                case 'file_upload':
                    $fieldSettings[]=(new FileUploadFieldSettings())->Initialize($field->Id,$field->Label,$field->Type);
                    break;
            }
        }

        return $fieldSettings;
    }

    public function SyncCurrentForms($formId=-1)
    {
        global $wpdb;
        $results=$wpdb->get_results($wpdb->prepare("select forms.id FormId,title Title, field.label Label,field.type Type,field.key FieldKey,field.id FieldId
                                            from ".$wpdb->prefix."nf3_forms forms
                                            join ".$wpdb->prefix."nf3_fields field
                                            on forms.id=field.parent_id
                                            where forms.id=%d or %d=-1
                                            order by FormId",$formId,$formId));

       // $actions=$wpdb->get_results('select id,parent_id, type, label from '.$wpdb->prefix.'nf3_actions where type="email"');

        $currentForm=null;
        $formIds=array();
        foreach($results as $form)
        {
            if($currentForm==null|| $form->FormId!=$currentForm->Id)
            {
                $formIds[]=$form->FormId;
                if($currentForm!=null)
                {
                    $formToSave=$this->SerializeForm($currentForm);
                    $this->SaveOrUpdateForm($formToSave);
                }
                $currentForm=new stdClass();
                $currentForm->Id=$form->FormId;
                $currentForm->Fields=[];
                $currentForm->Name=$form->Title;

                $currentForm->actions=[];

                $actions=Ninja_Forms()->form( $currentForm->Id )->get_actions();
                foreach($actions as $currentAction)
                {
                    if($currentAction->get_setting('type')=='email')
                    {
                        $currentForm->actions[]=(object)array(
                            'id'=>$currentAction->get_id(),
                            'settings'=>(object)array(
                                'label'=>$currentAction->get_setting('label'),
                                'type'=>'email'
                            )
                        );
                    }
                }

            }

            $field=new stdClass();
            $currentForm->Fields[]=$field;
            $field->Id=$form->FieldId;
            $field->Type=$form->Type;
            $field->Label=$form->Label;
            $field->Key=$form->FieldKey;



        //    $this->SaveOrUpdateForm($form);
        }

        if($currentForm!=null)
        {
            $form=$this->SerializeForm($currentForm);
            $this->SaveOrUpdateForm($form);
        }

        $how_many = count($formIds);
        $placeholders = array_fill(0, $how_many, '%d');
        $format = implode(', ', $placeholders);

        $query = "delete from ".$this->Loader->FormConfigTable." where original_id not in($format)";
        $wpdb->query($wpdb->prepare($query,$formIds));
    }

    public function GetFormList()
    {
        global $wpdb;

        $rows= $wpdb->get_results("select id Id, name Name, fields Fields,original_id OriginalId,notifications Notifications from ".$this->Loader->FormConfigTable );
        return $rows;
    }
}