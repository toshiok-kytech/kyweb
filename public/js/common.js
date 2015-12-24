// JavaScript Document

$(function() {
	$(".common-back").click(commonBack_Click);
	$(".common-top").click(commonTop_Click);
	
	$.kyweb.scrollToTop();
});


/********************************************************************************
* EVENT METHODS
********************************************************************************/

function commonBack_Click(e) {
	history.back();
}

function commonTop_Click(e) {
	location.href = "top";
}