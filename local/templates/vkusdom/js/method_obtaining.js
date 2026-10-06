var MethodObtaining = {
	LastAction: "",
	IsSend: false,
	Type: "",
	Init: function(){
		$("[data-method-obtaining]").click($.proxy(this.EventClickShowPopupBtn,this));
	},
	EventClickHidePopupBtn: function(e){
		this.HidePopup();
		return false;
	},
	EventClickShowPopupBtn: function(e){
		var JObj = $(e.target || e.currentTarget);
		var Type = "";
		var Data = [];
		
		if(typeof JObj.attr("data-method-obtaining") == "undefined"){
			JObj = JObj.parents("[data-method-obtaining]");
		}
		Type = JObj.attr("data-method-obtaining");
		
		if($("#MethodObtaining").length){
			this.ShowPopup();
		} else {
			Data.push({name: "action",value: "get"});
			Data.push({name: "type",value: Type});
			this.Send(Data);

		}
		
		return false;
	},
	Get: function(Type){
		this.Send(Type)
	},
	Send: function(Data){
		var i;
		if(!this.IsSend){
			this.IsSend = true;
			
			for(i in Data){
				if(Data[i].name == "action"){
					this.LastAction = Data[i].value;
					break
				}
			}
			
			$.ajax({
				type: "POST",
				dataType: this.LastAction == "get" ? "html" :"json",
				url: "/ajax/method_obtaining.php",
				data: Data,
				success:$.proxy(this.SendSuccess,this),
				error: $.proxy(this.SendError,this)
			})
		}
		
	},
	SendSuccess: function(Result){
		this.IsSend = false;
		
		if(this.LastAction == "get"){
			$("body").append(Result);
			
			$("#MethodObtaining .btn-popup-close").click($.proxy(this.EventClickHidePopupBtn,this))
			
			
			
			this.ShowPopup();
		}
	},
	SendError: function(){
		this.IsSend = false;
	},
	ShowPopup: function(){
		$("#MethodObtaining").show();
	},
	HidePopup: function(){
		$("#MethodObtaining").hide();
	}
	
}
$(document).ready(function(){
	MethodObtaining.Init();
})