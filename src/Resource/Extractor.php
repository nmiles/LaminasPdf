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

/**
 * Resource extractor class is used to detach resources from original PDF document.
 *
 * It provides resources sharing, so different pages or other PDF resources can share
 * its dependent resources (e.g. fonts or images) or other resources still use them without duplication.
 * It also reduces output PDF size, required memory for PDF processing and
 * processing time.
 *
 * The same extractor may be used for different source documents, several
 * extractors may be used for constracting one target document, but extractor
 * must not be shared between target documents.
 *
 * @package    LaminasPdf
 */
class Extractor
{
    /**
     * PDF objects factory.
     *
     * @var \LaminasPdf\ObjectFactory
     */
    protected $_factory;

    /**
     * Reusable list of already processed objects
     *
     * @var array
     */
    protected $_processed;

    /**
     * Object constructor.
     */
    public function __construct()
    {
        $this->_factory = Pdf\ObjectFactory::createFactory(1);
        $this->_processed = [];
    }

    /**
     * Clone page, extract it and dependent objects from the current document,
     * so it can be used within other docs
     *
     * return \LaminasPdf\Page
     */
    public function clonePage(Pdf\Page $page)
    {
        return $page->clonePage($this->_factory, $this->_processed);
    }
}
