<?php

namespace AppBundle\Services;

use AppBundle\Entity\CPSUser;
use AppBundle\Logging\LogConstants;
use AppBundle\Model\IdCard;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * Class UserProvider
 *
 * @package AppBundle\Services
 */
class CPSUserProvider implements UserProviderInterface
{

  const EMAIL_BLACKLIST = array('noreply@infotn.it', 'nobody@infotn.it');

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
   *
   * @param EntityManager $em
   * @param LoggerInterface $logger
   */
  public function __construct(EntityManager $em, LoggerInterface $logger)
  {
    $this->em = $em;
    $this->logger = $logger;
  }

  /**
   * @param UserInterface $user
   *
   * @return CPSUser
   */
  public function refreshUser(UserInterface $user): CPSUser
  {
    if (!$user instanceof CPSUser) {
      throw new UnsupportedUserException(
        sprintf('Instances of "%s" are not supported.', get_class($user))
      );
    }

    return $this->loadUserByUsername($user->getUsername());
  }

  /**
   * @param string $username
   *
   * @return CPSUser
   */
  public function loadUserByUsername($username): CPSUser
  {
    $user = $this->getPersistedUser($username);
    if ($user instanceof CPSUser) {
      return $user;
    }
    throw new UsernameNotFoundException("User $username not found");
  }

  private function getPersistedUser($codiceFiscale)
  {
    $repo = $this->em->getRepository('AppBundle:CPSUser');
    try {
      $user = $repo->createQueryBuilder('cpsuser')
        ->where('upper(cpsuser.codiceFiscale) = upper(:codiceFiscale)')
        ->setParameter('codiceFiscale', $codiceFiscale)
        ->getQuery()->getOneOrNullResult();
    } catch (\Exception $e) {
      $user = null;
    }

    return $user;
  }

  /**
   * @param string $class
   *
   * @return bool
   */
  public function supportsClass($class)
  {
    return $class == CPSUser::class;
  }

  /**
   * @param array $data
   *
   * @return CPSUser
   */
  public function provideUser(array $data)
  {
    $user = $this->getPersistedUser($data['codiceFiscale']);
    if (!$user instanceof CPSUser) {
      $user = $this->createUserFromArray($data);
    } else {
      $this->updateRequiredFields($user, $data);
    }

    return $user;
  }

  private function createUserFromArray(array $data): CPSUser
  {
    $user = new CPSUser();

    $fieldSetters = $this->getCreateUserFieldSetters();
    foreach ($fieldSetters as $key => $callback) {
      if (isset($data[$key])) {
        $callback($user, $data[$key]);
      }
    }

    if ($user->getEmail() === null) {
      $user->setEmail($user->getId() . '@' . CPSUser::FAKE_EMAIL_DOMAIN);
      $this->logger->notice(
        LogConstants::CPS_USER_CREATED_WITH_BOGUS_DATA,
        ['user' => $user]
      );
    }

    if ($user->getEmail() && $user->getEmail() !== $user->getId() . '@' . CPSUser::FAKE_EMAIL_DOMAIN) {
      $user->setEmailContatto($user->getEmail());
    } elseif ($user->getCpsEmailPersonale()) {
      $user->setEmailContatto($user->getCpsEmailPersonale());
    }

    if ($user->getCpsCellulare()) {
      $user->setCellulareContatto($user->getCpsCellulare());
    }

    $user->addRole('ROLE_USER')
      ->addRole('ROLE_CPS_USER')
      ->setEnabled(true)
      ->setPassword('');

    $this->em->persist($user);
    $this->logger->info(
      LogConstants::CPS_USER_CREATED,
      ['type' => $user->getType(), 'user' => $user]
    );
    $this->em->flush();

    return $user;
  }

