<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

global $USER;

$UserData = PhpInterface\Libs\MethodObtaining::GetUserData();
$Types= PhpInterface\Libs\MethodObtaining::GetTypes();
$Stores = PhpInterface\Libs\MethodObtaining::GetStores();

$Action = htmlspecialchars(isset($_POST["action"]) ? $_POST["action"] : "");



$DeliveryZones = array();

$Hb9 = PhpInterface\Libs\Help::GetHighloadBlock(9);
$Query = $Hb9::getList(array("select" => array("*")));
while($Answer = $Query->Fetch()){
	
	if(!empty($Answer["UF_FILE_GEOJSON"])){
		$DeliveryZones[] = array(
			"ID" => $Answer["ID"],
			"FILE" => CFile::GetPath($Answer["UF_FILE_GEOJSON"])
		);
	}
	
	
}



if($Action == "save"){
	
	$Result = array(
		"STATUS" => false,
		"MESSAGE" => "",
	);
	
	$UFields = array();
	$NUser = new CUser;
	
	$NTypeId = intval( isset( $_POST["METHOD_OBTAINING_TYPE_ID"]) ? $_POST["METHOD_OBTAINING_TYPE_ID"] : "");
	$NStoreId = intval( isset($_POST["METHOD_OBTAINING_STORE_ID"]) ? $_POST["METHOD_OBTAINING_STORE_ID"] : "");
	
	$NZoneId = intval( isset($_POST["METHOD_OBTAINING_DELIVERY_ZONE_ID"]) ? $_POST["METHOD_OBTAINING_DELIVERY_ZONE_ID"] : "");
	$NAddress = htmlspecialchars( isset($_POST["METHOD_OBTAINING_ADDRESS"]) ? $_POST["METHOD_OBTAINING_ADDRESS"] : "");
	$NLat = htmlspecialchars( isset($_POST["METHOD_OBTAINING_LAT"]) ? $_POST["METHOD_OBTAINING_LAT"] : "");
	$NLon = htmlspecialchars( isset($_POST["METHOD_OBTAINING_LON"]) ? $_POST["METHOD_OBTAINING_LON"] : "");

	
	if($NTypeId > 0){
		foreach($Types as $key => $value){
			if($value["ID"] == $NTypeId){
				$UFields["UF_METHOD_OBTAINING_TYPE"] = $NTypeId;
				break;
			}
		}
	}
	
	if($NStoreId > 0){
		foreach($Stores as $key => $value){
			if($value["ID"] == $NStoreId){
				$UFields["UF_METHOD_OBTAINING_STORE_ID"] = $NStoreId;
				break;
			}
		}
	}
	
	if($NLat > 0 && $NLon > 0 && $NZoneId > 0 && !empty($NAddress) ){
		$UFields["UF_METHOD_OBTAINING_LAT"] = $NLat;
		$UFields["UF_METHOD_OBTAINING_LON"] = $NLon;
		
		$UFields["UF_METHOD_OBTAINING_ADDRESS"] = $NAddress;
		
		foreach($DeliveryZones as $key => $value){
			if($value["ID"] == $NZoneId){
				$UFields["UF_METHOD_OBTAINING_DELIVERY_ZONE_ID"] = $NZoneId;
				break;
			}
		}
	}

	
	if(!empty($UFields)){
		
		if($UFields["UF_METHOD_OBTAINING_TYPE"] == 2){
			
			if(
				!empty($UFields["UF_METHOD_OBTAINING_ADDRESS"])
				&&
				!empty($UFields["UF_METHOD_OBTAINING_LAT"])
				&&
				!empty($UFields["UF_METHOD_OBTAINING_LON"])
				&&
				!empty($UFields["UF_METHOD_OBTAINING_DELIVERY_ZONE_ID"])
			){
				$Result["STATUS"] = true;
			} else {
				$Result["MESSAGE"] = "Некорректный адрес доставки";
			}
			
		} else {
			
			if(!empty($UFields["UF_METHOD_OBTAINING_STORE_ID"]) && $UFields["UF_METHOD_OBTAINING_STORE_ID"] > 0){
				$Result["STATUS"] = true;
			} else {
				$Result["MESSAGE"] = "Выберите склад самовывоза";
			}
			
		}
		
		if($Result["STATUS"]){
			
			if($USER->IsAuthorized()){
				$NUser->Update($USER->GetID(), $UFields);
			} else {
				$Result["SAVE_COOKIE"] = $UFields;
			}
		}
		
		
	}
	
	
	
	echo json_encode($Result);
	die();
}
?>

