let _slideUp = (target, duration = 500, showmore = 0) => {
   if (!target.classList.contains("_slide")) {
       target.classList.add("_slide");
       target.style.transitionProperty = "height, margin, padding";
       target.style.transitionDuration = duration + "ms";
       target.style.height = `${target.offsetHeight}px`;
       target.offsetHeight;
       target.style.overflow = "hidden";
       target.style.height = showmore ? `${showmore}px` : `0px`;
       target.style.paddingTop = 0;
       target.style.paddingBottom = 0;
       target.style.marginTop = 0;
       target.style.marginBottom = 0;
       window.setTimeout((() => {
           target.hidden = !showmore ? true : false;
           !showmore ? target.style.removeProperty("height") : null;
           target.style.removeProperty("padding-top");
           target.style.removeProperty("padding-bottom");
           target.style.removeProperty("margin-top");
           target.style.removeProperty("margin-bottom");
           !showmore ? target.style.removeProperty("overflow") : null;
           target.style.removeProperty("transition-duration");
           target.style.removeProperty("transition-property");
           target.classList.remove("_slide");
           document.dispatchEvent(new CustomEvent("slideUpDone", {
               detail: {
                   target
               }
           }));
       }), duration);
   }
};

let _slideDown = (target, duration = 500, showmore = 0) => {
   if (!target.classList.contains("_slide")) {
       target.classList.add("_slide");
       target.hidden = target.hidden ? false : null;
       showmore ? target.style.removeProperty("height") : null;
       let height = target.offsetHeight;
       target.style.overflow = "hidden";
       target.style.height = showmore ? `${showmore}px` : `0px`;
       target.style.paddingTop = 0;
       target.style.paddingBottom = 0;
       target.style.marginTop = 0;
       target.style.marginBottom = 0;
       target.offsetHeight;
       target.style.transitionProperty = "height, margin, padding";
       target.style.transitionDuration = duration + "ms";
       target.style.height = height + "px";
       target.style.removeProperty("padding-top");
       target.style.removeProperty("padding-bottom");
       target.style.removeProperty("margin-top");
       target.style.removeProperty("margin-bottom");
       window.setTimeout((() => {
           target.style.removeProperty("height");
           target.style.removeProperty("overflow");
           target.style.removeProperty("transition-duration");
           target.style.removeProperty("transition-property");
           target.classList.remove("_slide");
           document.dispatchEvent(new CustomEvent("slideDownDone", {
               detail: {
                   target
               }
           }));
       }), duration);
   }
};

let _slideToggle = (target, duration = 500) => {
   if (target.hidden) return _slideDown(target, duration); else return _slideUp(target, duration);
};

const modules_flsModules = {};

function FLS(message) {
   setTimeout((() => {
    //    if (window.FLS) console.log(message);
   }), 1);
}

class SelectConstructor {
   constructor(props, data = null) {
       let defaultConfig = {
           init: true,
           logging: true,
           speed: 150
       };
       this.config = Object.assign(defaultConfig, props);
       this.selectClasses = {
           classSelect: "select",
           classSelectBody: "select__body",
           classSelectTitle: "select__title",
           classSelectValue: "select__value",
           classSelectLabel: "select__label",
           classSelectInput: "select__input",
           classSelectText: "select__text",
           classSelectLink: "select__link",
           classSelectOptions: "select__options",
           classSelectOptionsScroll: "select__scroll",
           classSelectOption: "select__option",
           classSelectContent: "select__content",
           classSelectRow: "select__row",
           classSelectData: "select__asset",
           classSelectClear: "select__clear",
           classSelectClearIcon: "select__clear-icon",
           classSelectDisabled: "_select-disabled",
           classSelectTag: "_select-tag",
           classSelectOpen: "_select-open",
           classSelectActive: "_select-active",
           classSelectFocus: "_select-focus",
           classSelectMultiple: "_select-multiple",
           classSelectCheckBox: "_select-checkbox",
           classSelectOptionSelected: "_select-selected",
           classSelectPseudoLabel: "_select-pseudo-label"
       };
       this._this = this;
       if (this.config.init) {
           const selectItems = data ? document.querySelectorAll(data) : document.querySelectorAll("select");
           if (selectItems.length) {
               this.selectsInit(selectItems);
               this.setLogging(`Прокинувся, построїв селектов: (${selectItems.length})`);
           } else this.setLogging("Сплю, немає жодного select");
       }
   }
   
