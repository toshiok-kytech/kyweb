// JavaScript Document
// Version 1.0.0

$.kyweb = function() {
	
}

/********************************************************************************
* Prototypes
********************************************************************************/

String.prototype.replaceAll = function(from, to) {
	return this.split(from).join(to);
}

String.prototype.trim = function() {
    return this.replace(/^\s+|\s+$/g, "");
}

String.prototype.ltrim = function() {
    return this.replace(/^\s+/, "");
}

String.prototype.rtrim = function() {
    return this.replace(/\s+$/, "");
}

String.prototype.isEmail = function() {
	if (this.match(/^[A-Za-z0-9]+[\w\.-]+@[\w\.-]+\.\w{2,}$/)) {
		return true;
	}
	return false;
}

String.prototype.isZenKatakana = function() {
	if (this.match(/^[\u30A0-\u30FF 　]+$/)) {
		return true;
	}
	return false;
}

String.prototype.toZenKatakana = function() {
	return this.replace(/[ぁ-ん]/g, function(s) {
		return String.fromCharCode(s.charCodeAt(0) + 0x60);
	});
}

Array.prototype.shuffle = function() {
	var i = this.length;
	while (--i) {
		var j = Math.floor(Math.random() * (i + 1));
		var temp = this[i];
		this[i] = this[j];
		this[j] = temp;
	}
	
	return this; // for convenience, in case we want a reference to the array
};

$.fn.selfHtml = function(options){
	if($(this).get(0)){
		return $(this).get(0).outerHTML;
	}
}

/**
 * Format date
 * @param  {String} [format] Format
 * @return {String}          Formated date
 */
Date.prototype.formatDate = function(format) {
	var date = this;
	if (!format) format = 'YYYY-MM-DD hh:mm:ss.SSS';
	format = format.replace(/YYYY/g, date.getFullYear());
	format = format.replace(/MM/g, ('0' + (date.getMonth() + 1)).slice(-2));
	format = format.replace(/DD/g, ('0' + date.getDate()).slice(-2));
	format = format.replace(/hh/g, ('0' + date.getHours()).slice(-2));
	format = format.replace(/mm/g, ('0' + date.getMinutes()).slice(-2));
	format = format.replace(/ss/g, ('0' + date.getSeconds()).slice(-2));
	if (format.match(/S/g)) {
		var milliSeconds = ('00' + date.getMilliseconds()).slice(-3);
		var length = format.match(/S/g).length;
		for (var i = 0; i < length; i++) format = format.replace(/S/, milliSeconds.substring(i, i + 1));
	}
	return format;
};

/**
 * Add date
 * @param  {Number} num        Mount to add
 * @param  {String} [interval] Unit to add
 * @return {Date}              Added date
 */
Date.prototype.addDate = function(num, interval) {
	var date = this;
	switch (interval) {
		case 'Y': date.setYear(date.getYear() + num); break;
		case 'M': date.setMonth(date.getMonth() + num); break;
		case 'h': date.setHours(date.getHours() + num); break;
		case 'm': date.setMinutes(date.getMinutes() + num); break;
		case 's': date.setSeconds(date.getSeconds() + num); break;
		default:  date.setDate(date.getDate() + num);
	}
	return date;
};

Date.prototype.firstDate = function() {
	var date = this;
	return new Date(date.getFullYear(), date.getMonth(), 1);
}

Date.prototype.lastDate = function() {
	var date = this;
	return new Date(date.getFullYear(), date.getMonth() + 1, 0);
}

if (!Array.isArray) {
	Array.isArray = function (vArg) {  
		return Object.prototype.toString.call(vArg) === "[object Array]";  
	};
}


/********************************************************************************
* Public methods (Audio)
********************************************************************************/

$.kyweb.audioLoad = function(path, files, clear) {
	if (clear == true) {
		$(".kyweb_audio").remove();
	}
	
	for (var i = 0; i < files.length; i++) {
		var file = files[i];
		var mp3 = $("<source>").attr("src", path + file + ".mp3").attr("type", "audio/mp3");
		var ogg = $("<source>").attr("src", path + file + ".ogg").attr("type", "audio/ogg");
		var audio = $("<audio>").attr("id", "audio_" + file).addClass("kyweb_audio").attr("preload", "metadata").append(mp3).append(ogg);
		$("body").append(audio);
	}
}

$.kyweb.audioPlay = function(file) {
	var audio = $("#audio_" + file)[0];
	audio.play();
}

$.kyweb.audioStop = function(file) {
	var audio = $("#audio_" + file)[0];
	if(!audio.ended){
		audio.pause();
		audio.currentTime = 0;
	}
}


