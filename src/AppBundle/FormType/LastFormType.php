<?php
/** 
 * Date: 7/9/15
 * Time: 7:07 PM
 */

namespace AppBundle\FormType;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class LastFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $formBuilder, array $options)
    {
        $formBuilder
            ->add("name", "text")
            ->add("type", "choice", ["choices" => ["top" => "Top", "recent" => "Recent", "loved" => "Loved"]])
            ->add("submit", "submit");
        return $formBuilder;

    }
    public function getName()
    {
        return "last_type";
    }
}