   getSelectClass(className) {
       return `.${className}`;
   }
   
   getSelectElement(selectItem, className) {
       return {
           originalSelect: selectItem.querySelector("select"),
           selectElement: selectItem.querySelector(this.getSelectClass(className))
       };
   }
   
   selectsInit(selectItems) {
       selectItems.forEach(((originalSelect, index) => {
           this.selectInit(originalSelect, index + 1);
       }));
       document.addEventListener("click", function(e) {
           this.selectsActions(e);
       }.bind(this));
       document.addEventListener("keydown", function(e) {
           this.selectsActions(e);
       }.bind(this));
       document.addEventListener("focusin", function(e) {
           this.selectsActions(e);
       }.bind(this));
       document.addEventListener("focusout", function(e) {
           this.selectsActions(e);
       }.bind(this));
   }

   selectInit(originalSelect, index) {
       const _this = this;
       let selectItem = document.createElement("div");
       selectItem.classList.add(this.selectClasses.classSelect);
       originalSelect.parentNode.insertBefore(selectItem, originalSelect);
       selectItem.appendChild(originalSelect);
       originalSelect.hidden = true;
       
       if (index) {
           originalSelect.dataset.id = index;
       }

       if (this.getSelectPlaceholder(originalSelect)) {
           // Очищаем значение от лишних пробелов
           let placeholderValue = this.getSelectPlaceholder(originalSelect).value.trim();
           originalSelect.dataset.placeholder = placeholderValue;

           if (this.getSelectPlaceholder(originalSelect).label.show) {
               const selectItemTitle = this.getSelectElement(selectItem, this.selectClasses.classSelectTitle).selectElement;
               // Очищаем текст перед вставкой
               let labelText = this.getSelectPlaceholder(originalSelect).label.text 
                   ? this.getSelectPlaceholder(originalSelect).label.text.trim() 
                   : placeholderValue;

               selectItemTitle.insertAdjacentHTML("afterbegin", `<span class="${this.selectClasses.classSelectLabel}">${labelText}</span>`);
           }
       }

       selectItem.insertAdjacentHTML("beforeend", `<div class="${this.selectClasses.classSelectBody}"><div hidden class="${this.selectClasses.classSelectOptions}"></div></div>`);
       
       this.selectBuild(originalSelect);
       originalSelect.dataset.speed = originalSelect.dataset.speed ? originalSelect.dataset.speed : this.config.speed;
       this.config.speed = +originalSelect.dataset.speed;

       // Добавляем обработчик для крестика если есть data-clear
       if (originalSelect.hasAttribute('data-clear')) {
           this.initClearButton(originalSelect);
       }

       originalSelect.addEventListener("change", function(e) {
           _this.selectChange(e);
       });
   }

