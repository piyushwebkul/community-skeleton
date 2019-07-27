// Load the application once the DOM is ready
$(function () {
  // Initialize templates
  const templates = {
      loading_screen_template: $("#uvdesk-order-syncronization-loading-screen-template").html().replace(/&lt;/g, '<').replace(/&gt;/g, '>'),
      welcome_section_template: $("#uvdesk-order-syncronization-welcome-section-template").html().replace(/&lt;/g, '<').replace(/&gt;/g, '>'),
      manage_stores_template: $("#uvdesk-order-syncronization-manage-stores-template").html().replace(/&lt;/g, '<').replace(/&gt;/g, '>'),
      manage_store_settings_template: $("#uvdesk-order-syncronization-manage-store-settings-template").html().replace(/&lt;/g, '<').replace(/&gt;/g, '>'),
      manage_store_form_template: $("#uvdesk-order-syncronization-manage-store-form-template").html().replace(/&lt;/g, '<').replace(/&gt;/g, '>'),
      uvdesk_platform_accordion_template: $("#uvdesk-platform-accordion-template").html().replace(/&lt;/g, '<').replace(/&gt;/g, '>'),
      uvdesk_platform_manage_stores: $("#uvdesk-order-syncronization-manage-stores").html().replace(/&lt;/g, '<').replace(/&gt;/g, '>'),


      // Prestashop
      prestashop_loading_screen_template: $("#uvdesk-order-syncronization-loading-screen-prestashop-template").html().replace(/&lt;/g, '<').replace(/&gt;/g, '>'),
      prestashop_welcome_section_template: $("#uvdesk-order-syncronization-welcome-section-prestashop-template").html().replace(/&lt;/g, '<').replace(/&gt;/g, '>'),
      prestashop_manage_shops_template: $("#uvdesk-order-syncronization-manage-shops-prestashop-template").html().replace(/&lt;/g, '<').replace(/&gt;/g, '>'),
      prestashop_manage_shop_settings_template: $("#uvdesk-order-syncronization-manage-shop-settings-prestashop-template").html().replace(/&lt;/g, '<').replace(/&gt;/g, '>'),
      prestashop_manage_shop_form_template: $("#uvdesk-order-syncronization-manage-shop-prestashop-form-template").html().replace(/&lt;/g, '<').replace(/&gt;/g, '>'),
      prestashop_uvdesk_platform_manage_shops: $("#uvdesk-order-syncronization-manage-shops-prestashop").html().replace(/&lt;/g, '<').replace(/&gt;/g, '>'),
  
  };

  var platformsCollection = [];

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
          let collection = this;

          $.ajax({
              type: 'GET',
              url: this.url,
              dataType: 'json',
              success: function(response) {
                  platformsCollection = response.platforms;
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

  var Platform = Backbone.View.extend({
      el: $("#applicationDashboard"),
      url: "./order-syncronization/api?endpoint=",
      formTemplate: _.template(templates.manage_store_form_template),
      template: _.template(templates.uvdesk_platform_accordion_template),
      storesTemplate: _.template(templates.uvdesk_platform_manage_stores),
      
      // Prestashop templates
      prestashopFormTemplate: _.template(templates.prestashop_manage_shop_form_template),
      prestashopShopsTemplate: _.template(templates.prestashop_uvdesk_platform_manage_shops),

      events: {
          'click .uv-accordion-container': 'handleAccordionClick',
          'click .uv-app-list-brick .edit-channel': 'handleEditChannelEvent',
          'click .uv-app-list-brick .delete-channel': 'handleDeleteChannelEvent',
          'click .uv-add-channel-icon': 'handleAddChannelEvent',
          'click .uv-action-buttons #cancel': 'renderChannelList',
          'click .uv-action-buttons #save-channel': 'saveChannelDetails',

      },
      render: function () {
          this.$el.html(this.template({
              platformsCollection: platformsCollection
          }));
      },
      handleAccordionClick: function (event) {
          let target = event.target.parentNode;

          if (target.id.search("uv-accordion-btn-") > -1) {
              let parentNode = target.parentNode;
              let uvAccordionArrow = parentNode.getElementsByClassName('uv-accordion-arrow')[0];
              let uvAccordionTextContainer = parentNode.getElementsByClassName('uv-accordion-description-container')[0];
              let targetId = this.getEventIdentifier(target);
              let childView = parentNode.querySelector(`#uv-channel-view-${targetId}`);
              
              switch(targetId) {
                  case 'PrestashopECommerceCollection':
                    childView.innerHTML = this.prestashopShopsTemplate({
                        'platform': platformsCollection[targetId]
                    });
                    break;
                  default:
                    childView.innerHTML = this.storesTemplate({
                        'platform': platformsCollection[targetId]
                    });
                    break;
              }

              if (uvAccordionTextContainer.style.display == "block") {
                  uvAccordionTextContainer.style.display = "none";
                  uvAccordionArrow.classList.remove('bottom');
                  uvAccordionArrow.classList.add('right');
              } else {
                  uvAccordionArrow.classList.remove('right');
                  uvAccordionArrow.classList.add('bottom');
                  uvAccordionTextContainer.style.display = "block";
              }
          }
      },
      handleEditChannelEvent: function (event) {
          let targetPlatformId = event.target.closest('.uv-extension-accordion-view').id;
          let platformDetails = platformsCollection[targetPlatformId].channels[event.target.parentNode.id];
          platformDetails['channelIndex'] = event.target.parentNode.id;
          
          switch (targetPlatformId) {
              case 'PrestashopECommerceCollection':
                  $(`#uv-channel-view-${targetPlatformId}`).html(this.prestashopFormTemplate({
                      channelDetails: platformDetails
                  }));
                  break;
              default:
                  $(`#uv-channel-view-${targetPlatformId}`).html(this.formTemplate({
                      channelDetails: platformDetails
                  }));
                  break;      
          }
      },
      handleDeleteChannelEvent: function (event) {
          event.preventDefault();
          let platformId = this.findPlatformId(event.target);
          let channelId = this.findChannelId(event.target);

          let fields = {
              'platform': platformId,
              'channelId': channelId
          }

          $.ajax({
              type: 'DELETE',
              url: `${this.url}remove-store`,
              dataType: 'json',
              data: fields,
              success: function(response) {
                  platformsCollection = response.platforms;
                  collection.reset(collection.parse(response));
              },
              error: function (response) {
                  console.log('error:', response)
              }
          });
      },
      handleAddChannelEvent: function (event) {
          let targetPlatformId = event.target.closest('.uv-extension-accordion-view').id;
          
          switch (targetPlatformId) {
              case 'PrestashopECommerceCollection':
                  $(`#uv-channel-view-${targetPlatformId}`).html(this.prestashopFormTemplate({
                      channelDetails: {
                          'name': ''
                      },
                  }));
                  break;
              default:
                  $(`#uv-channel-view-${targetPlatformId}`).html(this.formTemplate({
                      channelDetails: {
                          'name': ''
                      },
                  }));
                  break;
          }
      },
      renderChannelList: function (event) {
          this.$el.html(this.template({
              platformsCollection: platformsCollection
          }));

          // open last opened channels list
          let activePlatformId = this.findPlatformId(event.target);
          let activePlatformButton = this.$el.find(`#uv-accordion-btn-${activePlatformId} button`);
          activePlatformButton.click();
      },
      getEventIdentifier: function (target) {
          let splittedIdentifier = target.id.split('-');
          return splittedIdentifier[splittedIdentifier.length - 1]
      },
      saveChannelDetails: function (event) {
          event.preventDefault();
          let platformId = this.findPlatformId(event.target);
          let channelId = this.findChannelId(event.target);

          var fields = {};
          fields[platformId] = {};
          var requestType;

          if (channelId) {
              requestType = 'PUT';
              fields[platformId]['id'] = channelId;
          } else {
              requestType = 'POST';
          }
          fields[platformId]['enabled'] = true;

          this.$el.find('form#uv-channel-action-form').find("input").each(function () {
              this.name ? (fields[platformId][this.name] = $(this).val()) : '';
          });

          $.ajax({
              type: requestType,
              url: `${this.url}save-store`,
              dataType: 'json',
              data: fields,
              success: function(response) {
              },
              error: function (response) {
                  console.log('error:', response)
              }
          });
      },
      
      findPlatformId: function (target) {
          return target.closest('.uv-extension-accordion-view').id;
      },

      findChannelId: function (target) {
          let targetedId;

          if (target.closest('.uv-action-buttons')) {
              targetedId = target.closest('.uv-action-buttons').id;
          } else {
              targetedId = target.closest('.uv-app-list-actions').id;
          }

          return targetedId;
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





    //----------------------------------PRESTASHOP-----------------------------//

    var PrestashopShop = Backbone.Model.extend({
        url: "./order-syncronization/api?endpoint=save-store",
        defaults: function () {
            return {
                domain: "",
                api_key: "",
                enabled: false,
            };
        },
        validate: function (attributes, options) {
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
        validateAttribute: function (name, value) {
            switch (name) {
                case 'domain':
                case 'api_key':
                    if (value == undefined || value == '') return 'This field cannot be left empty.';
                    break;
                default:
                    break;
            }

            return true;
        }
    });

    var PrestashopShopCollection = Backbone.Collection.extend({
        url: "./order-syncronization/api?endpoint=get-stores",
        model: PrestashopShop,
        parse: function (response) {
            return response.shops;
        },
        fetch: function () {
            let collection = this;

            $.ajax({
                type: 'GET',
                url: this.url,
                dataType: 'json',
                success: function (response) {
                    platformsCollection = response.platforms;
                    collection.reset(collection.parse(response));
                },
                error: function (response) {
                    console.log('error:', response)
                }
            });
        }
    });

    var PrestashopShopSettingsForm = Backbone.View.extend({
        el: $("#applicationDashboard"),
        template: _.template(templates.prestashop_manage_shop_form_template),
        events: {
            'input form input': 'setAttribute',
            'submit form': 'submitForm'
        },
        render: function (el) {
            console.log('render form:', this.model.toJSON());
            this.listenTo(this.model, 'sync', this.handleSync);
            this.listenTo(this.model, 'error', this.handleSyncFailure);

            el.html(this.template(this.model.toJSON()));
        },
        setAttribute: function (ev) {
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
            prestashopShopCollection.add(model);
            app.appView.renderResponseAlert({ alertClass: 'success', alertMessage: 'Settings saved successfully' });
        },
        handleSyncFailure: function (model, xhr, options) {
            let response = xhr.responseJSON;
            let message = (typeof (response) == 'undefined' || false == response.hasOwnProperty('error')) ? 'An unexpected error occurred. Please try again later.' : response.error;

            app.appView.renderResponseAlert({ alertClass: 'danger', alertMessage: message });
        }
    });

    var PrestashopWelcome = Backbone.View.extend({
        el: $("#applicationDashboard"),
        template: _.template(templates.prestashop_welcome_section_template),
        events: {
            'click .uv-app-shopify-cta-setup': 'renderStoreSettingsForm'
        },
        initialize: function () {
            this.listenTo(prestashopShopCollection, 'add', this.addPrestashopShop);
        },
        render: function () {
            this.$el.html(this.template());
        },
        renderShopSettingsForm: function (e) {
            let self = this;
            this.model = new PrestashopShop();
            this.welcomeForm = new PrestashopShopSettingForm({ model: this.model });

            this.$el.find('.welcome-screen.banner').hide();
            this.welcomeForm.render(this.$el.find('.welcome-screen.configure-store form'));
            this.$el.find('.welcome-screen.configure-store').show();
        },
        addPrestashopShop: function (prestashopShop) {
            console.log('adding prestashop shop:', prestashopShop);

            prestashopApp.render();
        }
    });


    var PrestashopDashboard = Backbone.View.extend({
        el: $("#applicationDashboard"),
        template: _.template(templates.prestashop_manage_shops_template),
        settings_template: _.template(templates.prestashop_manage_shop_settings_template),
        events: {
            'click button.edit': 'manageSettings',
            'input form input': 'setAttribute',
            'submit form': 'submitForm'
        },
        render: function () {
            console.log('rendering dashboard');

            prestashopShopCollection.each(function (model) {
                console.log("model:", model.get('id'))
            })

            this.$el.html(this.template({ shops: prestashopShopCollection.toJSON() }));
        },
        manageSettings: function (e) {
            let id = $(e.currentTarget).closest('.shopify-store-item').data('id');
            this.activeModel = prestashopShopCollection.get(id);

            console.log('managing settings for store:', id, this.activeModel.toJSON());

            this.listenTo(this.activeModel, 'sync', this.handleSync);
            this.listenTo(this.activeModel, 'error', this.handleSyncFailure);

            this.$el.html(this.settings_template(this.activeModel.toJSON()));
        },
        setAttribute: function (ev) {
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


    var PrestashopApp = Backbone.View.extend({
        el: $("#applicationDashboard"),
        initialize: function (prestashopShopCollection) {
            this.$el.empty();
            this.listenTo(prestashopShopCollection, 'reset', this.render);

            prestashopShopCollection.fetch();
        },
        render: function () {
            // // Remove and unbind current section
            // if (this.hasOwnProperty('section') || typeof this.section != 'undefined') {
            //     this.section.remove();
            // }

            console.log('Prestashop Shop collection:', prestashopShopCollection.length, prestashopShopCollection.toJSON());

            this.section = !prestashopShopCollection.length ? new PrestashopWelcome() : new PrestashopDashboard();

            this.section = platform;
            this.section.render();
        }
    });



    //----------------------------------PRESTASHOP----------------------------- end//



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

          this.section = platform;
          this.section.render();
      }
  });

  
  const platform = new Platform();

  const shopifyStoreCollection = new ShopifyStoreCollection();
  const shopifyApp = new ShopifyApp(shopifyStoreCollection);

  // Prestashop
  const prestashopShopCollection = new PrestashopShopCollection();
  const prestashopApp = new PrestashopApp(prestashopShopCollection);

});
