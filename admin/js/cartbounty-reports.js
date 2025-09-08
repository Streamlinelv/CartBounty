(function( $ ) {
	'use strict';
	 
	 jQuery(document).ready(function(){

		function toggleReportOptions(event){
			event.stopPropagation();
			var tag_parent_element = jQuery(this).parent();
			var tag_parent_active_class = 'cartbounty-report-options-active';

			if(!tag_parent_element.hasClass( tag_parent_active_class )){
				tag_parent_element.addClass( tag_parent_active_class );
				jQuery(document).on("click", closeReportOptions);

			}else{
				tag_parent_element.removeClass(tag_parent_active_class);
				jQuery(document).off("click", closeReportOptions);
			}
		}

		function closeReportOptions(event){
			var tooltip = jQuery(".cartbounty-options-tooltip");
			if (!tooltip.is(event.target) && tooltip.has(event.target).length === 0) {
				tooltip.parent().removeClass("cartbounty-report-options-active");
				jQuery(document).off("click", closeReportOptions);
			}
		}

		function toggleCalendar(event){
			event.stopPropagation();
			var tag_parent_element = jQuery(this).parent();
			var tag_parent_active_class = 'cartbounty-calendar-open';

			if(!tag_parent_element.hasClass( tag_parent_active_class )){
				jQuery('.cartbounty-icon-button-container').removeClass('cartbounty-report-options-active');
				tag_parent_element.addClass( tag_parent_active_class );

			}else{
				tag_parent_element.removeClass(tag_parent_active_class);
			}
		}

		function closeCalendar(){
			var tag_parent_element = jQuery("#cartbounty-period-dropdown-container").parent();
			var tag_parent_active_class = 'cartbounty-calendar-open';
			tag_parent_element.removeClass(tag_parent_active_class);
		}

		function updateReportsOptions(){
			var item = jQuery(this);
			var action = item.data('action');
			var startDate = jQuery('#cartbounty-daypicker-start-date-input-duplicate');
			var endDate = jQuery('#cartbounty-daypicker-end-date-input-duplicate');
			var selectedPeriod = jQuery('.cartbounty-daypicker-periods input[type=checkbox]:checked');
			var comparePeriod = jQuery('.cartbounty-daypicker-comparison-options input[type=checkbox]:checked');
			var skeletonScreenClass = 'cartbounty-loading-skeleton-screen';
			var reportsContainer = jQuery('#cartbounty-abandoned-cart-quick-stats-container');

			if( action == 'update_charts' ){
				reportsContainer = jQuery('#cartbounty-charts-container');
			}

			var data = {
				nonce				: item.data('nonce'),
				action				: action,
				value				: item.prop('value'),
				status				: item.prop('checked'),
				name				: item.data('name'),
				start_date 			: startDate.val(),
				end_date 			: endDate.val(),
				period 				: selectedPeriod.val(),
				compare 			: comparePeriod.val()
			};

			reportsContainer.addClass(skeletonScreenClass);

			jQuery.post(cartbounty_admin_data.ajaxurl, data,
			function(response){
				if ( response.success == true ){
					reportsContainer.html(response.data.report_data);

					if( action == 'update_charts' ){
						var chart_type = response.data.chart_type;
						var active_charts = response.data.active_charts;
						initializeCharts(chart_type, active_charts);
					}
				}else{
					console.log('An error occurred while updating quick stats.');
				}

				reportsContainer.removeClass(skeletonScreenClass);
			});
		}

		function addLoadingIndicator(){
			jQuery(this).addClass('cartbounty-loading');
		}

		function onDayPickerLoaded() {
			jQuery("#cartbounty-daypicker-apply").on("click", updateReports);
			jQuery("#cartbounty-daypicker-close").on("click", closeCalendar);
			jQuery(".cartbounty-progress").on("click", addLoadingIndicator );

			//Display reports on first page load
			setTimeout(function(){
				updateReports.call(jQuery('#cartbounty-daypicker-apply'), null);
			}, 300);

		}

		function getTipOptions(){
			return {
				tipShadow			: "drop-shadow(2px 2px 10px rgba(0,0,0,0.07))",
				tipStrokeWidth		: 0.5,
				tipStrokeOpacity	: 0.1,
				tipTextPadding		: 20,
				tipFontSize			: 13,
				tipPointerSize		: 0,
				tipPreferredAnchor	: "left",
			};
		}

		function updateReports(e){
			if(e && e.type === 'click'){
				e.preventDefault();
			}
			var element = jQuery(this);
			var startDate = jQuery('#cartbounty-daypicker-start-date-input-duplicate');
			var endDate = jQuery('#cartbounty-daypicker-end-date-input-duplicate');
			var selectedPeriod = jQuery('.cartbounty-daypicker-periods input[type=checkbox]:checked');
			var comparePeriod = jQuery('.cartbounty-daypicker-comparison-options input[type=checkbox]:checked');
			var quickContainer = jQuery('#cartbounty-abandoned-cart-quick-stats-container');
			var chartContainer = jQuery('#cartbounty-charts-container');
			var periodDropdownContainer = jQuery('#cartbounty-period-dropdown-container');
			var topProductContainer = jQuery('#cartbounty-top-abandoned-products-container');
			var map = jQuery('#cartbounty-abandoned-carts-by-country-container');
			var skeletonScreenClass = 'cartbounty-loading-skeleton-screen';

			quickContainer.addClass(skeletonScreenClass);
			chartContainer.addClass(skeletonScreenClass);
			periodDropdownContainer.addClass(skeletonScreenClass);
			topProductContainer.addClass(skeletonScreenClass);
			map.addClass(skeletonScreenClass);

			var data = {
				nonce				: element.data('nonce'),
				action				: element.data('action'),
				start_date 			: startDate.val(),
				end_date 			: endDate.val(),
				period 				: selectedPeriod.val(),
				compare 			: comparePeriod.val(),
				current_url 		: window.location.href
			};

			jQuery.post(cartbounty_admin_data.ajaxurl, data,
			function(response){

				if( response.success == true ){
					element.removeClass('cartbounty-loading');
					history.pushState({}, '', response.data.url);
					periodDropdownContainer.html(response.data.period_dropdown);
					quickContainer.html(response.data.report_data);
					chartContainer.html(response.data.chart_data);
					topProductContainer.html(response.data.top_products);
					map.html(response.data.map_data); //Update map data
					var chart_type = response.data.chart_type;
					var active_charts = response.data.active_charts;
					initializeCharts(chart_type, active_charts);
					initializeMap();

				}else{
					console.log('An error occurred while updating the report period');
				}

				quickContainer.removeClass(skeletonScreenClass);
				chartContainer.removeClass(skeletonScreenClass);
				periodDropdownContainer.removeClass(skeletonScreenClass);
				topProductContainer.removeClass(skeletonScreenClass);
				map.removeClass(skeletonScreenClass);
			});
		}

		if(typeof CartBountyDayPicker !== 'undefined'){
			var daypicker = cartbounty_admin_data.daypicker;
			var options = {
				'id'                    : daypicker.id,
				'action'                : daypicker.action,
				'nonce'                 : daypicker.nonce,
				'mode'                  : daypicker.mode,
				'showOutsideDays'       : daypicker.showOutsideDays,
				'defaultStartDate'      : new Date(daypicker.defaultStartDate),
				'defaultEndDate'        : new Date(daypicker.defaultEndDate),
				'defaultDatePeriod'     : daypicker.defaultDatePeriod,
				'defaultFallbackPeriod' : daypicker.defaultFallbackPeriod,

				'defaultComparison'     : daypicker.defaultComparison,
				'dateFormat'            : daypicker.dateFormat,
				'minFromDate'           : new Date(daypicker.minFromDate),
				'maxToDate'             : new Date(daypicker.maxToDate),
				'weekStartsOn'          : daypicker.weekStartsOn,
				'dir'                   : daypicker.dir,
				'language'              : daypicker.language,
				'componentNames'        : daypicker.componentNames,
				'comparisonOptions'     : daypicker.comparisonOptions,
				'datePeriods'           : daypicker.datePeriods,
				'onLoaded'           	: onDayPickerLoaded
			}
			CartBountyDayPicker( options );
		}

		function switchActiveChart(){
			var button = jQuery(this);
			var button_parent = button.parent();
			var chart_type = button.data('name');
			var chartContainer = jQuery('#cartbounty-charts-container');
			var skeletonScreenClass = 'cartbounty-loading-skeleton-screen';
			
			button_parent.find('.cartbounty-chart-type-trigger').removeClass('cartbounty-chart-type-active');
			button.addClass('cartbounty-chart-type-active');
			chartContainer.addClass(skeletonScreenClass);
			
			var data = {
				nonce				: button.data('nonce'),
				action				: button.data('action'),
				value 				: chart_type
			};

			jQuery.post(cartbounty_admin_data.ajaxurl, data,
			function(response){
				var active_charts = false;

				if ( response.success == true ){
					chart_type = response.data.chart_type;
					active_charts = response.data.active_charts;
				}
				initializeCharts( chart_type, active_charts );
				chartContainer.removeClass(skeletonScreenClass);
			});
		}

		//Function for formatting tip value
		function formatTipValue(d){
			return d.toString();
		}

		function initializeCharts(chartType = false, activeCharts = false){
			if(typeof d3 !== 'undefined' && typeof Plot !== 'undefined'){
				
				if( !chartType ){
					var chartType = cartbounty_admin_data.chart_type;
				}

				if( !activeCharts ){
					var activeCharts = cartbounty_admin_data.active_charts;
				}
				
				if(Object.keys(activeCharts).length > 0 ){
					for(var key in activeCharts) {
						if (activeCharts.hasOwnProperty(key)) {
							var value = activeCharts[key];
							var chartId = 'chart-' + value;

							if(jQuery('#' + chartId).length > 0){
								var chartStartDate = chartId.replace(/-/g, '_') + '_start_date';
								var chartEndDate = chartId.replace(/-/g, '_') + '_end_date';
								var chartCurrentPeriodData = chartId.replace(/-/g, '_') + '_current_period_data';
								var chartPreviousPeriodData = chartId.replace(/-/g, '_') + '_previous_period_data';
								
								var startDate = window[chartStartDate];
								var endDate = window[chartEndDate];
								var currentPeriodData = window[chartCurrentPeriodData];
								var previousPeriodData = window[chartPreviousPeriodData];
								drawChart(startDate, endDate, currentPeriodData, previousPeriodData, '#' + chartId, chartType);
							}
						}
					}
				}

				function addHoursToDate(date, hours){
					var date = new Date(date);
					date.setHours(date.getHours() + hours);
					return date;
				}

				function subtractHoursFromDate(date, hours){
					var date = new Date(date);
					date.setHours(date.getHours() - hours);
					return date;
				}

				function formatDate(date, locale){
					var newDate = new Intl.DateTimeFormat(locale, {
					  year 	: 'numeric',
					  month : 'long',
					  day 	: 'numeric'
					}).format(date);
					return newDate;
				}

				function formatYTicks(d){
					return d => d >= 1000 ? `${d / 1000}K` : d.toString();
				}

				function getNextEvenRoundNumber(value) {
					var roundedValue;
					if (value <= 100) {
						roundedValue = Math.ceil(value);
						return roundedValue % 2 === 0 ? roundedValue : roundedValue + 1;
					} else {
						roundedValue = Math.ceil(value);
						var roundUpTo = roundedValue < 1000 ? 100 : 1000;
						return Math.ceil(roundedValue / roundUpTo / 2) * roundUpTo * 2;
					}
				}

				function getChartValues(data){
					var values = data.map(d => d.Value);
					var max = Math.max(...values);
					max = getNextEvenRoundNumber(max);
					var interval = max / 2;
					var items = {
						'min'		: 0,
						'max'		: max,
						'interval'	: interval,
					}
					return items;
				}

				function calculateChartWidth(data){
					var width = 0;
					var days = data.length;
					var pixelsPerYear = 620;
					var daysPerYear = 365;
					var minWidth = jQuery(".cartbounty-report-content-chart").width();
					var chartWidth = (days / daysPerYear) * pixelsPerYear;
					length = Math.max(chartWidth, minWidth);
					return Math.round(length);
				}

				function createBinnedData(dataArray){
					var binnedData = [];

					dataArray.forEach(function(dataPoint, index, array) {
						var endDate;
						if(index < array.length - 1) {
							endDate = array[index + 1].Date;
						}else{
							endDate = addHoursToDate(dataPoint.Date, 24);
						}

						var binData = {
							Value: 	dataPoint.Value,
							Date: 	dataPoint.Date,
							Start: 	subtractHoursFromDate(dataPoint.Date, 12),
							End: 	subtractHoursFromDate(endDate, 12)
						};

						binnedData.push(binData);
					});

					return binnedData;
				}

				//Function for creating charts
				function drawChart(startDate, endDate, currentPeriodData, previousPeriodData, containerId, chartType){
					var container = jQuery(containerId);
					var values = getChartValues(currentPeriodData);

					for (var i = 0; i < currentPeriodData.length; i++){
						if(chartType == 'line'){
							currentPeriodData[i].Date = new Date(currentPeriodData[i].Date);
						}
						currentPeriodData[i].Value = parseFloat(currentPeriodData[i].Value);
					}

					var options = {
						width 				: calculateChartWidth(currentPeriodData),
						height 				: 230,
						locale 				: cartbounty_admin_data.locale,
						type 				: 'utc',
						color 				: '#ececec',
						bottomBorderColor 	: '#dddddd',
						currentPeriodColor 	: '#818181',
						clamp 				: true,
						dashes 				: '5,7',
						opacity 			: 0.1,
						labelArrow 			: 'none',
						tickSize 			: 0,
						pointerRadius 		: 500,
					}

					const tip = getTipOptions();

					let emptyText = [];

					if( currentPeriodData.length === 0 ){
						emptyText = Plot.text([cartbounty_admin_data.report_translations.missing_chart_data], {
							fontSize 	: 13,
							frameAnchor : "middle",
						})
					}

					if(chartType == "bar"){
						var binnedData = createBinnedData(currentPeriodData);
						var plot = Plot.plot({
							width 		: options.width,
							height 		: options.height,
							x: {
								type 	: options.xAxisType,
							},
							y: {
								domain 	: [values.min, values.max],
								clamp 	: options.clamp,
							},
							marks: [
								Plot.rectY(binnedData, Plot.pointerX({
									x1 			: "Start",
									x2 			: "End",
									y1 			: values.min,
									y2 			: values.max,
									fill 		: options.color,
									maxRadius 	: options.pointerRadius
								})),
								Plot.gridY({
									strokeDasharray : options.dashes,
									strokeOpacity 	: options.opacity,
									interval 		: values.interval
								}),
								Plot.ruleY([values.min], {
									stroke 	: options.bottomBorderColor,
								}),
								Plot.axisX({
									fill 		: options.currentPeriodColor,
									color 		: options.bottomBorderColor,
								}),
								Plot.axisY({
									label 		: '',
									labelArrow 	: options.labelArrow,
									tickSize 	: options.tickSize,
									tickFormat 	: formatYTicks(d => d),
									interval 	: values.interval,
									fill 		: options.currentPeriodColor,
								}),
								Plot.rectY(binnedData, {
									x1 		: "Start",
									x2 		: "End",
									y 		: "Value",
									fill 	: options.currentPeriodColor,
									insetLeft 	: 0.5,
									insetRight 	: 0.5,
									insetTop 	: -0.5,
								}),
								Plot.tip(binnedData, Plot.pointerX({
									x1 				: "Start",
									x2 				: "End",
									y1 				: values.min,
									y2 				: values.max,
									title 			: (d) => [formatDate(d.End, options.locale), formatTipValue(d.Value)].join("\t\t"),
									pathFilter 		: tip.tipShadow,
									strokeWidth 	: tip.tipStrokeWidth,
									strokeOpacity 	: tip.tipStrokeOpacity,
									textPadding 	: tip.tipTextPadding,
									fontSize		: tip.tipFontSize,
									pointerSize 	: tip.tipPointerSize,
									preferredAnchor : tip.tipPreferredAnchor,
									maxRadius 		: options.pointerRadius,
								})),
								emptyText,
							]					
						});

					}else if(chartType == "line"){
						var plot = Plot.plot({
							width 		: options.width,
							height 		: options.height,
							x: {
								type 	: options.xAxisType
							},
							y: {
								domain 	: [values.min, values.max],
								clamp 	: options.clamp,
							},
							marks: [
								Plot.ruleX(currentPeriodData, Plot.pointerX({
									x 			: "Date",
									py 			: "Value",
									stroke 		: options.color,
									maxRadius 	: options.pointerRadius
								})),
								Plot.gridY({
									strokeDasharray : options.dashes,
									strokeOpacity 	: options.opacity,
									interval 		: values.interval
								}),
								Plot.ruleY([values.min], {
									stroke 	: options.bottomBorderColor,
								}),
								Plot.axisX({
									fill 	: options.currentPeriodColor,
									color 	: options.bottomBorderColor
								}),
								Plot.axisY({
									label 		: '',
									labelArrow 	: options.labelArrow,
									tickSize 	: options.tickSize,
									tickFormat 	: formatYTicks(d => d),
									interval 	: values.interval,
									fill 		: options.currentPeriodColor,
								}),
								Plot.line(currentPeriodData, {
									x 			: "Date",
									y 			: "Value",
									strokeWidth : 1,
									stroke 		: options.currentPeriodColor,
									curve 		: "monotone-x",
								}),
								Plot.dot(currentPeriodData, Plot.pointerX({
									x 			: "Date",
									y 			: "Value",
									symbol 		: 'circle',
									fill 		: "#976dfb",
									maxRadius 	: options.pointerRadius,
								})),
								Plot.tip( currentPeriodData, Plot.pointerX({
									x 				: "Date",
									y1 				: values.min,
									y2 				: values.max,
									title 			: (d) => [formatDate(d.Date, options.locale), formatTipValue(d.Value)].join("\t\t"),
									pathFilter 		: tip.tipShadow,
									strokeWidth 	: tip.tipStrokeWidth,
									strokeOpacity 	: tip.tipStrokeOpacity,
									textPadding 	: tip.tipTextPadding,
									fontSize		: tip.tipFontSize,
									pointerSize 	: tip.tipPointerSize,
									preferredAnchor : tip.tipPreferredAnchor,
									maxRadius 		: options.pointerRadius,
								})),
								emptyText,
							]					
						});
					}

					container.html(plot);
				}
			}
		}

		//Function that displays country data on Dashboard
		function initializeMap(){

			if(typeof d3 !== 'undefined' && typeof Plot !== 'undefined'){
				var container = jQuery('#cartbounty-country-map');

				//Function for calculating how wide the map should be
				function calculateMapWidth(){
					var mapWidth = container.width();
					return mapWidth || 640; //If map width is undefined, setting map width to 640
				}

				const mapWidth = calculateMapWidth();
				const mapData = cartbounty_admin_data.countries;

				fetch(mapData).then((response) => response.json()).then((countries) => {
					//Exclude Antarctica from the dataset
					const filteredFeatures = countries.features.filter((feature) => feature.properties.name !== "Antarctica");
					const filteredCountries = {
						...countries,
						features: filteredFeatures,
					};

					createMap(filteredCountries);

				}).catch((error) => console.error("Error loading GeoJSON data:", error));

				function createMap(countries){
					var abandonedCartData = [];

					if(container.length > 0){ //If map element has been found
						if (Array.isArray(window.abandoned_cart_country_data)) {
							abandonedCartData = window.abandoned_cart_country_data;
						} else {
							abandonedCartData = [];
						}

					}

					//Calculate map height based on desired aspect ratio
					const aspectRatio = 16 / 10.5;
					const mapHeight = mapWidth / aspectRatio;
					const scaleFactor = 0.16;
					const scale = mapWidth * scaleFactor;
					const dataMap = new Map(abandonedCartData.map((d) => [d.country, +d.value]));

				 	//Defining map colors based on cart count
					const colorScale = d3.scaleQuantize()
					.domain([0, d3.max(abandonedCartData, (d) => d.value)])
					.range(["#bdbcbc", "#a09f9f", "#828282", "#676767", "#414141"]);

					const options = {
						tipPointerSize 	: 10,
						pointerRadius 	: 50,
					};

					const tip = getTipOptions();

					// Calculate centroids and prepare tooltip data
					const centroids = countries.features.map((feature) => {
						const countryName = feature.properties.name;
						const countryCode = feature.id;
						const countryValue = dataMap.get(countryCode) || 0;
						const centroid = d3.geoCentroid(feature);

						return {
							name 		: countryName,
							value 		: countryValue,
							coordinates : centroid,
						};
					});

					const map = Plot.plot({
						projection: ({ width, height }) => d3.geoMercator()
							.scale(scale) //Set scale
							.translate([width / 2, height / 1.42]), //Center the map
						marks: [
							Plot.geo(countries, {
								fill: (d) => {
									const count = dataMap.get(d.id) || 0;
									return count === 0 ? "#dcdcdc" : colorScale(count);
								},
								stroke: "#ffffff", // White stroke for boundaries
								title: (d) => d.properties ? [`${d.properties.name}:`, formatTipValue(dataMap.get(d.id) || 0)].join("\t\t") : cartbounty_admin_data.report_translations.missing_chart_data,
							}),
							Plot.tip(centroids, Plot.pointer({
								x 				: (d) => d.coordinates[0], 
								y 				: (d) => d.coordinates[1],
								title 			: (d) => d.value ? [`${d.name}:`, formatTipValue(d.value)].join("\t\t") : null,
								fontSize 		: tip.tipFontSize,
								textAnchor 		: "middle",
								dy 				: "0",
								pathFilter 		: tip.tipShadow,
								strokeWidth 	: tip.tipStrokeWidth,
								strokeOpacity 	: tip.tipStrokeOpacity,
								textPadding 	: tip.tipTextPadding,
								pointerSize 	: options.tipPointerSize,
								preferredAnchor : tip.tipPreferredAnchor,
								maxRadius 		: options.pointerRadius,
							})),
						],
						width 	: mapWidth,
						height 	: mapHeight,
						margin 	: 0,
					});

					container.html(map);
				}
			}
		}

		initializeCharts();
		initializeMap(); //Display map on Dashboard page load

		jQuery(document).on('change', '#cartbounty-abandoned-cart-stats-options input', updateReportsOptions);
		jQuery(".cartbounty-report-options-trigger").on("click", toggleReportOptions );
		jQuery("#cartbounty-period-dropdown-container").on("click", toggleCalendar );
		jQuery(".cartbounty-chart-type-trigger").on("click", switchActiveChart );
	});

})( jQuery );