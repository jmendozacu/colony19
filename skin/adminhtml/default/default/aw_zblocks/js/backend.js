var AWZBlock = Class.create({
    initialize: function(name) {
        window[name] = this;
        document.observe('dom:loaded', this.prepareSelf.bind(this));
    },

    prepareSelf: function() {
        if ($('block_position')) {
            $('block_position').observe('change', this.checkPosition.bind(this));
        }
        this.checkPosition();
    },

    checkPosition:function() {
        this.checkCategoryPosition();
        this.checkProductPosition();
    },

    checkCategoryPosition: function() {
        if ($('block_position') && typeof(aw_zblocks_categories) != 'undefined') {
            var needCategory = false;
            for (var i = 0; i < aw_zblocks_categories.length; i++) {
                if (aw_zblocks_categories[i] == $('block_position').value) {
                    needCategory = true;
                }
            }
            var categoryErrorDiv = $('awzb_categories_error');
            var categories = $('categories-fields');
            var categoriesIds = $('product_categories');
            if (needCategory && categories && categoryErrorDiv) {
                if(typeof(this._old_categories) != 'undefined') {
                    categoriesIds.value = this._old_categories;
                    var _catIds = categoriesIds.value.split(',');
                    if(typeof(_catIds) == 'object') {
                        _catIds.each(function(el) {
                            if(el) {
                                tree.getNodeById(el).getUI().check(1);
                            }
                        });
                    }
                }
                categoryErrorDiv.hide();
                categories.show();
            } else {
                if(categoriesIds.value) {
                    this._old_categories = categoriesIds.value;
                    categoriesIds.value = '';
                    var _catIds = categoriesIds.value.split(',');
                    if(typeof(_catIds) == 'object') {
                        _catIds.each(function(el) {
                            if(el) {
                                tree.getNodeById(el).getUI().check(0);
                            }
                        });
                    }
                }
                categoryErrorDiv.show();
                categories.hide();
            }
        }
    },

    checkProductPosition: function() {
        if ($('block_position') && typeof(aw_zblocks_products) != 'undefined') {
            var needProduct = false;
            for (var i = 0; i < aw_zblocks_products.length; i++) {
                if (aw_zblocks_products[i] == $('block_position').value) {
                    needProduct = true;
                }
            }
            var productErrorDiv = $('awzb_products_error');
            var productFilter = $('awzblocks-product-conditions');

            if (needProduct && productFilter && productErrorDiv) {
                productErrorDiv.hide();
                productFilter.show();
            } else {
                productErrorDiv.show();
                productFilter.hide();
            }
        }
    }
});

new AWZBlock('awzblock');


Event.observe(window, 'load', function(){
    if (contentGrid_massactionJsObject && !contentGrid_massactionJsObject.hasOwnProperty('prepareForm')) {
        contentGrid_massactionJsObject.prepareForm = function(){
            var form = $(this.containerId + '-form'), formPlace = null,
                formElement = this.formHiddens || this.formAdditional;

            if (!formElement) {
                formElement = this.container.getElementsByTagName('button')[0];
                formElement && formElement.parentNode;
            }
            if (!form && formElement) {
                /* fix problem with rendering form in FF through innerHTML property */
                form = document.createElement('form');
                form.setAttribute('method', 'post');
                form.setAttribute('action', '');
                form.id = this.containerId + '-form';
                formPlace = formElement.parentNode.parentNode;
                formPlace.parentNode.appendChild(form);
                form.appendChild(formPlace);
            }

            return form;
        };

        contentGrid_massactionJsObject.initMassactionElements = function(){
            this.container      = $(this.containerId);
            this.count          = $(this.containerId + '-count');
            this.formHiddens    = $(this.containerId + '-form-hiddens');
            this.formAdditional = $(this.containerId + '-form-additional');
            this.select         = $(this.containerId + '-select');
            this.form           = this.prepareForm();
            this.validator      = new Validation(this.form);
            this.select.observe('change', this.onSelectChange.bindAsEventListener(this));
        };
    }
});