   // Новый метод для инициализации кнопки очистки
   initClearButton(originalSelect) {
       const _this = this;
       const selectItem = originalSelect.parentElement;
       
       // Создаем и добавляем крестик
       const updateClearButton = () => {
           const selectTitle = selectItem.querySelector(`.${this.selectClasses.classSelectTitle}`);
           const selectValue = selectItem.querySelector(`.${this.selectClasses.classSelectValue}`);
           
           if (selectValue) {
               // Удаляем старый крестик если есть
               const oldClearIcon = selectValue.querySelector(`.${this.selectClasses.classSelectClearIcon}`);
               if (oldClearIcon) oldClearIcon.remove();
               
               // Добавляем новый крестик только если есть выбранное значение
               if (this.getSelectedOptionsData(originalSelect).values.length) {
                   selectValue.insertAdjacentHTML('beforeend', `<span class="${this.selectClasses.classSelectClearIcon}"></span>`);
                   
                   // Добавляем обработчик на крестик
                   const clearIcon = selectValue.querySelector(`.${this.selectClasses.classSelectClearIcon}`);
                   if (clearIcon) {
                       clearIcon.addEventListener('click', (e) => {
                           e.stopPropagation(); // Предотвращаем открытие селекта
                           this.clearSelect(selectItem, originalSelect);
                       });
                   }
               }
           }
       };
       
       // Первоначальное обновление
       updateClearButton();
       
       // Наблюдаем за изменениями класса _select-active
       const observer = new MutationObserver((mutations) => {
           mutations.forEach((mutation) => {
               if (mutation.attributeName === 'class') {
                   updateClearButton();
               }
           });
       });
       
       observer.observe(selectItem, { attributes: true });
       
       // Сохраняем функцию обновления для использования в других методах
       originalSelect._updateClearButton = updateClearButton;
   }
   
   // Новый метод для очистки селекта
   clearSelect(selectItem, originalSelect) {
       if (originalSelect.disabled) return;
       
       if (originalSelect.multiple) {
           // Для мультивыбора снимаем все выделения
           Array.from(originalSelect.options).forEach(option => {
               option.selected = false;
           });
           
           // Убираем классы выделения у элементов списка
           const selectedOptions = selectItem.querySelectorAll(`.${this.selectClasses.classSelectOptionSelected}`);
           selectedOptions.forEach(option => {
               option.classList.remove(this.selectClasses.classSelectOptionSelected);
           });
       } else {
           // Для одиночного выбора устанавливаем значение на пустое
           originalSelect.value = '';
           
           // Показываем все скрытые элементы
           const hiddenOptions = selectItem.querySelectorAll(`${this.getSelectClass(this.selectClasses.classSelectOption)}[hidden]`);
           hiddenOptions.forEach(option => {
               option.hidden = false;
           });
       }
       
       // Обновляем заголовок селекта
       this.setSelectTitleValue(selectItem, originalSelect);
       
       // Обновляем крестик
       if (originalSelect._updateClearButton) {
           originalSelect._updateClearButton();
       }
       
       // Вызываем событие изменения
       this.setSelectChange(originalSelect);
       
       // Триггерим событие change на оригинальном селекте
       const event = new Event('change', { bubbles: true });
       originalSelect.dispatchEvent(event);
   }

   selectBuild(originalSelect) {
       const selectItem = originalSelect.parentElement;
       selectItem.dataset.id = originalSelect.dataset.id;
       originalSelect.dataset.classModif ? selectItem.classList.add(`select_${originalSelect.dataset.classModif}`) : null;
       originalSelect.multiple ? selectItem.classList.add(this.selectClasses.classSelectMultiple) : selectItem.classList.remove(this.selectClasses.classSelectMultiple);
       originalSelect.hasAttribute("data-checkbox") && originalSelect.multiple ? selectItem.classList.add(this.selectClasses.classSelectCheckBox) : selectItem.classList.remove(this.selectClasses.classSelectCheckBox);
       this.setSelectTitleValue(selectItem, originalSelect);
       this.setOptions(selectItem, originalSelect);
       originalSelect.hasAttribute("data-search") ? this.searchActions(selectItem) : null;
       originalSelect.hasAttribute("data-open") ? this.selectAction(selectItem) : null;
       this.selectDisabled(selectItem, originalSelect);
   }
   
