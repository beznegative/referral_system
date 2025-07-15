document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('user-search');
    const usersList = document.querySelector('.users-list');
    const noResultsMessage = document.getElementById('no-results');
    
    if (!searchInput || !usersList) return;

    let debounceTimer;

    const debounce = (callback, time) => {
        window.clearTimeout(debounceTimer);
        debounceTimer = window.setTimeout(callback, time);
    };

    const normalizeText = (text) => {
        return text.toLowerCase()
            .replace(/ё/g, 'е')
            .trim();
    };

    const filterUsers = () => {
        const searchTerm = normalizeText(searchInput.value);
        const userItems = usersList.getElementsByTagName('li');
        let visibleCount = 0;

        // Получаем текущую активную вкладку из URL
        const urlParams = new URLSearchParams(window.location.search);
        const activeTab = urlParams.get('tab') || 'users';

        Array.from(userItems).forEach(item => {
            const userName = normalizeText(item.querySelector('.user-name').textContent);
            const isAffiliate = item.querySelector('.icon-affiliate') !== null;
            
            // Проверяем, соответствует ли пользователь текущей вкладке и поисковому запросу
            const matchesTab = (activeTab === 'affiliates' && isAffiliate) || 
                             (activeTab === 'users' && !isAffiliate);
            const matchesSearch = userName.includes(searchTerm);

            if (matchesTab && matchesSearch) {
                item.style.display = '';
                visibleCount++;
            } else {
                item.style.display = 'none';
            }
        });

        // Показываем или скрываем сообщение "Ничего не найдено"
        if (noResultsMessage) {
            noResultsMessage.style.display = visibleCount === 0 ? 'block' : 'none';
        }
    };

    // Обработчик ввода с debounce
    searchInput.addEventListener('input', () => {
        debounce(filterUsers, 300);
    });

    // Предотвращаем отправку формы при нажатии Enter
    searchInput.form?.addEventListener('submit', (e) => {
        e.preventDefault();
    });
}); 