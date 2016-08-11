<?php

namespace AppBundle\Services;

use AppBundle\Entity\CPSUser;
use AppBundle\Logging\LogConstants;
use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\VarDumper\VarDumper;

/**
 * Class UserProvider
 * @package AppBundle\Services
 */
class CPSUserProvider implements UserProviderInterface
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var LoggerInterface
     */
    private $logger;


    /**
     * UserProvider constructor.
     * @param EntityManager   $em
     * @param LoggerInterface $logger
     */
    public function __construct(EntityManager $em, LoggerInterface $logger)
    {
        $this->em = $em;
        $this->logger = $logger;
    }

    /**
     * @param string $username
     * @return CPSUser
     */
    public function loadUserByUsername($username):CPSUser
    {
        $user = $this->getPersistedUser(['username' => $username]);
        if ($user instanceof CPSUser){
            return $user;
        }
        throw new UsernameNotFoundException("User $username not found");
    }

    /**
     * @param UserInterface $user
     * @return CPSUser
     */
    public function refreshUser(UserInterface $user):CPSUser
    {
        if (!$user instanceof CPSUser) {
            throw new UnsupportedUserException(
                sprintf('Instances of "%s" are not supported.', get_class($user))
            );
        }

        return $this->loadUserByUsername($user->getUsername());
    }

    /**
     * @param string $class
     * @return bool
     */
    public function supportsClass($class)
    {
        return $class == CPSUser::class;
    }

    private function getPersistedUser(array $conditions)
    {
        $repo = $this->em->getRepository('AppBundle:CPSUser');
        try {
            $user = $repo->findOneBy($conditions);
        } catch (\Exception $e) {
            $user = null;
        }

        return $user;
    }

    private function createUserFromArray(array $data):CPSUser
    {
        $user = new CPSUser();

        $fieldSetters = $this->getFieldSetters();
        foreach( $fieldSetters as $key => $callback){
            $callback($user, $data[$key], $this->logger);
        }

        $user->addRole('ROLE_USER')
            ->addRole('ROLE_CPS_USER')
            ->setEnabled(true)
            ->setPassword('');

        $this->em->persist($user);
        $this->logger->info(
            LogConstants::CPS_USER_CREATED, ['type' => $user->getType(), 'user' => $user]
        );
        $this->em->flush();

        return $user;
    }

    /**
     * @param array $data
     *
     * @return CPSUser
     */
    public function provideUser(array $data)
    {
        $user = $this->getPersistedUser(['codiceFiscale' => $data['codiceFiscale']]);
        if (!$user instanceof CPSUser){
            $user = $this->createUserFromArray($data);
        }
        return $user;
    }

    /**
     * @return array
     */
    private function getFieldSetters()
    {
        $fieldSetters = [
            'codiceFiscale' => function(CPSUser $user, $value){
                if (!$value){
                    throw new \Exception("Field codiceFiscale not found");
                }
                $user->setUsername($value);
                $user->setCodiceFiscale($value);
            },
            'capDomicilio' => function(CPSUser $user, $value){
                $user->setCapDomicilio($value);
            },
            'capResidenza' => function(CPSUser $user, $value){
                $user->setCapResidenza($value);
            },
            'cellulare' => function(CPSUser $user, $value){
                $user->setCellulare($value);
            },
            'cittaDomicilio' => function(CPSUser $user, $value){
                $user->setCittaDomicilio($value);
            },
            'cittaResidenza' => function(CPSUser $user, $value){
                $user->setCittaResidenza($value);
            },
            'cognome' => function(CPSUser $user, $value){
                $user->setCognome($value);
            },
            'dataNascita' => function(CPSUser $user, $value){
                $dateTime = \DateTime::createFromFormat('d/m/Y', $value);
                if ($dateTime instanceof \DateTime) {
                    $user->setDataNascita($dateTime);
                }
            },
            'emailAddress' => function(CPSUser $user, $value, LoggerInterface $logger){
                if ($value === null){
                    $user->setEmail($user->getId().'@'.CPSUser::FAKE_EMAIL_DOMAIN);
                    $logger->notice(
                        LogConstants::CPS_USER_CREATED_WITH_BOGUS_DATA, ['user' => $user]
                    );
                }else{
                    $user->setEmail($value);
                }
            },
            'emailAddressPersonale' => function(CPSUser $user, $value){
                $user->setEmailAlt($value);
            },
            'indirizzoDomicilio' => function(CPSUser $user, $value){
                $user->setIndirizzoDomicilio($value);
            },
            'indirizzoResidenza' => function(CPSUser $user, $value){
                $user->setIndirizzoResidenza($value);
            },
            'luogoNascita' => function(CPSUser $user, $value){
                $user->setLuogoNascita($value);
            },
            'nome' => function(CPSUser $user, $value){
                $user->setNome($value);
            },
            'provinciaDomicilio' => function(CPSUser $user, $value){
                $user->setProvinciaDomicilio($value);
            },
            'provinciaNascita' => function(CPSUser $user, $value){
                $user->setProvinciaNascita($value);
            },
            'provinciaResidenza' => function(CPSUser $user, $value){
                $user->setProvinciaResidenza($value);
            },
            'sesso' => function(CPSUser $user, $value){
                $user->setSesso($value);
            },
            'statoDomicilio' => function(CPSUser $user, $value){
                $user->setStatoDomicilio($value);
            },
            'statoNascita' => function(CPSUser $user, $value){
                $user->setStatoNascita($value);
            },
            'statoResidenza' => function(CPSUser $user, $value){
                $user->setStatoResidenza($value);
            },
            'telefono' => function(CPSUser $user, $value){
                $user->setTelefono($value);
            },
            'titolo' => function(CPSUser $user, $value){
                $user->setTitolo($value);
            },
            'x509certificate_issuerdn' => function(CPSUser $user, $value){
                $user->setX509certificateIssuerdn($value);
            },
            'x509certificate_subjectdn' => function(CPSUser $user, $value){
                $user->setX509certificateSubjectdn($value);
            },
            'x509certificate_base64' => function(CPSUser $user, $value){
                $user->setX509certificateBase64($value);
            }
        ];

        return $fieldSetters;
    }

}
