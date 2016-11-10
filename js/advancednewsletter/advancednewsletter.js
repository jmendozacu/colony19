var Advancednewsletter = Class.create();
Advancednewsletter.prototype = {
    url: '',
    initialize: function(ctrl, url) {
        this.url = url;
        Event.observe(ctrl, 'click', function(event){this.display();Event.stop(event);}.bind(this));
        $('an-content').observe('click', (function(event) {
            if (event.element().id == 'advancednewsletter-cancel') {
                this.deactivate();
            }
        }).bind(this));

        //prepare position
        Event.observe(window,'resize', (function(event) {
            this.alignBlockAn($('an-content'), 400, null);
        }).bind(this));
    },
    display: function(){
        if ($('advancednewsletter-subscribe-ajax') == undefined) {
            this.sendResponse()
        } else {
            $('advancednewsletter-overlay').show();
            $('an-content').show();
            this.alignBlockAn($('an-content'), 400, null);
        }
    },
    deactivate: function(){
        $('advancednewsletter-overlay').hide();
        $('an-content').hide();
    },
    sendResponse: function(){
        this.displayWait();
        new Ajax.Request(this.url, {
            onSuccess: function(resp){
                $('subscribe-please-wait').hide();
                $('an-content').update(resp.responseText.stripScripts());
                $('an-content').show();
                this.alignBlockAn($('an-content'), 400, null);
                advancednewsletterForm = new VarienForm('advancednewsletter-form');
            }.bind(this)
        });
    },
    displayWait: function(){
        $('advancednewsletter-overlay').show();
        $('subscribe-please-wait').show();
        this.alignBlockAn($('subscribe-please-wait'), null, null);
        Event.observe($('advancednewsletter-overlay'), 'click', function(event){this.deactivate();Event.stop(event);}.bind(this));
    },
    alignBlockAn: function(block, width, height){
        if (!width) {
            width = block.getWidth();
        }
        if (!height) {
            height = block.getHeight();
        }
        var left = 0;
        var top = 0;
        block.style.position ="absolute";

        if (document.viewport.getWidth() > width && document.viewport.getHeight() > height) {
            block.style.position ="fixed";
        }
        if (document.viewport.getWidth() > width) {
            left = (document.viewport.getWidth()/2 - width/2);
        }
        if (document.viewport.getHeight() > height) {
            top = (document.viewport.getHeight()/2 - height/2);
        }

        block.style.height = height + 'px';
        block.style.width = width + 'px';
        block.style.left = left + 'px';
        block.style.top = top + 'px';
    }
};