/********************************************************************************
* Public methods (image)
********************************************************************************/

var _imagePath;
var _imageFiles = [];
var _preloadImages = [];
var _preloadImagesCnt;
var _preloadProgress;
var _preloadFile;
var _preloadLoadingCallback;
var _preloadFinishCallback;

// loadingCallback([progress in percentage], [loaded file name])
// finishCallback()

$.kyweb.imageLoad = function(path, files, loadingCallback, finishCallback) {
	_imagePath = (path == null || path == undefined) ? "" : path;
	_imageFiles = files;
	_preloadImagesCnt = 0;
	_preloadProgress = 0;
	_preloadLoadingCallback = loadingCallback;
	_preloadFinishCallback = finishCallback;
	
	if (_preloadLoadingCallback != null && _preloadLoadingCallback != undefined) {
		_preloadLoadingCallback(_preloadProgress, "");
	}
	
	_preloadImagesByIndex(0);
}


var _lapseDatas = {};

// finishCallback(id)

$.kyweb.lapseImage = function(id, delay, sources, loop, finishCallback) {
	if (_lapseDatas[id] != undefined) {
		$.kyweb.lapseImageStop(id);
	}
	
	_lapseDatas[id] = {
		"delay": delay,
		"sources": sources,
		"loop": loop,
		"callback": finishCallback,
		"index": 0,
		"timeId": null
	};
}

$.kyweb.lapseImageStart = function(id) {
	if (_lapseDatas[id] == undefined) return;
	if (_lapseDatas[id]["timeId"] != null) return;
	
	var index = _lapseDatas[id]["index"];
	var src = _lapseDatas[id]["sources"][index];
	$(id).prop("src", src);
	
	index++;
	if (index >= _lapseDatas[id]["sources"].length) {
		index = 0;
		if (_lapseDatas[id]["loop"] != true) {
			$.kyweb.lapseImageStop(id);
			if (_lapseDatas[id]["callback"] != undefined) {
				_lapseDatas[id]["callback"](id);
			}
			return;
		}
	}
	
	_lapseDatas[id]["index"] = index;
	
	_lapseDatas[id]["timeId"] = setTimeout(function() { 
		_lapseDatas[id]["timeId"] = null;
		$.kyweb.lapseImageStart(id);
	}, _lapseDatas[id]["delay"]);
}

$.kyweb.lapseImageStop = function(id) {
	if (_lapseDatas[id] == undefined) return;
	if (_lapseDatas[id]["timeId"] == null) return;
	
	clearTimeout(_lapseDatas[id]["timeId"]);
	_lapseDatas[id]["timeId"] = null;
}


/********************************************************************************
* Public methods
********************************************************************************/

$.kyweb.isset = function(data) {
    return (typeof(data) != 'undefined');
}

$.kyweb.isNumber = function(num) {
	return !isNaN(parseFloat(num)) && isFinite(num);
}

var _get = null;

$.kyweb.get = function(name, value) {
	if (_get == null) {
		_get = [];
		var pos1 = window.location.href.indexOf('?');
		var pos2 = window.location.href.indexOf('#');
		
		if (pos1 > -1) {
			var args;
			if (pos2 > -1) {
				pos2 = pos2 - window.location.href.length;
				args = window.location.href.slice(pos1 + 1, pos2);
			} else {
				args = window.location.href.slice(pos1 + 1)
			}
			
			var hashes = args.split('&');
			for(var i = 0; i < hashes.length; i++) {
				var hash = hashes[i].split('=');
				_get.push(hash[0]);
				_get[hash[0]] = hash[1];
			}
		}
	}
	
	if (_get[name] == undefined) return value;
	return _get[name];
}

$.kyweb.decodeURL = function(url) {
	return decodeURIComponent((url + '').replace(/\+/g, '%20'));
}

$.kyweb.cookie = function(name, value, exdays) {
	if (name != undefined && value == undefined && exdays == undefined) {
		// get
		var cookies = document.cookie.split(";");
		for (var i = 0; i < cookies.length; i++) {
			var cname  = cookies[i].substr(0, cookies[i].indexOf("="));
			var cvalue = cookies[i].substr(cookies[i].indexOf("=") + 1);
			cname = cname.replace(/^\s+|\s+$/g,"");
			if (cname == name) {
				return unescape(cvalue);
			}
		}
		return false;
	} else if (name != undefined && value != undefined && exdays != undefined) {
		// set
		var exdate = new Date();
		exdate.setDate(exdate.getDate() + exdays);
		value = escape(value) + ((exdays==null) ? "" : "; expires=" + exdate.toUTCString());
		document.cookie = name + "=" + value;
		return true;
	}
}

