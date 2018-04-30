/**
 * This file is part of the Magento 2 Shipping module of DPD France.
 *
 * Copyright (C) 2018  Tiefanovic.
 *
 */
define([
	'jquery',
	'ko',
	'uiComponent',
	'Magento_Ui/js/modal/modal',
	'Magento_Checkout/js/model/quote',
	'Magento_Checkout/js/model/shipping-service',
	'Magento_Checkout/js/checkout-data',
	'Magento_Checkout/js/model/step-navigator',
    'Magento_Checkout/js/model/sidebar',
    'Magento_Checkout/js/view/shipping-information/address-renderer/default',
    'mage/translate',
], function ($, ko, Component, modal, quote, shippingService, checkoutData, stepNavigator, sidebarMode) {
	'use strict';

	return Component.extend({
		 defaults: {
		 	//template: 'DPDFrance_Shipping/checkout/shipping/parcelshops'
             //template: 'DPDFrance_Shipping/ShippingInfo',
			 isVisible: false
		 },

		initObservable: function() {

			this._super().observe([
				'pickupAddresses',
				'postalCode',
				'city',
				'countryCode',
				'street',
				'hasAddress',
				'selectedOption'
			]);

			var parcelShops = null;

			this.selectedMethod = ko.computed(function() {
				var method = quote.shippingMethod();
				var selectedMethod = method !== null ? method.carrier_code + '_' + method.method_code : null;
                var address = quote.shippingAddress();
				if(selectedMethod !== null){
				   if (selectedMethod.indexOf('dpdfrrelais') !== -1) {
				        $('#dpd_predict_container').remove();
    				    if ($('#dpd_relais_container').length === 0) {
    						$('<tr><td id="dpd_relais_container" colspan="6"></td><tr>').insertAfter($('td[id^=label_method_code][id$="dpdfrrelais"]').parent());
    						$('#dpd_relais_container').html('<div class="relaypoint_header"><label class="relaypoint_label_header">'+$.mage.__("Your Pickup delivery by DPD")+'</label><br></div><div class="relaypoint_search"><div class="relaypoint_logo"><label class="relaypoint_label_logo"><img src="' + window.checkoutConfig.dpd_frrelais_logo +'" alt=""/></label></div>' +
                                                            '<div class="relaypoint_input"><label class="relaypoint_label_find" >'+$.mage.__("Find DPD Pickup points near this address : ")+' </label><br/><input id="address" name="address" type="text" class="relaypoint-input-address" value="'+address.street+'"/><br>'+
                                                            '<input id="zipcode" name="zipcode" type="text" class="relaypoint-input-zipcode" value="'+address.postcode+'"/><input id="city" name="city" type="text" class="relaypoint-input-city" value="'+ address.city+'"/><button class="dpdfrbutton" type="button" style="height: 20px;padding: 0;width: 36px;"><span>'+$.mage.__("OK")+'</span>'+
                                                            '</button><div id="relais_button_holder"></div><span id="loadingpointswait" style="display:none;"><img src="'+window.checkoutConfig.dpd_frrelais_loader+'" alt="" class="v-middle" /></span></div><div id="suggestion"></div></div>');
    					}
    				}else if(selectedMethod.indexOf('dpdfrpredict') !== -1){
    				    $('#dpd_relais_container').remove();
                            if ($('#dpd_predict_container').length === 0) {
                            $('<tr><td id="dpd_predict_container" colspan="6"></td><tr>').insertAfter($('td[id^=label_method_code][id$="dpdfrpredict"]').parent());
    						$('#dpd_predict_container').html('<div id="dpdfrpredict"><div class="predict_header"><label class="predict_label_header">'+$.mage.__("Your Predict delivery by DPD")+'</label><br>'+
    						 '</div><div class="predict_search"><div class="predict_logo"><label class="predict_label_logo"><img src="' + window.checkoutConfig.dpd_frpredict_logo + '" alt=""/></div><div class="copy"><p><h2>'+$.mage.__("Predict offers you the following benefits")+'</h2></p>'+
                             '<ul><li><b>'+$.mage.__("A parcel delivery in a 1-hour time window (choice is made by SMS or through our website)")+'</b></li><li><b>'+$.mage.__("A complete and detailed tracking of your delivery")+'</b></li><li><b> '+$.mage.__("In case of absence, you can schedule a new delivery when and where you it suits you best")+'</b></li>'+
                             '</ul><p><h2> '+$.mage.__("How does it work?")+'</h2></p><ul><li> '+$.mage.__("Once your order is ready for shipment, you will receive an SMS proposing various days and time windows for your delivery.")+'</li><li> '+$.mage.__("You choose the moment which suits you best for the delivery by replying to the SMS (no extra cost) or through our website")+' <a href="http://www.dpd.fr/destinataires">dpd.fr</a></li>'+
                             '<li> '+$.mage.__("On the day of delivery, a text message will precise you a 1-hour time window for the delivery.")+'</li></ul></div><br/><div id="div_dpdfrance_dpd_logo"></div></div><div id="dpdfrance_predict_error" class="warnmsg" style="display:none;">'+$.mage.__("It seems that the GSM number you provided is incorrect. Please provide a french GSM number, starting with 06 or 07, on 10 consecutive digits.")+
                             '</div><div id="div_dpdfrance_predict_gsm">'+$.mage.__("To get all the advantages of DPD Predict service, please provide a french mobile phone number here ")+'<input id="gsm_dest" name="gsm_dest" type="text" class="predict-input-text" value="'+address.telephone+'"/></div></div>');
    					}
                            var regex = new RegExp(/^(?:(?:\+|00)33|0)\s*[1-9](?:[\s.-]*\d{2}){4}$/);
                            var gsmDest = document.getElementById('gsm_dest');
                            var numbers = gsmDest.value.substr(-8);
                            var pattern = new Array('00000000','11111111','22222222','33333333','44444444','55555555','66666666','77777777','88888888','99999999','12345678','23456789','98765432');
                
                            if (regex.test(gsmDest.value) && !pattern.includes(numbers)) {
                                document.getElementById('dpdfrance_predict_error').style.display = 'none';
                            } else {
                                document.getElementById('dpdfrance_predict_error').style.display = 'block';
                                return false;
                            }
                           
    				} else {
    				    $('#dpd_relais_container').remove();
    				    $('#dpd_predict_container').remove();
                        jQuery('.dpd-shipping-information').hide();
    				}
				}
				return selectedMethod;
			}, this);

			$(document).ready(function() {

			    $(document).on('change', 'input[name="relay-point"]', function(e) {
					e.preventDefault();
					var shopId = e.target.id;
					if(!shopId) {
						shopId = e.target.parentNode.id;
					}
                    var relaisaddress = $(this).val();
                    var shippingstring = [];
                    shippingstring=relaisaddress.split("|||");
                    
                    
                    
                    var shippingAddressData = checkoutData.getShippingAddressFromData();
                    var address = quote.shippingAddress();
					window.dpdShippingAddress = shippingstring;
					
					var newShippingAddress = {
									firstName:address.firstname, 
									lastName: address.lastname, 
									street: {0:shippingstring[0], 1:""},
									postcode: shippingstring[2],
									city: shippingstring[3],
									country_id: address.country_id,
									company: shippingstring[1],
									region: "",
									region_id: null,
									telephone: address.telephone
									};

					shippingAddressData = checkoutData.getShippingAddressFromData();

					jQuery.ajax({
						method: 'POST',
						showLoader: true, // enable loader
						url : window.checkoutConfig.dpd_parcelshop_save_url,
						data : newShippingAddress
					}).done(function (response) {
						$('#map_canvas').empty();
						$('#map_canvas').html(response);
					});
				});
			    $(document).on('change', 'input[id="gsm_dest"]', function(e) {
					var regex = new RegExp(/^(?:(?:\+|00)33|0)\s*[1-9](?:[\s.-]*\d{2}){4}$/);
                    var gsmDest = document.getElementById('gsm_dest');
                    var numbers = gsmDest.value.substr(-8);
                    var pattern = new Array('00000000','11111111','22222222','33333333','44444444','55555555','66666666','77777777','88888888','99999999','12345678','23456789','98765432');
                    if (regex.test(gsmDest.value) && !pattern.includes(numbers)) {
                        document.getElementById('dpdfrance_predict_error').style.display = 'none';
                    } else {
                        document.getElementById('dpdfrance_predict_error').style.display = 'block';
                        return false;
                    }

				});
				$(document).on('click', '.dpdfrbutton', function () {



                    var address = escape($('input[id="address"]').val());
                    var zipcode = escape($('input[id="zipcode"]').val());
                    var city = escape($('input[id="city"]').val());

                    jQuery.ajax({
                        method: 'POST',
                        showLoader: true, // enable loader
                        url : window.checkoutConfig.dpd_frrelais_check,
                        data :{address:address,zipcode:zipcode,city:city}
                    }).done(function (response) {
                        $('#suggestion').empty();
                        $('#suggestion').html(response);
                    });

                });
                $(document).on('click', '#dpd_more_details', function(e){
                    e.preventDefault();
					var popId = $(this).data('id');
                    openDialog($(this).data('id'),$("#title" + popId).data('popup-title'), $(this).data('map-canvas'), $(this).data('latitude'), $(this).data('longtitude'));
                });
                function openDialog(id,title,mapid,lat,longti,baseurl) {
                    var options = {
                    type: 'popup',
                    
                    title: title,
                    buttons: [{
                        text: $.mage.__('X'),
                        class: '',
                        click: function () {
                            this.closeModal();
                        }
                    }]
                    };
                   var popup = modal(options, $('#'+id));
                   $("#"+id).modal("openModal");
                     //alert('breakpoint');
                    initialize(mapid,lat,longti,baseurl);
                }
                function initialize(mapid,lat,longti,baseurl) {
					setTimeout(function(){
						var div = document.getElementById(mapid);
						div.style.display = "block";
					   var map = new google.maps.Map(document.getElementById(mapid), {
									zoom: 15,
									center: new google.maps.LatLng(lat, longti),
									mapTypeId: 'roadmap'
								});

						var markerBounds = new google.maps.LatLngBounds();
						var marker_image = new google.maps.MarkerImage(window.checkoutConfig.dpd_frrelais_marker, new google.maps.Size(57, 81), new google.maps.Point(0, 0), new google.maps.Point(0, 40));

						var marker = new google.maps.Marker({
							map: map,
							animation: google.maps.Animation.BOUNCE,
							position: new google.maps.LatLng(lat, longti),
							icon: marker_image
						});
						$(window).resize(function() {
						google.maps.event.trigger(map, 'resize');
						});
						google.maps.event.trigger(map, 'resize');
					}, 300);
                }
			});


			return this;
		}

	});


});
