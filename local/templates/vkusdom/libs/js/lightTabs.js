(function(){
    function lightTabs(selector) {
        // Если передан селектор, используем его, иначе ищем .lightTabs
        const elements = selector 
            ? document.querySelectorAll(selector)
            : document.querySelectorAll('.tabs');

        elements.forEach((tabs) => {
            let i = 0;

            const showPage = (index) => {
                const contentDivs = tabs.querySelectorAll('.tabs__content > div');
                contentDivs.forEach((div, idx) => {
                    div.style.display = idx === index ? 'block' : 'none';
                });
                
                const listItems = tabs.querySelectorAll('.tabs__list > li');
                listItems.forEach((li, idx) => {
                    li.classList.toggle('active', idx === index);
                });
            };
            
            showPage(0);
            
            const listItems = tabs.querySelectorAll('.tabs__list > li');
            listItems.forEach((li) => {
                li.setAttribute('data-page', i);
                li.addEventListener('click', () => {
                    showPage(parseInt(li.getAttribute('data-page')));
                });
                i++;
            });
        });
    }
    
    window.lightTabs = lightTabs;
})();

// Примеры использования:
// lightTabs(); // ищет все элементы с классом .lightTabs
// lightTabs('.my-tabs'); // ищет все элементы с классом .my-tabs
// lightTabs('#tabs-container'); // ищет элемент с id tabs-container