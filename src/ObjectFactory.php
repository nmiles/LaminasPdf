<?php

/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 * @package   LaminasPdf
 */

namespace LaminasPdf;

use LaminasPdf\Exception;
use LaminasPdf\InternalType;
use LaminasPdf\ObjectFactory\UpdateInfoContainer;

/**
 * PDF element factory.
 * Responsibility is to log PDF changes
 *
 * @package    LaminasPdf
 * @subpackage LaminasPdf\Internal
 */
class ObjectFactory
{
    /**
     * List of the modified objects.
     * Also contains new and removed objects
     *
     * Array: ojbectNumber => \LaminasPdf\InternalType\IndirectObject
     *
     * @var array
     */
    private $_modifiedObjects = [];

    /**
     * List of the removed objects
     *
     * Array: ojbectNumber => \LaminasPdf\InternalType\IndirectObject
     *
     * @var SplObjectStorage
     */
    private $_removedObjects;

    /**
     * List of registered objects.
     * Used for resources clean up when factory is destroyed.
     *
     * Array of \LaminasPdf\InternalType\AbstractTypeObject objects
     *
     * @var array
     */
    private $_registeredObjects = [];

    /**
     * PDF object counter.
     * Actually it's an object number for new PDF object
     *
     * @var integer
     */
    private $_objectCount;


    /**
     * List of the attached object factories.
     * Array of \LaminasPdf\ObjectFactory objects
     *
     * @var array
     */
    private $_attachedFactories = [];


    /**
     * Factory internal id
     *
     * @var integer
     */
    private $_factoryId;

    /**
     * Identity, used for factory id generation
     *
     * @var integer
     */
    private static $_identity = 0;


    /**
     * Internal cache to save calculated shifts
     *
     * @var array
     */
    private $_shiftCalculationCache = [];


    /**
     * Object constructor
     *
     * @param integer $objCount
     */
    public function __construct($objCount)
    {
        $this->_objectCount = (int)$objCount;
        $this->_factoryId = self::$_identity++;
        $this->_removedObjects = new \SplObjectStorage();
    }


    /**
     * Factory generator
     *
     * @param integer $objCount
     * @return \LaminasPdf\ObjectFactory
     */
    public static function createFactory($objCount)
    {
        return new self($objCount);
    }

    /**
     * Close factory and clean-up resources
     *
     * @internal
     */
    public function close()
    {
        $this->_modifiedObjects = null;
        $this->_removedObjects = null;
        $this->_attachedFactories = null;

        foreach ($this->_registeredObjects as $obj) {
            $obj->cleanUp();
        }
        $this->_registeredObjects = null;
    }

    /**
     * Get factory ID
     *
     * @return integer
     */
    public function getId()
    {
        return $this->_factoryId;
    }

    /**
     * Set object counter
     *
     * @param integer $objCount
     */
    public function setObjectCount($objCount)
    {
        $this->_objectCount = (int)$objCount;
    }

    /**
     * Get object counter
     *
     * @return integer
     */
    public function getObjectCount()
    {
        $count = $this->_objectCount;

        foreach ($this->_attachedFactories as $attached) {
            $count += $attached->getObjectCount() - 1; // -1 as "0" object is a special case and shared between factories
        }

        return $count;
    }


    /**
     * Attach factory to the current;
     *
     * @param \LaminasPdf\ObjectFactory $factory
     */
    public function attach(ObjectFactory $factory)
    {
        if ($factory === $this || isset($this->_attachedFactories[$factory->getId()])) {
            /**
             * Don't attach factory twice.
             * We do not check recusively because of nature of attach operation
             * (Pages are always attached to the Documents, Fonts are always attached
             * to the pages even if pages already use Document level object factory and so on)
             */
            return;
        }

        $this->_attachedFactories[$factory->getId()] = $factory;
    }


    /**
     * Calculate object enumeration shift.
     *
     * @param \LaminasPdf\ObjectFactory $factory
     * @return integer
     */
    public function calculateShift(ObjectFactory $factory)
    {
        if ($factory === $this) {
            return 0;
        }

        if (isset($this->_shiftCalculationCache[$factory->_factoryId])) {
            return $this->_shiftCalculationCache[$factory->_factoryId];
        }

        $shift = $this->_objectCount - 1;

        foreach ($this->_attachedFactories as $subFactory) {
            $subFactoryShift = $subFactory->calculateShift($factory);

            if ($subFactoryShift != -1) {
                // context found
                $this->_shiftCalculationCache[$factory->_factoryId] = $shift + $subFactoryShift;
                return $shift + $subFactoryShift;
            } else {
                $shift += $subFactory->getObjectCount() - 1;
            }
        }

        $this->_shiftCalculationCache[$factory->_factoryId] = -1;
        return -1;
    }

