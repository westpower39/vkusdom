<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");?>
<div id="UseCookie">
	<div class="section-cookies">
		<button class="section-cookies__close">
			<svg width="32" height="32">
				<use xlink:href="<?=SITE_TEMPLATE_PATH?>/img/sprite.svg#close-icon">
				</use>
			</svg>
		</button>
		<div class="section-cookies__columns">
			<div class="section-cookies__column">
				<div class="section-cookies__title">
					Мы используем cookie
				</div>
				<div class="section-cookies__text">
					«ВкусДом» использует файлы cookie, чтобы сайт работал корректно, запоминал ваши настройки и помогал нам делать покупки удобнее.
				</div>
			</div>
			<div class="section-cookies__column">
				<div class="section-cookies__notice">
					Вы можете принять все cookies или настроить их использование.
				</div>
				<div class="section-cookies__buttons">
					<button data-use-cookie="accept" class="btn btn_accept-coookies">
						Принять все
					</button>
					<button data-use-cookie="settings" class="btn btn_settings-coookies">
						Настроить
					</button>
				</div>
			</div>
		</div>
	</div>
	<div class="section-cookies-settings">
		<button class="section-cookies__close">
			<svg width="32" height="32">
				<use xlink:href="<?=SITE_TEMPLATE_PATH?>/img/sprite.svg#close-icon">
				</use>
			</svg>
		</button>
		<div class="section-cookies-settings__title">
			Настройка cookies
		</div>
		<div class="section-cookies-settings__desc">
			Выберите, какие файлы cookie мы можем использовать. Обязательные cookies нужны для корректной работы сайта и доступны всегда. Остальные помогают нам улучшать сайт и делать предложения более подходящими для вас.
		</div>
		<div class="section-cookies-settings__options">
			<div class="section-cookies-settings__option">
				<div class="section-cookies-settings__option-name">
					<div class="custom-switch">
						<label class="switch">
							<input type="checkbox"/>
							<span class="switch__slider">
							</span>
							<span class="switch__text">
								Обязательные
							</span>
						</label>
					</div>
				</div>
				<div class="section-cookies-settings__option-text">
					Нужны для работы сайта, оформления заказов, авторизации и сохранения настроек. Отключить их нельзя.
				</div>
			</div>
			<div class="section-cookies-settings__option">
				<div class="section-cookies-settings__option-name">
					<div class="custom-switch">
						<label class="switch">
							<input type="checkbox"/>
							<span class="switch__slider">
							</span>
							<span class="switch__text">
								Аналитические
							</span>
						</label>
					</div>
				</div>
				<div class="section-cookies-settings__option-text">
					Помогают нам понимать, как пользователи взаимодействуют с сайтом, находить ошибки и улучшать его работу.
				</div>
			</div>
			<div class="section-cookies-settings__option">
				<div class="section-cookies-settings__option-name">
					<div class="custom-switch">
						<label class="switch">
							<input type="checkbox"/>
							<span class="switch__slider">
							</span>
							<span class="switch__text">
								Обязательные
							</span>
						</label>
					</div>
				</div>
				<div class="section-cookies-settings__option-text">
					Помогают показывать более релевантные предложения и оценивать эффективность рекламных кампаний.
				</div>
			</div>
		</div>
		<div class="section-cookies-settings__buttons">
			<button data-use-cookie="accept" class="btn btn_accept-coookies">
				Сохранить настройки
			</button>
			<button data-use-cookie="accept" class="btn btn_settings-coookies">
				Принять все cookies
			</button>
		</div>
	</div>
</div>