$.kyweb.removeCookie = function(name) {
	var exdate = new Date();
	exdate.setTime(0);
	document.cookie = name + "=; expires=" + exdate.toUTCString();
}

$.kyweb.popupWindow = function(url, windowname, width, height) {
	var features="location=no, menubar=no, status=yes, scrollbars=yes, resizable=yes, toolbar=no";
	if (width) {
		if (window.screen.width > width)
			features += ", left=" + (window.screen.width-width) / 2;
		else width = window.screen.width;
			features += ", width=" + width;
	}
	if (height) {
		if (window.screen.height > height)
			features += ", top=" + (window.screen.height-height) / 2;
		else height = window.screen.height;
			features += ", height=" + height;
	}
	window.open(url, windowname, features);
}

$.kyweb.orientation = function() {
	if (window.innerWidth > window.innerHeight) {
		return "landscape";
	} else {
		return "portrait";
	}
}

$.kyweb.scrollToTop = function() {
	setTimeout("scrollTo(0, 1)", 100);
}

$.kyweb.lockScreen = function(animation, opacity, color, offsetX, offsetY) {
	unlockScreen();
	
	animation = animation || _lockscreen_cAnimation;
	opacity   = opacity || _lockscreen_cOpacity;
	color     = color || _lockscreen_cColor;
	offsetX   = offsetX || _lockscreen_cOffsetX;
	offsetY   = offsetY || _lockscreen_cOffsetY;
	
	_lockscreen_cAnimation = animation;
	_lockscreen_cOpacity = opacity;
	_lockscreen_cColor = color;
	_lockscreen_cOffsetX = offsetX;
	_lockscreen_cOffsetY = offsetY;
	
	if (animation == "rand") {
		animation = 1 + Math.floor(Math.random() * 13);
	}
	
	var verticalCenter = (window.innerHeight / 2);
	
	var lockDiv = document.createElement("div");
	lockDiv.setAttribute("id", "lockDiv");
	lockDiv.style.cssText = "position:fixed; z-index:100; "
		+ "border:0; margin:0; padding:0; "
		+ "top:0; left:0; right:0; bottom:0; "	
		+ "width:100%; height:" + $(document).height() + "px; "
		+ "background:" + color + "; "
		+ "opacity:" + (opacity / 100) + "; "
		+ "filter:alpha(opacity=" + opacity + ");";
	$("body").append(lockDiv);
	
	if (animation > 0) {
		switch (animation) {
			case  1: _lockscreen_cSpeed = 9; _lockscreen_cWidth = 32; _lockscreen_cHeight = 32; _lockscreen_cTotalFrames = 12; _lockscreen_cFrameWidth = _lockscreen_cWidth; break;
			case  2: _lockscreen_cSpeed = 9; _lockscreen_cWidth = 32; _lockscreen_cHeight = 32; _lockscreen_cTotalFrames =  8; _lockscreen_cFrameWidth = _lockscreen_cWidth; break;
			case  3: _lockscreen_cSpeed = 9; _lockscreen_cWidth = 32; _lockscreen_cHeight = 32; _lockscreen_cTotalFrames =  8; _lockscreen_cFrameWidth = _lockscreen_cWidth; break;
			case  4: _lockscreen_cSpeed = 9; _lockscreen_cWidth = 32; _lockscreen_cHeight = 32; _lockscreen_cTotalFrames = 16; _lockscreen_cFrameWidth = _lockscreen_cWidth; break;
			case  5: _lockscreen_cSpeed = 9; _lockscreen_cWidth = 32; _lockscreen_cHeight = 32; _lockscreen_cTotalFrames =  8; _lockscreen_cFrameWidth = _lockscreen_cWidth; break;
			case  6: _lockscreen_cSpeed = 9; _lockscreen_cWidth = 64; _lockscreen_cHeight = 13; _lockscreen_cTotalFrames = 22; _lockscreen_cFrameWidth = _lockscreen_cWidth; break;
			case  7: _lockscreen_cSpeed = 9; _lockscreen_cWidth = 64; _lockscreen_cHeight = 13; _lockscreen_cTotalFrames = 20; _lockscreen_cFrameWidth = _lockscreen_cWidth; break;
			case  8: _lockscreen_cSpeed = 6; _lockscreen_cWidth = 32; _lockscreen_cHeight = 32; _lockscreen_cTotalFrames = 25; _lockscreen_cFrameWidth = _lockscreen_cWidth; break;
			case  9: _lockscreen_cSpeed = 9; _lockscreen_cWidth = 32; _lockscreen_cHeight = 32; _lockscreen_cTotalFrames =  8; _lockscreen_cFrameWidth = _lockscreen_cWidth; break;
			case 10: _lockscreen_cSpeed = 6; _lockscreen_cWidth = 64; _lockscreen_cHeight =  8; _lockscreen_cTotalFrames = 20; _lockscreen_cFrameWidth = _lockscreen_cWidth; break;
			case 11: _lockscreen_cSpeed = 6; _lockscreen_cWidth = 64; _lockscreen_cHeight =  6; _lockscreen_cTotalFrames = 37; _lockscreen_cFrameWidth = _lockscreen_cWidth; break;
			case 12: _lockscreen_cSpeed = 6; _lockscreen_cWidth = 32; _lockscreen_cHeight = 32; _lockscreen_cTotalFrames = 24; _lockscreen_cFrameWidth = _lockscreen_cWidth; break;
			case 13: _lockscreen_cSpeed = 6; _lockscreen_cWidth = 32; _lockscreen_cHeight = 32; _lockscreen_cTotalFrames = 13; _lockscreen_cFrameWidth = _lockscreen_cWidth; break;
		}
		
		if (color == "black" && animation in {2:"", 3:"", 6:"", 7:"", 8:"", 11:"", 12:"", 13:""}) {
			animation += "w";
		}
		
		_lockscreen_cImageSrc = "../img/common/lockscreen_" + animation + ".png";
		
		var lockImgDiv = document.createElement("div");
		lockImgDiv.setAttribute("id", "lockImgDiv");
		lockImgDiv.style.cssText = "position:fixed; z-index:101; "
			+ "border:0; margin:0; padding:0; "
			+ "top:40%; left:50%; "
			+ "margin-left:" + (- (_lockscreen_cWidth / 2) + offsetX) + "px; "
			+ "margin-top:" + (- (_lockscreen_cHeight / 2) + offsetY) + "px;";
		$("body").append(lockImgDiv);
		
		// Animation image
		new _lockscreen_ImageLoader(_lockscreen_cImageSrc, '_lockscreen_StartAnimation()');
	}
	
	var lockMsgDiv = document.createElement("div");
	lockMsgDiv.setAttribute("id", "lockMsgDiv");
	lockMsgDiv.style.cssText = "position:fixed; z-index:102; "
		+ "border:0; margin:0; padding:0;"
		+ "top:50%; left:0; width:100%; "
		+ "margin-top:" + (- (_lockscreen_cHeight / 2) + offsetY) + "px; "
		+ "text-align:center; "
		+ "color:white; ";
	$("body").append(lockMsgDiv);
	
	var lockTimeDiv = document.createElement("div");
	lockTimeDiv.setAttribute("id", "lockTimeDiv");
	lockTimeDiv.style.cssText = "position:fixed; z-index:103; "
		+ "border:0; margin:0; padding:0;"
		+ "top:33%; left:0; width:100%; "
		+ "margin-top:" + (- (_lockscreen_cHeight / 2) + offsetY) + "px; "
		+ "text-align:center; "
		+ "color:white; ";
	$("body").append(lockTimeDiv);
}