  /**
   * @return array
   */
  private function getCreateUserFieldSetters()
  {
    $fieldSetters = [
      'codiceFiscale' => function (CPSUser $user, $value) {
        $user->setUsername($value);
        $user->setCodiceFiscale($value);
      },
      'cognome' => function (CPSUser $user, $value) {
        $user->setCognome($value);
      },
      'dataNascita' => function (CPSUser $user, $value) {
        $dateTime = \DateTime::createFromFormat('d/m/Y', $value);
        if ($dateTime instanceof \DateTime) {
          $user->setDataNascita($dateTime);
        }
      },
      'luogoNascita' => function (CPSUser $user, $value) {
        $user->setCodiceNascita($value);
        $user->setLuogoNascita($user->getMunicipalityFromCode($value));
      },
      'provinciaNascita' => function (CPSUser $user, $value) {
        $user->setProvinciaNascita($value);
      },
      'statoNascita' => function (CPSUser $user, $value) {
        $user->setStatoNascita($value);
      },
      'sesso' => function (CPSUser $user, $value) {
        $user->setSesso($value);
      },
      'emailAddress' => function (CPSUser $user, $value) {
        $user->setEmail($value);
        $user->setCpsEmail($value);
      },
      'emailAddressPersonale' => function (CPSUser $user, $value) {
        $user->setCpsEmailPersonale($value);
      },
      'capDomicilio' => function (CPSUser $user, $value) {
        $user->setCpsCapDomicilio($value);
      },
      'capResidenza' => function (CPSUser $user, $value) {
        $user->setCpsCapResidenza($value);
      },
      'cellulare' => function (CPSUser $user, $value) {
        $user->setCpsCellulare($value);
      },
      'cittaDomicilio' => function (CPSUser $user, $value) {
        $user->setCpsCittaDomicilio($value);
      },
      'cittaResidenza' => function (CPSUser $user, $value) {
        $user->setCpsCittaResidenza($value);
      },
      'indirizzoDomicilio' => function (CPSUser $user, $value) {
        $user->setCpsIndirizzoDomicilio($value);
      },
      'indirizzoResidenza' => function (CPSUser $user, $value) {
        $user->setCpsIndirizzoResidenza($value);
      },
      'nome' => function (CPSUser $user, $value) {
        $user->setNome($value);
      },
      'provinciaDomicilio' => function (CPSUser $user, $value) {
        $user->setCpsProvinciaDomicilio($value);
      },
      'provinciaResidenza' => function (CPSUser $user, $value) {
        $user->setCpsProvinciaResidenza($value);
      },
      'statoDomicilio' => function (CPSUser $user, $value) {
        $user->setCpsStatoDomicilio($value);
      },
      'statoResidenza' => function (CPSUser $user, $value) {
        $user->setCpsStatoResidenza($value);
      },
      'telefono' => function (CPSUser $user, $value) {
        $user->setCpsTelefono($value);
      },
      'titolo' => function (CPSUser $user, $value) {
        $user->setCpsTitolo($value);
      },
      'x509certificate_issuerdn' => function (CPSUser $user, $value) {
        $user->setX509certificateIssuerdn($value);
      },
      'x509certificate_subjectdn' => function (CPSUser $user, $value) {
        $user->setX509certificateSubjectdn($value);
      },
      'x509certificate_base64' => function (CPSUser $user, $value) {
        $user->setX509certificateBase64($value);
      },
      'spidCode' => function (CPSUser $user, $value) {
        $user->setSpidCode($value);
      },
      'idCard' => function (CPSUser $user, $value) {
        try {
          $idCard = new IdCard();
          $data = explode(' ', $value);
          $idCard->setNumero($data[1]);
          $dataRilascio = \DateTime::createFromFormat('Y-m-d', $data[3]);
          if ($dataRilascio instanceof \DateTime) {
            $idCard->setDataRilascio($dataRilascio);
          }
          $dataScadenza = \DateTime::createFromFormat('Y-m-d', $data[4]);
          if ($dataScadenza instanceof \DateTime) {
            $idCard->setDataScadenza($dataScadenza);
          }
          $idCard->setComuneRilascio(str_replace("COMUNE", "", $data[2]));
          $user->setIdCard($idCard);
        } catch (\Exception $exception) {
          $this->logger->error(
            $exception->getMessage(),
            ['type' => $user->getType(), 'user' => $user]
          );
        }
      },
      'shibSessionId' => function (CPSUser $user, $value) {
        $user->setShibSessionId($value);
      },
      'shibSessionIndex' => function (CPSUser $user, $value) {
        $user->setShibSessionIndex($value);
      },
      'shibAuthenticationIstant' => function (CPSUser $user, $value) {
        $user->setShibAuthenticationIstant($value);
      },
    ];

    return $fieldSetters;
  }

