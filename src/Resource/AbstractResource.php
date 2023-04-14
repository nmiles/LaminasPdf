<?php

/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 * @package   LaminasPdf
 */

namespace LaminasPdf\Resource;

use LaminasPdf as Pdf;
use LaminasPdf\InternalType;
use LaminasPdf\ObjectFactory;

/**
 * PDF file Resource abstraction
 *
 * @package    LaminasPdf
 * @subpackage LaminasPdf\Internal
 */
abstract class AbstractResource
{
    /**
     * Each PDF resource (fonts, images, ...) interacts with a PDF itself.
     * It creates appropriate PDF objects, structures and sometime embedded files.
     * Resources are referenced in content streams by names, which are stored in
     * a page resource dictionaries.
     *
     * Thus, resources must be attached to the PDF.
     *
     * Resource abstraction uses own PDF object factory to store all necessary information.
     * At the render time internal object factory is appended to the global PDF file
     * factory.
     *
     * Resource abstraction also cashes information about rendered PDF files and
     * doesn't duplicate resource description each time then Resource is rendered
     * (referenced).
     *
     * @var \LaminasPdf\ObjectFactory
     */
    protected $_objectFactory;

    /**
     * Main resource object
     *
     * @var \LaminasPdf\InternalType\IndirectObject
     */
    protected $_resource;

    /**
     * Object constructor.
     *
     * If resource is not a \LaminasPdf\InternalType\AbstractTypeObject object,
     * then stream object with specified value is generated.
     *
     * @param \LaminasPdf\InternalType\AbstractTypeObject|string $resource
     */
    public function __construct($resource)
    {
        $this->_objectFactory = ObjectFactory::createFactory(1);
        if ($resource instanceof InternalType\AbstractTypeObject) {
            $this->_resource = $this->_objectFactory->newObject($resource);
        } else {
            $this->_resource = $this->_objectFactory->newStreamObject($resource);
        }
    }

    /**
     * Clone page, extract it and dependent objects from the current document,
     * so it can be used within other docs.
     */
    public function __clone()
    {
        $factory = \LaminasPdf\ObjectFactory::createFactory(1);
        $processed = [];

        // Clone dictionary object.
        // Do it explicitly to prevent sharing page attributes between different
        // results of clonePage() operation (other resources are still shared)
        $dictionary = new InternalType\DictionaryObject();
        foreach ($this->_pageDictionary->getKeys() as $key) {
            $dictionary->$key = $this->_pageDictionary->$key->makeClone(
                $factory,
                $processed,
                InternalType\AbstractTypeObject::CLONE_MODE_SKIP_PAGES
            );
        }

        $this->_pageDictionary = $factory->newObject($dictionary);
        $this->_objFactory = $factory;
        $this->_attached = false;
        $this->_style = null;
        $this->_font = null;
    }

    /**
     * Clone page, extract it and dependent objects from the current document,
     * so it can be used within other docs.
     *
     * @param \LaminasPdf\ObjectFactory $factory
     * @param array $processed
     * @return \LaminasPdf\Page
     * @internal
     */
    public function clonePage($factory, &$processed)
    {
        // Clone dictionary object.
        // Do it explicitly to prevent sharing page attributes between different
        // results of clonePage() operation (other resources are still shared)
        $dictionary = new InternalType\DictionaryObject();
        foreach ($this->_pageDictionary->getKeys() as $key) {
            $dictionary->$key = $this->_pageDictionary->$key->makeClone(
                $factory,
                $processed,
                InternalType\AbstractTypeObject::CLONE_MODE_SKIP_PAGES
            );
        }

        $clonedPage = new Pdf\Page($factory->newObject($dictionary), $factory);
        $clonedPage->_attached = false;

        return $clonedPage;
    }

    /**
     * Get resource.
     * Used to reference resource in an internal PDF data structures (resource dictionaries)
     *
     * @return \LaminasPdf\InternalType\IndirectObject
     * @internal
     */
    public function getResource()
    {
        return $this->_resource;
    }

    /**
     * Get factory.
     *
     * @return \LaminasPdf\ObjectFactory
     * @internal
     */
    public function getFactory()
    {
        return $this->_objectFactory;
    }
}
