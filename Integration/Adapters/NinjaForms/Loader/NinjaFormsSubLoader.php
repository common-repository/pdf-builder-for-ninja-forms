<?php


namespace rednaoformpdfbuilder\Integration\Adapters\NinjaForms\Loader;
use rednaoformpdfbuilder\core\Loader;
use rednaoformpdfbuilder\htmlgenerator\generators\PDFGenerator;
use rednaoformpdfbuilder\Integration\Adapters\NinjaForms\FormProcessor\NinjaFormsFormProcessor;
use rednaoformpdfbuilder\pr\PRLoader;
use rednaoformpdfbuilder\Integration\Adapters\NinjaForms\Entry\Retriever\NinjaFormsEntryRetriever;

class NinjaFormsSubLoader extends Loader
{

    public function __construct($rootFilePath,$config)
    {
        $this->ItemId=382;
        $prefix='rednaopdfninja';
        $formProcessorLoader=new NinjaFormsProcessorLoader($this);
        $formProcessorLoader->Initialize();
        parent::__construct($prefix,$formProcessorLoader,$rootFilePath,$config);
        $this->AddMenu('Ninja Forms PDF Builder',$prefix.'_pdf_builder','pdfbuilder_manage_templates','','Pages/BuilderList.php');
        if($this->IsPR())
        {
            $this->PRLoader=new PRLoader($this);
        }else{
            $this->AddMenu('Entries',$prefix.'_pdf_builder_entries','manage_options','','Pages/EntriesFree.php');
        }
    }

    public function AddPDFLink($message,$formData)
    {
        global $RNWPCreatedEntry;
        if(!isset($RNWPCreatedEntry['CreatedDocuments']))
            return $message;

        if(\strpos($message,'[wpformpdflink]')===false)
            return $message;

        $links=array();
        foreach($RNWPCreatedEntry['CreatedDocuments'] as $createdDocument)
        {
            $data=array(
              'entryid'=>$RNWPCreatedEntry['EntryId'],
              'templateid'=>$createdDocument['TemplateId'],
              'nonce'=>\wp_create_nonce($this->Prefix.'_'.$RNWPCreatedEntry['EntryId'].'_'.$createdDocument['TemplateId'])
            );
            $url=admin_url('admin-ajax.php').'?data='.\json_encode($data).'&action='.$this->Prefix.'_view_pdf';
            $links[]='<a target="_blank" href="'.esc_attr($url).'">'.\esc_html($createdDocument['Name']).'.pdf</a>';
        }

        $message=\str_replace('[wpformpdflink]',\implode($links),$message);

        return $message;


    }

    /**
     * @return NinjaFormsEntryRetriever
     */
    public function CreateEntryRetriever()
    {
        return new NinjaFormsEntryRetriever($this);
    }


    public function AddBuilderScripts()
    {
        $this->AddScript('wpformbuilder','js/dist/WPFormBuilder_bundle.js',array('jquery', '@react','@builder'));
    }

    public function GetPurchaseURL()
    {
        return 'https://pdfbuilder.rednao.com/get-it-ninja-forms/';
    }

    public function GetForm($formId)
    {
        global $wpdb;
        $results=$wpdb->get_results($wpdb->prepare("select forms.id FormId,title Title, field.label Label,field.type Type,field.key FieldKey,field.id FieldId
                                            from ".$wpdb->prefix."nf3_forms forms
                                            join ".$wpdb->prefix."nf3_fields field
                                            on forms.id=field.parent_id
                                            where forms.id=%d or %d=-1
                                            order by FormId",$formId,$formId));
        if($results==null)
            return null;

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
                $currentForm=new \stdClass();
                $currentForm->Id=$form->FormId;
                $currentForm->Fields=[];
                $currentForm->Name=$form->Title;

                $currentForm->actions=[];
            }

            $field=new \stdClass();
            $currentForm->Fields[]=$field;
            $field->Id=$form->FieldId;
            $field->Type=$form->Type;
            $field->Label=$form->Label;
            $field->Key=$form->FieldKey;



            //    $this->SaveOrUpdateForm($form);
        }

        $form=null;
        if($currentForm!=null)
        {
            /** @var NinjaFormsFormProcessor $processor */
            $processor=$this->ProcessorLoader->FormProcessor;
            $form=$processor->SerializeForm($currentForm);
        }

        return $form;

    }

    public function GetEntry($entryId)
    {
        global $wpdb;
        $entry=$wpdb->get_results($wpdb->prepare('select meta_key Id, meta_value Value from '.$wpdb->postmeta.' where post_id=%d',$entryId));
        if($entry==null)
            return;

        $dictionary=new \stdClass();
        foreach($entry as $row)
        {
            $id=$row->Id;
            $dictionary->$id=$row->Value;
        }

        $dictionary->form_id=$wpdb->get_var($wpdb->prepare('select meta_value from '.$wpdb->postmeta.' where post_id=%d and meta_key="_form_id"',$entryId));
        return $dictionary;
    }


}