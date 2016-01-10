<?php
/** 
 * Date: 7/9/15
 * Time: 7:07 PM
 */

namespace AppBundle\FormType;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LastFormType extends AbstractType
{
    const SUBMIT_FOLLOW = 'follow';

    public function buildForm(FormBuilderInterface $formBuilder, array $options)
    {
        $formBuilder
            ->add("name", TextType::class)
            ->add("type", ChoiceType::class, [
                "choices" => ["Top" => "top", "Recent" => "recent", "Loved" => "loved"],
                'expanded' => true,
                'choices_as_values' => true,
                'required' => false
            ])
            ->add("submit", SubmitType::class, ['label' => 'Submit', 'attr' => ['value' => 'submit']])
            ->add("follow", SubmitType::class, ['label' => 'Follow', 'attr' => ['value' => 'follow']])
            ;
        return $formBuilder;

    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(['csrf_protection' => false]);
    }
}