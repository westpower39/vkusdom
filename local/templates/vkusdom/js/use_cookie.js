var UseCookie = {
	Init: function(){
		if(typeof $.cookie != "undefined"){
			if($.cookie('USE_COOKIE') != "Y"){
				$.ajax({
					url: "/ajax/use_cookie.php",
					success:$.proxy(this.SendSuccess,this),
					error: $.proxy(this.SendError,this)
				})
			}	
		}
	},
	SendSuccess: function(Result){
		
		if($("#UseCookie").length == 0){
			$("body").append(Result);
		}
		$("#UseCookie .section-cookies").show();
		
		$("#UseCookie [data-use-cookie='accept']").click($.proxy(this.EventClickAccept,this));
		$("#UseCookie [data-use-cookie='settings']").click($.proxy(this.EventClickSettings,this));
		$("#UseCookie .section-cookies__close").click($.proxy(this.EventClickClose,this));
		
	},
	SendError: function(){
		
	},
	EventClickSettings: function(){
		$("#UseCookie .section-cookies").hide();
		$("#UseCookie .section-cookies-settings").show();	
	},
	EventClickAccept: function(){
		this.Accept();
		return false;
	},
	EventClickClose: function(){
		this.Close();
		return false;
	},
	Accept: function(){
		$.cookie('USE_COOKIE', 'Y', { expires: 365, path: '/' });
		this.Close();
		return false;
	},
	Close: function(){
		$("#UseCookie").remove();
	}
}
$(document).ready(function(){
	UseCookie.Init();
})