<script src="https://api-maps.yandex.ru/2.1/?apikey=62296741-e452-4ca8-a86c-83a8e9e5c179&load=package.full&lang=ru-RU"></script>
<script src="https://cdn.jsdelivr.net/npm/suggestions-jquery@20.3.0/dist/js/jquery.suggestions.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/suggestions-jquery@20.3.0/dist/css/suggestions.min.css" type="text/css" rel="stylesheet"/>

<div id="MethodObtaining" style="display: none">
	<form id="MethodObtainingForm">
		<input type="hidden" name="METHOD_OBTAINING_TYPE_ID" value="<?=$UserData["TYPE_ID"]?>"/>
		<input type="hidden" name="METHOD_OBTAINING_STORE_ID" value="<?=$UserData["STORE"]["ID"]?>"/>
		<input type="hidden" name="METHOD_OBTAINING_DELIVERY_ZONE_ID" value="<?=$UserData["ZONE_ID"]?>"/>
		<input type="hidden" name="METHOD_OBTAINING_ADDRESS" value="<?=$UserData["ADDRESS"]?>"/>
		<input type="hidden" name="METHOD_OBTAINING_LAT" value="<?=$UserData["LAT"]?>"/>
		<input type="hidden" name="METHOD_OBTAINING_LON" value="<?=$UserData["LON"]?>"/>
		
		<div class="popup-outer-box" id="popup-delivery" style="display: block" >
			<div class="popup-box">
				<a class="btn-popup-close btn-action-ico ico-close js-popup-close" href="">
					<svg width="32" height="32">
						<use xlink:href="<?=SITE_TEMPLATE_PATH?>/img/sprite.svg#cross-close-icon ">
						</use>
					</svg>
				</a>
				<div class="popup-content-wrap">
					<div class="popup-delivery">
						<div class="popup-delivery__inner">
							<div class="popup-delivery__left">
								<div class="tabs">
									<ul class="tabs__list">
										<?foreach($Types as $key => $value):?>
										<li data-type="" data-id="<?=$value["ID"]?>" class="<?=$value["ID"] == $UserData["TYPE_ID"] ? "active" : ""?>">
											<?if($value["UF_CODE"] == "DELIVERY"):?>
											<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
												<g clip-path="url(#clip0_3002_55094)">
													<path d="M12.75 4.875C12.75 4.35583 12.904 3.84831 13.1924 3.41663C13.4808 2.98495 13.8908 2.6485 14.3705 2.44982C14.8501 2.25114 15.3779 2.19915 15.8871 2.30044C16.3963 2.40173 16.864 2.65173 17.2312 3.01885C17.5983 3.38596 17.8483 3.85369 17.9496 4.36289C18.0508 4.87209 17.9989 5.39989 17.8002 5.87955C17.6015 6.3592 17.2651 6.76917 16.8334 7.05761C16.4017 7.34605 15.8942 7.5 15.375 7.5C14.6788 7.5 14.0111 7.22344 13.5188 6.73116C13.0266 6.23887 12.75 5.57119 12.75 4.875ZM22.5 16.5C22.5 17.2417 22.2801 17.9667 21.868 18.5834C21.456 19.2001 20.8703 19.6807 20.1851 19.9646C19.4998 20.2484 18.7458 20.3226 18.0184 20.1779C17.291 20.0333 16.6228 19.6761 16.0984 19.1517C15.5739 18.6272 15.2168 17.959 15.0721 17.2316C14.9274 16.5042 15.0016 15.7502 15.2855 15.0649C15.5693 14.3797 16.0499 13.794 16.6666 13.382C17.2833 12.9699 18.0083 12.75 18.75 12.75C19.7446 12.75 20.6984 13.1451 21.4017 13.8484C22.1049 14.5516 22.5 15.5054 22.5 16.5ZM18.75 10.5C18.75 10.3011 18.671 10.1103 18.5303 9.96967C18.3897 9.82902 18.1989 9.75 18 9.75H14.5603L11.7806 6.96938C11.711 6.89964 11.6283 6.84432 11.5372 6.80658C11.4462 6.76884 11.3486 6.74941 11.25 6.74941C11.1514 6.74941 11.0538 6.76884 10.9628 6.80658C10.8717 6.84432 10.789 6.89964 10.7194 6.96938L7.71938 9.96938C7.64964 10.039 7.59432 10.1217 7.55658 10.2128C7.51884 10.3038 7.49941 10.4014 7.49941 10.5C7.49941 10.5986 7.51884 10.6962 7.55658 10.7872C7.59432 10.8783 7.64964 10.961 7.71938 11.0306L11.25 14.5603V18.75C11.25 18.9489 11.329 19.1397 11.4697 19.2803C11.6103 19.421 11.8011 19.5 12 19.5C12.1989 19.5 12.3897 19.421 12.5303 19.2803C12.671 19.1397 12.75 18.9489 12.75 18.75V14.25C12.7501 14.1515 12.7307 14.0539 12.6931 13.9629C12.6555 13.8718 12.6003 13.7891 12.5306 13.7194L9.31031 10.5L11.25 8.56031L13.7194 11.0306C13.7891 11.1003 13.8718 11.1555 13.9629 11.1931C14.0539 11.2307 14.1515 11.2501 14.25 11.25H18C18.1989 11.25 18.3897 11.171 18.5303 11.0303C18.671 10.8897 18.75 10.6989 18.75 10.5ZM9 16.5C9 17.2417 8.78007 17.9667 8.36801 18.5834C7.95596 19.2001 7.37029 19.6807 6.68506 19.9646C5.99984 20.2484 5.24584 20.3226 4.51841 20.1779C3.79098 20.0333 3.1228 19.6761 2.59835 19.1517C2.0739 18.6272 1.71675 17.959 1.57206 17.2316C1.42736 16.5042 1.50162 15.7502 1.78545 15.0649C1.78545 15.0649 2.54993 13.794 3.16661 13.382C3.7833 12.9699 4.50832 12.75 5.25 12.75C5.74246 12.75 6.23009 12.847 6.68506 13.0355C7.14004 13.2239 7.55343 13.5001 7.90165 13.8484C8.24987 14.1966 8.52609 14.61 8.71455 15.0649C8.90301 15.5199 9 16.0075 9 16.5Z" fill="currentColor" />
													<path d="M5.51792 10.1337C5.65425 10.1885 5.80364 10.202 5.94757 10.1725C6.09149 10.1431 6.22361 10.0721 6.32752 9.96824L10.3684 5.93141C10.509 5.79085 10.588 5.60022 10.5881 5.40142C10.5882 5.20262 10.5094 5.01191 10.369 4.87121L8.35056 2.85075C8.21 2.71017 8.01937 2.63114 7.82057 2.63104C7.62177 2.63094 7.43106 2.70977 7.29035 2.85021L3.24944 6.88704C3.10885 7.02761 3.02983 7.21823 3.02973 7.41703C3.02963 7.61583 3.10846 7.80654 3.2489 7.94725L5.26732 9.96771C5.33879 10.0394 5.42404 10.0959 5.51792 10.1337Z" fill="currentColor" />
												</g>
												<defs>
													<clipPath id="clip0_3002_55094">
														<rect width="24" height="24" fill="white" />
													</clipPath>
												</defs>
											</svg>
											<?else:?>
											<svg width="20" height="18" viewBox="0 0 20 18" fill="none" xmlns="http://www.w3.org/2000/svg">
												<path d="M19.4709 5.79469L18.1266 1.0875C18.0361 0.775232 17.8471 0.500591 17.5878 0.304588C17.3284 0.108585 17.0126 0.00173713 16.6875 0H2.8125C2.48741 0.00173713 2.1716 0.108585 1.91223 0.304588C1.65287 0.500591 1.46386 0.775232 1.37344 1.0875L0.0290624 5.79469C0.00984345 5.86143 6.11116e-05 5.93054 0 6V7.5C0 8.08217 0.135544 8.65634 0.395898 9.17705C0.656252 9.69776 1.03426 10.1507 1.5 10.5V17.25C1.5 17.4489 1.57902 17.6397 1.71967 17.7803C1.86032 17.921 2.05109 18 2.25 18H17.25C17.4489 18 17.6397 17.921 17.7803 17.7803C17.921 17.6397 18 17.4489 18 17.25V10.5C18.4657 10.1507 18.8437 9.69776 19.1041 9.17705C19.3645 8.65634 19.5 8.08217 19.5 7.5V6C19.4999 5.93054 19.4902 5.86143 19.4709 5.79469ZM6 7.5C5.99986 7.88691 5.89996 8.26725 5.70993 8.60428C5.5199 8.94131 5.24617 9.22364 4.91518 9.42401C4.58419 9.62437 4.20713 9.736 3.82041 9.74811C3.43368 9.76022 3.05037 9.67239 2.7075 9.49312C2.65533 9.45251 2.59794 9.41909 2.53688 9.39375C2.21913 9.19033 1.95764 8.91029 1.77645 8.57937C1.59527 8.24845 1.5002 7.87728 1.5 7.5V6.75H6V7.5ZM12 7.5C12 8.09674 11.7629 8.66903 11.341 9.09099C10.919 9.51295 10.3467 9.75 9.75 9.75C9.15326 9.75 8.58097 9.51295 8.15901 9.09099C7.73705 8.66903 7.5 8.09674 7.5 7.5V6.75H12V7.5ZM18 7.5C17.9997 7.87736 17.9045 8.24859 17.7231 8.57952C17.5418 8.91045 17.2801 9.19045 16.9622 9.39375C16.9019 9.41914 16.8452 9.45223 16.7934 9.49219C16.4506 9.67164 16.0673 9.75964 15.6805 9.74769C15.2937 9.73574 14.9166 9.62423 14.5855 9.42395C14.2544 9.22367 13.9805 8.94138 13.7904 8.60436C13.6002 8.26733 13.5002 7.88696 13.5 7.5V6.75H18V7.5Z" fill="currentColor" />
											</svg>
											<?endif;?>
											<span>
												<?=$value["UF_NAME"]?>
											</span>
											
										</li>
										<?endforeach;?>
									</ul>
									<div class="tabs__content">
										<div style="display: <?=($UserData["TYPE_ID"] == 1 ? "block" : "none")?>">
											<div class="popup-delivery__shops">
												<div class="popup-delivery__shops-items">
													<div class="shop-block">
														<div class="shop-block-title">
															<svg width="24" height="24">
																<use xlink:href="<?=SITE_TEMPLATE_PATH?>/img/sprite.svg#store-icon">
																</use>
															</svg>
															<span>
																<?=$UserData["STORE"]["SCHEDULE"]?>
															</span>
														</div>
														<div class="shop-block-name">
															<?=$UserData["STORE"]["TITLE"]?>
														</div>
														<div class="shop-block-address"><?=$UserData["STORE"]["ADDRESS"]?></div>
													</div>
												</div>
											</div>
										</div>
										<div style="display: <?=($UserData["TYPE_ID"] == 2 ? "block" : "none")?>">
											<div class="popup-delivery__shops-items">
												<div class="popup-delivery__shops-title">
													Адрес доставки:
												</div>
												<div class="popup-delivery__shops-input">
													<div class="popup-delivery__shops-input-label">
														<span>
															Город, улица, дом
														</span>
														<a class="popup-delivery__shops-input-link" id="MethodObtainingDefine" href="javascript:void(0)">
															<svg width="24" height="24">
																<use xlink:href="<?=SITE_TEMPLATE_PATH?>/img/sprite.svg#compass-icon">
																</use>
															</svg>Определить
														</a>
													</div>
													<div class="popup-delivery__shops-input-field">
														<input type="text" id="MethodObtainingAddress" value="<?=$UserData["ADDRESS"]?>" placeholder="Введите адрес, чтобы найти ближайшие магазины">
													</div>
												</div>
											</div>
										</div>
									</div>
								</div>
								<div id="MethodObtainingError"></div>
								<button type="submit" class="btn btn_big popup-delivery__btn">
									Выбрать
								</button>
							</div>
							<div class="popup-delivery__right">
								<div class="popup-delivery__tabs">
									<div class="tabs">
										<ul class="tabs__list">
											<li id="MethodObtainingTabListsMap">
												На карте
											</li>
											<li style="display: <?=($UserData["TYPE_ID"] == 2 ? "none" : "block")?>" id="MethodObtainingTabListsList">
												Списком
											</li>
										</ul>
										<div class="tabs__content">
											<div>
												<div id="MethodObtainingMap" class="popup-delivery__map"></div>
											</div>
											<div style="display: <?=($UserData["TYPE_ID"] == 2 ? "none" : "block")?> id="MethodObtainingTabContentList">
												<div class="popup-delivery__list">
													<?foreach($Stores as $key => $value):?>
													<div data-store="" data-id="<?=$value["ID"]?>" class="popup-delivery__list-item">
														<div class="popup-delivery__list-title">
															<div class="popup-delivery__list-name">
																<?=$value["TITLE"]?>
															</div>
															<svg width="24" height="24">
																<use xlink:href="<?=SITE_TEMPLATE_PATH?>/img/sprite.svg#store-icon">
																</use>
															</svg>
														</div>
														<div class="popup-delivery__list-address">
															<?=$value["ADDRESS"]?>
														</div>
														<div class="popup-delivery__list-time">
															<?=$value["SCHEDULE"]?>
														</div>
													</div>
													<?endforeach;?>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</form>