   selectsActions(e) {
       const targetElement = e.target;
       const targetType = e.type;
       
       // Проверяем клик на крестик очистки
       if (targetElement.closest(`.${this.selectClasses.classSelectClearIcon}`) || targetElement.closest(`.${this.selectClasses.classSelectClear}`)) {
           const selectItem = targetElement.closest(`.${this.selectClasses.classSelect}`);
           if (selectItem) {
               const originalSelect = this.getSelectElement(selectItem).originalSelect;
               e.stopPropagation();
               this.clearSelect(selectItem, originalSelect);
               return;
           }
       }
       
       if (targetElement.closest(this.getSelectClass(this.selectClasses.classSelect)) || targetElement.closest(this.getSelectClass(this.selectClasses.classSelectTag))) {
           const selectItem = targetElement.closest(".select") ? targetElement.closest(".select") : document.querySelector(`.${this.selectClasses.classSelect}[data-id="${targetElement.closest(this.getSelectClass(this.selectClasses.classSelectTag)).dataset.selectId}"]`);
           const originalSelect = this.getSelectElement(selectItem).originalSelect;
           if (targetType === "click") {
               if (!originalSelect.disabled) if (targetElement.closest(this.getSelectClass(this.selectClasses.classSelectTag))) {
                   const targetTag = targetElement.closest(this.getSelectClass(this.selectClasses.classSelectTag));
                   const optionItem = document.querySelector(`.${this.selectClasses.classSelect}[data-id="${targetTag.dataset.selectId}"] .select__option[data-value="${targetTag.dataset.value}"]`);
                   this.optionAction(selectItem, originalSelect, optionItem);
               } else if (targetElement.closest(this.getSelectClass(this.selectClasses.classSelectTitle))) this.selectAction(selectItem); else if (targetElement.closest(this.getSelectClass(this.selectClasses.classSelectOption))) {
                   const optionItem = targetElement.closest(this.getSelectClass(this.selectClasses.classSelectOption));
                   this.optionAction(selectItem, originalSelect, optionItem);
               }
           } else if (targetType === "focusin" || targetType === "focusout") {
               if (targetElement.closest(this.getSelectClass(this.selectClasses.classSelect))) targetType === "focusin" ? selectItem.classList.add(this.selectClasses.classSelectFocus) : selectItem.classList.remove(this.selectClasses.classSelectFocus);
           } else if (targetType === "keydown" && e.code === "Escape") this.selectsСlose();
       } else this.selectsСlose();
   }
   
   selectsСlose(selectOneGroup) {
       const selectsGroup = selectOneGroup ? selectOneGroup : document;
       const selectActiveItems = selectsGroup.querySelectorAll(`${this.getSelectClass(this.selectClasses.classSelect)}${this.getSelectClass(this.selectClasses.classSelectOpen)}`);
       if (selectActiveItems.length) selectActiveItems.forEach((selectActiveItem => {
           this.selectСlose(selectActiveItem);
       }));
   }
   
   selectСlose(selectItem) {
       const originalSelect = this.getSelectElement(selectItem).originalSelect;
       const selectOptions = this.getSelectElement(selectItem, this.selectClasses.classSelectOptions).selectElement;
       if (!selectOptions.classList.contains("_slide")) {
           selectItem.classList.remove(this.selectClasses.classSelectOpen);
           _slideUp(selectOptions, originalSelect.dataset.speed);
           setTimeout((() => {
               selectItem.style.zIndex = "";
           }), originalSelect.dataset.speed);
       }
   }
   
   selectAction(selectItem) {
       const originalSelect = this.getSelectElement(selectItem).originalSelect;
       const selectOptions = this.getSelectElement(selectItem, this.selectClasses.classSelectOptions).selectElement;
       const selectOpenzIndex = originalSelect.dataset.zIndex ? originalSelect.dataset.zIndex : 3;
       this.setOptionsPosition(selectItem);
       if (originalSelect.closest("[data-one-select]")) {
           const selectOneGroup = originalSelect.closest("[data-one-select]");
           this.selectsСlose(selectOneGroup);
       }
       setTimeout((() => {
           if (!selectOptions.classList.contains("_slide")) {
               selectItem.classList.toggle(this.selectClasses.classSelectOpen);
               _slideToggle(selectOptions, originalSelect.dataset.speed);
               if (selectItem.classList.contains(this.selectClasses.classSelectOpen)) selectItem.style.zIndex = selectOpenzIndex; else setTimeout((() => {
                   selectItem.style.zIndex = "";
               }), originalSelect.dataset.speed);
           }
       }), 0);
   }
   
