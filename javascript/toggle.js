function toggleLogin(e){
	if( e.shiftKey && e.ctrlKey && e.keyCode == 116 ) {
		location.href="/Security/login?BackURL=" + escape(location.href);
	}
}

if (window.addEventListener) {
	window.addEventListener("keyup", toggleLogin);
} else { 
	window.attachEvent("onkeyup", toggleLogin);
}
