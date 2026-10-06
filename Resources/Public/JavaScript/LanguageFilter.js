(() => {
    document.querySelectorAll('[data-language-filter]').forEach((filter) => {
        const summary = filter.querySelector('[data-language-summary]');
        const search = filter.querySelector('[data-language-search]');
        const options = [...filter.querySelectorAll('[data-language-option]')];
        const empty = filter.querySelector('[data-language-empty]');
        const normalize = (value) => value.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLocaleLowerCase();

        const updateSummary = () => {
            const selected = options
                .filter((option) => option.querySelector('input[type="checkbox"]').checked)
                .map((option) => option.querySelector('[data-language-name]').textContent.trim());
            summary.textContent = selected.length === 0
                ? summary.dataset.empty
                : selected.slice(0, 2).join(', ') + (selected.length > 2 ? ` +${selected.length - 2}` : '');
            summary.title = selected.join(', ');
        };

        const searchOptions = () => {
            const query = normalize(search.value.trim());
            options.forEach((option) => {
                const code = option.querySelector('input[type="checkbox"]').value;
                option.hidden = !normalize(`${option.textContent} ${code}`).includes(query);
            });
            empty.hidden = options.some((option) => !option.hidden);
        };

        filter.querySelector('[data-language-tools]').hidden = false;
        filter.addEventListener('change', updateSummary);
        search.addEventListener('input', searchOptions);
        search.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
            }
        });
        filter.querySelector('[data-language-clear]').addEventListener('click', () => {
            options.forEach((option) => {
                option.querySelector('input[type="checkbox"]').checked = false;
            });
            updateSummary();
        });
        filter.addEventListener('toggle', () => {
            if (filter.open) {
                search.value = '';
                searchOptions();
                search.focus();
            }
        });
        filter.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                filter.open = false;
                filter.querySelector('summary').focus();
            }
        });
        document.addEventListener('click', (event) => {
            if (!filter.contains(event.target)) {
                filter.open = false;
            }
        });
        updateSummary();
    });
})();