   setSelectTitleValue(selectItem, originalSelect) {
       const selectItemBody = this.getSelectElement(selectItem, this.selectClasses.classSelectBody).selectElement;
       const selectItemTitle = this.getSelectElement(selectItem, this.selectClasses.classSelectTitle).selectElement;
       if (selectItemTitle) selectItemTitle.remove();
       selectItemBody.insertAdjacentHTML("afterbegin", this.getSelectTitleValue(selectItem, originalSelect));
       originalSelect.hasAttribute("data-search") ? this.searchActions(selectItem) : null;
       
       // Обновляем крестик если есть data-clear
       if (originalSelect.hasAttribute('data-clear') && originalSelect._updateClearButton) {
           setTimeout(() => {
               originalSelect._updateClearButton();
           }, 0);
       }
       
       const selectedOption = this.getSelectedOptionsData(originalSelect).values.length;
       if (selectedOption) selectItem.querySelector(`.${this.selectClasses.classSelectTitle}`).classList.add("_selected-item"); else selectItem.querySelector(`.${this.selectClasses.classSelectTitle}`).classList.remove("_selected-item");
   }
   
   getSelectTitleValue(selectItem, originalSelect) {
        let selectTitleValue = this.getSelectedOptionsData(originalSelect, 2).html.toString().trim();

        if (originalSelect.multiple && originalSelect.hasAttribute("data-tags")) {
            selectTitleValue = this.getSelectedOptionsData(originalSelect).elements.map((option) => 
                `<span role="button" data-select-id="${selectItem.dataset.id}" data-value="${option.value}" class="_select-tag">
                    ${this.getSelectElementContent(option)}
                    <span class="select__clear" data-action="clear" data-value="${option.value}">&times;</span>
                </span>`
            ).join("");
            
            if (originalSelect.dataset.tags && document.querySelector(originalSelect.dataset.tags)) {
                document.querySelector(originalSelect.dataset.tags).innerHTML = selectTitleValue;
                if (originalSelect.hasAttribute("data-search")) selectTitleValue = false;
            }
        }

        const hasSelectedValue = this.getSelectedOptionsData(originalSelect).values.length;
        selectTitleValue = selectTitleValue.length ? selectTitleValue : originalSelect.dataset.placeholder ? originalSelect.dataset.placeholder : "";

        let pseudoAttribute = "";
        let pseudoAttributeClass = "";
        if (originalSelect.hasAttribute("data-pseudo-label")) {
            pseudoAttribute = originalSelect.dataset.pseudoLabel ? ` data-pseudo-label="${originalSelect.dataset.pseudoLabel}"` : ` data-pseudo-label="Заповніть атрибут"`;
            pseudoAttributeClass = ` ${this.selectClasses.classSelectPseudoLabel}`;
        }

        hasSelectedValue 
            ? selectItem.classList.add(this.selectClasses.classSelectActive) 
            : selectItem.classList.remove(this.selectClasses.classSelectActive);

        if (originalSelect.hasAttribute("data-search")) {
            return `<div class="${this.selectClasses.classSelectTitle}">
                <span${pseudoAttribute} class="${this.selectClasses.classSelectValue}">
                    <input autocomplete="off" type="text" placeholder="${selectTitleValue}" data-placeholder="${selectTitleValue}" class="${this.selectClasses.classSelectInput}">
                    ${hasSelectedValue ? `<span class="select__clear" data-action="clear">&times;</span>` : ''}
                </span>
            </div>`;
        } else {
            const customClass = this.getSelectedOptionsData(originalSelect).elements.length && this.getSelectedOptionsData(originalSelect).elements[0].dataset.class 
                ? ` ${this.getSelectedOptionsData(originalSelect).elements[0].dataset.class}` 
                : "";
            
            // Для селектов с data-clear добавляем крестик
            const clearIcon = originalSelect.hasAttribute('data-clear') && hasSelectedValue && !originalSelect.multiple 
                ? `<span class="${this.selectClasses.classSelectClearIcon}"></span>` 
                : '';
            
            return `<button type="button" class="${this.selectClasses.classSelectTitle}">
                <span${pseudoAttribute} class="${this.selectClasses.classSelectValue}${pseudoAttributeClass}">
                    <span class="${this.selectClasses.classSelectContent}${customClass}">
                        ${selectTitleValue}
                    </span>
                    ${clearIcon}
                </span>
            </button>`;
        }
    }
    
