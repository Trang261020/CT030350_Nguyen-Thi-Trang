/**
 * Copyright © Ecommage, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'Magento_Catalog/js/product/view/product-ids-resolver',
    'Magento_Catalog/js/product/view/product-info-resolver',
    'mage/translate',
    'jquery-ui-modules/widget'
], function ($,idsResolver, productInfoResolver) {
    'use strict';

    $.widget('mage.customerName', {

        options: {
            processStart: null,
            processStop: null,
            bindSubmit: true,
            minicartSelector: '[data-block="minicart"]',
            messagesSelector: '[data-placeholder="messages"]',
            productStatusSelector: '.stock.available',
            addToCartButtonSelector: '.action.tocart',
            addToCartButtonDisabledClass: 'disabled',
            addToCartButtonTextWhileAdding: '',
            addToCartButtonTextAdded: '',
            addToCartButtonTextDefault: '',
            productInfoResolver: productInfoResolver
        },

        /** @inheritdoc */
        _create: function () {
            var self = this;
            this.initFullName();
            this._updateNameInput();
            this._addToCart();
            this._btnLogin();
            this._ajaxResetPassword();
            this._displayPopup();
            this._removeMessage();
            this._updateFieldPhone()
            this.element.on('change blur', 'input[name="fullname"]', function () {
                self.updateNameText(this.value.trim());
            });
        },

        _updateNameInput :function () {
            $('.mobile_number').on('change',function () {
               $('#sparsh-mobiles-number').val($(this).val());
            })
        },
        updateNameText: function (value) {
            let firstname = `${value}`,
                lastname = firstname,
                items;
            if (typeof value != "undefined" && value.indexOf(' ') !== -1) {
                items = value.trim().split(' ');
                lastname = items.pop();
                firstname = items.join(' ');
            }

            this.getElement('firstname').val(firstname);
            this.getElement('lastname').val(lastname);
        },
        initFullName: function () {
            let fullName = '',
                firstName = this.getElement('firstname').val(),
                lastName = this.getElement('lastname').val();
            if (typeof firstName != "undefined") {
                fullName = firstName;
            }

            if (typeof lastName != "undefined") {
                fullName += ' '+ lastName;
            }
            if (firstName == lastName)
            {
                fullName = lastName;
            }
            if ($('#is-check-login').val() == 1)
            {
                fullName = lastName + ' ' + firstName;
            }

            this.element.find('input[name="fullname"]').val(fullName);
        },
        getElement: function (name) {
            return this.element.parent().find('input[name="'+ name +'"]');
        },

        _btnLogin:function () {
            $('body').on('click','.mess-same-mobile',function () {
               $('body').find('li[aria-controls=amsl-login-content]').trigger('click');
               $('body').find('input[value=sparsh-mobile-number]').trigger('click');
                return false;
            })
            $('body').on('click','.forgot-password',function () {
                $('body').find('.action.remind').trigger('click');
                return false;
            })
        },
        
        _ajaxResetPassword : function () {
            var form = $('.form.password.reset');
            var html = '';
            form.on('click','.action.submit.primary',function () {
                var data = form.serializeArray();
                $.ajax({
                    url: form.attr('action'),
                    data: data,
                    type: 'Post',
                    dataType: 'json',
                    success: function (response) {
                        if (response.status == 'success')
                        {
                            $('body').find('.amsl-popup-overlay').css('display','flex');
                            $('input[name=user_option]').find('#mobile_number_user').trigger('click');
                             html = '<div class="mage-error message-popup">'+$.mage.__('Change password successfully')+'</div>';
                            $('#amsl-login-content').prepend(html);
                        }
                    },
                    error:function (response) {
                         html = '<div class="mage-error message-popup">'+$.mage.__(response.message)+'</div>';
                         form.prepend(html);
                    }
                });
                return false;
            });
        },
        _addToCart:function () {
            var self = this;
            $(document).on('click','#add-to-cart',function () {
                var form = $('[data-role=tocart-form-'+ $(this).data('value')+']');
                self.ajaxSubmit(form);
            })
        },
        
        _displayPopup :function () {
            $('body').on('click','#amty-account-popup',function () {
                $('body').find('.amsl-popup-overlay').css('display','flex');
                $('body').find('a[href=#amsl-register-content]').trigger('click');
                return false;
            })
        },

        _updateFieldPhone:function () {
            $(document).on('change','.mobile_number_register,#mobile_number',function () {
               var number = $(this).val();
               if (parseInt(number.charAt(0)) != 0)
               {
                   number = '0%1'.replace('%1',number);
               }

               if (typeof $(this).attr('id') == "undefined")
               {
                   $('#mobile_number_register_hide').val(number);
                    return true;
               }
                $('#sparsh-mobiles-number').val(number);
            })
        },

        /**
         * @param {jQuery} form
         */
        ajaxSubmit: function (form) {
            var self = this,
                productIds = idsResolver(form),
                productInfo = self.options.productInfoResolver(form),
                formData;

            $(self.options.minicartSelector).trigger('contentLoading');
            self.disableAddToCartButton(form);
            formData = new FormData(form[0]);
            $.ajax({
                url: form.prop('action'),
                data: formData,
                type: 'post',
                dataType: 'json',
                cache: false,
                contentType: false,
                processData: false,

                /** @inheritdoc */
                beforeSend: function () {
                    if (self.isLoaderEnabled()) {
                        $('body').trigger(self.options.processStart);
                    }
                },

                /** @inheritdoc */
                success: function (res) {
                    var eventData, parameters;

                    $(document).trigger('ajax:addToCart', {
                        'sku': form.data().productSku,
                        'productIds': productIds,
                        'productInfo': productInfo,
                        'form': form,
                        'response': res
                    });

                    if (self.isLoaderEnabled()) {
                        $('body').trigger(self.options.processStop);
                    }

                    if (res.backUrl) {
                        eventData = {
                            'form': form,
                            'redirectParameters': []
                        };
                        // trigger global event, so other modules will be able add parameters to redirect url
                        $('body').trigger('catalogCategoryAddToCartRedirect', eventData);

                        if (eventData.redirectParameters.length > 0 &&
                            window.location.href.split(/[?#]/)[0] === res.backUrl
                        ) {
                            parameters = res.backUrl.split('#');
                            parameters.push(eventData.redirectParameters.join('&'));
                            res.backUrl = parameters.join('#');
                        }

                        self._redirect(res.backUrl);

                        return;
                    }

                    if (res.messages) {
                        $(self.options.messagesSelector).html(res.messages);
                    }

                    if (res.minicart) {
                        $(self.options.minicartSelector).replaceWith(res.minicart);
                        $(self.options.minicartSelector).trigger('contentUpdated');
                    }

                    if (res.product && res.product.statusText) {
                        $(self.options.productStatusSelector)
                            .removeClass('available')
                            .addClass('unavailable')
                            .find('span')
                            .html(res.product.statusText);
                    }
                    self.enableAddToCartButton(form);
                },

                /** @inheritdoc */
                error: function (res) {
                    $(document).trigger('ajax:addToCart:error', {
                        'sku': form.data().productSku,
                        'productIds': productIds,
                        'productInfo': productInfo,
                        'form': form,
                        'response': res
                    });
                },

                /** @inheritdoc */
                complete: function (res) {
                    if (res.state() === 'rejected') {
                        location.reload();
                    }
                }
            });
        },

        _removeMessage:function () {
            $(document).on('click','#send2',function () {
                $('div').remove('.message-popup');
            })
        },

        /**
         * @private
         */
        _redirect: function (url) {
            var urlParts, locationParts, forceReload;

            urlParts = url.split('#');
            locationParts = window.location.href.split('#');
            forceReload = urlParts[0] === locationParts[0];

            window.location.assign(url);

            if (forceReload) {
                window.location.reload();
            }
        },
        /**
         * @param {String} form
         */
        disableAddToCartButton: function (form) {
            var addToCartButtonTextWhileAdding = this.options.addToCartButtonTextWhileAdding || $.mage.__('Adding...'),
                addToCartButton = $(form).find(this.options.addToCartButtonSelector);

            addToCartButton.addClass(this.options.addToCartButtonDisabledClass);
            addToCartButton.find('span').text(addToCartButtonTextWhileAdding);
            addToCartButton.prop('title', addToCartButtonTextWhileAdding);
        },
        /**
         * @return {Boolean}
         */
        isLoaderEnabled: function () {
            return this.options.processStart && this.options.processStop;
        },

        /**
         * @param {String} form
         */
        enableAddToCartButton: function (form) {
            var addToCartButtonTextAdded = this.options.addToCartButtonTextAdded || $.mage.__('Added'),
                self = this,
                addToCartButton = $(form).find(this.options.addToCartButtonSelector);

            addToCartButton.find('span').text(addToCartButtonTextAdded);
            addToCartButton.prop('title', addToCartButtonTextAdded);

            setTimeout(function () {
                var addToCartButtonTextDefault = self.options.addToCartButtonTextDefault || $.mage.__('Add to Cart');

                addToCartButton.removeClass(self.options.addToCartButtonDisabledClass);
                addToCartButton.find('span').text(addToCartButtonTextDefault);
                addToCartButton.prop('title', addToCartButtonTextDefault);
            }, 1000);
        }
    });

    return $.mage.customerName;
});
