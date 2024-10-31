<?php
/**
 * Created by PhpStorm.
 * User: Edgar
 * Date: 3/22/2019
 * Time: 5:03 AM
 */

namespace rednaoformpdfbuilder\Integration\Adapters\NinjaForms\Entry;


use DateTime;
use DateTimeZone;
use Exception;
use rednaoformpdfbuilder\htmlgenerator\generators\PDFGenerator;
use rednaoformpdfbuilder\Integration\Adapters\NinjaForms\Entry\EntryItems\NinjaFormsAddressEntryItem;
use rednaoformpdfbuilder\Integration\Adapters\NinjaForms\Entry\EntryItems\NinjaFormsDateTimeEntryItem;
use rednaoformpdfbuilder\Integration\Adapters\NinjaForms\Entry\EntryItems\NinjaFormsFileUploadEntryItem;
use rednaoformpdfbuilder\Integration\Adapters\NinjaForms\Entry\EntryItems\NinjaFormsNameEntryItem;
use rednaoformpdfbuilder\Integration\Adapters\NinjaForms\Entry\Retriever\NinjaFormsEntryRetriever;
use rednaoformpdfbuilder\Integration\Adapters\NinjaForms\FormProcessor\NinjaFormsFormProcessor;
use rednaoformpdfbuilder\Integration\Processors\Entry\EntryItems\DropDownEntryItem;
use rednaoformpdfbuilder\Integration\Processors\Entry\EntryItems\EntryItemBase;
use rednaoformpdfbuilder\Integration\Processors\Entry\EntryItems\MultipleSelectionEntryItem;
use rednaoformpdfbuilder\Integration\Processors\Entry\EntryItems\SimpleTextEntryItem;
use rednaoformpdfbuilder\Integration\Processors\Entry\EntryProcessorBase;
use rednaoformpdfbuilder\Integration\Processors\Settings\Forms\Fields\FieldSettingsBase;
use rednaoformpdfbuilder\Integration\Processors\Settings\Forms\Fields\MultipleOptionsFieldSettings;
use rednaoformpdfbuilder\Integration\Processors\Settings\Forms\Fields\TextFieldSettings;
use rednaoformpdfbuilder\Integration\Processors\Settings\Forms\FormSettings;
use stdClass;

class NinjaFormsEntryProcessor extends EntryProcessorBase
{
    public function __construct($loader)
    {
        parent::__construct($loader);
        \add_action('nf_save_sub',array($this,'SaveEntry'));
        \add_action('ninja_forms_action_email_attachments',array($this,'AddAttachment'),10,3);
        \add_shortcode('bpdfbuilder_download_link',array($this,'AddPDFLink'));
        add_action('ninja_forms_action_email_message',array($this,'ProcessEmailMessage'),10,3);

    }

    public function ProcessEmailMessage($message,$data,$actionSettings)
    {
        if(isset($data['actions'])&&isset($data['actions']['save'])&&isset($data['actions']['save']['sub_id']))
        {
            global $wpdb;
            $entry=$wpdb->get_results($wpdb->prepare('select meta_key Id, meta_value Value from '.$wpdb->postmeta.' where post_id=%d',$data['actions']['save']['sub_id']));
            if($entry==null)
                return;

            $dictionary=new stdClass();
            foreach($entry as $row)
            {
                $id=$row->Id;
                $dictionary->$id=$row->Value;
            }

            return $this->MaybeUpdateEmailBody($message,$data['form_id'],$data['actions']['save']['sub_id'],$dictionary);
        }

        return $message;
    }


    public function AddPDFLink($attrs,$content){
        $message='Click here to download';
        if(isset($attrs['message']))
            $message=$attrs['message'];

        $templateId=0;
        if(isset($attrs['templateid']))
            $templateId=$attrs['templateid'];
        if(!isset($_SESSION['Ninja_Generated_PDF']))
            return;

        $pdfData=$_SESSION['Ninja_Generated_PDF'];


        global $wpdb;
        $result=$wpdb->get_results($wpdb->prepare(
            "select template.id Id,template.pages Pages, template.document_settings DocumentSettings,styles Styles,form_id FormId
                    from ".$this->Loader->FormConfigTable." form
                    join ".$this->Loader->TEMPLATES_TABLE." template
                    on form.id=template.form_id
                    where original_id=%s and (%s=0 or template.id=%s)"
            ,$_SESSION['Ninja_Generated_PDF']['FormId'],$templateId,$templateId));
        foreach($result as $templateSettings)
        {

            $nonce=\wp_create_nonce('view_'.$_SESSION['Ninja_Generated_PDF']['EntryId'].'_'.$templateSettings->Id);
            $url=admin_url('admin-ajax.php').'?action='.$this->Loader->Prefix.'_view_pdf'.'&nonce='.\urlencode($nonce).'&templateid='.$templateSettings->Id.'&entryid='.$_SESSION['Ninja_Generated_PDF']['EntryId'];
            return "<a target='_blank' href='$url'>".\esc_html($message)."</a>";

            break;

        }





    }