   getSelectElementContent(selectOption) {
       const selectOptionData = selectOption.dataset.asset ? `${selectOption.dataset.asset}` : "";
       const selectOptionDataHTML = selectOptionData.indexOf("img") >= 0 ? `<img src="${selectOptionData}" alt="">` : selectOptionData;
       let selectOptionContentHTML = ``;
       selectOptionContentHTML += selectOptionData ? `<span class="${this.selectClasses.classSelectRow}">` : "";
       selectOptionContentHTML += selectOptionData ? `<span class="${this.selectClasses.classSelectData}">` : "";
       selectOptionContentHTML += selectOptionData ? selectOptionDataHTML : "";
       selectOptionContentHTML += selectOptionData ? `</span>` : "";
       selectOptionContentHTML += selectOptionData ? `<span class="${this.selectClasses.classSelectText}">` : "";
       selectOptionContentHTML += selectOption.textContent;
       selectOptionContentHTML += selectOptionData ? `</span>` : "";
       selectOptionContentHTML += selectOptionData ? `</span>` : "";
       return selectOptionContentHTML;
   }
   
   getSelectPlaceholder(originalSelect) {
       const selectPlaceholder = Array.from(originalSelect.options).find((option => !option.value));
       if (selectPlaceholder) return {
           value: selectPlaceholder.textContent,
           show: selectPlaceholder.hasAttribute("data-show"),
           label: {
               show: selectPlaceholder.hasAttribute("data-label"),
               text: selectPlaceholder.dataset.label
           }
       };
   }
   
   getSelectedOptionsData(originalSelect, type) {
       let selectedOptions = [];
       if (originalSelect.multiple) selectedOptions = Array.from(originalSelect.options).filter((option => option.value)).filter((option => option.selected)); else selectedOptions.push(originalSelect.options[originalSelect.selectedIndex]);
       return {
           elements: selectedOptions.map((option => option)),
           values: selectedOptions.filter((option => option.value)).map((option => option.value)),
           html: selectedOptions.map((option => this.getSelectElementContent(option)))
       };
   }
   
   getOptions(originalSelect) {
       const selectOptionsScroll = originalSelect.hasAttribute("data-scroll") ? `data-simplebar` : "";
       const customMaxHeightValue = +originalSelect.dataset.scroll ? +originalSelect.dataset.scroll : null;
       let selectOptions = Array.from(originalSelect.options);
       if (selectOptions.length > 0) {
           let selectOptionsHTML = ``;
           if (this.getSelectPlaceholder(originalSelect) && !this.getSelectPlaceholder(originalSelect).show || originalSelect.multiple) selectOptions = selectOptions.filter((option => option.value));
           selectOptionsHTML += `<div ${selectOptionsScroll} ${selectOptionsScroll ? `style="max-height: ${customMaxHeightValue}px"` : ""} class="${this.selectClasses.classSelectOptionsScroll}">`;
           selectOptions.forEach((selectOption => {
               selectOptionsHTML += this.getOption(selectOption, originalSelect);
           }));
           selectOptionsHTML += `</div>`;
           return selectOptionsHTML;
       }
   }
   
