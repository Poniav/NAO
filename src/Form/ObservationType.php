<?php
namespace App\Form;

use App\Entity\Bird;
use App\Entity\Observation;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use App\Form\BirdType;
use Vich\UploaderBundle\Form\Type\VichImageType;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use Symfony\Component\HttpFoundation\File\File;
/**
 * Class ObservationType
 * @package App\Form
 * @Vich\Uploadable
 */
class ObservationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('latitude',HiddenType::class, array('label' => 'Ajouter la latitude : '))
            ->add('longitude',HiddenType::class, array('label' => 'Ajouter la longitude : '))
            ->add('pictureFile',VichImageType::class, array('label' => 'Ajouter une photo de l\'oiseau : '))
            ->add('bird',EntityType::class, array(
                'label' => 'Choisir l\'oiseau',
                'class' => Bird::class,
            ))
            ->add('description', TextareaType::class, array('label' => 'Ajouter une courte description : '))
        ;
    }
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Observation::class,
        ]);
    }
}