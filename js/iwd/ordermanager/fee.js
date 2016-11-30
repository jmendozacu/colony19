;IWD.OrderManager.AdditionalDiscount = {
    applyUrl: '',
    amountField: '#iwd_om_custom_amount',
    descriptionField: '#iwd_om_custom_amount_desc',
    amount: '',
    description: '',
    removeButton: '#iwd_om_custom_amount_remove',
    applyButton: '#iwd_om_custom_amount_apply',
    minimalAmount: 0,
    createOrder: false,

    init: function() {
        this.initField();

        var self = this;
        $ji(document).off('click', self.applyButton);
        $ji(document).on('click', self.applyButton, function () {
            self.applyFee();
        });

        $ji(document).off('click', self.removeButton);
        $ji(document).on('click', self.removeButton, function () {
            self.removeFee();
        });
    },

    initField: function() {
        var self = this;
        $ji(document).off('keypress change', self.amountField + ', ' + self.descriptionField);
        $ji(document).on('keypress change', self.amountField + ', ' + self.descriptionField, function () {
            var amount = $ji(self.amountField).val();
            var description = $ji(self.descriptionField).val();
            if ((amount.length == 0 || self.amount == amount) ||
                (description.length == 0 || self.description == description)
            ) {
                self.disableApplyButton();
            } else {
                self.enableApplyButton();
            }
        });

        $ji(document).off('keypress', self.amountField);
        $ji(document).on('keypress', self.amountField, function (e) {
            if (e.which == 13 || e.which == 8) return 1;
            var letters = '1234567890.,+-*/';
            return (letters.indexOf(String.fromCharCode(e.which)) != -1);
        });

        $ji(document).off('change', self.amountField);
        $ji(document).on('change', self.amountField, function (e) {
            var amount = $ji(self.amountField).val();
            amount = amount.trim();
            amount = eval(amount);
            amount = amount < self.minimalAmount ? self.minimalAmount : amount;
            amount = (amount) ? parseFloat(amount).toFixed(2) : '';
            $ji(self.amountField).val(amount);
        });
    },

    applyFee: function() {
        var self = this;
        self.disableApplyButton();

        var amount = $ji(self.amountField).val();
        var description = $ji(self.descriptionField).val();

        if (self.createOrder) {
            if (amount) {
                self.showRemoveButton();
            }
            return self.createOrderApplyFee();
        }

        self.disableApplyButton();
        IWD.OrderManager.ShowLoadingMask();
        $ji.ajax({
            url: self.applyUrl,
            type: "POST",
            dataType: 'json',
            data: "form_key=" + FORM_KEY + "&amount=" + amount + "&description=" + description + "&order_id=" + IWD.OrderManager.orderId,
            success: function (result) {
                if (result.status == 1) {
                    location.reload();
                } else {
                    IWD.OrderManager.handleErrorResult(result);
                }
            },
            error: function (result) {
                IWD.OrderManager.handleErrorResult(result);
            }
        });
    },

    createOrderApplyFee: function() {
        var data = {};
        data['iwd_om_fee_amount'] = $ji(this.amountField).val();
        data['iwd_om_fee_description'] = $ji(this.descriptionField).val();
        order.loadArea(['totals', 'billing_method'], true, data);
    },

    removeFee: function() {
        if (confirm('Are you sure you want to remove additional fee?')) {
            $ji(this.amountField).val('');
            $ji(this.descriptionField).val('');
            this.hideRemoveButton();
            this.applyFee();
        }
    },

    enableApplyButton: function() {
        $ji(this.applyButton).removeClass('disabled').removeAttr('disabled');
    },

    disableApplyButton: function() {
        $ji(this.applyButton).addClass('disabled').attr('disabled', 'disabled');
    },

    showRemoveButton: function() {
        $ji(this.removeButton).show();
    },

    hideRemoveButton: function() {
        $ji(this.removeButton).hide();
    }
};
