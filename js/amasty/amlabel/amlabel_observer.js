amlabel_product_ids     = [];

Event.observe(document, 'dom:loaded', amlabel_init);

function amlabel_init() {
    if('undefined' != typeof(amlabel_selector)) {
        $$(amlabel_selector).each(function (element) {
            amlabel_add_label(element);
        });
        amLabelSetCorrectHeight();
    }
}

function amlabel_add_label(element){
    var product_id = 0;
    var element_id = 0;
    var element_with_id = 0;
    var n = 0;
    var max_parent_search_level = 3;

    do {
        // find price block
        element_with_id = element.up(n).down('[id*="-price-"]');
        // find block with id
        if (element_with_id) {
            // get element ID
            element_id = element_with_id.readAttribute('id');

            // check if parent is not a table (configurable products fix)
            if (element_with_id.up('#super-product-table')) {
                element_with_id = element_with_id.up('#super-product-table').next('.price');
                element_id = element_with_id.readAttribute('id');
            }

            // if element with ID placed one level upper (e.g. bundle price box styles)
            if (!element_id) {
                element_id = element_with_id.up().readAttribute('id');
            }
        }
        n += 1;
    } while (!element_with_id && n < max_parent_search_level);

    // if element have any ID
    if (element_id) {
        element_id = parseInt(element_id.match(/\d+/).first());
        if(element_id > 0 && element_id < 10000000) {
            product_id = element_id;
        }
    }

    if (product_id > 0
        && amlabel_product_ids[product_id]
        && !$(element).hasClassName('amlabel-observed')//fix for ajax scroll-duplicated items
    ) {
        // check on zoom elements before insert
        var classes = $w(element.className).join();
        if (classes.indexOf('zoom-') > 0) {
            element = $(element).down();
        }
        var html = amlabel_product_ids[product_id];
        html = html.replace(/\\"/g, '"');
        $(element).setStyle({'position': 'relative'}).insert(html);
        $(element).addClassName('amlabel-observed');
    }
}
