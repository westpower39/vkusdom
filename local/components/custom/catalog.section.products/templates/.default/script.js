var CatalogSectionProductsTplDefault = {
	JObj: null,
	IsSend: false,
	Init: function(){
		
		$("[data-catalog-section-products] .section-production__tags a").click($.proxy(this.EventClick,this));
		
	},
	EventClick: function(e){
		var JObj = $(e.target || e.currentTarget);
		if(!JObj.hasClass("active")){
			
			this.Send(JObj);
		}
		
		return false;
	},
	Send: function(JObj){
		var Data = [];
		var Id = 0;
		var Url = "";
		var SignedParams = "";
		if(!this.IsSend){
			this.IsSend = true;
			this.JObj = JObj;
			
			console.log(this.JObj);
			
			Id = this.JObj.attr("data-id");
			
			Url = this.JObj.parents("[data-catalog-section-products]").attr("data-url");
			SignedParams = this.JObj.parents("[data-catalog-section-products]").attr("data-signed-params");
			
			Data.push({name: "id",value: Id});
			Data.push({name: "signedParams",value: SignedParams});
			
			
			$.ajax({
				url: Url,
				type: "POST",
				data: Data,
				success: $.proxy(this.SendSuccess,this),
				error:$.proxy(this.SendError,this)
			});
		}
	},
	SendSuccess: function(Result){
		var PJObj = null;
		this.IsSend = false;
		
		PJObj = this.JObj.parents("[data-catalog-section-products]");
		
		PJObj.find(".section-production__tags").children("a").removeClass("active");
		this.JObj.addClass("active");
		
		PJObj.find(".slider-inner").empty().append(Result);
		Slide.Init()
		
		
	},
	SendError:function(Result){
		this.IsSend = false;
	},
	
}
$(document).ready(function(){
	CatalogSectionProductsTplDefault.Init();
});