    public function UpdateOriginalEntryId($entryId,$formData)
    {
        if(!isset($formData['fields']))
            return;
        global $RNWPCreatedEntry;
        if(!isset($RNWPCreatedEntry)||!isset($RNWPCreatedEntry['Entry']))
            return;

        global $wpdb;
        $wpdb->update($this->Loader->RECORDS_TABLE,array(
            'original_id'=>$entryId
        ),array('id'=>$RNWPCreatedEntry['EntryId']));

    }


    public function SaveEntry($entryId){
        global $wpdb;
        $entry=$wpdb->get_results($wpdb->prepare('select meta_key Id, meta_value Value from '.$wpdb->postmeta.' where post_id=%d',$entryId));
        if($entry==null)
            return;

        $dictionary=new stdClass();
        foreach($entry as $row)
        {
            $id=$row->Id;
            $dictionary->$id=$row->Value;
        }




        $serializedEntry=$this->SerializeEntry($dictionary);
        if($serializedEntry==null)
            return;

        if(!\rednaoformpdfbuilder\Utils\Sanitizer::SanitizeBoolean(get_option($this->Loader->Prefix.'_skip_save',false)))
            $entryId=$this->SaveEntryToDB($dictionary->{'_form_id'},$serializedEntry,$entryId);
        global $RNWPCreatedEntry;
        $RNWPCreatedEntry=array(
            'Entry'=>$serializedEntry,
            'FormId'=>$dictionary->{'_form_id'},
            'OriginalId'=>$dictionary->{'_form_id'},
            'EntryId'=>$entryId,
            'Raw'=>$dictionary
        );

        $_SESSION['Ninja_Generated_PDF']=array(
            'EntryId'=>$entryId,
            'FormId'=>$dictionary->{'_form_id'}
        );
    }

    public function AddAttachment($attachment,$target,$settings)
    {
        global $wpdb;
        $entryRetriever=null;
        if(!isset($RNWPCreatedEntry)||!isset($RNWPCreatedEntry['Entry']))
        {
            $RNWPCreatedEntry=[];
            if($target!=null&&isset($target['settings'])&&isset($target['fields']))
            {

                $dictionary=new stdClass();
                foreach($target['fields'] as $row)
                {
                    $id="_field_".$row['id'];
                    $value=$row['value'];

                    if(is_array($value))
                        $value=serialize($value);

                    $dictionary->$id=$value;
                }
                $dictionary->_form_id=$target['form_id'];

                $serializedEntry=$this->SerializeEntry($dictionary);
                if($serializedEntry==null)
                    return $attachment;

                $entryRetriever=new NinjaFormsEntryRetriever($this->Loader);
                $fields=$wpdb->get_var($wpdb->prepare('select fields from '.$this->Loader->FormConfigTable.' where original_id=%s',$target['form_id']));
                $entryRetriever->InitializeByEntryItems($serializedEntry,$dictionary,$fields);
/*
                $fields=$wpdb->get_var($wpdb->prepare('select fields from '.$this->Loader->FormConfigTable.' where original_id=%s',$target['form_id']));


                $entryRetriever=new NinjaFormsEntryRetriever($this->Loader);
                $entryRetriever->InitializeByEntryItems($RNWPCreatedEntry['Entry'],$RNWPCreatedEntry['Raw'],$fields);

                $formProcessor=new NinjaFormsFormProcessor($this->Loader);
                $formSettings=$formProcessor->

                global $wpdb;
                $RNWPCreatedEntry['Entry'] = $this->SerializeEntry($wpFormSettings->fields,$formSettings);
                $RNWPCreatedEntry['FormId']=$wpFormSettings->form_data['id'];
                $RNWPCreatedEntry['EntryId']='';
                $RNWPCreatedEntry['Raw']=json_encode($wpFormSettings->fields);
                $RNWPCreatedEntry['EntryId']=$wpdb->get_var($wpdb->prepare('select id from '.$this->Loader->RECORDS_TABLE.' where original_id=%d',$wpFormSettings->entry_id));
*/
            }else
                return $attachment;
        }

        global $wpdb;
        $result=$wpdb->get_results($wpdb->prepare(
            "select template.id Id,template.pages Pages, template.document_settings DocumentSettings,styles Styles,form_id FormId
                    from ".$this->Loader->FormConfigTable." form
                    join ".$this->Loader->TEMPLATES_TABLE." template
                    on form.id=template.form_id
                    where original_id=%s"
        ,$target['form_id']));
        $files=[];

        foreach($result as $templateSettings)
        {
            $templateSettings->Pages=\json_decode($templateSettings->Pages);
            $templateSettings->DocumentSettings=\json_decode($templateSettings->DocumentSettings);

            if(isset($templateSettings->DocumentSettings->Notifications)&&count($templateSettings->DocumentSettings->Notifications)>0)
            {
                $found=false;
                foreach($templateSettings->DocumentSettings->Notifications as $attachToNotificationId)
                {
                    if($attachToNotificationId==$settings['id'])
                        $found=true;
                }

                if(!$found)
                    continue;
            }

            $generator=(new PDFGenerator($this->Loader,$templateSettings,$entryRetriever));
            if(!$generator->ShouldAttach())
            {
                continue;
            }
            $path=$generator->SaveInTempFolder();


            $attachment[]=$path;



        }



        return $attachment;


    }

