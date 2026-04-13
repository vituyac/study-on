<?php

namespace App\Form;

use App\Entity\Lesson;
use App\Form\DataTransformer\CourseToIdTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LessonType extends AbstractType
{
    public function __construct(
        private CourseToIdTransformer $courseToIdTransformer,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('course', HiddenType::class)
            ->add('title', TextType::class, [
                'label' => 'Название',
                'empty_data' => '',
            ])
            ->add('content', TextareaType::class, [
                'label' => 'Содержание',
                'empty_data' => '',
                'attr' => [
                    'rows' => 6,
                ]
            ])
            ->add('ordering', IntegerType::class, [
                'label' => 'Порядок',
                'required' => false,
                'invalid_message' => 'Порядок должен быть числом',
            ])
        ;

        $builder->get('course')->addModelTransformer($this->courseToIdTransformer);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Lesson::class,
        ]);
    }
}