    /**
     * Clean enumeration shift cache.
     * Has to be used after PDF render operation to let followed updates be correct.
     */
    public function cleanEnumerationShiftCache()
    {
        $this->_shiftCalculationCache = [];

        foreach ($this->_attachedFactories as $attached) {
            $attached->cleanEnumerationShiftCache();
        }
    }

    /**
     * Retrive object enumeration shift.
     *
     * @param \LaminasPdf\ObjectFactory $factory
     * @return integer
     * @throws \LaminasPdf\Exception\ExceptionInterface
     */
    public function getEnumerationShift(ObjectFactory $factory)
    {
        if (($shift = $this->calculateShift($factory)) == -1) {
            throw new Exception\RuntimeException('Wrong object context');
        }

        return $shift;
    }

    /**
     * Mark object as modified in context of current factory.
     *
     * @param \LaminasPdf\InternalType\IndirectObject $obj
     * @throws \LaminasPdf\Exception\ExceptionInterface
     */
    public function markAsModified(InternalType\IndirectObject $obj)
    {
        if ($obj->getFactory() !== $this) {
            throw new Exception\RuntimeException('Object is not generated by this factory');
        }

        $this->_modifiedObjects[$obj->getObjNum()] = $obj;
    }


    /**
     * Remove object in context of current factory.
     *
     * @param \LaminasPdf\InternalType\IndirectObject $obj
     * @throws \LaminasPdf\Exception\ExceptionInterface
     */
    public function remove(InternalType\IndirectObject $obj)
    {
        if (!$obj->compareFactory($this)) {
            throw new Exception\RuntimeException('Object is not generated by this factory');
        }

        $this->_modifiedObjects[$obj->getObjNum()] = $obj;
        $this->_removedObjects->attach($obj);
    }


    /**
     * Generate new \LaminasPdf\InternalType\IndirectObject
     *
     * @todo Reusage of the freed object. It's not a support of new feature, but only improvement.
     *
     * @param \LaminasPdf\InternalType\AbstractTypeObject $objectValue
     * @return \LaminasPdf\InternalType\IndirectObject
     */
    public function newObject(InternalType\AbstractTypeObject $objectValue)
    {
        $obj = new InternalType\IndirectObject($objectValue, $this->_objectCount++, 0, $this);
        $this->_modifiedObjects[$obj->getObjNum()] = $obj;
        return $obj;
    }

    /**
     * Generate new \LaminasPdf\InternalType\StreamObject
     *
     * @todo Reusage of the freed object. It's not a support of new feature, but only improvement.
     *
     * @param mixed $objectValue
     * @return \LaminasPdf\InternalType\StreamObject
     */
    public function newStreamObject($streamValue)
    {
        $obj = new InternalType\StreamObject($streamValue, $this->_objectCount++, 0, $this);
        $this->_modifiedObjects[$obj->getObjNum()] = $obj;
        return $obj;
    }


    /**
     * Enumerate modified objects.
     * Returns array of \LaminasPdf\ObjectFactory\UpdateInfoContainer
     *
     * @param \LaminasPdf\ObjectFactory $rootFactory
     * @return array
     */
    public function listModifiedObjects($rootFactory = null)
    {
        if ($rootFactory == null) {
            $rootFactory = $this;
            $shift = 0;
        } else {
            $shift = $rootFactory->getEnumerationShift($this);
        }

        ksort($this->_modifiedObjects);

        $result = [];
        foreach ($this->_modifiedObjects as $objNum => $obj) {
            if ($this->_removedObjects->contains($obj)) {
                $result[$objNum + $shift] = new UpdateInfoContainer(
                    $objNum + $shift,
                    $obj->getGenNum() + 1,
                    true
                );
            } else {
                $result[$objNum + $shift] = new UpdateInfoContainer(
                    $objNum + $shift,
                    $obj->getGenNum(),
                    false,
                    $obj->dump($rootFactory)
                );
            }
        }

        foreach ($this->_attachedFactories as $factory) {
            $result += $factory->listModifiedObjects($rootFactory);
        }

        return $result;
    }

    /**
     * Register object in the factory
     *
     * It's used to clear "parent object" referencies when factory is closed and clean up resources
     *
     * @param string $refString
     * @param \LaminasPdf\InternalType\IndirectObject $obj
     */
    public function registerObject(InternalType\IndirectObject $obj, $refString)
    {
        $this->_registeredObjects[$refString] = $obj;
    }

    /**
     * Fetch object specified by reference
     *
     * @param string $refString
     * @return \LaminasPdf\InternalType\IndirectObject|null
     */
    public function fetchObject($refString)
    {
        if (!isset($this->_registeredObjects[$refString])) {
            return null;
        }
        return $this->_registeredObjects[$refString];
    }


    /**
     * Check if PDF file was modified
     *
     * @return boolean
     */
    public function isModified()
    {
        if (count($this->_modifiedObjects) != 0) {
            return true;
        }

        foreach ($this->_attachedFactories as $subFactory) {
            if ($subFactory->isModified()) {
                return true;
            }
        }

        return false;
    }
}