    public function SerializeEntry($entry,$formSettings=null)
    {
        global $wpdb;
        $formSettings=$this->Loader->ProcessorLoader->FormProcessor->GetFormByOriginalId($entry->_form_id);
        if($formSettings==null)
            return null;
        /** @var EntryItemBase $entryItems */
        $entryItems=array();
        foreach($formSettings->Fields as $field)
        {
            if(!isset($entry->{'_field_'.$field->Id}))
                continue;
            $currentEntry=$entry->{'_field_'.$field->Id};
            switch($field->SubType)
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
                case 'confirm':
                case 'hidden':
                case 'number':
                case 'starrating':
                    $entryItems[]=(new SimpleTextEntryItem())->Initialize($field)->SetValue($currentEntry);
                    break;
                case 'checkbox':
                    if($currentEntry=='1')
                        $currentEntry=$field->Label;
                    else
                        $currentEntry='';
                    $entryItems[]=(new SimpleTextEntryItem())->Initialize($field)->SetValue($currentEntry);
                    break;
                case 'listcheckbox':
                case 'listmultiselect':

                    $options=\unserialize($currentEntry);
                    if($options==null)
                        break;
                    $entryItems[]=(new DropDownEntryItem())->Initialize($field)->SetValue($options);
                    break;
                case 'listradio':
                case 'listselect':
                    $item=(new DropDownEntryItem())->Initialize($field);
                    $item->AddItem($currentEntry,0);

                    $entryItems[]=$item;
                    break;
                case 'file_upload':
                    if($currentEntry=='')
                        break;
                    $url=\array_values (\unserialize($currentEntry));


                    $item=(new NinjaFormsFileUploadEntryItem())->InitializeWithValues($field,$url,'','');
                    $entryItems[]=$item;
                    break;

            }
        }


        return $entryItems;

    }

    public function InflateEntryItem(FieldSettingsBase $field,$entryData)
    {
        $entryItem=null;
        switch($field->SubType)
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
            case 'date-time':
            case 'checkbox':
            case 'listradio':
            case 'listselect':
            case 'confirm':
            case 'hidden':
            case 'number':
            case 'starrating':
                $entryItem=new SimpleTextEntryItem();
                break;

            case 'listcheckbox':
            case 'listmultiselect':
                $entryItem=new DropDownEntryItem();
                break;
            case 'file_upload':
               $entryItem=new NinjaFormsFileUploadEntryItem();
                break;
        }

        if($entryItem==null)
            throw new Exception("Invalid entry sub type ".$field->SubType);
        $entryItem->InitializeWithOptions($field,$entryData);
        return $entryItem;
    }


}