</div>
<script>
	
	MethodObtainingForm = {
		Map: false,
		MapObjectManager: null,
		IsSend: false,
		Params:{},
		MapZone: null,
		IsLoadDeliveryZones: false,
		Init: function(Params){
			
			this.Params = Params;
			$("#MethodObtainingForm").submit($.proxy(this.EventSubmitForm,this));
			$("#MethodObtainingForm [data-type]").click($.proxy(this.EventClickType,this));
			$("#MethodObtainingForm [data-store]").click($.proxy(this.EventClickStore,this));
			$("#MethodObtainingDefine").click($.proxy(this.EventClickDefine,this));
			
			
			this.YmapsWaiter();
			this.SuggestionsWaiter();		
			this.LoadDeliveryZones();
		},
		EventClickDefine: function(){
			
			
			if ("geolocation" in navigator) {
				navigator.geolocation.getCurrentPosition(
					function(Result){
						
						if(Result.coords.latitude && Result.coords.longitude){
							MethodObtainingForm.SetDeliveryData(Result.coords.latitude,Result.coords.longitude,"");
						}
						
					},
					function(Result){
						MethodObtainingForm.SetErrorMessage("Не удалось определить автоматически местоположение");
					},
					{
						enableHighAccuracy: true,
						timeout: 10000,
						maximumAge: 0 
					}
				);
			}
			
			return false;
		},
		SuggestionsWaiter: function(){
			if(typeof $.Suggestions == "function"){
				this.SuggestionsInit();
			} else {
				setTimeout($.proxy(this.SuggestionsWaiter,this), 500);
			}
		},	
		SuggestionsInit: function(){
			
			$("#MethodObtainingAddress").suggestions({
				token: "d25c061a178900ad6230b608fcd9348ca2fbb0f1",
				type: "ADDRESS",
				onSelect: $.proxy(this.SuggestionsSelect,this)
			});
			
		},
		SuggestionsSelect: function(Result){
			this.SetErrorMessage("");
			this.SetDeliveryData("","",Result.value);
		},
		LoadDeliveryZones: function(){
			var i;
			var Flag = true;
			for(i in this.Params.DELIVERY_ZONES){
				if(typeof this.Params.DELIVERY_ZONES[i].FILE_DATA == "undefined"){
					$.getJSON(this.Params.DELIVERY_ZONES[i].FILE, $.proxy(this.LoadDeliveryZonesSuccess,this));
					Flag = false;
					break;
				}
			}
			
			if(Flag){
				this.IsLoadDeliveryZones = true;
			}
			
		},
		LoadDeliveryZonesSuccess: function(Result){
			var i,j,k;
			var Item;
			var GObj = null
			
			for(i in this.Params.DELIVERY_ZONES){
				if(typeof this.Params.DELIVERY_ZONES[i].FILE_DATA == "undefined"){
					
					Item = Result.features[0];
					
					Item.id = this.Params.DELIVERY_ZONES[i].ID;
					NewCoordinates = [];
					
					for(j in Item.geometry.coordinates){
						for(k in Item.geometry.coordinates[j]){
							Item.geometry.coordinates[j][k] = [Item.geometry.coordinates[j][k][1],Item.geometry.coordinates[j][k][0]];
						}
					}
					
					this.Params.DELIVERY_ZONES[i].FILE_DATA = Item;
					
					break;
				}
			}
			this.LoadDeliveryZones();
		},
		
		YmapsInit: function(){
			if(!this.Map){
				this.Map = new ymaps.Map("MethodObtainingMap", {
					center: [43.312791, 45.731304],
					zoom: 11,
					//type: 'yandex#<?=$arResult['ALL_MAP_TYPES'][$arParams['INIT_MAP_TYPE']]?>'
				});
				
				//this.Map.behaviors.disable('scrollZoom');
				
				this.MapObjectManager = new ymaps.ObjectManager();
				
				this.Map.geoObjects.add(this.MapObjectManager);
				this.Map.events.add('click', $.proxy(this.EventClickMap,this))
			}
			
			this.MapObjectManager.removeAll();
			
			if(this.MapZone){
				this.MapZone.removeFromMap(this.Map);
			}
			
			
			this.MapObjectManagerRemoveAll();
		},
		EventClickMap: function(e){
			var TypeId = parseInt($("#MethodObtainingForm input[name='METHOD_OBTAINING_TYPE_ID']").val());
			var Coords = [];
			
			if(TypeId == 2){
				Coords = e.get('coords');
				this.SetErrorMessage("");
				this.SetDeliveryData(Coords[0],Coords[1],"");
			}
			return false;
		},
		DeliveryData:{
			Step: 0,
			Lat: "",
			Lon: "",
			Address: "",
			ZoneId: 0
		},
		SetDeliveryData: function(Lat,Lon,Address){
			var IHere;
			var IsRestart = true;
			
			//г Грозный, р-н Байсангуровский, ул Рассветная, д 1 
			//MethodObtainingForm.DeliveryData
			
			if(this.DeliveryData.Step == 3){
				
				var Zone = this.MapZone.searchContaining([this.DeliveryData.Lat,this.DeliveryData.Lon]).get(0);
				if(typeof Zone != "undefined"){
					this.DeliveryData.ZoneId = Zone.properties._data.hintContent
				}
				
				this.MapObjectManager.add({
				    type: 'Feature',
				    id: "IHere",
				    geometry: {
				        type: 'Point',
				        coordinates: [this.DeliveryData.Lat,this.DeliveryData.Lon] 
				    },
				    properties: {
				        balloonContent: "Я здесь",
				        hintContent: "Я здесь",
				    }
				},{});
				
				this.Map.setCenter([this.DeliveryData.Lat,this.DeliveryData.Lon] );
				
				$("#MethodObtainingForm input[name='METHOD_OBTAINING_DELIVERY_ZONE_ID']").val(this.DeliveryData.ZoneId);
				$("#MethodObtainingForm input[name='METHOD_OBTAINING_ADDRESS']").val(this.DeliveryData.Address);
				$("#MethodObtainingForm input[name='METHOD_OBTAINING_LAT']").val(this.DeliveryData.Lat);
				$("#MethodObtainingForm input[name='METHOD_OBTAINING_LON']").val(this.DeliveryData.Lon);
				
				
				IsRestart = false;
				
				if(this.DeliveryData.ZoneId > 0){
					
				} else {
					this.SetErrorMessage("Указанный адрес находиться за пределами зоны доставки");
				}
				
			}else if(this.DeliveryData.Step == 2){
				
				
				if(this.DeliveryData.Address != ""){
					
					ymaps.geocode(this.DeliveryData.Address).then(function (Result) {
						var Coordinates = Result.geoObjects.get(0).geometry.getCoordinates();
						MethodObtainingForm.DeliveryData.Lat = Coordinates[0];
						MethodObtainingForm.DeliveryData.Lon = Coordinates[1];
						MethodObtainingForm.DeliveryData.Step = 3;
						MethodObtainingForm.SetDeliveryData();
					});
					
				} else if(this.DeliveryData.Lat != "" && this.DeliveryData.Lon != ""){
					
					ymaps.geocode([this.DeliveryData.Lat,this.DeliveryData.Lon]).then(function (Result) {
						MethodObtainingForm.DeliveryData.Address = Result.geoObjects.get(0).getAddressLine();
						MethodObtainingForm.DeliveryData.Step = 3;
						MethodObtainingForm.SetDeliveryData();
						
					});
					
				}
				IsRestart = false;
				
				
			} else if(this.DeliveryData.Step == 1){
				
				IHere = this.MapObjectManager.objects.getById("IHere");
				if(IHere){
					this.MapObjectManager.remove(IHere);
				} else {
					this.DeliveryData.Step = 2
				}
				
			} else {
				this.DeliveryData = {
					Step: 1,
					Lat: Lat,
					Lon: Lon,
					Address: Address,
					ZoneId: 0
				}
				
				$("#MethodObtainingForm input[name='METHOD_OBTAINING_DELIVERY_ZONE_ID']").val("");
				$("#MethodObtainingForm input[name='METHOD_OBTAINING_ADDRESS']").val("");
				$("#MethodObtainingForm input[name='METHOD_OBTAINING_LAT']").val("");
				$("#MethodObtainingForm input[name='METHOD_OBTAINING_LON']").val("");
			}
			
			if(IsRestart){
				setTimeout($.proxy(this.SetDeliveryData,this),500);
			}
			
		},
		
		MapObjectManagerAdd: function(){
			var i;
			var TypeId = parseInt($("#MethodObtainingForm input[name='METHOD_OBTAINING_TYPE_ID']").val());
			var GObj;
			var GObjs = [];
			if(TypeId == 2){
				
				if(this.IsLoadDeliveryZones){
					for(i in this.Params.DELIVERY_ZONES){
						
						
						//this.MapObjectManager.add(this.Params.DELIVERY_ZONES[i].FILE_DATA);
						
						GObj = new ymaps.GeoObject({
							geometry:{
								coordinates:this.Params.DELIVERY_ZONES[i].FILE_DATA.geometry.coordinates,
								type:'Polygon'
							},
							properties:{
								hintContent: this.Params.DELIVERY_ZONES[i].FILE_DATA.id
							}
						},{
							"mediumVehiclePrice":"80",
							"heavyVehiclePrice":"112",
							"fill":"#177bc9",
							"fill-opacity":"0.6",
							"stroke":"#82cdff",
							"stroke-width":"0",
							"stroke-opacity":"0.9",
						});
						
						
						
						
						GObjs.push(GObj);
						
					}
					
					this.MapZone =ymaps.geoQuery(GObjs).addToMap(this.Map);
					
					
				} else {
					
					setTimeout( $.proxy(this.MapObjectManagerAdd,this) ,1000);
					
				}
				
			} else {
				
				for(i in this.Params.STORES){
			
					this.MapObjectManager.add({
					    type: 'Feature',
					    id: this.Params.STORES[i].ID, 
					    geometry: {
					        type: 'Point',
					        coordinates: [this.Params.STORES[i].GPS_N, this.Params.STORES[i].GPS_S] 
					    },
					    properties: {
					        balloonContent:this.Params.STORES[i].TITLE,
					        hintContent: this.Params.STORES[i].TITLE
					    }
					});
					
					this.MapObjectManager.events.add('click', function (e) {
						var ObjectId = e.get('objectId');
						
						MethodObtainingForm.SetStore(ObjectId);
						
					});
					
				}
			}
			
			
		},
		MapObjectManagerRemoveAll: function(){
			setTimeout(function(){
			
				if(MethodObtainingForm.MapObjectManager.objects.getAll().length == 0){
					MethodObtainingForm.MapObjectManagerAdd();
				} else {
					MethodObtainingForm.MapObjectManagerRemoveAll();
				}
			},500);
		},
		
		YmapsWaiter: function(){
			if(typeof ymaps !== 'undefined' &&  typeof ymaps.ObjectManager !== 'undefined' && typeof ymaps.GeoObject !== 'undefined'){
				ymaps.ready($.proxy(this.YmapsInit,this));
			} else {
				setTimeout($.proxy(this.YmapsWaiter,this), 500);
			}
		},	
		EventClickStore: function(e){
			var JObj = $(e.target || e.currentTarget);
			var Id = 0;
			
			if(typeof JObj.attr("data-id") == "undefined"){
				JObj = JObj.parents("[data-id]");
			}
			Id = JObj.attr("data-id");
			
			this.SetStore(Id);
			return false;
		},
		SetStore: function(Id){
			var i;
			
			Id = parseInt(Id);
			
			$("#MethodObtainingForm input[name='METHOD_OBTAINING_STORE_ID']").val(Id);
			
			for(i in this.Params.STORES){
			
				if(parseInt(this.Params.STORES[i].ID) == Id){
					
					$("#MethodObtainingForm .shop-block-title span").text(this.Params.STORES[i].SCHEDULE);
					$("#MethodObtainingForm .shop-block-name").text(this.Params.STORES[i].TITLE);
					$("#MethodObtainingForm .shop-block-address").text(this.Params.STORES[i].ADDRESS);
					
					break;
				}
				
			}
		},
		EventClickType: function(e){
			var JObj = $(e.target || e.currentTarget);
			var Id = 0;
			
			if(typeof JObj.attr("data-id") == "undefined"){
				JObj = JObj.parents("[data-id]");
			}
			
			Id = parseInt(JObj.attr("data-id"));
			
			$("#MethodObtainingForm input[name='METHOD_OBTAINING_TYPE_ID']").val(Id);
			
			if(Id == 2){
				$("#MethodObtainingTabListsMap").click();
				$("#MethodObtainingTabListsList").hide();
				$("#MethodObtainingTabContentList").hide();
			} else {
				$("#MethodObtainingTabListsList").show();
				$("#MethodObtainingTabContentList").show();
			}
			
			this.YmapsInit();
			
			return false;
		},
		EventSubmitForm: function(e){
			var JObj = $(e.target || e.currentTarget);
			var i;
			var Data = JObj.serializeArray();
			
			Data.push({name:"action",value:"save"});
				
			this.Send(Data);
			
			return false;
		},
		Send: function(Data){
			
			if(!this.IsSend){
				this.IsSend = true;
				this.SetErrorMessage();
				$.ajax({
					url: "/ajax/method_obtaining.php",
					data: Data,
					type: "POST",
					dataType: "json",
					success: $.proxy(this.SendSuccess,this),
					error: $.proxy(this.SendError,this),
				})
				
			}
			
		},
		SendSuccess: function(Result){
			var i;
			this.IsSend = false;
			
			if(Result.STATUS){
				if(typeof Result.SAVE_COOKIE != "undefined"){
					for(i in Result.SAVE_COOKIE){
						$.cookie(i, Result.SAVE_COOKIE[i], { expires: 365, path: '/' });	
					}
				}
				window.location.href = window.location.href;
			} else {
				this.SetErrorMessage(Result.MESSAGE);
			}
		},
		SendError: function(){
			this.IsSend = false;
			window.location.href = window.location.href;
		},
		SetErrorMessage: function(Message){
			var StrHtml = "";
			$("#MethodObtainingError").empty();
			
			if(typeof Message != "undefined" && Message != "" ){
				
				StrHtml += '<div class="popup-delivery__delivery-error">';
					StrHtml += '<div class="popup-delivery__delivery-error-title">';
						StrHtml += '<svg width="24" height="24"><use xlink:href="/local/templates/vkusdom/img/sprite.svg#shop-error-icon"></use></svg>';
						StrHtml += "Ошибка";
					StrHtml += '</div>';
					StrHtml += '<div class="popup-delivery__delivery-error-text">'+Message+'</div>';
					StrHtml += '<div class="popup-delivery__delivery-error-field"></div>';
				StrHtml += '</div><br>';
				
				$("#MethodObtainingError").append(StrHtml);
			}
			
			
		}
	}
	
	MethodObtainingForm.Init({
		"DELIVERY_ZONES":<?=json_encode($DeliveryZones)?>,
		"STORES" :<?=json_encode($Stores);?>
	});
	
</script>