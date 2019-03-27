<?php
namespace Mapbender\CoreBundle\Element\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * 
 */
class LegendAdminType extends AbstractType
{

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'legend';
    }

    /**
     * @inheritdoc
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'application' => null
        ));
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('tooltip', 'text', array('required' => false))
            ->add('elementType', 'choice',
                array(
                'required' => true,
                'choices' => array(
                    "dialog" => "dialog",
                    "blockelement" => "blockelement")))
            ->add('autoOpen', 'checkbox', array(
                'required' => false,
                'label' => 'mb.core.admin.legend.label.autoopen',
                'label_attr' => array(
                    'class' => 'labelCheck',
                ),
            ))
            ->add('displayType', 'choice',
                array(
                'required' => true,
                'choices' => array(
                    "list" => "list")))
            ->add('target', 'target_element',
                array(
                'element_class' => 'Mapbender\\CoreBundle\\Element\\Map',
                'application' => $options['application'],
                'property_path' => '[target]',
                'required' => false))
            ->add('showSourceTitle', 'checkbox', array(
                'required' => false,
                'label' => 'mb.core.admin.legend.label.showsourcetitle',
                'label_attr' => array(
                    'class' => 'labelCheck',
                ),
            ))
            ->add('showLayerTitle', 'checkbox', array(
                'required' => false,
                'label' => 'mb.core.admin.legend.label.showlayertitle',
                'label_attr' => array(
                    'class' => 'labelCheck',
                ),
            ))
            ->add('showGroupedLayerTitle', 'checkbox', array(
                'required' => false,
                'label' => 'mb.core.admin.legend.label.showgroupedlayertitle',
                'label_attr' => array(
                    'class' => 'labelCheck',
                ),
            ))
        ;
    }

}