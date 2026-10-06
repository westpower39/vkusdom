var Favorit = {
	Data: {},
	LastAction: "",
	IsSend: false,
	Init: function(){
		$(document).on("click","[data-favorit='btn']",$.proxy(this.EventClickBtn,this));
		this.GetData();
	},
	
	GetData: function(){
		var Data = [];
		Data.push({name: "action",value: "getData"});
		this.Send(Data);
	},
	SetData: function(){
		var i;
		var Quantity;
		var JObjCounter = null;
		if(typeof this.Data.ADD != "undefined"){
			$("[data-favorit='totalCount'] .header-links__link-count").html(this.Data.ADD.IDS.length );
			$("[data-favorit='btn']").removeClass("active")
			for(i in this.Data.ADD.IDS){
				$("[data-favorit='btn'][data-id='"+this.Data.ADD.IDS[i]+"']").addClass("active");
			}
			
		}
	},
	Send: function(Data){
		if(!this.IsSend){
			this.IsSend = true;
			
			$.ajax({
				type: "POST",
				dataType: "json",
				url: "/ajax/favorit.php",
				data: Data,
				success:$.proxy(this.SendSuccess,this),
				error: $.proxy(this.SendError,this)
			})
		}
		
	},
	SendSuccess: function(Result){
		this.IsSend = false;
		
		this.Data = Result;
		this.SetData();
		
	},
	SendError: function(){
		this.IsSend = false;
	},
	EventClickBtn: function(e){
		var JObj = $(e.target || e.currentTarget);
		var Data = [];
		var Id = 0;
		
		
		if(typeof JObj.attr("data-id") == "undefined"){
			JObj = JObj.parents("[data-favorit='btn']");
		}
		
		Id = JObj.attr("data-id");
		
		Data.push({name: "action",value: "add"});
		Data.push({name: "id",value: JObj.attr("data-id")});
		
		
		this.Send(Data);
		
		if(JObj.hasClass("active")){
			JObj.removeClass("active")
		} else {
			JObj.addClass("active")
		}
		
		return false;
	}
}
$(document).ready(function(){
	Favorit.Init();
});