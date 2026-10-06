var Basket = {
	Data: {},
	LastAction: "",
	IsSend: false,
	Init: function(){
		
		$(document).on("click","[data-basket='btn']",$.proxy(this.EventClickBtn,this));
		
		$(document).on("click","[data-basket='counter'] .counter__plus",$.proxy(this.EventClickPlus,this));
		$(document).on("click","[data-basket='counter'] .counter__minus",$.proxy(this.EventClickMinus,this));
		$(document).on("change","[data-basket='counter'] .counter__input",$.proxy(this.EventChangeInput,this));
		
		$(document).on("click","[data-basket='clear']",$.proxy(this.EventClickClear,this));
		
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
			$("[data-basket='totalCount'] .header-links__link-count").html(this.Data.ADD.IDS.length );
			$("[data-basket='totalCount'] .mobile-fixed-block__link-count").html(this.Data.ADD.IDS.length );
			
			
			for(i in this.Data.ADD.ITEMS){
				Quantity = parseInt(this.Data.ADD.ITEMS[i].QUANTITY);
				
				$("[data-basket='btn'][data-id='"+this.Data.ADD.ITEMS[i].PRODUCT_ID+"']").hide();
				JObjCounter = $("[data-basket='counter'][data-id='"+this.Data.ADD.ITEMS[i].PRODUCT_ID+"']")
				JObjCounter.removeClass("hide");
				JObjCounter.find("input[name='quantity']").val(Quantity);
			}
			
		}
	},
	EventClickClear: function(){
		var Data = [];
		Data.push({name: "action",value: "clear"});
		this.Send(Data);
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
				dataType: "json",
				url: "/ajax/basket.php",
				data: Data,
				success:$.proxy(this.SendSuccess,this),
				error: $.proxy(this.SendError,this)
			})
		}
		
	},
	SendSuccess: function(Result){
		this.IsSend = false;
		
		if(this.LastAction == "clear"){
			window.location.href = window.location.href;
			return false;
		}
		
		this.Data = Result;
		this.SetData();
		
	},
	SendError: function(){
		this.IsSend = false;
	},
	EventClickPlus: function(e){
		this.EventChangeInput(e,1);
		return false;
	},
	EventClickMinus: function(e){
		this.EventChangeInput(e,-1);
		return false;
	},
	EventChangeInput: function(e,i){
		var JObj = $(e.target || e.currentTarget);
		var Id = 0;
		var Data = [];
		var Quantity = 0;
		var JObjQuantity = null;
		
		JObj = JObj.parents("[data-basket='counter']");
		
		Id = JObj.attr("data-id");
		
		Data.push({name: "action",value: "add"});
		Data.push({name: "id",value: JObj.attr("data-id")});
		
		JObjQuantity = JObj.find("input[name='quantity']");
		
		Quantity = parseInt(JObjQuantity.val());
		
		if(typeof i != "undefined"){
			Quantity += i;
		}
		Quantity = parseInt(Quantity);
		
		if(isNaN(Quantity)){
			Quantity = 1;
		}
		
		if(Quantity > 0){
			Data.push({name: "quantity",value: Quantity});
		} else {
			Quantity = 1;
			Data.push({name: "quantity",value: 0});
			JObj.addClass("hide");
			$("[data-basket='btn'][data-id='"+Id+"']").show()
		}
		
		JObjQuantity.val(Quantity);
		
		this.Send(Data);
		
		return false;
	},
	EventClickBtn: function(e){
		var JObj = $(e.target || e.currentTarget);
		var Data = [];
		var Id = 0;
		var JObjCounter = null;
		
		if(typeof JObj.attr("data-id") == "undefined"){
			JObj = JObj.parents("[data-basket='btn']");
		}
		
		Id = JObj.attr("data-id");
		
		Data.push({name: "action",value: "add"});
		Data.push({name: "id",value: JObj.attr("data-id")});
		Data.push({name: "quantity",value: 1});
		
		this.Send(Data);
		
		JObj.hide();
		$("[data-basket='counter'][data-id='"+Id+"']").removeClass("hide")
		
		return false;
	}
}
$(document).ready(function(){
	Basket.Init();
});