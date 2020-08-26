<?php

namespace AppBundle\Form;

use AppBundle\Entity\User;
use Craue\FormFlowBundle\Form\FormFlowInterface;
use Craue\FormFlowBundle\Storage\DataManager as BaseDataManager;
use Craue\FormFlowBundle\Storage\SerializableFile;
use Craue\FormFlowBundle\Storage\StorageInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Manages data of flows and their steps.
 *
 * It uses the following data structure with {@link $this->user->getId()} as name of the root element within the storage:
 * <code>
 *    $this->user->getId() => array(
 *        name of the flow => array(
 *            instance id of the flow => array(
 *                'data' => array() // the actual step data
 *            )
 *        )
 *    )
 * </code>
 *
 * @author Christian Raue <christian.raue@gmail.com>
 * @copyright 2011-2016 Christian Raue
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class DataManager extends BaseDataManager
{

  /**
   * @var TokenStorageInterface
   */
  private $tokenStorage;

  /**
   * @var UserInterface
   */
  private $user;

  /**
   * @var SessionInterface
   */
  private $session;

  private $id;

  /**
   * DataManager constructor.
   * @param StorageInterface $storage
   * @param TokenStorageInterface $tokenStorage
   * @param SessionInterface $session
   */
  public function __construct(StorageInterface $storage, TokenStorageInterface $tokenStorage, SessionInterface $session)
  {
    parent::__construct($storage);
    $this->tokenStorage = $tokenStorage;
    $this->user = $this->tokenStorage->getToken()->getuser();
    $this->session = $session;
  }

  private function getId()
  {
    if ($this->id === null){
      if ($this->user instanceof User) {
        $this->id = $this->user->getId();
      } else {
        if (!$this->session->isStarted()) {
          $this->session->start();
        }
        $this->id = $this->session->getId();
      }
    }

    return $this->id;
  }

  /**
   * {@inheritDoc}
   */
  public function save(FormFlowInterface $flow, array $data)
  {

    // handle file uploads
    if ($flow->isHandleFileUploads()) {
      array_walk_recursive($data, function (&$value, $key) {
        if (SerializableFile::isSupported($value)) {
          $value = new SerializableFile($value);
        }
      });
    }

    // drop old data
    $this->drop($flow);

    // save new data
    $savedFlows = $this->getStorage()->get($this->getId(), array());

    $savedFlows = array_merge_recursive($savedFlows, array(
      $flow->getName() => array(
        $flow->getInstanceId() => array(
          self::DATA_KEY => $data,
        ),
      ),
    ));

    $this->getStorage()->set($this->getId(), $savedFlows);
  }

  /**
   * {@inheritDoc}
   */
  public function drop(FormFlowInterface $flow)
  {

    $savedFlows = $this->getStorage()->get($this->getId(), array());

    // remove data for only this flow instance
    if (isset($savedFlows[$flow->getName()][$flow->getInstanceId()])) {
      unset($savedFlows[$flow->getName()][$flow->getInstanceId()]);
    }

    $this->getStorage()->set($this->getId(), $savedFlows);
  }

  /**
   * {@inheritDoc}
   */
  public function load(FormFlowInterface $flow)
  {
    $data = array();

    // try to find data for the given flow
    $savedFlows = $this->getStorage()->get($this->getId(), array());
    if (isset($savedFlows[$flow->getName()][$flow->getInstanceId()][self::DATA_KEY])) {
      $data = $savedFlows[$flow->getName()][$flow->getInstanceId()][self::DATA_KEY];
    }

    // handle file uploads
    if ($flow->isHandleFileUploads()) {
      $tempDir = $flow->getHandleFileUploadsTempDir();
      array_walk_recursive($data, function (&$value, $key) use ($tempDir) {
        if ($value instanceof SerializableFile) {
          $value = $value->getAsFile($tempDir);
        }
      });
    }

    return $data;
  }

  /**
   * {@inheritDoc}
   */
  public function exists(FormFlowInterface $flow)
  {
    $savedFlows = $this->getStorage()->get($this->getId(), array());

    return isset($savedFlows[$flow->getName()][$flow->getInstanceId()][self::DATA_KEY]);
  }

  /**
   * {@inheritDoc}
   */
  public function listFlows()
  {
    return array_keys($this->getStorage()->get($this->getId(), array()));
  }

  /**
   * {@inheritDoc}
   */
  public function listInstances($name)
  {
    $savedFlows = $this->getStorage()->get($this->getId(), array());

    if (array_key_exists($name, $savedFlows)) {
      return array_keys($savedFlows[$name]);
    }

    return array();
  }

  /**
   * {@inheritDoc}
   */
  public function dropAll()
  {
    $this->getStorage()->remove($this->getId());
  }
}
