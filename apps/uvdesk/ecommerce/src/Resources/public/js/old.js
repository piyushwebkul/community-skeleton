// Load the application once the DOM is ready
$(function () {
    // Initialize templates
    const templates = {
        loading_screen_template: $("#uvdesk-order-syncronization-loading-screen-template").html().replace(/&lt;/g, '<').replace(/&gt;/g, '>'),
        welcome_section_template: $("#uvdesk-order-syncronization-welcome-section-template").html().replace(/&lt;/g, '<').replace(/&gt;/g, '>'),
        manage_stores_template: $("#uvdesk-order-syncronization-manage-stores-template").html().replace(/&lt;/g, '<').replace(/&gt;/g, '>'),
        manage_store_settings_template: $("#uvdesk-order-syncronization-manage-store-settings-template").html().replace(/&lt;/g, '<').replace(/&gt;/g, '>'),
        manage_store_form_template: $("#uvdesk-order-syncronization-manage-store-form-template").html().replace(/&lt;/g, '<').replace(/&gt;/g, '>'),
    };

    // Animated Loaders
    var DashboardAnimations = Backbone.View.extend({
        el: $("#applicationDashboard"),
        template: _.template(templates.loading_screen_template),
        showDashboardLoader: function (text) {
            this.$el.append(this.template({ text: text }));
        },
        hideDashboardLoader: function () {
            this.$el.find('.shopify-dashboard-loader').remove();
        }
    });

    var ShopifyStore = Backbone.Model.extend({
        url: "./order-syncronization/api?endpoint=save-store",
        defaults: function() {
            return {
                domain: "",
                api_key: "",
                api_password: "",
                enabled: false,
            };
        },
        validate: function(attributes, options) {
            let validationErrors = {};

            for (let name in attributes) {
                let result = this.validateAttribute(name, attributes[name]);

                if (result !== true) {
                    validationErrors[name] = result;
                }
            }

            if (false == $.isEmptyObject(validationErrors)) {
                return validationErrors;
            }
        },
        validateAttribute: function(name, value) {
            switch (name) {
                case 'domain':
                case 'api_key':
                case 'api_password':
                    if (value == undefined || value == '') return 'This field cannot be left empty.';
                    break;
                default:
                    break;
            }

            return true;
        }
    });

    var ShopifyStoreCollection = Backbone.Collection.extend({
        url: "./order-syncronization/api?endpoint=get-stores",
        model: ShopifyStore,
        parse: function (response) {
            return response.stores;
        },
        fetch: function () {
            console.log('fetching stores...');
            let collection = this;

            $.ajax({
                type: 'GET',
                url: this.url,
                dataType: 'json',
                success: function(response) {
                    collection.reset(collection.parse(response));
                },
                error: function (response) {
                    console.log('error:', response)
                }
            });
        }
    });

    var ShopifyStoreSettingsForm = Backbone.View.extend({
        el: $("#applicationDashboard"),
        template: _.template(templates.manage_store_form_template),
        events: {
            'input form input': 'setAttribute',
            'submit form': 'submitForm'
        },
        render: function(el) {
            console.log('render form:', this.model.toJSON());
            this.listenTo(this.model, 'sync', this.handleSync);
            this.listenTo(this.model, 'error', this.handleSyncFailure);

            el.html(this.template(this.model.toJSON()));
        },
        setAttribute: function(ev) {
            let name = $(ev.currentTarget)[0].name.trim();
            let value = $(ev.currentTarget)[0].value.trim();

            if (this.model.has(name)) {
                this.model.set(name, value);
            }
        },
        submitForm: function (ev) {
            ev.preventDefault();

            if (this.model.isValid()) {
                console.log('saving model');
                this.model.save();
            }
        },
        handleSync: function (model, response, options) {
            console.log('model synced:', model);
            shopifyStoreCollection.add(model);
            app.appView.renderResponseAlert({ alertClass: 'success', alertMessage: 'Settings saved successfully' });
        },
        handleSyncFailure: function (model, xhr, options) {
            let response = xhr.responseJSON;
            let message = (typeof(response) == 'undefined' || false == response.hasOwnProperty('error')) ? 'An unexpected error occurred. Please try again later.' : response.error;

            app.appView.renderResponseAlert({ alertClass: 'danger', alertMessage: message });
        }
    });

    var Welcome = Backbone.View.extend({
        el: $("#applicationDashboard"),
        template: _.template(templates.welcome_section_template),
        events: {
            'click .uv-app-shopify-cta-setup': 'renderStoreSettingsForm'
        },
        initialize: function() {
            this.listenTo(shopifyStoreCollection, 'add', this.addShopifyStore);
        },
        render: function () {
            this.$el.html(this.template());
        },
        renderStoreSettingsForm: function(e) {
            let self = this;
            this.model = new ShopifyStore();
            this.welcomeForm = new ShopifyStoreSettingsForm({ model: this.model });

            this.$el.find('.welcome-screen.banner').hide();
            this.welcomeForm.render(this.$el.find('.welcome-screen.configure-store form'));
            this.$el.find('.welcome-screen.configure-store').show();
        },
        addShopifyStore: function(shopifyStore) {
            console.log('adding shopify store:', shopifyStore);

            shopifyApp.render();
        }
    });

    var Dashboard = Backbone.View.extend({
        el: $("#applicationDashboard"),
        template: _.template(templates.manage_stores_template),
        settings_template: _.template(templates.manage_store_settings_template),
        events: {
            'click button.edit': 'manageSettings',
            'input form input': 'setAttribute',
            'submit form': 'submitForm'
        },
        render: function () {
            console.log('rendering dashboard');

            shopifyStoreCollection.each(function (model) {
                console.log("model:", model.get('id'))
            })

            this.$el.html(this.template({ stores : shopifyStoreCollection.toJSON() }));
        },
        manageSettings: function (e) {
            let id = $(e.currentTarget).closest('.shopify-store-item').data('id');
            this.activeModel = shopifyStoreCollection.get(id);
            
            console.log('managing settings for store:', id, this.activeModel.toJSON());

            this.listenTo(this.activeModel, 'sync', this.handleSync);
            this.listenTo(this.activeModel, 'error', this.handleSyncFailure);

            this.$el.html(this.settings_template(this.activeModel.toJSON()));
        },
        setAttribute: function(ev) {
            let name = $(ev.currentTarget)[0].name.trim();
            let value = $(ev.currentTarget)[0].value.trim();

            if (this.activeModel.has(name)) {
                this.activeModel.set(name, value);
            }
        },
        submitForm: function (ev) {
            ev.preventDefault();

            if (this.activeModel.isValid()) {
                console.log('saving model');
                this.activeModel.save();
            }
        },
        handleSync: function (model, response, options) {
            console.log('model synced:', model);
            app.appView.renderResponseAlert({ alertClass: 'success', alertMessage: 'Settings saved successfully' });

            shopifyApp.render();
        },
        handleSyncFailure: function (model, xhr, options) {
            let response = xhr.responseJSON;
            let message = response.hasOwnProperty('error') ? response.error : 'An unexpected error occurred. Please try again later.';

            app.appView.renderResponseAlert({ alertClass: 'danger', alertMessage: message });
        }
    });

    var ShopifyApp = Backbone.View.extend({
        el: $("#applicationDashboard"),
        initialize: function(shopifyStoreCollection) {
            this.$el.empty();
            this.listenTo(shopifyStoreCollection, 'reset', this.render);

            shopifyStoreCollection.fetch();
        },
        render: function() {
            // // Remove and unbind current section
            // if (this.hasOwnProperty('section') || typeof this.section != 'undefined') {
            //     this.section.remove();
            // }

            console.log('shopify collection:', shopifyStoreCollection.length, shopifyStoreCollection.toJSON());

            this.section = !shopifyStoreCollection.length ? new Welcome() : new Dashboard();
            this.section.render();
        }
    });

    let shopifyStoreCollection = new ShopifyStoreCollection();
    let shopifyApp = new ShopifyApp(shopifyStoreCollection);
});