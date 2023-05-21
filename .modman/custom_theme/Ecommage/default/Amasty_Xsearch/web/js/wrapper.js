/**
 * Amasty Xsearch Wrapper UI Component
 */

define([
    'jquery',
    'ko',
    'uiRegistry',
    'amsearch_helpers',
    'uiComponent',
    'amsearch_color_helper',
    'mage/translate',
    'Amasty_Base/vendor/slick/slick.min'
], function ($, ko, registry, helpers, Component, colorHelper, $t) {
    'use strict';

    return Component.extend({
        defaults: {
            isMobile: $(window).width() < helpers.constants.mobile_view,
            icons: {
                chevron: 'Amasty_MegaMenuLite/components/icons/chevron'
            },
            nodes: {},
            components: [
                'index = amsearch_overlay_section'
            ],
            templates: {
                preload: 'Amasty_Xsearch/components/preload.html',
                message: 'Amasty_Xsearch/components/message.html',
                results: 'Amasty_Xsearch/results/wrapper.html',
                loader: 'Amasty_Xsearch/components/loader.html'
            },
            messages: {
                emptyProductSearch: $t('We could not find anything for <b>"%search_query%"</b>')
            }
        },

        /**
         * @inheritDoc
         */
        initialize: function () {
            this._super();

            registry.get(this.components, function () {
                helpers.initComponentsArray(arguments, this);

                this._initInput();
                this._initResize();
                this._initOverlay();
            }.bind(this));

            return this;
        },

        /**
         * @inheritDoc
         */
        initObservable: function () {
            var focusSubscriber;

            this._super()
                .observe({
                    loading: false,
                    focused: false,
                    opened: false,
                    preload: false,
                    readyForSearch: false,
                    searchProducts: false,
                    inputValue: '',
                    resultSectionStyles: false,
                    resized: false,
                    searchItems: [],
                    match: false,
                    message: ''
                });

            focusSubscriber = this.focused.subscribe(function (value) {
                if (value) {
                    focusSubscriber.dispose();
                    this.updatePreload();
                }
            }, this);

            return this;
        },

        /**
         * Input event 'enter keydown' handle
         *
         * @public
         * @param {Object} UiClass
         * @param {Object} event
         * @return {Boolean} for propagation
         */
        onEnter: function (UiClass, event) {
            if (event.keyCode === 13) {
                this.search();

                return false;
            }

            return true;
        },

        /**
         * Go to search page via input value
         *
         * @public
         * @returns {void}
         */
        search: function () {
            window.location = this.data.url_result + '?q=' + this.inputValue();
        },

        /**
         * Closing Search Popup and clear text
         *
         * @public
         * @returns {void}
         */
        close: function () {
            this.opened(false);
            this.inputValue('');
            this.amsearch_overlay_section.hide();
        },

        /**
         * Update html event handler
         *
         * @param {Object} node
         * @public
         * @returns {void}
         */
        updateHtml: function (node) {
            helpers.applyBindings(node, this);
        },

        /**
         * Initialize css variables for node element
         * And generate names via list of the color_settings conf keys
         *
         * @param {Object} node
         * @public
         * @returns {void}
         */
        initCssVariables: function (node) {
            Object.keys(this.data.color_settings).forEach(function (key) {
                node.style.setProperty('--amsearch-color-' + key, '#' + this.data.color_settings[key]);
                node.style.setProperty('--amsearch-color-' + key + '-focus', colorHelper.getDarken('#' + this.data.color_settings[key], 0.1));
                node.style.setProperty('--amsearch-color-' + key + '-hover', colorHelper.getDarken('#' + this.data.color_settings[key], 0.05));
            }.bind(this));
        },

        /**
         * Update Preload Section
         *
         * @public
         * @return {void}
         */
        updatePreload: function () {
            $.ajax({
                url: this.data.url.slice(0, -1) + 'recent',// amasty_xsearch/autocomplete/indexrecent
                data: {
                    uenc: this.data.currentUrlEncoded
                },
                dataType: 'html',
                success: function (html) {
                    this.preload(html);
                }.bind(this)
            });
        },

        /**
         * Start Search Process
         *
         * @public
         * @param {String} value
         * @return {void}
         */
        searchProcess: function (value) {
            this.loading(true);

            $.get(
                this.data.url,
                {
                    q: value,
                    uenc: this.data.currentUrlEncoded,
                    form_key: $.mage.cookies.get('form_key')
                },
                $.proxy(function (data) {
                    this.opened(true);
                    this.match(true);
                    this.loading(false);
                    this._parseSearchData(data);
                }, this));
        },

        /**
         * Initialize result section node element
         * Added subscriber for showed position and checking viewport for rendering
         *
         * @public
         * @param {Object} node
         * @param {Boolean} isBaseSearch
         * @returns {void}
         */
        initResultSection: function (node, isBaseSearch) {
            if (!isBaseSearch) {
                var subscriber = this.opened.subscribe(function (value) {
                    if (value) {
                        helpers.setNodePositionByViewport(node);
                        subscriber.dispose();
                    }
                }.bind(this));
            }

            this.nodes.results = node;

            this.resultSectionStyles({
                background: this.data.color_settings.background,
                borderColor: this.data.color_settings.border,
                color: this.data.color_settings.text
            });
        },

        /**
         * init Input value if is setup before ko initialized
         *
         * @public
         * @param {Object} node input node
         * @returns {void}
         */
        initInputValue: function (node) {
            var value = node.value;

            if (value && value.length) {
                this.inputValue(node.value);
            }
        },

        /**
         * init Input functionality
         *
         * @private
         * @returns {void}
         */
        _initInput: function () {
            this.readyForSearch = ko.computed(function () {
                return this.inputValue().length >= this.data.minChars;
            }.bind(this));

            this.inputValue
                .extend({
                    rateLimit: {
                        method: 'notifyWhenChangesStop',
                        timeout: this.data.delay
                    }
                })
                .subscribe(function (value) {
                    var isSearch = value.length >= this.data.minChars,
                        strippedValue = helpers.stripTags(value);

                    if (isSearch && strippedValue) {
                        this.inputValue.silentUpdate(strippedValue);

                        return false;
                    }

                    this.message(false);

                    if (isSearch) {
                        this.searchProcess(value);
                    } else {
                        this.searchItems.removeAll();
                        this.searchProducts(false);
                        this.match(false);
                    }
                }.bind(this));
        },

        /**
         * init overlay functionality
         *
         * @private
         * @returns {void}
         */
        _initOverlay: function () {
            this.focused.subscribe(function (value) {
                if (value) {
                    this.amsearch_overlay_section.show();
                    this.opened(true);
                    helpers.initProductAddToCart(this.nodes.results);
                }
            }.bind(this));

            this.amsearch_overlay_section.opened.subscribe(function (value) {
                if (!value) {
                    this.opened(false);
                }
            }.bind(this));
        },

        /**
         * init resize functionality
         *
         * @private
         * @returns {void|Boolean}
         */
        _initResize: function () {
            if (this.isMobile) {
                return false;
            }

            this.resized = ko.computed(function () {
                return this.readyForSearch() && this.data.width && this.opened();
            }.bind(this));
        },

        /**
         * Parsing data from search request
         *
         * @private
         * @param {Object} data
         * @return {void}
         */
        _parseSearchData: function (data) {
            var searchItems = [];

            Object.keys(data).forEach(function (key) {
                if (data[key].type === 'product') {
                    if (!data[key].html.length) {
                        this.searchProducts(false);
                        this.message(this.messages.emptyProductSearch.replace('%search_query%', this.inputValue()));
                    } else {
                        this.searchProducts(data[key].html);
                    }

                    return;
                }

                if (data[key].html === undefined || data[key].html.length <= 1) {
                    return;
                }

                searchItems.push(data[key]);
            }.bind(this));

            this.searchItems(searchItems);
        }
    });
});
