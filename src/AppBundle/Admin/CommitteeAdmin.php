<?php

namespace AppBundle\Admin;

use AppBundle\Committee\CommitteeManager;
use AppBundle\Entity\Committee;
use AppBundle\Entity\CommitteeMembership;
use AppBundle\Intl\UnitedNationsBundle;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\QueryBuilder;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class CommitteeAdmin extends AbstractAdmin
{
    protected $datagridValues = [
        '_page' => 1,
        '_per_page' => 32,
        '_sort_order' => 'DESC',
        '_sort_by' => 'createdAt',
    ];

    private $manager;
    private $committeeMembershipRepository;
    private $cachedDatagrid;

    public function __construct($code, $class, $baseControllerName, CommitteeManager $manager, ObjectManager $om)
    {
        parent::__construct($code, $class, $baseControllerName);

        $this->manager = $manager;
        $this->committeeMembershipRepository = $om->getRepository(CommitteeMembership::class);
    }

    /**
     * {@inheritdoc}
     */
    public function getDatagrid()
    {
        if (!$this->cachedDatagrid) {
            $this->cachedDatagrid = new CommitteeDatagrid(parent::getDatagrid(), $this->manager);
        }

        return $this->cachedDatagrid;
    }

    protected function configureShowFields(ShowMapper $showMapper)
    {
        $showMapper
            ->with('Comité', array('class' => 'col-md-7'))
                ->add('name', null, [
                    'label' => 'Nom',
                ])
                ->add('description', null, [
                    'label' => 'Description',
                    'attr' => [
                        'rows' => '3',
                    ],
                ])
                ->add('slug', null, [
                    'label' => 'Slug',
                ])
                ->add('facebookPageUrl', 'url', [
                    'label' => 'Facebook',
                ])
                ->add('twitterNickname', null, [
                    'label' => 'Twitter',
                ])
                ->add('googlePlusPageUrl', 'url', [
                    'label' => 'Google+',
                ])
                ->add('status', null, [
                    'label' => 'Status',
                ])
                ->add('createdAt', null, [
                    'label' => 'Date de création',
                ])
                ->add('approvedAt', null, [
                    'label' => 'Date d\'approbation',
                ])
                ->add('refusedAt', null, [
                    'label' => 'Date de refus',
                ])
            ->end()
            ->with('Adresse', array('class' => 'col-md-5'))
                ->add('postAddress.address', TextType::class, [
                    'label' => 'Rue',
                ])
                ->add('postAddress.postalCode', TextType::class, [
                    'label' => 'Code postal',
                ])
                ->add('postAddress.cityName', TextType::class, [
                    'label' => 'Ville',
                ])
                ->add('postAddress.country', CountryType::class, [
                    'label' => 'Pays',
                ])
                ->add('postAddress.latitude', TextType::class, [
                    'label' => 'Latitude',
                ])
                ->add('postAddress.longitude', TextType::class, [
                    'label' => 'Longitude',
                ])
            ->end()
        ;
    }

    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->with('Comité', array('class' => 'col-md-7'))
                ->add('name', null, [
                    'label' => 'Nom',
                ])
                ->add('description', null, [
                    'label' => 'Description',
                    'attr' => [
                        'rows' => '3',
                    ],
                ])
                ->add('slug', null, [
                    'label' => 'Slug',
                ])
                ->add('facebookPageUrl', 'url', [
                    'label' => 'Facebook',
                    'required' => false,
                ])
                ->add('twitterNickname', null, [
                    'label' => 'Twitter',
                    'required' => false,
                ])
                ->add('googlePlusPageUrl', 'url', [
                    'label' => 'Google+',
                    'required' => false,
                ])
            ->end()
            ->with('Localisation', array('class' => 'col-md-5'))
                ->add('postAddress.latitude', TextType::class, [
                    'label' => 'Latitude',
                ])
                ->add('postAddress.longitude', TextType::class, [
                    'label' => 'Longitude',
                    'help' => 'Pour modifier l\'adresse, impersonnifiez un animateur de ce comité.',
                ])
            ->end()
        ;
    }

    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $committeeMembershipRepository = $this->committeeMembershipRepository;
        $manager = $this->manager;

        $datagridMapper
            ->add('id', null, [
                'label' => 'ID',
            ])
            ->add('name', null, [
                'label' => 'Nom',
            ])
            ->add('createdAt', 'doctrine_orm_date_range', [
                'label' => 'Date de création',
                'field_type' => 'sonata_type_date_range_picker',
            ])
            ->add('hostFirstName', 'doctrine_orm_callback', [
                'label' => 'Prénom de l\'animateur',
                'show_filter' => true,
                'field_type' => 'text',
                'callback' => function ($qb, $alias, $field, $value) use ($committeeMembershipRepository, $manager) {
                    /* @var QueryBuilder $qb */

                    if (!$value['value']) {
                        return;
                    }

                    $ids = $committeeMembershipRepository->findCommitteesUuidByHostFirstName($value['value']);

                    if (!$ids) {
                        // Force no results when no user is found
                        $qb->andWhere($qb->expr()->in(sprintf('%s.id', $alias), [0]));

                        return true;
                    }

                    $qb->andWhere($qb->expr()->in(sprintf('%s.uuid', $alias), $ids));

                    return true;
                },
            ])
            ->add('hostLastName', 'doctrine_orm_callback', [
                'label' => 'Nom de l\'animateur',
                'show_filter' => true,
                'field_type' => 'text',
                'callback' => function ($qb, $alias, $field, $value) use ($committeeMembershipRepository, $manager) {
                    /* @var QueryBuilder $qb */

                    if (!$value['value']) {
                        return;
                    }

                    $ids = $committeeMembershipRepository->findCommitteesUuidByHostLastName($value['value']);

                    if (!$ids) {
                        // Force no results when no user is found
                        $qb->andWhere($qb->expr()->in(sprintf('%s.id', $alias), [0]));

                        return true;
                    }

                    $qb->andWhere($qb->expr()->in(sprintf('%s.uuid', $alias), $ids));

                    return true;
                },
            ])
            ->add('postalCode', 'doctrine_orm_callback', [
                'label' => 'Code postal',
                'show_filter' => true,
                'field_type' => 'text',
                'callback' => function ($qb, $alias, $field, $value) {
                    /* @var QueryBuilder $qb */

                    if (!$value['value']) {
                        return;
                    }

                    $qb->andWhere(sprintf('%s.postAddress.postalCode', $alias).' LIKE :postalCode');
                    $qb->setParameter('postalCode', $value['value'].'%');

                    return true;
                },
            ])
            ->add('city', 'doctrine_orm_callback', [
                'label' => 'Ville',
                'field_type' => 'text',
                'callback' => function ($qb, $alias, $field, $value) {
                    /* @var QueryBuilder $qb */

                    if (!$value['value']) {
                        return;
                    }

                    $qb->andWhere(sprintf('LOWER(%s.postAddress.cityName)', $alias).' LIKE :cityName');
                    $qb->setParameter('cityName', '%'.strtolower($value['value']).'%');

                    return true;
                },
            ])
            ->add('country', 'doctrine_orm_callback', [
                'label' => 'Pays',
                'show_filter' => true,
                'field_type' => 'choice',
                'field_options' => [
                    'choices' => array_flip(UnitedNationsBundle::getCountries()),
                ],
                'callback' => function ($qb, $alias, $field, $value) {
                    /* @var QueryBuilder $qb */

                    if (!$value['value']) {
                        return;
                    }

                    $qb->andWhere(sprintf('LOWER(%s.postAddress.country)', $alias).' = :country');
                    $qb->setParameter('country', strtolower($value['value']));

                    return true;
                },
            ])
            ->add('status', null, [
                'label' => 'Statut',
                'show_filter' => true,
                'field_type' => 'choice',
                'field_options' => [
                    'choices' => [
                        'En attente' => Committee::PENDING,
                        'Accepté' => Committee::APPROVED,
                        'Refusé' => Committee::REFUSED,
                    ],
                ],
            ])
        ;
    }

    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->add('id', null, [
                'label' => 'ID',
            ])
            ->addIdentifier('name', null, [
                'label' => 'Nom',
            ])
            ->add('postAddress.postalCode', null, [
                'label' => 'Code postal',
            ])
            ->add('postAddress.cityName', null, [
                'label' => 'Ville',
            ])
            ->add('postAddress.country', null, [
                'label' => 'Pays',
            ])
            ->add('membersCounts', null, [
                'label' => 'Membres',
            ])
            ->add('createdAt', null, [
                'label' => 'Date de création',
            ])
            ->add('hosts', TextType::class, [
                'label' => 'Animateur(s)',
                'template' => 'admin/committee_hosts.html.twig',
            ])
            ->add('status', TextType::class, [
                'label' => 'Statut',
                'template' => 'admin/committee_status.html.twig',
            ])
            ->add('_action', null, [
                'virtual_field' => true,
                'template' => 'admin/committee_actions.html.twig',
            ])
        ;
    }
}