$.kyweb.unlockScreen = function() {
	$("#lockDiv").remove();
	$("#lockImgDiv").remove();
	$("#lockMsgDiv").remove();
	$("#lockTimeDiv").remove();
}

$.kyweb.lockScreenAnimation = function(animation) {
	// 0..13, rand
	_lockscreen_cAnimation = animation;
}

$.kyweb.lockScreenOpacity = function(opacity) {
	// 1..100
	_lockscreen_cOpacity = opacity;
}

$.kyweb.lockScreenColor = function(color) {
	// black, white
	_lockscreen_cColor = color;
}

$.kyweb.lockScreenOffset = function(x, y) {
	_lockscreen_cOffsetX = x;
	_lockscreen_cOffsetY = y;
}

$.kyweb.lockScreenMessage = function(msg) {
	$("#lockMsgDiv").html(msg);
	return msg;
}

$.kyweb.lockScreenTime = function(boolValue) {
	_lockscreen_cTimeDisplay = boolValue;
}


/********************************************************************************
* Private methods
********************************************************************************/

function _preloadImagesByIndex(i) {
	_preloadFile = _imagePath + _imageFiles[i];
	
	if (_preloadLoadingCallback != null && _preloadLoadingCallback != undefined) {
		_preloadLoadingCallback(_preloadProgress, _preloadFile);
	}
	
	var image = new Image;
	image.src = _preloadFile;
	image.onload = _preloadImages_onLoad;
	image.onerror = _preloadImages_onError;
	_preloadImages[i] = $(image).hide();
}

