var landingCalendar;

function loadCalender() {
	var dateinput = document.getElementById('inputdate');
	var dateinput2 = document.getElementById('inputdate2');

	if(dateinput){
		loadDatePicker('inputdate', 'inputdate2');
	}else{
		loadDatePicker('inputdate2');
	}
}

function loadDatePicker(ids){
	var date = new Date();
	date.setDate(date.getDate() + 1);
	var months = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
	landingCalendar = new dhtmlXCalendarObject([ids]);
	landingCalendar.setDateFormat("%j %F, %Y");
	var yesterday = new Date();

	landingCalendar.setInsensitiveRange(null, yesterday);
	landingCalendar.hideToday();
	landingCalendar.hideTime();
}