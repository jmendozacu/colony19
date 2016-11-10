<?php
/**
 * aheadWorks Co.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://ecommerce.aheadworks.com/AW-LICENSE.txt
 *
 * =================================================================
 *                 MAGENTO EDITION USAGE NOTICE
 * =================================================================
 * This software is designed to work with Magento community edition and
 * its use on an edition other than specified is prohibited. aheadWorks does not
 * provide extension support in case of incorrect edition use.
 * =================================================================
 *
 * @category   AW
 * @package    AW_Zblocks
 * @version    2.5.2
 * @copyright  Copyright (c) 2010-2012 aheadWorks Co. (http://www.aheadworks.com)
 * @license    http://ecommerce.aheadworks.com/AW-LICENSE.txt
 */


class AW_Zblocks_Test_Helper_Data extends EcomDev_PHPUnit_Test_Case
{

    /**
     * @test
     * @doNotIndexAll
     * @loadFixture
     * @loadExpectation
     * @dataProvider dataProvider
     */
    public function testGetBlocks($dataProvider)
    {
        $this->_registerGetCustomerGroupStub($dataProvider['customer_group_id']);

        # register current product
        if ($dataProvider['product_id'] > 0) {
            $currentProduct = Mage::getModel('catalog/product')->load($dataProvider['product_id']);
            Mage::register('current_product', $currentProduct);
        }

        $blocks = Mage::helper('zblocks/data')
            ->getBlocks(
                $dataProvider['custom_position'],
                $dataProvider['block_position'],
                $dataProvider['category_path'],
                $dataProvider['category_id']
            );

        # unregister current product
        if ($dataProvider['product_id'] > 0) {
            Mage::unregister('current_product');
        }

        $expected = $this->expected($dataProvider['test_id']);

        $this->assertEquals(
            $expected->getAmount(),
            count($blocks)
        );
    }

    private function _registerGetCustomerGroupStub($value) {
        $mock = $this->getHelperMock(
            'zblocks/data',
            array(
                'getCustomerGroup'
            )
        );

        $mock
            ->expects($this->any())
            ->method('getCustomerGroup')
            ->will($this->returnValue($value));

        $this->replaceByMock(
            'helper',
            'zblocks/data',
            $mock
        );
    }

}
