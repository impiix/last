<?php
/** 
 * Date: 7/9/15
 * Time: 7:07 PM
 */

namespace AppBundle\FormType;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class LastFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $formBuilder, array $options)
    {
        $formBuilder
            ->add("name", TextType::class)
            ->add("type", ChoiceType::class, [
                "choices" => ["Top" => "top", "Recent" => "recent", "Loved" => "loved"],
                'expanded' => true,
                'choices_as_values' => true
            ])
            ;
        return $formBuilder;

    }
}