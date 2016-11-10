
/**
 * Iksanika llc.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.iksanika.com/products/IKS-LICENSE.txt
 *
 * @category   Iksanika
 * @package    Iksanika_Stockmanage
 * @copyright  Copyright (c) 2013 Iksanika llc. (http://www.iksanika.com)
 * @license    http://www.iksanika.com/products/IKS-LICENSE.txt
 */


function productsUpdate()
{
//    $('#myForm').submit(function() {
        // get all the inputs into an array.
        var $inputs = $j('#editData :input');

        // not sure if you wanted this, but I thought I'd add it.
        // get an associative array of just the values.
        var values = {};
        $inputs.each(function() {
            values[this.name] = $j(this).val();
        });
        console.log(values);
//alert('test');
//return null;
//    });
/*
editData.action = this.massUpdateProducts;
    console.log(editData.action);
    editData.submit();*/
}
