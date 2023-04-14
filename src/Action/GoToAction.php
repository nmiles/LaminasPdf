<?php

/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 * @package   LaminasPdf
 */

namespace LaminasPdf\Action;

use LaminasPdf\Destination;
use LaminasPdf\Exception;
use LaminasPdf\InternalType;

/**
 * PDF 'Go to' action
 *
 * @package    LaminasPdf
 * @subpackage LaminasPdf\Action
 */
class GoToAction extends AbstractAction
{
    /**
     * GoTo Action destination
     *
     * @var \LaminasPdf\Destination\AbstractDestination
     */
    protected $_destination;


    /**
     * Object constructor
     *
     * @param \LaminasPdf\InternalType\DictionaryObject $dictionary
     * @param SplObjectStorage $processedActions list of already processed action dictionaries,
     *                                               used to avoid cyclic references
     */
    public function __construct(InternalType\AbstractTypeObject $dictionary, \SplObjectStorage $processedActions)
    {
        parent::__construct($dictionary, $processedActions);

        $this->_destination = Destination\AbstractDestination::load($dictionary->D);
    }

    /**
     * Create new \LaminasPdf\Action\GoToAction object using specified destination
     *
     * @param \LaminasPdf\Destination\AbstractDestination|string $destination
     * @return \LaminasPdf\Action\GoToAction
     */
    public static function create($destination)
    {
        if (is_string($destination)) {
            $destination = Destination\Named::create($destination);
        }

        if (!$destination instanceof Destination\AbstractDestination) {
            throw new Exception\InvalidArgumentException('$destination parameter must be a \LaminasPdf\Destination object or string.');
        }

        $dictionary = new InternalType\DictionaryObject();
        $dictionary->Type = new InternalType\NameObject('Action');
        $dictionary->S = new InternalType\NameObject('GoTo');
        $dictionary->Next = null;
        $dictionary->D = $destination->getResource();

        return new self($dictionary, new \SplObjectStorage());
    }

    /**
     * Set goto action destination
     *
     * @param \LaminasPdf\Destination\AbstractDestination|string $destination
     * @return \LaminasPdf\Action\GoToAction
     */
    public function setDestination(Destination\AbstractDestination $destination)
    {
        $this->_destination = $destination;

        $this->_actionDictionary->touch();
        $this->_actionDictionary->D = $destination->getResource();

        return $this;
    }

    /**
     * Get goto action destination
     *
     * @return \LaminasPdf\Destination\AbstractDestination
     */
    public function getDestination()
    {
        return $this->_destination;
    }
}
