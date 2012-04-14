<?php
namespace DotsPages\Form;
use Zend\Form\Form,
    DotsPages\Form\PageMeta;

class Page extends Form
{

    public function init()
    {
        //Set elements
        $this->addElement('hidden', 'id', array('decorators'=>array('ViewHelper')));
        $this->addElement('text', 'title', array(
            'label'=>'Title',
            'required'=>true,
        ));
        $this->addElement('text', 'alias', array(
            'label' => 'Alias / Uri',
            'required' => true
        ));
        $this->addElement('select', 'template', array(
            'label' => 'Template',
            'multiOptions'=>array(
                'dots-pages/pages/page'=>'Default page',
                'dots-pages/pages/two-columns'=>'Two Columns',
                'dots-pages/pages/home'=>'Homepage',
            ),
            'required' => true
        ));
        $this->addElement('select', 'language', array(
            'label' => 'Language',
            'multiOptions'=>array(
                'en'=>'English',
                'de'=>'German',
                'ro'=>'Romanian',
            ),
            'required' => true
        ));

        // Set display group
        $this->addDisplayGroup(
            array('id', 'title', 'alias', 'template', 'language'),
            'page',
            array(
                'legend'=>'Page Settings',
                'description'=>'Fill out the form to set up the general settings of the page.'
            )
        );

        // Set decorators for the form
        $this->setDecorators(array(
            'FormElements',
            'FormDecorator',
        ));

        $this->setDisplayGroupDecorators(array(
            'FormElements',
            array('HtmlTag', array('tag' => 'dl')),
            array('Description',array('placement'=>'prepend')),
            'Fieldset',
        ));

        // Add metadata form to the page form
        $metaForm = new PageMeta();
        $metaForm->setIsArray(true);
        $this->addSubForm($metaForm, 'meta');
    }

}