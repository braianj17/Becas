var interval = null;
function countdown(htmlTarget,finalDate,section_id,url) {
	var finalYear=finalDate.substr(0,4);
	var finalMonth=finalDate.substr(5,2);
	var finalDay=finalDate.substr(8,2);		
	var finalHour=finalDate.substr(11,2);		
	var finalMinutes=finalDate.substr(14,2);				
	var finalSeconds=finalDate.substr(17,2);
	now = new Date();
	var curr_date=two_digits(now.getDate());
	if(checkVersion()=="IE" || checkVersion()=="OLDIE"){
		var curr_month=two_digits(now.getMonth()+1);
	}else{
		var curr_month=two_digits(now.getMonth());
	}
	var curr_hour=two_digits(now.getHours());
	var curr_minutes=two_digits(now.getMinutes());
	var curr_seconds=two_digits(now.getSeconds());
	currTime = new Date(now.getYear(),curr_month,curr_date,curr_hour,curr_minutes,curr_seconds);
	y2k = new Date(finalYear,finalMonth,finalDay,finalHour,finalMinutes,finalSeconds);
	days = (y2k - currTime) / 1000 / 60 / 60 / 24;
	daysRound = Math.floor(days);
	hours = (y2k - currTime) / 1000 / 60 / 60 - (24 * daysRound);
	hoursRound = Math.floor(hours);
	minutes = (y2k - currTime) / 1000 /60 - (24 * 60 * daysRound) - (60 * hoursRound);
	minutesRound = Math.floor(minutes);
	seconds = (y2k - currTime) / 1000 - (24 * 60 * 60 * daysRound) - (60 * 60 * hoursRound) - (60 * minutesRound);
	secondsRound = Math.round(seconds);
	document.getElementById(htmlTarget).innerHTML= "Tiempo Restante: " + hoursRound + " Hrs. " + minutesRound + " Mins. " + secondsRound + " Segs.";
	if(hoursRound<=0 && minutesRound<=0 && secondsRound<=0){
		clearcount();
		var final_url=url + "?section_id=" + section_id;
		window.open(final_url ,'_self');
	}
}
function clearcount(){
	clearInterval(interval);
}
function createcount(htmlTarget,finalDate,section_id,url){
	interval = setInterval("countdown('" + htmlTarget + "','" + finalDate +"','" + section_id + "','" + url + "')",1000);	
}