function _preloadImages_onLoad() {
	_preloadImagesCnt++;
	_preloadProgress = Math.floor((_preloadImagesCnt * 100) / _imageFiles.length);
	
	if (_preloadLoadingCallback != null && _preloadLoadingCallback != undefined) {
		_preloadLoadingCallback(_preloadProgress, _preloadFile);
	}
	
	if (_preloadImagesCnt == _imageFiles.length) {
		if (_preloadFinishCallback != null && _preloadFinishCallback != undefined) {
			_preloadFinishCallback();
		}
		return;
	}
	
	_preloadImagesByIndex(_preloadImagesCnt);
}

function _preloadImages_onError() {
	_preloadImagesByIndex(_preloadImagesCnt);
};


var _lockscreen_cAnimation = 0;
var _lockscreen_cOpacity = 70;
var _lockscreen_cColor = "black"
var _lockscreen_cOffsetX = 0;
var _lockscreen_cOffsetY = 0;

var _lockscreen_cSpeed       = 0;
var _lockscreen_cWidth       = 0;
var _lockscreen_cHeight      = 0;
var _lockscreen_cTotalFrames = 0;
var _lockscreen_cFrameWidth  = _lockscreen_cWidth;
var _lockscreen_cImageSrc    = '';

var _lockscreen_cImageTime = false;
var _lockscreen_cIndex = 0;
var _lockscreen_cXPos  = 0;
var _lockscreen_SEC_FRAMES = 0;

var _lockscreen_cTimeDisplay = false;
var _lockscreen_cTimeStart;
var _lockscreen_cTimeNow;

function _lockscreen_StartAnimation() {
	var lockImgDiv = $("#lockImgDiv")[0];
	if (lockImgDiv == undefined) {
		return;
	}
	lockImgDiv.style.backgroundImage='url('+_lockscreen_cImageSrc+')';
	lockImgDiv.style.width=_lockscreen_cWidth+'px';
	lockImgDiv.style.height=_lockscreen_cHeight+'px';
	
	//FPS = Math.round(100/(maxSpeed+2-speed));
	FPS = Math.round(100/_lockscreen_cSpeed);
	_lockscreen_SEC_FRAMES = 1 / FPS;
	
	_lockscreen_cTimeStart = new Date();
	
	setTimeout('_lockscreen_ContinueAnimation()', _lockscreen_SEC_FRAMES / 1000);
}

function _lockscreen_ContinueAnimation() {
	var lockImgDiv = $("#lockImgDiv")[0];
	
	if (lockImgDiv == undefined) {
		clearTimeout(_lockscreen_cImageTime);
		return;
	}
	
	_lockscreen_cXPos += _lockscreen_cFrameWidth;
	//increase the index so we know which frame of our animation we are currently on
	_lockscreen_cIndex += 1;
	 
	//if our _lockscreen_cIndex is higher than our total number of frames, we're at the end and should restart
	if (_lockscreen_cIndex >= _lockscreen_cTotalFrames) {
		_lockscreen_cXPos =0;
		_lockscreen_cIndex=0;
	}
	
	lockImgDiv.style.backgroundPosition=(-_lockscreen_cXPos)+'px 0';
	lockImgDiv.style.zIndex = "101";
	
	if (_lockscreen_cTimeDisplay == true) {
		_lockscreen_cTimeNow = new Date();
		
		var sec = parseInt((_lockscreen_cTimeNow.getTime() - _lockscreen_cTimeStart.getTime()) / 1000);
		var hour = parseInt(sec / 3600);
		var min = parseInt((sec / 60) % 60);
		var sec = sec % 60;
		
		if (hour < 10) { hour = "0" + hour; }
		if (min < 10)  { min = "0" + min; }
		if (sec < 10)  { sec = "0" + sec; }
		
		$("#lockTimeDiv").html(hour + ":" + min + ":" + sec);
	}
	
	setTimeout('_lockscreen_ContinueAnimation()', _lockscreen_SEC_FRAMES * 1000);
}

//Pre-loads the sprites image
function _lockscreen_ImageLoader(s, fun) {
	clearTimeout(_lockscreen_cImageTime);
	_lockscreen_cImageTime = 0;
	genImage = new Image();
	genImage.onload = function (){_lockscreen_cImageTime=setTimeout(fun, 0)};
	genImage.onerror = new Function('alert(\'Could not load the image\')');
	genImage.src=s;
}