   getOption(selectOption, originalSelect) {
       const selectOptionSelected = selectOption.selected && originalSelect.multiple ? ` ${this.selectClasses.classSelectOptionSelected}` : "";
       const selectOptionHide = selectOption.selected && !originalSelect.hasAttribute("data-show-selected") && !originalSelect.multiple ? `hidden` : ``;
       const selectOptionClass = selectOption.dataset.class ? ` ${selectOption.dataset.class}` : "";
       const selectOptionLink = selectOption.dataset.href ? selectOption.dataset.href : false;
       const selectOptionLinkTarget = selectOption.hasAttribute("data-href-blank") ? `target="_blank"` : "";
       const selectOptionPopup = selectOption.dataset.popup ? selectOption.dataset.popup : false;
       let selectOptionHTML = ``;
       if (selectOptionLink) {
           const popupAttribute = selectOptionPopup ? ` data-popup="${selectOptionPopup}"` : "";
           selectOptionHTML += `<a ${selectOptionLinkTarget} ${selectOptionHide} href="${selectOptionLink}"${popupAttribute} data-value="${selectOption.value}" class="${this.selectClasses.classSelectOption}${selectOptionClass}${selectOptionSelected}">`;
       } else selectOptionHTML += `<button ${selectOptionHide} class="${this.selectClasses.classSelectOption}${selectOptionClass}${selectOptionSelected}" data-value="${selectOption.value}" type="button">`;
       selectOptionHTML += this.getSelectElementContent(selectOption);
       if (selectOptionLink) selectOptionHTML += `</a>`; else selectOptionHTML += `</button>`;
       return selectOptionHTML;
   }
   
   setOptions(selectItem, originalSelect) {
       const selectItemOptions = this.getSelectElement(selectItem, this.selectClasses.classSelectOptions).selectElement;
       selectItemOptions.innerHTML = this.getOptions(originalSelect);
   }
   
   setOptionsPosition(selectItem) {
       const originalSelect = this.getSelectElement(selectItem).originalSelect;
       const selectOptions = this.getSelectElement(selectItem, this.selectClasses.classSelectOptions).selectElement;
       const selectItemScroll = this.getSelectElement(selectItem, this.selectClasses.classSelectOptionsScroll).selectElement;
       const customMaxHeightValue = +originalSelect.dataset.scroll ? `${+originalSelect.dataset.scroll}px` : ``;
       const selectOptionsPosMargin = +originalSelect.dataset.optionsMargin ? +originalSelect.dataset.optionsMargin : 10;
       if (!selectItem.classList.contains(this.selectClasses.classSelectOpen)) {
           selectOptions.hidden = false;
           const selectItemScrollHeight = selectItemScroll.offsetHeight ? selectItemScroll.offsetHeight : parseInt(window.getComputedStyle(selectItemScroll).getPropertyValue("max-height"));
           const selectOptionsHeight = selectOptions.offsetHeight > selectItemScrollHeight ? selectOptions.offsetHeight : selectItemScrollHeight + selectOptions.offsetHeight;
           const selectOptionsScrollHeight = selectOptionsHeight - selectItemScrollHeight;
           selectOptions.hidden = true;
           const selectItemHeight = selectItem.offsetHeight;
           const selectItemPos = selectItem.getBoundingClientRect().top;
           const selectItemTotal = selectItemPos + selectOptionsHeight + selectItemHeight + selectOptionsScrollHeight;
           const selectItemResult = window.innerHeight - (selectItemTotal + selectOptionsPosMargin);
           if (selectItemResult < 0) {
               const newMaxHeightValue = selectOptionsHeight + selectItemResult;
               if (newMaxHeightValue < 100) {
                   selectItem.classList.add("select--show-top");
                   selectItemScroll.style.maxHeight = selectItemPos < selectOptionsHeight ? `${selectItemPos - (selectOptionsHeight - selectItemPos)}px` : customMaxHeightValue;
               } else {
                   selectItem.classList.remove("select--show-top");
                   selectItemScroll.style.maxHeight = `${newMaxHeightValue}px`;
               }
           }
       } else setTimeout((() => {
           selectItem.classList.remove("select--show-top");
           selectItemScroll.style.maxHeight = customMaxHeightValue;
       }), +originalSelect.dataset.speed);
   }
   