  private function updateRequiredFields(CPSUser $user, $data)
  {
    $fieldSetters = [
      'x509certificate_issuerdn' => function (CPSUser $user, $value) {
        if ($user->getX509certificateIssuerdn() !== $value) {
          $user->setX509certificateIssuerdn($value);
        }
      },
      'x509certificate_subjectdn' => function (CPSUser $user, $value) {
        if ($user->getX509certificateSubjectdn() !== $value) {
          $user->setX509certificateSubjectdn($value);
        }
      },
      'x509certificate_base64' => function (CPSUser $user, $value) {
        if ($user->getX509certificateBase64() !== $value) {
          $user->setX509certificateBase64($value);
        }
      },
      'spidCode' => function (CPSUser $user, $value) {
        if ($user->getSpidCode() !== $value) {
          $user->setSpidCode($value);
        }
      },
      'shibSessionId' => function (CPSUser $user, $value) {
        if ($user->getShibSessionId() !== $value) {
          $user->setShibSessionId($value);
        }
      },
      'shibSessionIndex' => function (CPSUser $user, $value) {
        if ($user->getShibSessionIndex() !== $value) {
          $user->setShibSessionIndex($value);
        }
      },
      'shibAuthenticationIstant' => function (CPSUser $user, $value) {
        if ($user->getShibAuthenticationIstant() !== $value) {
          $user->setShibAuthenticationIstant($value);
        }
      },
      'dataNascita' => function (CPSUser $user, $value) {
        if ($user->getDataNascita() === null || $user->getDataNascita() === '' || $user->getDataNascita()->format('d/m/Y') !== $value) {
          $dateTime = \DateTime::createFromFormat('d/m/Y', $value);
          if ($dateTime instanceof \DateTime) {
            $user->setDataNascita($dateTime);
          }
        }
      },
      'luogoNascita' => function (CPSUser $user, $value) {
        if ($user->getCodiceNascita() !== $value) {
          $user->setCodiceNascita($value);
          $user->setLuogoNascita($user->getMunicipalityFromCode($value));
        }
      },
      'provinciaNascita' => function (CPSUser $user, $value) {
        if ($user->getProvinciaNascita() !== $value) {
          $user->setProvinciaNascita($value);
        }
      },
      'statoNascita' => function (CPSUser $user, $value) {
        if ($user->getStatoNascita() !== $value) {
          $user->setStatoNascita($value);
        }
      },
      'sesso' => function (CPSUser $user, $value) {
        if ($user->getSesso() !== $value) {
          $user->setSesso($value);
        }
      },
    ];

    foreach ($fieldSetters as $key => $callback) {
      if (isset($data[$key])) {
        $callback($user, $data[$key]);
      }
    }

    $uow = $this->em->getUnitOfWork();
    $uow->computeChangeSets();
    if ($uow->isEntityScheduled($user)) {
      $this->logger->info(
        LogConstants::CPS_USER_UPDATE_SECURITY_FIELDS,
        ['type' => $user->getType(), 'user' => $user]
      );
    }

    $this->em->persist($user);
    $this->em->flush();
  }

  public function userHasEnoughData(CPSUser $user)
  {
    //Regex per controllo fake email utente
    $regex = "/[^@]*(".$user::FAKE_EMAIL_DOMAIN.")/";
    return $user->getNome() !== null
      && $user->getCognome() !== null
      && $user->getCodiceFiscale() !== null
      && $user->getDataNascita() !== null
      && $user->getDataNascita() !== ''
      && $user->getLuogoNascita() !== null
      && $user->getLuogoNascita() !== ''
      /*&& $user->getIndirizzoResidenza() !== null
      && $user->getCapResidenza() !== null
      && $user->getCittaResidenza() !== null
      && $user->getProvinciaResidenza() !== null
      && $user->getProvinciaResidenza() !== ''
      && in_array($user->getProvinciaResidenza(), CPSUser::getProvinces())*/
      && $user->getCellulare() !== null
      && $user->getEmail() !== null
      && !in_array($user->getEmail(), self::EMAIL_BLACKLIST)
      && !preg_match($regex, $user->getEmail());
  }

}
