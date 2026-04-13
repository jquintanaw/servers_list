<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('fullName', TextType::class, [
                'required' => false,
                'label' => 'Nombre completo',
                'attr' => [
                    'maxlength' => 100,
                ],
            ])
            ->add('address', TextType::class, [
                'required' => false,
                'label' => 'Dirección',
                'attr' => [
                    'maxlength' => 255,
                ],
            ])
            ->add('city', TextType::class, [
                'required' => false,
                'label' => 'Ciudad',
                'attr' => [
                    'maxlength' => 100,
                ],
            ])
            ->add('country', TextType::class, [
                'required' => false,
                'label' => 'País',
                'attr' => [
                    'maxlength' => 100,
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}