   optionAction(selectItem, originalSelect, optionItem) {
       const selectOptions = selectItem.querySelector(`${this.getSelectClass(this.selectClasses.classSelectOptions)}`);
       if (!selectOptions.classList.contains("_slide")) {
           if (originalSelect.multiple) {
               optionItem.classList.toggle(this.selectClasses.classSelectOptionSelected);
               const originalSelectSelectedItems = this.getSelectedOptionsData(originalSelect).elements;
               originalSelectSelectedItems.forEach((originalSelectSelectedItem => {
                   originalSelectSelectedItem.removeAttribute("selected");
               }));
               const selectSelectedItems = selectItem.querySelectorAll(this.getSelectClass(this.selectClasses.classSelectOptionSelected));
               selectSelectedItems.forEach((selectSelectedItem => {
                   originalSelect.querySelector(`option[value="${selectSelectedItem.dataset.value}"]`).setAttribute("selected", "selected");
               }));
           } else {
               if (!originalSelect.hasAttribute("data-show-selected")) setTimeout((() => {
                   if (selectItem.querySelector(`${this.getSelectClass(this.selectClasses.classSelectOption)}[hidden]`)) selectItem.querySelector(`${this.getSelectClass(this.selectClasses.classSelectOption)}[hidden]`).hidden = false;
                   optionItem.hidden = true;
               }), this.config.speed);
               originalSelect.value = optionItem.hasAttribute("data-value") ? optionItem.dataset.value : optionItem.textContent;
               this.selectAction(selectItem);
           }
           this.setSelectTitleValue(selectItem, originalSelect);
           this.setSelectChange(originalSelect);
           
           // Обновляем крестик если есть data-clear
           if (originalSelect.hasAttribute('data-clear') && originalSelect._updateClearButton) {
               originalSelect._updateClearButton();
           }
           
           const event = new Event("change", {
               bubbles: true
           });
           originalSelect.dispatchEvent(event);
       }
   }
   
   selectChange(e) {
       const originalSelect = e.target;
       this.selectBuild(originalSelect);
       this.setSelectChange(originalSelect);
   }
   
   setSelectChange(originalSelect) {
       if (originalSelect.hasAttribute("data-validate")) formValidate.validateInput(originalSelect);
       if (originalSelect.hasAttribute("data-submit") && originalSelect.value) {
           let tempButton = document.createElement("button");
           tempButton.type = "submit";
           originalSelect.closest("form").append(tempButton);
           tempButton.click();
           tempButton.remove();
       }
       const selectItem = originalSelect.parentElement;
       this.selectCallback(selectItem, originalSelect);
   }
   
   selectDisabled(selectItem, originalSelect) {
       if (originalSelect.disabled) {
           selectItem.classList.add(this.selectClasses.classSelectDisabled);
           this.getSelectElement(selectItem, this.selectClasses.classSelectTitle).selectElement.disabled = true;
       } else {
           selectItem.classList.remove(this.selectClasses.classSelectDisabled);
           this.getSelectElement(selectItem, this.selectClasses.classSelectTitle).selectElement.disabled = false;
       }
   }
   
   searchActions(selectItem) {
       this.getSelectElement(selectItem).originalSelect;
       const selectInput = this.getSelectElement(selectItem, this.selectClasses.classSelectInput).selectElement;
       const selectOptions = this.getSelectElement(selectItem, this.selectClasses.classSelectOptions).selectElement;
       const selectOptionsItems = selectOptions.querySelectorAll(`.${this.selectClasses.classSelectOption} `);
       const _this = this;
       selectInput.addEventListener("input", (function() {
           selectOptionsItems.forEach((selectOptionsItem => {
               if (selectOptionsItem.textContent.toUpperCase().includes(selectInput.value.toUpperCase())) selectOptionsItem.hidden = false; else selectOptionsItem.hidden = true;
           }));
           selectOptions.hidden === true ? _this.selectAction(selectItem) : null;
       }));
   }
   
   selectCallback(selectItem, originalSelect) {
       document.dispatchEvent(new CustomEvent("selectCallback", {
           detail: {
               select: originalSelect
           }
       }));
   }
   
   setLogging(message) {
       this.config.logging ? FLS(`[select]: ${message} `) : null;
   }
}

modules_flsModules.select = new SelectConstructor({});