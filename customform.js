$(function(){
	// datepicker
	var DATE_PICKER_OPTIONS = {
		dateFormat: 'y-m-d',
		dayNames:['Nedjelja', 'Ponedjeljak', 'Utorak', 'Srijeda', 'Četvrtak', 'Petak', 'Subota'],
		dayNamesMin:['N', 'P', 'U', 'S', 'Č', 'P', 'S'],
		dayNamesShort:['NED', 'PON', 'UTO', 'SRI', 'ČET', 'PET', 'SUB'],
		firstDay:1, // pon
		monthNames:['Siječanj', 'Veljača', 'Ožujak', 'Travanj', 'Svibanj', 'Lipanj', 'Srpanj', 'Kolovoz', 'Rujan', 'Listopad', 'Studeni', 'Prosinac'],
		monthNamesShort:['SIJ','VELJ','OŽU','TRA','SVI','LIP','SRP','KOL','RUJ','LIS','STU','PRO'],
		nextText: "Dalje",
		prevText: "Natrag",
		numberOfMonths:1,
		showAnim:'show'
	};
	$.datepicker.setDefaults(DATE_PICKER_OPTIONS);
	$(".control.type-date .input-field").datepicker(DATE_PICKER_OPTIONS);
});
