<?php

/*
 * This file is part of the Elcodi package.
 *
 * Copyright (c) 2014-2016 Elcodi Networks S.L.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Feel free to edit as you please, and have fun.
 *
 * @author Marc Morera <yuhu@mmoreram.com>
 * @author Aldo Chiecchia <zimage@tiscali.it>
 * @author Elcodi Team <tech@elcodi.com>
 */

namespace Elcodi\Component\EntityTranslator\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Elcodi\Component\EntityTranslator\Services\Interfaces\EntityTranslationProviderInterface;

/**
 * Class TranslatableFieldType.
 */
class TranslatableFieldType extends AbstractType
{
    const NAME = 'translatable_field';

    /**
     * @var EntityTranslationProviderInterface
     *
     * Entity Translation provider
     */
    protected $entityTranslationProvider;

    /**
     * @var FormConfigInterface
     *
     * Form Config
     */
    protected $formConfig;

    /**
     * @var object
     *
     * Entity
     */
    protected $entity;

    /**
     * @var string
     *
     * Field name
     */
    protected $fieldName;

    /**
     * @var array
     *
     * Entity configuration
     */
    protected $entityConfiguration;

    /**
     * @var array
     *
     * Field configuration
     */
    protected $fieldConfiguration;

    /**
     * @var array
     *
     * Locales
     */
    protected $locales;

    /**
     * @var string
     *
     * Master locale
     */
    protected $masterLocale;

    /**
     * @var bool
     *
     * Fallback is enabled.
     *
     * If a field is required and the fallback flag is enabled, all translations
     * will not be required anymore, but just the translation with same language
     * than master
     */
    protected $fallback;

    /**
     * @param EntityTranslationProviderInterface $entityTranslationProvider Entity Translation provider
     */
    public function __construct(
        EntityTranslationProviderInterface $entityTranslationProvider
    ) {
        $this->entityTranslationProvider = $entityTranslationProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired([
            'formConfig',
            'fieldName',
            'entityConfiguration',
            'locales',
            'masterLocale',
            'originalFormType',
        ]);
    }

    /**
     * Buildform function.
     *
     * @param FormBuilderInterface $builder the formBuilder
     * @param array                $options the options for this form
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var  FormConfigInterface $formConfig */
        $entityConfiguration = $options['entityConfiguration'];
        $fieldName           = $options['fieldName'];
        $locales             = $options['locales'];
        $formConfig          = $options['formConfig'];
        $entity              = $builder->getData();
        $fieldType           = $options['originalFormType'];

        if (isset($fieldOptions['required'])) {
            $builder->setRequired($fieldOptions['required']);
        }

        $entityAlias = $entityConfiguration['alias'];
        $entityIdGetter = $entityConfiguration['idGetter'];

        $fieldOptions = $formConfig->getOptions();

        foreach ($locales as $locale) {
            $translatedFieldName = $locale . '_' . $fieldName;

            $entityId = $entity->$entityIdGetter();
            $translationData = $entityId
                ? $this
                    ->entityTranslationProvider
                    ->getTranslation(
                        $entityAlias,
                        $entityId,
                        $fieldName,
                        $locale
                    )
                : '';

            $builder->add($translatedFieldName, $fieldType, [
                'required' => isset($fieldOptions['required']) ? $fieldOptions['required'] : false,
                'mapped' => false,
                'label' => $fieldOptions['label'],
                'data' => $translationData,
                'constraints' => $fieldOptions['constraints'],
            ]);
        }
    }

    /**
     * Check the require value.
     *
     * @param FormView $view
     * @param FormInterface $form
     * @param array $options
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['default_locale'] = $options['masterLocale'];
    }

    /**
     * Returns the name of this type.
     *
     * @return string The name of this type
     */
    public function getName()
    {
        return self::NAME;
    }
}
