<?php
// src/Form/PersonaValidationType.php
namespace App\Form;

use App\Entity\Persona;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;

class PersonaValidationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nombre', TextType::class, [
                'label' => 'Nombre',
                'attr' => ['placeholder' => 'Ingrese su nombre'],
                'required' => true,
            ])
            ->add('dni', TextType::class, [
                'label' => 'DNI',
                'attr' => ['placeholder' => 'Ingrese su DNI'],
                'required' => true,
            ])
            ->add('telefono', TextType::class, [
                'label' => 'Teléfono',
                'attr' => ['placeholder' => 'Ingrese su número de teléfono'],
                'required' => true,
            ])
            ->add('email', EmailType::class, [
                'label' => 'Correo',
                'attr' => ['placeholder' => 'Ingrese su correo'],
                'required' => true,
            ])
            ->add('tipoCita', ChoiceType::class, [
                'choices' => [
                    'Primera Consulta' => 'primera_consulta',
                    'Revisión' => 'revision',
                ],
                'expanded' => true,
                'multiple' => false,
                'required' => true,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Persona::class,
            'csrf_protection' => true, // Asegura que la protección CSRF esté habilitada
            'csrf_field_name' => '_token', // Nombre del campo que contiene el token
            'csrf_token_id' => 'persona_validation', // ID del token CSRF
        ]);
    }
}

