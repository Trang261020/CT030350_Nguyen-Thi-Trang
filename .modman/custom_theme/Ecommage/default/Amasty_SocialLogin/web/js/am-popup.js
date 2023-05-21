define([
    'jquery',
    'mage/loader',
    'mage/translate',
    'Amasty_SocialLogin/js/am-reload-content',
    'Magento_Customer/js/customer-data',
    'underscore',
    'Magento_Customer/js/model/customer',
    'mage/validation',
    'mage/menu'
], function ($, loader, $translate, amReloadContent, customerData, _, customer) {
    'use strict';

    $.widget('mage.amLoginPopup', {
        customerData: customerData,
        options: {
            selectors: {
                login: 'a[href*="customer/account/login"]',
                logOut: 'a[href*="customer/account/logout"]',
                createAccount: 'a[href*="customer/account/create"]',
                resetPassword: '.amsl-forgot-content form.forget',
                loginOverlay: '[data-am-js="am-login-overlay"]',
                tabWrapper: '[data-am-js="am-tabs-wrapper"]',
                tabForgotWrapper: '[data-am-js="am-tabs-wrapper"]',
                tabWrapperForgot: '[data-am-js="am-tabs-wrapper-forgot"]',
                contentWrapper: '[data-am-js="am-content-wrapper"]',
                popup: '[data-am-js="am-login-popup"]',
                form: '[data-am-js="am-login-popup"] .form',
                forgot_link: '[data-am-js="am-login-popup"] .action.remind',
                default_error: '',
                error_messages: '.amsl-error',
                visibleInput: 'input:not([type=hidden]):not([type=checkbox])',
                errorBlock: '[data-am-js="error-block"]',
                contentBlock: '.amsl-content',
                socialWrapper: '.amsl-social-wrapper',
                loginContentBlock: '.amsl-login-content',
                actionButton: 'button.action',
                createAccountForm: '.amsl-register-content .form-create-account',
                passwordField: 'input#password',
                passwordMeter: '#password-strength-meter-container',
                modalsOverlay: '.modals-overlay'
            },
            textValues: {
                emailFieldLabel: $.mage.__('Email'),
                passwordFieldLabel: $.mage.__('Password'),
                unexpectedError: $.mage.__('Sorry, an unspecified error occurred. Please try again.')
            },
            classes: {
                flexCenter: '-flex-center',
                parentModal: '_has-modal-custom _has-auth-shown',
                default: '-default',
                empty: '-empty',
                socials: {
                    right: '-social-right',
                    left: '-social-left'
                }
            },
            redirect_duration: 2000,
            popup_duration: 300
        },
        widgets: {
            tabs: null
        },

        _create: function () {
            this.init();
        },

        init: function () {
            this.initBindings();
            this.initClosePopupBindings();
            this.changeForms();
            this.initPasswordReadingListener();
            this.initWidgets();
        },

        /**
         * Store jQuery widgets in a 'this' instance
         *
         * @returns {void}
         */
        initWidgets: function () {
            this.widgets.tabs = $(this.options.selectors.popup).data('mage-tabs');
        },

        initBindings: function () {
            var self = this,
                protocol = document.location.protocol;

            $(self.options.selectors.login).prop('href', '#').on('click', function (event) {
                self.openPopup(0);
                self.initScrollToElement(event.target);
                event.preventDefault();

                return false;
            });

            $(document).on('click', self.options.selectors.login, function (event) {
                self.openPopup(0);
                event.preventDefault();
                self.initScrollToElement(event.target);

                return false;
            });

            /* observe create account links */
            $(self.options.selectors.createAccount).prop('href', '#').on('click', function (event) {
                self.openPopup(1);
                event.preventDefault();
                self.initScrollToElement(event.target);

                return false;
            });

            self._validateDuplicatedForm();

            /* checkout page */
            $('body').addClass('amsl-popup-observed'); // hide magento popup on checkout page
            $(document).on('click', '[data-trigger="authentication"]', function (event) {
                self.openPopup(0);
                event.preventDefault();

                return false;
            });

            $(self.options.selectors.logOut)
                .prop('href', '#')
                .removeAttr('data-post', '')
                .off('click')
                .on('click', function (event) {
                    self.sendLogOut();
                    event.preventDefault();

                    return false;
                });

            $(self.options.selectors.form).each(function (index, element) {
                element = $(element);
                var action = element.attr('action'),
                    parser = document.createElement('a');

                parser.href = action;

                if (protocol !== parser.protocol) {
                    element.attr('action', action.replace(parser.protocol, protocol));
                }
            });

            $(self.options.selectors.form).off('submit').on('submit', function (event) {
                var element = $(this);

                self.options.selectors.default_error = $(element)
                    .parents(self.options.selectors.contentBlock)
                    .find(self.options.selectors.errorBlock)
                    .addClass(self.options.classes.default);

                self.options.selectors.default_error.hide();

                if (element.valid()) {
                    self.toggleActionDisableState(element, true);
                    self.sendFormWithAjax(element);
                }

                event.preventDefault();

                return false;
            });

            $(self.options.selectors.resetPassword).off('submit').on('submit', function (event) {
                var element = $(event.currentTarget);

                if (element.valid()) {
                    self.toggleActionDisableState(element, true);
                    self.resetPasswordRequest(element);
                }

                event.preventDefault();

                return false;
            });

            $(self.options.selectors.forgot_link).off('click').on('click', function (event) {
                self.toggleWrappers();
                event.preventDefault();

                return false;
            });
        },

        initScrollToElement: function (element) {
            var scrollToSelector = $(element).attr('data-amsl-scroll-to'),
                scrollTo = scrollToSelector ? $(scrollToSelector) : null;

            if (scrollTo) {
                $(document.body).one('scroll-after-ajax-update', function () {
                    $([document.documentElement, document.body]).animate({
                        scrollTop: scrollTo.offset().top
                    }, 500);
                });
            } else {
                $(document.body).off('scroll-after-ajax-update');
            }
        },

        initClosePopupBindings: function () {
            var self = this;

            $(self.options.selectors.loginOverlay).on('click', function (e) {
                var target = $(e.target);

                if ((target.hasClass('amsl-popup-overlay') && self.options.close_when_clicked_outside)
                    || target.hasClass('amsl-close')
                ) {
                    self.closePopup();
                }
            });
        },

        resetPasswordRequest: function (form) {
            var self = this;

            $.ajax({
                url: self.options.reset_pass_url,
                type: 'post',
                data: $(form).serializeArray(),
                success: function (response) {
                    self.renderMessages(response, form);
                    self.toggleActionDisableState(form, false);
                    form.find('.captcha-reload').click();
                }
            });
        },

        sendLogOut: function () {
            var self = this;

            this.showAnimation();

            $.ajax({
                url: self.options.logout_url,
                type: 'post',
                dataType: 'json',
                complete: function () {
                    self.hideAnimation();
                },
                success: function (response) {
                    if (response && response.message) {
                        self.showResultPopup(response.message);

                        if (!self._isCustomerAccountPage()) {
                            if (response.redirect === '1') {
                                response.redirect = '2';
                            }

                            amReloadContent.customRedirect(self.options.redirect_data);
                        } else {
                            self.reloadPage('/');
                        }
                    } else {
                        window.location.href = 'customer/account/logout/';
                    }
                },
                error: function () {
                    window.location.href = 'customer/account/logout/';
                }
            });

            return false;
        },

        /**
         * In case of two #form-validate elements on the same page need to pass validate options to form
         *
         * @private
         * @returns {void}
         */
        _validateDuplicatedForm: function () {
            var options = $.mage.validation().options;

            if (_.isUndefined(options.radioCheckboxClosest)) {
                $(this.options.selectors.createAccountForm).validate(options);
            }
        },

        _isCustomerAccountPage: function () {
            return $('body').hasClass('account');
        },

        showResultPopup: function (message) {
            var parent = $(this.options.selectors.contentWrapper).addClass('amsl-login-success').html('');

            $('<div>').html(message).appendTo(parent);
            parent.show();
            $(this.options.selectors.tabWrapper).hide();
            $(this.options.selectors.tabWrapperForgot).hide();
            $(this.options.selectors.loginOverlay).fadeIn(this.options.popup_duration);
        },

        sendFormWithAjax: function (form) {
            var self = this;

            self.form = form;
            this.showAnimation();
            $.ajax({
                url: form.attr('action').replace('customer/account', 'amsociallogin/account'),
                data: form.serialize(),
                type: 'post',
                dataType: 'html',
                complete: function () {
                    self.hideAnimation();
                },
                success: function (response) {
                    var isSuccess = self.renderMessages(response, self.form);

                    if (isSuccess) {
                        customer.setIsLoggedIn(true);
                        $('body').trigger('ams-logged-in-successfully');
                        amReloadContent.customRedirect(self.options.redirect_data);
                    } else {
                        self.captchaLoad(response, self.form);
                    }

                    self.toggleActionDisableState(self.form, false);
                },
                error: function () {
                    self.showDefaultMessage(self.form);
                }
            });
        },

        captchaLoad: function (response, form) {
            var reloadButton = form.find('.captcha-reload');

            if (reloadButton.length) {
                $(reloadButton).click();
            } else {
                var captcha = $(response).find('.captcha.required');

                if (captcha.length) {
                    form.find('.actions-toolbar').before(captcha);
                    form.trigger('contentUpdated');
                }
            }
        },

        renderMessages: function (response, form) {
            var cookieMessages = _.unique($.cookieStorage.get('mage-messages'), 'text'),
                self = this;

            $.cookieStorage.set('mage-messages', '');

            if (cookieMessages.length) {
                var correct = true;

                $(cookieMessages).each(function (index, message) {
                    if (message.type === 'error') {
                        correct = false;
                    }
                });

                if (!correct) {
                    self.showDefaultMessage(form, cookieMessages);

                    return false;
                }
            }

            if (cookieMessages.length) {
                self.showResultPopup(cookieMessages[0].text);
            } else if (form.hasClass('form-login')) {
                if (response.indexOf('customer/account/logout') !== -1) {
                    self.showResultPopup($.mage.__('You have successfully logged in.'));
                } else {
                    self.showDefaultMessage(form);

                    return false;
                }
            } else if (form.hasClass('form-create-account')) {
                self.showResultPopup($.mage.__('Thank you for registering with us.'));
            } else {
                self.showDefaultMessage(form);

                return false;
            }

            return true;
        },

        openPopup: function (activeTabIndex) {
            this.hideModalsOverlay();
            this.showMore();
            this.refreshPopup();

            if ($('html').hasClass('nav-open')) {
                $('.navigation > .ui-menu').menu('toggle');
            }

            $(this.options.selectors.loginOverlay).fadeIn(this.options.popup_duration);
            $(this.options.selectors.popup).focus();
            this.widgets.tabs.activate(activeTabIndex);

            return this;
        },

        refreshPopup: function () {
            $(this.options.selectors.contentWrapper).hide();
            $(this.options.selectors.tabWrapperForgot).hide();
            $(this.options.selectors.tabWrapper).show();
        },

        closePopup: function () {
            this.hideModalsOverlay();
            $(this.options.selectors.loginOverlay).fadeOut(this.options.popup_duration, function () {
                $(this.options.selectors.error_messages).hide();
            }.bind(this));

            $('body').removeClass(this.options.classes.parentModal);
            this.resetFormFields();
        },

        hideModalsOverlay: function () {
            if ($(this.options.selectors.modalsOverlay).length) {
                $(this.options.selectors.modalsOverlay).remove();
            }
        },

        resetFormFields: function () {
            $(this.options.selectors.form + ' ' + this.options.selectors.visibleInput).val('');
        },

        changeForms: function () {
            var parent = $(this.options.selectors.popup);

            /* Login Form */

            /* adding placeholders for fields */
            parent
                .find('.amsl-login-content [name="login[username]"]')
                .prop('placeholder', this.options.textValues.emailFieldLabel);
            parent
                .find('.amsl-login-content [name="login[password]"]')
                .prop('placeholder', this.options.textValues.passwordFieldLabel);

            /* moving 'forgot password' link */
            parent.find('.fieldset.login .actions-toolbar .secondary')
                .insertAfter('[data-am-js="am-login-popup"] .fieldset.login .field.password');

            /* Register Form */

            /* moving 'newsletter' checkbox */
            parent.find('.amsl-register-content .field.choice.newsletter')
                .insertBefore('[data-am-js="am-login-popup"] .amsl-register-content .field.password');
        },

        /**
         * @param {Object} form - jQuery Element
         * @param {Object|Array} [messages]
         * @param {String} [messageType]
         * @returns {void}
         */
        showDefaultMessage: function (form, messages, messageType) {
            var classes = this.options.classes,
                selectors = this.options.selectors,
                popup = $(selectors.popup),
                parent;

            selectors.default_error = $(form)
                .parents(selectors.contentBlock)
                .find(selectors.errorBlock);

            selectors.default_error.addClass(messageType || classes.default);

            if (!popup.has(selectors.socialWrapper).length
                && (popup.hasClass(classes.socials.right) || popup.hasClass(classes.socials.left))
            ) {
                $(selectors.loginContentBlock).addClass(classes.empty);
            }

            parent = $(selectors.default_error).html('');

            if (messages) {
                $(messages).each(function (index, message) {
                    $('<div>').html(message.text).appendTo(parent);
                });
            } else {
                $('<div>').html(this.options.textValues.unexpectedError).appendTo(parent);

                // when we don't know error - it is better to make reload - error can be connected to form_key
                this.reloadPage();
            }

            parent.show();
            this.toggleActionDisableState(form, false);
        },

        /**
         * @param {Object} target - jQuery Element
         * @param {Boolean} isDisabled
         * @returns {void}
         */
        toggleActionDisableState: function (target, isDisabled) {
            target
                .find(this.options.selectors.actionButton).prop('disabled', isDisabled);
        },

        /**
         * @param {String} [href]
         * @returns {void}
         */
        reloadPage: function (href) {
            _.delay(function () {
                if (_.isUndefined(href)) {
                    window.location.reload(true);
                } else {
                    window.location.href = href;
                }
            }, this.options.redirect_duration);
        },

        showAnimation: function () {
            $('body').trigger('processStart');
        },

        hideAnimation: function () {
            $('body').trigger('processStop');
        },

        toggleWrappers: function () {
            $(this.options.selectors.tabWrapper).toggle();
            $(this.options.selectors.tabWrapperForgot).toggle();
        },

        showMore: function () {
            var self = this,
                hideAfterCount = 3,
                buttonWrap = '[data-amslogin="button-wrap"]',
                showMoreButtonAttr = 'data-amslogin="showmore"',
                showMoreButtonClass = 'amsl-showmore-wrapper',
                socialButtonSelector = '.amsl-button-wrapper',
                showMoreElement = '<div class="' + showMoreButtonClass + '"' + showMoreButtonAttr
                    + '><button class="amsl-showmore-button">' + $.mage.__('Show More')
                    + '<span class="amsl-arrow"></span></button></div>',
                buttons;

            $(buttonWrap).each(function () {
                if (!$(this).find('[' + showMoreButtonAttr + ']').length
                    && $(this).children().length > hideAfterCount
                    && $(this).parents(self.options.selectors.popup).length) {
                    $(this).addClass(self.options.classes.flexCenter)
                        .find(socialButtonSelector + ':nth-child(' + hideAfterCount + ')')
                        .after(showMoreElement);
                    $(this).find('.' + showMoreButtonClass + ' ~ ' + socialButtonSelector).hide();
                }
            });

            $('[' + showMoreButtonAttr + ']').off().on('click', function () {
                buttons = $(this).parent().find(socialButtonSelector);

                $(this).fadeOut(self.options.popup_duration).remove();
                buttons.fadeIn(self.options.popup_duration);
            });
        },

        /**
         * Add event focus/blur listeners to password field with the password meter.
         * The password meter has attribute for reading its own dynamic content.
         * Method removes it after page ready and set it back when field is on focus;
         *
         * @returns {void}
         */
        initPasswordReadingListener: function () {
            var self = this,
                $form = $(self.options.selectors.createAccountForm),
                $field = $form.find(self.options.selectors.passwordField),
                $passwordMeter = $field.next(self.options.selectors.passwordMeter);

            if (!$passwordMeter.length) {
                return;
            }

            self.toggleLiveScreenReading($passwordMeter, false);

            $field
                .on('focus', function () {
                    self.toggleLiveScreenReading($passwordMeter, true);
                })
                .on('blur', function () {
                    self.toggleLiveScreenReading($passwordMeter, false);
                });
        },

        /**
         * Screen readers read and speak page dynamic content, that has aria-live='polite' attr
         * Method toggles that attr for preventing speaking content if necessary
         *
         * @param {Object} target - jQuery Element
         * @param {Boolean} isReading
         * @returns {void}
         */
        toggleLiveScreenReading: function (target, isReading) {
            target.attr('aria-live', isReading ? 'polite' : '');
        }
    });

    return $.mage.amLoginPopup;
});
