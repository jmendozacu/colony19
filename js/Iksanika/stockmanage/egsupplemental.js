
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

//form tags to omit in NS6+:

var omitformtags=["input", "select"]

omitformtags=omitformtags.join("|")

function disableselect(e)
{
    if (omitformtags.indexOf(e.target.tagName.toLowerCase())==-1)
        return false
}

function reEnable()
{
    return true
}

var originalHighlighting = false;

function disableHighlighting() 
{
    if (typeof document.onselectstart!="undefined") 
    {
        originalHighlighting = document.onselectstart;
        document.onselectstart=new Function ("return false")
    } else{
        originalHighlighting = {
            down: document.onmousedown,
            up:   document.onmouseup
        }
        document.onmousedown=disableselect
        document.onmouseup=reEnable
    }
}

function enableHighlighting() 
{
    if (typeof document.onselectstart!="undefined") 
    {
        document.onselectstart = originalHighlighting;
    } else{
        document.onmousedown=originalHighlighting.down;
        document.onmouseup=originalHighlighting.up;
    }
}

function keyWasPressed(e, targetKeyNum) {
    var keychar;
    var numcheck;
    
    if(window.event) // IE
    {
        keynum = e.keyCode;
    }
    else if(e.which) // Netscape/Firefox/Opera
    {
        keynum = e.which;
    }
    if(keynum == targetKeyNum)
    {
        return true;
    }
    return false;
}