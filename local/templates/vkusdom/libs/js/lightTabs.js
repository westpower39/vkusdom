(function () {
    const stateCache = {};

    function getTabsKey(tabs) {
        const popup = tabs.closest('.popup-outer-box, .mfp-content');
        const popupId = popup ? (popup.id || 'no-popup-id') : 'no-popup';
        const allTabs = Array.from(document.querySelectorAll('.tabs'));
        const idx = allTabs.indexOf(tabs);
        return popupId + '::tabs-' + idx;
    }

    function showPage(tabs, index) {
        const contentDivs = tabs.querySelectorAll('.tabs__content > div');
        contentDivs.forEach((div, i) => {
            div.style.display = i === index ? 'block' : 'none';
        });

        const listItems = tabs.querySelectorAll('.tabs__list > li');
        listItems.forEach((li, i) => {
            li.classList.toggle('active', i === index);
        });

        tabs.dataset.activeIndex = index;
        stateCache[getTabsKey(tabs)] = index;
    }

    function resolveStartIndex(tabs) {
        const key = getTabsKey(tabs);

        if (stateCache[key] !== undefined) return stateCache[key];

        const ls = localStorage.getItem('lightTabs:' + key);
        if (ls !== null) return parseInt(ls, 10) || 0;

        if (tabs.dataset.activeIndex !== undefined) {
            return parseInt(tabs.dataset.activeIndex, 10) || 0;
        }

        let found = 0;
        tabs.querySelectorAll('.tabs__list > li').forEach((li, i) => {
            if (li.classList.contains('active')) found = i;
        });
        return found;
    }

    function initTabs(tabs) {
        if (tabs.dataset.tabsInitialized !== 'true') {
            tabs.querySelectorAll('.tabs__list > li').forEach((li, i) => {
                li.setAttribute('data-page', i);
                li.addEventListener('click', () => {
                    showPage(tabs, i);
                    localStorage.setItem('lightTabs:' + getTabsKey(tabs), i);
                });
            });
            tabs.dataset.tabsInitialized = 'true';
        }

        showPage(tabs, resolveStartIndex(tabs));
    }

    function lightTabs(selector) {
        const elements = selector
            ? document.querySelectorAll(selector)
            : document.querySelectorAll('.tabs');
        elements.forEach(initTabs);
    }

    window.lightTabs = lightTabs;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => lightTabs());
    } else {
        lightTabs();
    }
})();